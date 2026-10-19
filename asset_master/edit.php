<?php
// edit.php - Edit Data Asset Master
require_once 'koneksi.php';

// Proteksi Halaman: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Validasi parameter ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: index.php");
    exit;
}

// Ambil data asset dari database
try {
    $stmt = $pdo->prepare("SELECT * FROM assets WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $asset = $stmt->fetch();

    if (!$asset) {
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    die("Gagal mengambil data asset: " . $e->getMessage());
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
        $uploaded_new_photo = false;
        $new_foto_name      = null;

        // Cek jika user mengunggah foto baru
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto']['tmp_name'];
            $fileName      = $_FILES['foto']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExts   = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($fileExtension, $allowedExts)) {
                $cleanName          = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($fileName, PATHINFO_FILENAME));
                $new_foto_name      = time() . '_' . $cleanName . '.' . $fileExtension;
                $destPath           = __DIR__ . '/uploads/' . $new_foto_name;

                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    $uploaded_new_photo = true;

                    // Hapus foto fisik lama jika ada
                    if (!empty($asset['foto'])) {
                        $oldFilePath = __DIR__ . '/uploads/' . $asset['foto'];
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                } else {
                    $error = 'Gagal menyimpan file foto baru.';
                }
            } else {
                $error = 'Format file tidak didukung! Harap upload file gambar (JPG, JPEG, PNG, WEBP).';
            }
        }

        // Jalankan update database jika tidak ada error
        if (empty($error)) {
            try {
                if ($uploaded_new_photo) {
                    // Update dengan foto baru
                    $sql = "UPDATE assets SET sdt = :sdt, no_lambung = :no_lambung, merk = :merk, tipe = :tipe, foto = :foto WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':sdt'        => $sdt,
                        ':no_lambung' => (int)$no_lambung,
                        ':merk'       => $merk,
                        ':tipe'       => $tipe,
                        ':foto'       => $new_foto_name,
                        ':id'         => $id
                    ]);
                } else {
                    // Update tanpa mengubah foto
                    $sql = "UPDATE assets SET sdt = :sdt, no_lambung = :no_lambung, merk = :merk, tipe = :tipe WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':sdt'        => $sdt,
                        ':no_lambung' => (int)$no_lambung,
                        ':merk'       => $merk,
                        ':tipe'       => $tipe,
                        ':id'         => $id
                    ]);
                }

                header("Location: index.php?status=success_edit");
                exit;
            } catch (PDOException $e) {
                $error = 'Gagal memperbarui data: ' . $e->getMessage();
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
    <title>Edit Asset - Pendataan Asset Master</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .preview-img {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
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
                        <h5 class="card-title mb-0 fw-bold text-warning-emphasis">
                            <i class="bi bi-pencil-square me-2"></i>Edit Data Asset #<?= htmlspecialchars($asset['id']); ?>
                        </h5>
                    </div>
                    <div class="card-body p-4">

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="edit.php?id=<?= $asset['id']; ?>" method="POST" enctype="multipart/form-data">
                            
                            <!-- Input SDT -->
                            <div class="mb-3">
                                <label for="sdt" class="form-label fw-semibold">SDT <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sdt" name="sdt" required value="<?= htmlspecialchars($_POST['sdt'] ?? $asset['sdt']); ?>">
                            </div>

                            <!-- Input No Lambung dengan Datalist -->
                            <div class="mb-3">
                                <label for="no_lambung" class="form-label fw-semibold">No Lambung <span class="text-danger">*</span></label>
                                <input list="listLambung" type="number" class="form-control" id="no_lambung" name="no_lambung" required value="<?= htmlspecialchars($_POST['no_lambung'] ?? $asset['no_lambung']); ?>">
                                <datalist id="listLambung">
                                    <?php for ($i = 843; $i <= 8164; $i++): ?>
                                        <option value="<?= $i; ?>">
                                    <?php endfor; ?>
                                </datalist>
                                <div class="form-text">Bisa pilih nomor rekomendasi (843 s/d 8164) atau ketik manual.</div>
                            </div>

                            <!-- Input Merk -->
                            <div class="mb-3">
                                <label for="merk" class="form-label fw-semibold">Merk</label>
                                <input type="text" class="form-control" id="merk" name="merk" required value="<?= htmlspecialchars($_POST['merk'] ?? $asset['merk']); ?>">
                            </div>

                            <!-- Input Tipe -->
                            <div class="mb-3">
                                <label for="tipe" class="form-label fw-semibold">Tipe <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="tipe" name="tipe" required value="<?= htmlspecialchars($_POST['tipe'] ?? $asset['tipe']); ?>">
                            </div>

                            <!-- Foto Lama & Input Foto Baru -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold d-block">Foto Asset Saat Ini</label>
                                <div class="mb-2">
                                    <?php if (!empty($asset['foto']) && file_exists(__DIR__ . '/uploads/' . $asset['foto'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($asset['foto']); ?>" alt="Foto Asset" class="preview-img border shadow-sm">
                                        <div class="small text-muted mt-1"><?= htmlspecialchars($asset['foto']); ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-secondary p-2">Belum ada foto</span>
                                    <?php endif; ?>
                                </div>
                                <label for="foto" class="form-label fw-semibold mt-2">Ganti Foto (Opsional)</label>
                                <input class="form-control" type="file" id="foto" name="foto" accept="image/*">
                                <div class="form-text">Biarkan kosong jika tidak ingin mengubah foto asset.</div>
                            </div>

                            <div class="d-flex justify-content-between pt-3 border-top">
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-1"></i> Batal
                                </a>
                                <button type="submit" class="btn btn-warning px-4 text-dark fw-semibold">
                                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
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
