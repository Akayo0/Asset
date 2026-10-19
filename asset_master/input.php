<?php
// input.php - Tambah Data Asset Baru
require_once 'koneksi.php';

// Proteksi Halaman: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sdt        = trim($_POST['sdt'] ?? '');
    $no_lambung = trim($_POST['no_lambung'] ?? '');
    $merk       = trim($_POST['merk'] ?? 'Mercedez Benz');
    $tipe       = trim($_POST['tipe'] ?? '');

    // Validasi input wajib
    if (empty($sdt) || empty($no_lambung) || empty($tipe)) {
        $error = 'Field SDT, No Lambung, dan Tipe wajib diisi!';
    } elseif (!is_numeric($no_lambung)) {
        $error = 'No Lambung harus berupa angka!';
    } else {
        $foto_name = null;

        // Proses Upload Foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto']['tmp_name'];
            $fileName      = $_FILES['foto']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExts   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($fileExtension, $allowedExts)) {
                // Buat nama file unik: time()_namafile
                $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($fileName, PATHINFO_FILENAME));
                $foto_name = time() . '_' . $cleanName . '.' . $fileExtension;
                $destPath  = __DIR__ . '/uploads/' . $foto_name;

                if (!move_uploaded_file($fileTmpPath, $destPath)) {
                    $error = 'Gagal memindahkan file foto ke direktori uploads.';
                }
            } else {
                $error = 'Format file tidak didukung! Harap upload gambar (JPG, JPEG, PNG, WEBP).';
            }
        }

        // Jika tidak ada error dalam upload atau validasi, simpan ke database
        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO assets (sdt, no_lambung, merk, tipe, foto) VALUES (:sdt, :no_lambung, :merk, :tipe, :foto)");
                $stmt->execute([
                    ':sdt'        => $sdt,
                    ':no_lambung' => (int)$no_lambung,
                    ':merk'       => $merk,
                    ':tipe'       => $tipe,
                    ':foto'       => $foto_name
                ]);

                header("Location: index.php?status=success_add");
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal menyimpan data ke database: ' . $e->getMessage();
                // Hapus foto yang sudah terupload jika query database gagal
                if ($foto_name && file_exists(__DIR__ . '/uploads/' . $foto_name)) {
                    unlink(__DIR__ . '/uploads/' . $foto_name);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Asset - Pendataan Asset Master</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-truck-front-fill me-2"></i> Pendataan Asset Master
            </a>
            <div class="d-flex align-items-center ms-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-plus-square-fill me-2"></i>Tambah Data Asset Baru
                        </h5>
                    </div>
                    <div class="card-body p-4">

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="input.php" method="POST" enctype="multipart/form-data">
                            
                            <!-- Input SDT -->
                            <div class="mb-3">
                                <label for="sdt" class="form-label fw-semibold">SDT <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sdt" name="sdt" placeholder="Contoh: SDT-01-JKT" required value="<?= htmlspecialchars($_POST['sdt'] ?? ''); ?>">
                            </div>

                            <!-- Input No Lambung dengan Datalist (843 s/d 8164) -->
                            <div class="mb-3">
                                <label for="no_lambung" class="form-label fw-semibold">No Lambung <span class="text-danger">*</span></label>
                                <input list="listLambung" type="number" class="form-control" id="no_lambung" name="no_lambung" placeholder="Pilih atau ketik No Lambung (843 - 8164)..." required value="<?= htmlspecialchars($_POST['no_lambung'] ?? ''); ?>">
                                <datalist id="listLambung">
                                    <?php for ($i = 843; $i <= 8164; $i++): ?>
                                        <option value="<?= $i; ?>">
                                    <?php endfor; ?>
                                </datalist>
                                <div class="form-text">Bisa dipilih dari daftar rekomendasi atau diketik secara manual.</div>
                            </div>

                            <!-- Input Merk (Pre-filled dengan Mercedez Benz) -->
                            <div class="mb-3">
                                <label for="merk" class="form-label fw-semibold">Merk</label>
                                <input type="text" class="form-control" id="merk" name="merk" required value="<?= htmlspecialchars($_POST['merk'] ?? 'Mercedez Benz'); ?>">
                            </div>

                            <!-- Input Tipe -->
                            <div class="mb-3">
                                <label for="tipe" class="form-label fw-semibold">Tipe <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tipe" name="tipe" placeholder="Contoh: Axor 2528 R / Actros 4043" required value="<?= htmlspecialchars($_POST['tipe'] ?? ''); ?>">
                            </div>

                            <!-- Input Foto -->
                            <div class="mb-4">
                                <label for="foto" class="form-label fw-semibold">Foto Asset</label>
                                <input class="form-control" type="file" id="foto" name="foto" accept="image/*">
                                <div class="form-text">Format yang didukung: JPG, JPEG, PNG, WEBP. Nama file akan digenerate otomatis.</div>
                            </div>

                            <div class="d-flex justify-content-between pt-3 border-top">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Batal
                                </a>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i> Simpan Data
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
