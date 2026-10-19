<?php
// input.php - Tambah Data Asset Baru (Menu Master Data)
require_once 'koneksi.php';

// Proteksi Halaman: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$currentPage = 'master_data';
$error = '';

// Ambil semua daftar no_lambung dari database (master_no_lambung & assets)
try {
    $stmtLambung = $pdo->query("
        SELECT no_lambung FROM master_no_lambung
        UNION
        SELECT no_lambung FROM assets WHERE no_lambung IS NOT NULL
        ORDER BY no_lambung ASC
    ");
    $listLambung = $stmtLambung->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $listLambung = [];
}

// Fallback jika belum ada data sama sekali
if (empty($listLambung)) {
    $listLambung = range(8143, 8164);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sdt               = trim($_POST['sdt'] ?? '');
    $no_lambung_select = trim($_POST['no_lambung'] ?? '');
    $no_lambung_manual = trim($_POST['no_lambung_manual'] ?? '');
    $merk              = trim($_POST['merk'] ?? 'Mercedez Benz');
    $tipe              = trim($_POST['tipe'] ?? '');

    // Gunakan input manual jika diisi atau jika dropdown bernilai __manual__
    if (!empty($no_lambung_manual)) {
        $no_lambung = $no_lambung_manual;
    } else {
        $no_lambung = $no_lambung_select;
    }

    // Validasi input wajib
    if (empty($sdt) || empty($no_lambung) || $no_lambung === '__manual__' || empty($tipe)) {
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
                // Buat nama file unik: time()_namafile.ext
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

        // Jika tidak ada error, simpan data menggunakan PDO Prepared Statement
        if (empty($error)) {
            try {
                // 1. Simpan nomor lambung ke master_no_lambung agar otomatis masuk ke dropdown selanjutnya
                try {
                    $stmtMaster = $pdo->prepare("INSERT IGNORE INTO master_no_lambung (no_lambung) VALUES (:no_lambung)");
                    $stmtMaster->execute([':no_lambung' => (int)$no_lambung]);
                } catch (PDOException $e) {
                    // Abaikan jika tabel master belum ada / terjadi bentrok unik
                }

                // 2. Simpan ke tabel assets
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
                // Hapus foto yang sudah dipindahkan jika query insert database gagal
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
    <title>Master Data - Tambah Asset</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
            background-color: #f8fafc;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        #layout-wrapper {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            min-width: 260px;
            background-color: #0f172a;
            transition: all 0.3s ease;
            z-index: 1040;
        }
        .sidebar .nav-link {
            color: #94a3b8;
            border-radius: 0.5rem;
            padding: 0.7rem 0.9rem;
            transition: all 0.2s;
        }
        .sidebar .nav-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
        }
        .sidebar .nav-link.active {
            color: #ffffff;
            background-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.35);
        }
        #content-wrapper {
            flex-grow: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: -260px;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-backdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1030;
            }
            .sidebar-backdrop.show {
                display: block;
            }
        }
    </style>
</head>
<body>

    <div id="layout-wrapper">
        <!-- Backdrop Mobile -->
        <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

        <!-- Sidebar Kiri -->
        <?php include 'sidebar.php'; ?>

        <!-- Konten Halaman Kanan -->
        <div id="content-wrapper">
            
            <!-- Topbar Header -->
            <header class="navbar navbar-expand bg-white border-bottom shadow-sm px-4 py-3 sticky-top">
                <div class="d-flex align-items-center w-100 justify-content-between">
                    <div class="d-flex align-items-center">
                        <button id="sidebarToggle" class="btn btn-outline-secondary d-lg-none me-3" type="button" aria-label="Toggle Sidebar">
                            <i class="bi bi-list fs-5"></i>
                        </button>
                        <div>
                            <h5 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-folder-plus text-primary me-2"></i>Master Data: Tambah Asset
                            </h5>
                            <small class="text-muted">Formulir pendaftaran data asset kendaraan baru</small>
                        </div>
                    </div>
                    <div>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </header>

            <!-- Isi Konten Utama -->
            <main class="p-4">
                <div class="row justify-content-center">
                    <div class="col-xl-9 col-lg-10">
                        
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="bi bi-pencil-square text-primary me-2"></i>Form Input Asset
                                </h6>
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

                                    <!-- Input No Lambung Dropdown & Tambah Manual -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label fw-semibold mb-0">No Lambung <span class="text-danger">*</span></label>
                                            <button type="button" id="btnToggleManual" class="btn btn-sm btn-link p-0 text-decoration-none">
                                                <i class="bi bi-pencil-square me-1"></i><span id="toggleText">Ketik Nomor Manual</span>
                                            </button>
                                        </div>

                                        <!-- Pilihan Dropdown -->
                                        <div id="wrapperSelect">
                                            <select class="form-select" id="no_lambung_select" name="no_lambung" required>
                                                <option value="">-- Pilih No Lambung --</option>
                                                <?php foreach ($listLambung as $num): ?>
                                                    <option value="<?= $num; ?>" <?= (isset($no_lambung) && $no_lambung == $num) ? 'selected' : ''; ?>><?= $num; ?></option>
                                                <?php endforeach; ?>
                                                <option value="__manual__">+ Tambah Nomor Lambung Baru (Manual)...</option>
                                            </select>
                                            <div class="form-text">Pilih dari nomor yang tersedia (8143 - 8164 & data tersimpan) atau ketik manual.</div>
                                        </div>

                                        <!-- Input Manual -->
                                        <div id="wrapperManual" class="d-none">
                                            <div class="input-group">
                                                <span class="input-group-text bg-light text-muted"><i class="bi bi-123"></i></span>
                                                <input type="number" class="form-control" id="no_lambung_manual" name="no_lambung_manual" placeholder="Ketik nomor lambung baru (misal: 8165, 8200)...">
                                                <button type="button" id="btnBackToSelect" class="btn btn-outline-secondary">
                                                    <i class="bi bi-arrow-return-left me-1"></i>Pilih dari Dropdown
                                                </button>
                                            </div>
                                            <div class="form-text text-primary">
                                                <i class="bi bi-info-circle me-1"></i>Nomor baru yang diketik akan otomatis ditambahkan ke dropdown untuk input berikutnya.
                                            </div>
                                        </div>
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
                                        <div class="form-text">Format yang didukung: JPG, JPEG, PNG, WEBP. Nama file akan otomatis diubah menjadi unik di server.</div>
                                    </div>

                                    <div class="d-flex justify-content-between pt-3 border-top">
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle me-1"></i> Batal
                                        </a>
                                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                            <i class="bi bi-save me-1"></i> Simpan Data Asset
                                        </button>
                                    </div>

                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar untuk Layar Mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarMenu   = document.getElementById('sidebarMenu');
        const backdrop      = document.getElementById('sidebarBackdrop');

        if (sidebarToggle && sidebarMenu && backdrop) {
            sidebarToggle.addEventListener('click', () => {
                sidebarMenu.classList.toggle('show');
                backdrop.classList.toggle('show');
            });

            backdrop.addEventListener('click', () => {
                sidebarMenu.classList.remove('show');
                backdrop.classList.remove('show');
            });
        }

        // Logika Dropdown vs Input Manual No Lambung
        const wrapperSelect     = document.getElementById('wrapperSelect');
        const wrapperManual     = document.getElementById('wrapperManual');
        const selectLambung     = document.getElementById('no_lambung_select');
        const inputManual       = document.getElementById('no_lambung_manual');
        const btnToggleManual   = document.getElementById('btnToggleManual');
        const btnBackToSelect   = document.getElementById('btnBackToSelect');
        const toggleText        = document.getElementById('toggleText');

        function showManual() {
            wrapperSelect.classList.add('d-none');
            wrapperManual.classList.remove('d-none');
            selectLambung.removeAttribute('required');
            inputManual.setAttribute('required', 'required');
            inputManual.focus();
            if (toggleText) toggleText.innerText = 'Pilih dari Dropdown';
        }

        function showSelect() {
            wrapperManual.classList.add('d-none');
            wrapperSelect.classList.remove('d-none');
            inputManual.removeAttribute('required');
            inputManual.value = '';
            selectLambung.setAttribute('required', 'required');
            if (selectLambung.value === '__manual__') {
                selectLambung.value = '';
            }
            if (toggleText) toggleText.innerText = 'Ketik Nomor Manual';
        }

        if (selectLambung) {
            selectLambung.addEventListener('change', function() {
                if (this.value === '__manual__') {
                    showManual();
                }
            });
        }

        if (btnToggleManual) {
            btnToggleManual.addEventListener('click', function() {
                if (wrapperManual.classList.contains('d-none')) {
                    showManual();
                } else {
                    showSelect();
                }
            });
        }

        if (btnBackToSelect) {
            btnBackToSelect.addEventListener('click', showSelect);
        }
    </script>
</body>
</html>
