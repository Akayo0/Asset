<?php
// index.php - Dashboard & Read Data Asset Master
require_once 'koneksi.php';

// Proteksi Halaman: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

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
    <title>Dashboard Asset Master</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .asset-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
        }
        .placeholder-thumb {
            width: 80px;
            height: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #e9ecef;
            border-radius: 6px;
            color: #6c757d;
            font-size: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-truck-front-fill me-2"></i> Pendataan Asset Master
            </a>
            <div class="d-flex align-items-center ms-auto">
                <span class="navbar-text text-white me-3 d-none d-sm-inline">
                    <i class="bi bi-person-circle me-1"></i> Halo, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container pb-5">

        <!-- Notifikasi Aksi -->
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'success_add'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Data asset berhasil ditambahkan!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['status'] === 'success_edit'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> Data asset berhasil diperbarui!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['status'] === 'success_delete'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-trash-fill me-2"></i> Data asset berhasil dihapus!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Panel Header: Tombol Tambah & Form Pencarian -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-center justify-content-between">
                    <div class="col-md-4">
                        <a href="input.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Asset Baru
                        </a>
                    </div>
                    <div class="col-md-8 col-lg-6">
                        <!-- Form Pencarian (Metode GET) -->
                        <form action="index.php" method="GET" class="d-flex gap-2">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" name="q" class="form-control" placeholder="Cari berdasarkan SDT atau No Lambung..." value="<?= htmlspecialchars($search); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary text-nowrap">Cari</button>
                            <?php if ($search !== ''): ?>
                                <a href="index.php" class="btn btn-secondary text-nowrap" title="Reset Pencarian">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Data Assets -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-table me-2 text-primary"></i>Daftar Asset
                </h5>
                <span class="badge bg-secondary">Total: <?= count($assets); ?> Asset</span>
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
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
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
                                                    <img src="uploads/<?= htmlspecialchars($row['foto']); ?>" alt="Foto <?= htmlspecialchars($row['sdt']); ?>" class="asset-thumb img-thumbnail">
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

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
