<?php
require_once 'config.php';

// Pastikan request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Baca payload JSON mentah dari Python
    $json_data = file_get_contents('php://input');
    
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
            // 4. Log data ke tabel 'data_utama'
            $sql_insert = "INSERT INTO data_utama 
                           (id_node, tinggi_air, ntu, ket_turbidity, ec, tds, ph, status_mineral, status_hazard, status_uv) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql_insert);
            $stmt->execute([
                $id_node,
                $tinggi_air, 
                $ntu, 
                $ket_turbidity, 
                $ec, 
                $tds, 
                $ph, 
                $status_mineral, 
                $status_hazard,
                $status_uv
            ]);

            // [OPSIONAL] 5. Jika kamu masih punya tabel 'sensor_dashboard' untuk di-update,
            // Hapus tanda komentar (/* ... */) di bawah ini:
            /*
            $sql_update = "UPDATE sensor_dashboard SET 
                           tinggi_air = ?, ntu = ?, kualitas_air = ?, 
                           ec = ?, tds = ?, ph = ?, 
                           status_mineral = ?, status_hazard = ? 
                           WHERE id = 1";
            $pdo->prepare($sql_update)->execute([
                $tinggi_air, $ntu, $ket_turbidity, 
                $ec, $tds, $ph, 
                $status_mineral, $status_hazard
            ]);
            */

            // 6. Beri respon ke Python bahwa sukses
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Data berhasil disimpan."]);

        } catch (PDOException $e) {
            // Tangkap error jika ada kolom atau nama tabel yang salah ketik
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
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