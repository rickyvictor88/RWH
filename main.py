import RPi.GPIO as GPIO
import time
import serial
import requests
import board
import busio
import adafruit_ads1x15.ads1115 as ADS
from adafruit_ads1x15.analog_in import AnalogIn
from RPLCD.i2c import CharLCD

# ================= 1. KONFIGURASI SISTEM =================
URL_SERVER = "http://192.168.18.157/rainwater-harvesting/api/terima_data.php"
NODE_ID = "NODE_RWH_TELKOM"
INTERVAL_KIRIM_WEB_DETIK = 1
waktu_terakhir_kirim = 0

# Konfigurasi Hardware Sensor
PORT_UART = '/dev/ttyS0'
BAUD_RATE = 9600
TOTAL_KETINGGIAN_WADAH_CM = 150.0
tinggi_air_terakhir = 0.0

# ====================================================================
# TAHAP KALIBRASI LINEAR: SESUAI PEMBACAAN FISIK ALAT ANDA
# ====================================================================
VOLTASE_AIR_BERSIH = 2.7  # Ambang batas atas voltase saat air jernih pekat
VOLTASE_AIR_KERUH = 2.1   # Ambang batas bawah voltase saat air sangat kotor
# ====================================================================

# Konfigurasi 3 Channel Relay (Sesuai Logika NO - Active LOW)
RELAY_PINS = [27, 22, 17]

# ================= 2. INISIALISASI HARDWARE =================
lcd = CharLCD('PCF8574', 0x27, port=1, cols=20, rows=4)

# Inisialisasi GPIO untuk semua Relay
GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)
for pin in RELAY_PINS:
    GPIO.setup(pin, GPIO.OUT)
    # Inisialisasi awal: HIGH = Relay dalam kondisi MATI
    GPIO.output(pin, GPIO.HIGH)

i2c = busio.I2C(board.SCL, board.SDA)
ads = ADS.ADS1115(i2c)
ads.gain = 1
pin_turbidity = AnalogIn(ads, 0) # A0
pin_ec = AnalogIn(ads, 1)        # A1

try:
    ser = serial.Serial(PORT_UART, BAUD_RATE, timeout=1)
    ser.reset_input_buffer()
except Exception as e:
    print(f"Gagal membuka port Serial: {e}")

# ================= 3. FUNGSI LOGIKA =================

def baca_ultrasonik():
    global tinggi_air_terakhir
    try:
        if ser.in_waiting >= 4:
            if ser.read(1) == b'\xff':
                data = ser.read(3)
                if len(data) == 3:
                    jarak_mm = (data[0] << 8) + data[1]
                    jarak_cm = jarak_mm / 10.0
                    tinggi = TOTAL_KETINGGIAN_WADAH_CM - jarak_cm
                    tinggi_air_terakhir = round(max(0, tinggi), 2)
                    ser.reset_input_buffer()
    except Exception: pass
    return tinggi_air_terakhir

def hitung_ntu(voltase_sekarang):
    """
    Menghitung tingkat kekeruhan dalam satuan NTU berdasarkan pemetaan linear.
    Mengubah rentang voltase (3.11V s.d 2.50V) menjadi rentang NTU (0 s.d 3000).
    """
    # Jika voltase sama atau lebih tinggi dari air bersih, NTU pasti 0 (Sangat Jernih)
    if voltase_sekarang >= VOLTASE_AIR_BERSIH:
        return 0.0

    # Jika voltase drop di bawah batas keruh pekat, kunci di NTU maksimal 3000
    if voltase_sekarang <= VOLTASE_AIR_KERUH:
        return 3000.0

    # Rumus interpolasi linear desimal
    ntu_val = (VOLTASE_AIR_BERSIH - voltase_sekarang) * (3000.0 / (VOLTASE_AIR_BERSIH - VOLTASE_AIR_KERUH))

    return round(max(0.0, ntu_val), 2)
def status_turbidity(voltase):
    if voltase >= VOLTASE_AIR_BERSIH:
        return "Jernih"
    if (VOLTASE_AIR_BERSIH - voltase) <= 0.09:
        return "Agak Keruh"
    return "Kotor"

def estimasi_ec_ph(voltage_v):
    raw_ec = 1000.0 * (voltage_v * 1000) / 820.0 / 39.8
    ec_ms = raw_ec * 1.00563
    tds_ppm = ec_ms * 0.64 * 1000
    ket_ec = "Tercemar" if tds_ppm >= 1000 else "Aman"
    est_ph = 6.8
    ket_ph = "Normal"
    return ket_ph, ket_ec, tds_ppm, ec_ms, est_ph

# ================= 4. LOOP UTAMA =================
try:
    lcd.clear()
    lcd.write_string("Sistem RWH Aktif")
    time.sleep(2)
    print("\n==========================================================")
    print("        MONITORING DATA & LOG KALIBRASI TERMINAL        ")
    print("==========================================================")

    while True:
        tinggi_air = baca_ultrasonik()
        v_turb = pin_turbidity.voltage

        # Hitung Nilai NTU dan Keterangannya berdasarkan rumus interpolasi baru
        nilai_ntu = hitung_ntu(v_turb)
        ket_turb = status_turbidity(v_turb)

        ket_ph, ket_ec, nilai_tds, nilai_ec, nilai_ph = estimasi_ec_ph(pin_ec.voltage)

        waktu_sekarang = time.time()
        jam_menit_detik = time.strftime('%H:%M:%S')

        # Log Terminal untuk memantau nilai mentah voltase saat kalibrasi fisik
        print(f"[{jam_menit_detik}] Voltase Turbiditas: {v_turb:.3f} V | Hitung: {nilai_ntu:<7.2f} NTU | Status: {ket_turb}")

        # LOGIKA KONTROL SELURUH RELAY (MENYALA & MATI BERSAMAAN)
        if ket_turb == "Kotor" or ket_ec == "Tercemar":
            # Memberikan sinyal LOW akan menarik relay (ON) ke seluruh pin
            for pin in RELAY_PINS:
                GPIO.output(pin, GPIO.LOW)
            status_uv = "ON"
        else:
            # Jika air aman dan jernih, kembalikan seluruh relay ke HIGH (OFF)
            for pin in RELAY_PINS:
                GPIO.output(pin, GPIO.HIGH)
            status_uv = "OFF"

        # Update LCD display dengan status terkini
        lcd.clear()
        lcd.cursor_pos = (0, 0)
        lcd.write_string(f"UV:{status_uv} Air:{tinggi_air}cm")
        lcd.cursor_pos = (1, 0)
        lcd.write_string(f"Turb:{nilai_ntu} NTU")
        lcd.cursor_pos = (2, 0)
        lcd.write_string(f"pH  :{ket_ph}")
        lcd.cursor_pos = (3, 0)
        lcd.write_string(f"Kond:{ket_ec}")

        # PENGIRIMAN DATA DATA_UTAMA KE API WEB DASHBOARD
        if (waktu_sekarang - waktu_terakhir_kirim) >= INTERVAL_KIRIM_WEB_DETIK:
            payload = {
                "id_node": NODE_ID,
                "tinggi_air": tinggi_air,
                "ntu": nilai_ntu,                  # MENGIRIM NTU HASIL INTERPOLASI
                "ket_turbidity": ket_turb,
                "ec": round(nilai_ec, 2),
                "tds": round(nilai_tds, 2),
                "ph": round(nilai_ph, 2),
                "status_mineral": ket_ph,
                "status_hazard": ket_ec,
                "status_uv": status_uv
            }
            try:
                requests.post(URL_SERVER, json=payload, timeout=2)
            except Exception as e:
                print(f" Gagal kirim ke server API: {e}")
            waktu_terakhir_kirim = waktu_sekarang
        time.sleep(1)

except KeyboardInterrupt:
    print("\nProgram ditutup oleh pengguna.")
finally:
    # Prosedur Pengamanan: Mematikan semua relay (kembali ke HIGH) saat program berhenti
    for pin in RELAY_PINS:
        GPIO.output(pin, GPIO.HIGH)
    GPIO.cleanup()
    print("Semua sistem dibersihkan, seluruh relay dimatikan.")