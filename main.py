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
URL_SERVER = "http://192.168.18.100/rwh/api/terima_data.php"
NODE_ID = "NODE_RWH_TELKOM"
INTERVAL_KIRIM_WEB_DETIK = 5
waktu_terakhir_kirim = 0

# Konfigurasi Hardware Sensor
PORT_UART = '/dev/ttyS0'
BAUD_RATE = 9600
TOTAL_KETINGGIAN_WADAH_CM = 150.0
tinggi_air_terakhir = 0.0
VOLTASE_AIR_BERSIH = 3.3

# Konfigurasi 3 Channel Relay (Sesuai Logika NO - Active LOW)
# Jalur Pin BCM: CH1=17, CH2=27, CH3=22
RELAY_PINS = [27, 22, 17]

# ================= 2. INISIALISASI HARDWARE =================
lcd = CharLCD('PCF8574', 0x27, port=1, cols=20, rows=4)

# Inisialisasi GPIO untuk semua Relay
GPIO.setmode(GPIO.BCM)
GPIO.setwarnings(False)
for pin in RELAY_PINS:
    GPIO.setup(pin, GPIO.OUT)
    # Inisialisasi awal: HIGH = Relay dalam kondisi MATI (Sikit Terputus)
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

def hitung_ntu(voltase):
    """
    Menghitung tingkat kekeruhan dalam satuan NTU berdasarkan voltase.
    Menggunakan rumus konversi kurva karakteristik sensor turbiditas.
    """
    # Jika voltase sangat bersih (mendekati atau lebih besar dari batas air bersih), NTU dihitung 0
    if voltase >= VOLTASE_AIR_BERSIH:
        return 0.0
    
    # Batas bawah voltase sensor saat sangat keruh pekat biasanya berada di sekitar 2.5V
    if voltase < 2.5:
        voltase = 2.5
        
    # Rumus karakteristik sensor turbiditas: NTU = -1120.4 * (Volt^2) + 5742.3 * Volt - 4353.8
    ntu_val = -1120.4 * (voltase ** 2) + 5742.3 * voltase - 4353.8
    
    # Memastikan tidak ada nilai NTU minus karena kalkulasi matematika desimal
    return round(max(0.0, ntu_val), 2)

def status_turbidity(voltase):
    if voltase >= VOLTASE_AIR_BERSIH: return "Jernih"
    if (VOLTASE_AIR_BERSIH - voltase) <= 0.09: return "Agak Keruh"
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

    while True:
        tinggi_air = baca_ultrasonik()
        v_turb = pin_turbidity.voltage
        
        # Hitung Nilai NTU dan Keterangannya
        nilai_ntu = hitung_ntu(v_turb)
        ket_turb = status_turbidity(v_turb)
        
        ket_ph, ket_ec, nilai_tds, nilai_ec, nilai_ph = estimasi_ec_ph(pin_ec.voltage)

        waktu_sekarang = time.time()

        # LOGIKA KONTROL SEULURUH RELAY (MENYALA & MATI BERSAMAAN)
        if ket_turb == "Kotor" or ket_ec == "Tercemar":
            # Memberikan sinyal LOW akan menarik relay (ON) ke seluruh pin
            for pin in RELAY_PINS:
                GPIO.output(pin, GPIO.LOW)
            status_uv = "ALL-ON"
        else:
            # Jika air aman dan jernih, kembalikan seluruh relay ke HIGH (OFF)
            for pin in RELAY_PINS:
                GPIO.output(pin, GPIO.HIGH)
            status_uv = "ALL-OFF"

        # UPDATE LCD DISPLAY
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
                "ntu": nilai_ntu,                  # MENGIRIM NTU BUKAN VOLTASE
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
                print(f"Gagal kirim: {e}")
            waktu_terakhir_kirim = waktu_sekarang

        time.sleep(1)

except KeyboardInterrupt:
    print("\nProgram dihentikan.")
finally:
    # Prosedur Pengamanan: Mematikan semua relay (kembali ke HIGH) saat program berhenti
    for pin in RELAY_PINS:
        GPIO.output(pin, GPIO.HIGH)
    GPIO.cleanup()
    print("Semua sistem dibersihkan, seluruh relay dimatikan.")