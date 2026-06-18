<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'config.php'; 

try {
    $dataDashboard = null;
    $dataLog = [];

    // 1. Baca data dari log.json
    if (file_exists($log_file_path)) {
        $file_content = file_get_contents($log_file_path);
        $decoded = json_decode($file_content, true);
        if (is_array($decoded) && count($decoded) > 0) {
            // Data paling akhir di array adalah data terbaru
            $dataDashboard = end($decoded);
            
            // Urutan log di reversed agar data terbaru berada di index paling depan (indeks 0)
            $dataLog = array_reverse($decoded);
        }
    }

    // 2. Kirimkan respon JSON yang kompatibel dengan frontend
    echo json_encode([
        "status" => "success",
        "data" => $dataDashboard,   // Mengisi kartu metrik (angka terbaru)
        "data_log" => $dataLog      // Mengisi grafik & tabel riwayat (10 data terakhir, terbalik)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server Error: " . $e->getMessage()
    ]);
}
?>