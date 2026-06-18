<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php'; 

try {
    // 1. AMBIL DATA TERBARU UNTUK DASHBOARD (Kartu Metrik)
    // Mengambil 1 baris terakhir berdasarkan ID tertinggi (paling baru)
    $stmtDash = $pdo->query("SELECT * FROM data_utama ORDER BY id DESC LIMIT 1");
    $dataDashboard = $stmtDash->fetch(PDO::FETCH_ASSOC);

    // 2. AMBIL DATA LOG (Untuk Grafik)
    // Mengambil 10 baris terakhir dari data_utama (sudah mencakup semua sensor)
    $stmtLog = $pdo->query("SELECT * FROM data_utama ORDER BY id DESC LIMIT 10");
    $dataLog = $stmtLog->fetchAll(PDO::FETCH_ASSOC);

    // 3. GABUNGKAN SEMUA
    echo json_encode([
        "status" => "success",
        "data" => $dataDashboard,   // Ini untuk mengisi kartu metrik (angka-angka terbaru)
        "data_log" => $dataLog      // Ini untuk grafik (berisi histori semua sensor)
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database Error: " . $e->getMessage()
    ]);
}
?>