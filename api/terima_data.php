<?php
require_once 'config.php';

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Baca payload JSON mentah dari Python (atau dari mock_input untuk pengujian)
    $json_data = isset($mock_input) ? $mock_input : file_get_contents('php://input');
    
    // 2. Decode JSON menjadi array PHP
    $data = json_decode($json_data, true);

    // 3. Cek apakah data berhasil di-decode (tidak kosong)
    if ($data) {
        
        // Ambil data-data dengan fallback (nilai default 0 atau '-') jika kosong
        $id_node        = isset($data['id_node']) ? $data['id_node'] : 'NODE_RWH_TELKOM';
        $tinggi_air     = isset($data['tinggi_air']) ? (float)$data['tinggi_air'] : 0;
        $ntu            = isset($data['ntu']) ? (float)$data['ntu'] : 0;
        $ket_turbidity  = isset($data['ket_turbidity']) ? $data['ket_turbidity'] : '-';
        $ec             = isset($data['ec']) ? (float)$data['ec'] : 0;
        $tds            = isset($data['tds']) ? (float)$data['tds'] : 0;
        $ph             = isset($data['ph']) ? (float)$data['ph'] : 0;
        $status_mineral = isset($data['status_mineral']) ? $data['status_mineral'] : '-';
        $status_hazard  = isset($data['status_hazard']) ? $data['status_hazard'] : '-';
        $status_uv      = isset($data['status_uv']) ? $data['status_uv'] : '-';

        try {
            // 4. Baca data log.json yang sudah ada
            $current_logs = [];
            if (file_exists($log_file_path)) {
                $file_content = file_get_contents($log_file_path);
                $decoded = json_decode($file_content, true);
                if (is_array($decoded)) {
                    $current_logs = $decoded;
                }
            }

            // 5. Buat data log baru dengan timestamp dari backend
            $new_log = [
                "id_node"        => $id_node,
                "tinggi_air"     => $tinggi_air,
                "ntu"            => $ntu,
                "ket_turbidity"  => $ket_turbidity,
                "ec"             => $ec,
                "tds"            => $tds,
                "ph"             => $ph,
                "status_mineral" => $status_mineral,
                "status_hazard"  => $status_hazard,
                "status_uv"      => $status_uv,
                "created_at"     => date('Y-m-d H:i:s')
            ];

            // 6. Push data baru ke akhir array
            $current_logs[] = $new_log;

            // 7. Batasi hanya menyimpan 10 log terakhir (FIFO)
            if (count($current_logs) > 10) {
                $current_logs = array_slice($current_logs, -10);
            }

            // 8. Tulis kembali ke file log.json
            $write_success = file_put_contents($log_file_path, json_encode($current_logs, JSON_PRETTY_PRINT));

            if ($write_success !== false) {
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Data berhasil disimpan."]);
            } else {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Gagal menulis ke file log.json."]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Server Error: " . $e->getMessage()]);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Payload JSON tidak valid / Kosong."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed. Gunakan POST."]);
}
?>