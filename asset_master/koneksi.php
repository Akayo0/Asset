<?php
// koneksi.php - Konfigurasi Database dengan PDO & Inisialisasi Session

$host    = 'localhost';
$db      = 'db_master';
$user    = 'elma_user';
$pass    = 'ElmaPass2026!';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    try {
        // Fallback untuk local dev / XAMPP (root tanpa password)
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", 'root', '', $options);
    } catch (PDOException $e2) {
        die("Koneksi ke database gagal: " . $e->getMessage());
    }
}

// Inisialisasi session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan folder uploads tersedia
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Nonaktifkan cache browser untuk data dinamis
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
