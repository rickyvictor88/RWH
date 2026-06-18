<?php
// Konfigurasi Database
$host = 'localhost';
$dbname = 'rwh_db'; // Pastikan nama database sesuai dengan yang Anda buat
$username = 'root'; // Username default untuk XAMPP/Laragon
$password = '';     // Password default biasanya kosong, sesuaikan jika Anda menggunakan password

try {
    // Membuat koneksi menggunakan PDO (PHP Data Objects)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Mengatur mode error PDO menjadi Exception agar mudah dideteksi jika gagal
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Menangkap pesan error jika koneksi gagal (akan ditangkap oleh index.php untuk menampilkan indikator merah)
    $db_connection_error = $e->getMessage();
}
?>