<?php
// hapus.php - Hapus Data Asset dan File Foto Fisik
require_once 'koneksi.php';

// Proteksi Halaman: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    try {
        // Ambil nama file foto dari database sebelum dihapus
        $stmt = $pdo->prepare("SELECT foto FROM assets WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $asset = $stmt->fetch();

        if ($asset) {
            // Hapus file fisik foto jika ada di folder uploads
            if (!empty($asset['foto'])) {
                $filePath = __DIR__ . '/uploads/' . $asset['foto'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Hapus data dari tabel assets
            $deleteStmt = $pdo->prepare("DELETE FROM assets WHERE id = :id");
            $deleteStmt->execute([':id' => $id]);
        }
    } catch (PDOException $e) {
        die("Gagal menghapus data asset: " . $e->getMessage());
    }
}

// Redirect kembali ke index.php dengan notifikasi
header("Location: index.php?status=success_delete");
exit;
?>
