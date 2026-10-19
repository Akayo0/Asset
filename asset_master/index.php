<?php
// index.php - Dashboard & Read Data Asset Master
require_once 'koneksi.php';

// Proteksi Halaman: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$currentPage = 'beranda';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    if ($search !== '') {
        // Query pencarian berdasarkan sdt ATAU no_lambung menggunakan LIKE dengan PDO binding
        $sql = "SELECT * FROM assets WHERE sdt LIKE :search OR no_lambung LIKE :search ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':search' => '%' . $search . '%']);
    } else {
        // Ambil semua data asset
        $sql = "SELECT * FROM assets ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    $assets = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data asset: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Pendataan Asset Master</title>
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
        .asset-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .placeholder-thumb {
            width: 80px;
            height: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #e2e8f0;
            border-radius: 8px;
            color: #64748b;
            font-size: 1.5rem;
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
        <!-- Backdrop untuk Mobile -->
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
                                <i class="bi bi-house-door-fill text-primary me-2"></i>Beranda
                            </h5>
                            <small class="text-muted">Kelola dan pantau seluruh data asset kendaraan</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="input.php" class="btn btn-primary d-none d-sm-inline-flex align-items-center shadow-sm">
                            <i class="bi bi-plus-circle me-2"></i>Tambah Asset
                        </a>
                    </div>
                </div>
            </header>

            <!-- Isi Konten Utama -->
            <main class="p-4">
                
                <!-- Notifikasi Status Aksi -->
                <?php if (isset($_GET['status'])): ?>
                    <?php if ($_GET['status'] === 'success_add'): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> Data asset berhasil ditambahkan!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif ($_GET['status'] === 'success_edit'): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> Data asset berhasil diperbarui!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php elseif ($_GET['status'] === 'success_delete'): ?>
                        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                            <i class="bi bi-trash-fill me-2"></i> Data asset berhasil dihapus!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Panel Pencarian & Aksi Cepat -->
                <div class="card shadow-sm border-0 mb-4 rounded-3">
                    <div class="card-body p-3">
                        <div class="row g-3 align-items-center justify-content-between">
                            <div class="col-md-6 col-lg-5">
                                <!-- Form Pencarian (Metode GET) -->
                                <form action="index.php" method="GET" class="d-flex gap-2">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                        <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Cari SDT atau No Lambung..." value="<?= htmlspecialchars($search); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary px-3 text-nowrap">Cari</button>
                                    <?php if ($search !== ''): ?>
                                        <a href="index.php" class="btn btn-outline-secondary text-nowrap" title="Reset Pencarian">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                            <div class="col-md-6 col-lg-4 text-md-end">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 py-2 px-3 rounded-pill">
                                    <i class="bi bi-box-seam me-1"></i> Total: <strong><?= count($assets); ?></strong> Asset
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Data Assets -->
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-table text-primary me-2"></i>Tabel Data Asset
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">No</th>
                                        <th class="text-center" style="width: 100px;">Foto</th>
                                        <th>SDT</th>
                                        <th>No Lambung</th>
                                        <th>Merk</th>
                                        <th>Tipe</th>
                                        <th class="text-center" style="width: 160px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($assets)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                                Tidak ada data asset yang ditemukan <?= $search !== '' ? 'dengan kata kunci "<strong>' . htmlspecialchars($search) . '</strong>"' : ''; ?>.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($assets as $row): ?>
                                            <tr>
                                                <td class="text-center fw-semibold text-muted"><?= $no++; ?></td>
                                                <td class="text-center">
                                                    <?php if (!empty($row['foto']) && file_exists(__DIR__ . '/uploads/' . $row['foto'])): ?>
                                                        <a href="uploads/<?= htmlspecialchars($row['foto']); ?>" target="_blank">
                                                            <img src="uploads/<?= htmlspecialchars($row['foto']); ?>" alt="Foto <?= htmlspecialchars($row['sdt']); ?>" class="asset-thumb img-thumbnail shadow-sm">
                                                        </a>
                                                    <?php else: ?>
                                                        <div class="placeholder-thumb" title="Tidak ada foto">
                                                            <i class="bi bi-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['sdt']); ?></td>
                                                <td>
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 fs-6">
                                                        <?= htmlspecialchars($row['no_lambung']); ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($row['merk']); ?></td>
                                                <td><?= htmlspecialchars($row['tipe']); ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <a href="edit.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit Data">
                                                            <i class="bi bi-pencil-square"></i> Edit
                                                        </a>
                                                        <a href="hapus.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-danger" title="Hapus Data" onclick="return confirm('Apakah Anda yakin ingin menghapus data asset ini? Data dan foto terkait akan dihapus permanen.');">
                                                            <i class="bi bi-trash"></i> Hapus
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
    </script>
</body>
</html>
