Product Requirement Document (PRD)

1. Document Control & Overview
Project Name: Rainwater Harvesting Monitoring Dashboard – Database-less Migration

Author: OCL

Date: June 19, 2026

Status: Draft

1.1 Objective
Mengubah arsitektur penyimpanan data pada sistem Rainwater Harvesting dari menggunakan Relational Database (MySQL) menjadi sistem Database-less (tanpa database). Data tren parameter air (pH, TDS, Kekeruhan, Daya Hantar) akan dibatasi hanya 10 log terakhir secara volatile (sementara) menggunakan memori client-side (JavaScript) didukung oleh penyimpanan file lokal (JSON-cache) pada backend PHP.

1.2 Justification (Rasionalisasi)
Cost Efficiency: Menghilangkan biaya atau batasan jam operasional dari hosting database di platform seperti Railway.

Performance: Mengurangi beban I/O server. Data IoT yang masuk setiap beberapa detik tidak perlu ditulis ke harddisk server secara konstan, melainkan langsung dioper ke komponen visual.

Scope Realism: Dashboard hanya berfungsi sebagai alat monitoring kondisi saat ini (real-time) dan tren jangka pendek (10 data terakhir), sehingga penyimpanan histori jangka panjang tidak bersifat kritikal.

2. System Architecture & Data Flow
Sistem akan bermigrasi dari arsitektur Request-Save-Read konvensional menjadi arsitektur aliran data langsung (Stream-and-Slice).

[ Hardware: Raspberry Pi ] 
       │
       │ (HTTP POST JSON)
       ▼
[ Backend: PHP API (Railway/Render) ] ──(Memotong array menjadi 10 data)──► [ Simpan ke log.json ]
       │                                                                            │
       │ (Push Event via Pusher/WebSockets)                                         │ (Fetch awal saat reload)
       ▼                                                                            ▼
[ Frontend: Dashboard JS ] ◄────────────────────────────────────────────────────────┘

3. Product Features & Requirements

3.1 Real-Time Data Ingestion (Backend PHP)
    Functional Requirement: 
    * Backend PHP harus menyediakan satu endpoint API (misal: api/push-sensor.php) yang menerima data JSON dari Raspberry Pi.
    * Backend tidak boleh melakukan koneksi ke database (PDO / mysqli).
    * Technical Logic (Data Slicing):Setiap kali data baru masuk, PHP akan membaca file lokal log.json.
    * Data baru di-push ke dalam array tersebut.Jika panjang array $> 10$, elemen pertama (data tertua) harus dihapus (array_shift atau array_slice).
    * Array baru yang berisi tepat 10 data disimpan kembali ke log.json.

3.2 Frontend Dashboard UI (10 Log Retention)
Functional Requirement:

Grafik Tren pH & TDS (10 Log Terakhir): Harus mempertahankan komponen line-chart yang ada pada desain saat ini.

Initial Load: Saat halaman dashboard pertama kali dibuka atau di-refresh, JavaScript akan melakukan fetch ringan ke api/get-logs.php (membaca log.json) untuk merender 10 titik point grafik awal secara instan.

Real-time Update: Setelah initial load, setiap ada data baru masuk dari IoT via mekanisme real-time (Pusher/MQTT), grafik akan bergeser ke kiri (metode Queue / Antrean FIFO: First-In, First-Out).

3.3 State Management & Edge Cases
Server Restart / Ephemeral Reset: * Karena berjalan di atas ephemeral file system (Railway/Render), file log.json akan ter-reset menjadi kosong ([]) setiap kali server melakukan restart otomatis atau deploy ulang.

Requirement: Frontend harus menangani kondisi jika data log.json kosong dengan menampilkan pesan "Menunggu data dari alat..." atau grafik dimulai dari titik nol secara anggun (graceful degradation), bukan menampilkan error crash.

4. Non-Functional Requirements (NFR)
Performance: Waktu respons dari penerimaan data IoT hingga pembaruan komponen grafik di layar browser harus $< 1.5$ detik.
Memory Limits: File log.json tidak boleh membengkak lebih dari 5 KB karena isinya ketat dibatasi hanya 10 objek data sensor.
Availability: Selama server backend aktif, data real-time stream harus tetap berjalan terlepas dari kondisi file lokal sukses ditulis atau tidak.