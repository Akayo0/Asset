<?php
// radio_sdt.php - Monitoring & CRUD Data Radio Unit SDT, SWR, dan Antena
require_once 'koneksi.php';

// Proteksi Halaman: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$currentPage = 'radio_sdt';
$statusMessage = '';
$errorMessage  = '';

// --- Handle Export CSV ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Data_Radio_SDT_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    // BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, [
        'No', 'No Lambung', 'Status Unit SDT', 'Status Pemasangan Radio', 'Status Radio',
        'Tipe Radio', 'ID Radio', 'Serial Number Radio',
        'SWR Road 1 TCM', 'SWR Road 2 TCM', 'SWR Haulrod Bek', 'SWR Office LMA',
        'SWR ERT Bunyut', 'SWR ERT Bek', 'SWR Bengkirai',
        'Antena Jenis', 'Antena Ukuran', 'Keterangan'
    ]);

    $stmtExport = $pdo->query("SELECT * FROM radio_sdt ORDER BY id ASC");
    $no = 1;
    while ($row = $stmtExport->fetch()) {
        fputcsv($output, [
            $no++,
            $row['no_lambung'],
            $row['status_unit_sdt'],
            $row['status_pemasangan_radio'],
            $row['status_radio'],
            $row['tipe_radio'],
            $row['id_radio'],
            $row['serial_number_radio'],
            $row['swr_road1_tcm'],
            $row['swr_road2_tcm'],
            $row['swr_haulrod_bek'],
            $row['swr_office_lma'],
            $row['swr_ert_bunyut'],
            $row['swr_ert_bek'],
            $row['swr_bengkirai'],
            $row['antena_jenis'],
            $row['antena_ukuran'],
            $row['keterangan']
        ]);
    }
    fclose($output);
    exit;
}

// --- Handle POST Actions (Create / Update / Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --- Handle AJAX Toggle Status ---
    if ($action === 'toggle_status') {
        header('Content-Type: application/json');
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $field = $_POST['field'] ?? '';

        if (!$id || !in_array($field, ['status_unit_sdt', 'status_pemasangan_radio', 'status_radio'])) {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT no_lambung, status_unit_sdt, status_pemasangan_radio, status_radio FROM radio_sdt WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $curr = $stmt->fetch();
            if (!$curr) {
                echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan.']);
                exit;
            }

            $nextVal = '';
            if ($field === 'status_unit_sdt') {
                $nextVal = ($curr['status_unit_sdt'] === 'RFU') ? 'BD' : 'RFU';
            } elseif ($field === 'status_radio') {
                $nextVal = ($curr['status_radio'] === 'AKTIF') ? 'TIDAK AKTIF' : 'AKTIF';
            } elseif ($field === 'status_pemasangan_radio') {
                if ($curr['status_pemasangan_radio'] === 'TERPASANG') {
                    $nextVal = 'BELUM TERPASANG';
                } elseif ($curr['status_pemasangan_radio'] === 'BELUM TERPASANG') {
                    $nextVal = 'DILEPAS';
                } else {
                    $nextVal = 'TERPASANG';
                }
            }

            $updateStmt = $pdo->prepare("UPDATE radio_sdt SET $field = :val WHERE id = :id");
            $updateStmt->execute([':val' => $nextVal, ':id' => $id]);

            // Hitung ulang statistik ringkasan
            $statsAll = $pdo->query("SELECT status_unit_sdt, status_radio FROM radio_sdt")->fetchAll();
            $statRFU = 0; $statBD = 0; $statAktif = 0; $statNonaktif = 0;
            foreach ($statsAll as $s) {
                if ($s['status_unit_sdt'] === 'RFU') $statRFU++;
                if ($s['status_unit_sdt'] === 'BD')  $statBD++;
                if ($s['status_radio'] === 'AKTIF')  $statAktif++;
                if ($s['status_radio'] === 'TIDAK AKTIF') $statNonaktif++;
            }

            echo json_encode([
                'success' => true,
                'id' => $id,
                'field' => $field,
                'new_value' => $nextVal,
                'no_lambung' => $curr['no_lambung'],
                'stats' => [
                    'rfu' => $statRFU,
                    'bd' => $statBD,
                    'aktif' => $statAktif,
                    'nonaktif' => $statNonaktif,
                ]
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'create') {
        $no_lambung       = trim($_POST['no_lambung'] ?? '');
        $status_unit_sdt  = trim($_POST['status_unit_sdt'] ?? 'RFU');
        $status_pasang    = trim($_POST['status_pemasangan_radio'] ?? 'TERPASANG');
        $status_radio     = trim($_POST['status_radio'] ?? 'AKTIF');
        $tipe_radio       = trim($_POST['tipe_radio'] ?? 'XiR3688');
        $id_radio         = trim($_POST['id_radio'] ?? '');
        $serial_number    = trim($_POST['serial_number_radio'] ?? '');
        $swr_road1        = trim($_POST['swr_road1_tcm'] ?? '');
        $swr_road2        = trim($_POST['swr_road2_tcm'] ?? '');
        $swr_haulrod      = trim($_POST['swr_haulrod_bek'] ?? '');
        $swr_office       = trim($_POST['swr_office_lma'] ?? '');
        $swr_bunyut       = trim($_POST['swr_ert_bunyut'] ?? '');
        $swr_ert_bek      = trim($_POST['swr_ert_bek'] ?? '');
        $swr_bengkirai    = trim($_POST['swr_bengkirai'] ?? '');
        $antena_jenis     = trim($_POST['antena_jenis'] ?? '');
        $antena_ukuran    = trim($_POST['antena_ukuran'] ?? '');
        $keterangan       = trim($_POST['keterangan'] ?? '');

        if (empty($no_lambung)) {
            $errorMessage = 'Nomor lambung wajib diisi.';
        } else {
            try {
                // Ambil nomor urut terakhir
                $urutStmt = $pdo->query("SELECT MAX(no_urut) as max_urut FROM radio_sdt");
                $maxUrut = (int)($urutStmt->fetch()['max_urut'] ?? 0) + 1;

                $sql = "INSERT INTO radio_sdt 
                        (no_urut, no_lambung, status_unit_sdt, status_pemasangan_radio, status_radio, tipe_radio, id_radio, serial_number_radio,
                         swr_road1_tcm, swr_road2_tcm, swr_haulrod_bek, swr_office_lma, swr_ert_bunyut, swr_ert_bek, swr_bengkirai,
                         antena_jenis, antena_ukuran, keterangan) 
                        VALUES 
                        (:no_urut, :no_lambung, :status_unit_sdt, :status_pemasangan_radio, :status_radio, :tipe_radio, :id_radio, :serial_number_radio,
                         :swr_road1_tcm, :swr_road2_tcm, :swr_haulrod_bek, :swr_office_lma, :swr_ert_bunyut, :swr_ert_bek, :swr_bengkirai,
                         :antena_jenis, :antena_ukuran, :keterangan)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':no_urut'                => $maxUrut,
                    ':no_lambung'             => $no_lambung,
                    ':status_unit_sdt'        => $status_unit_sdt,
                    ':status_pemasangan_radio'=> $status_pasang,
                    ':status_radio'           => $status_radio,
                    ':tipe_radio'             => $tipe_radio,
                    ':id_radio'               => $id_radio,
                    ':serial_number_radio'    => $serial_number,
                    ':swr_road1_tcm'          => $swr_road1,
                    ':swr_road2_tcm'          => $swr_road2,
                    ':swr_haulrod_bek'        => $swr_haulrod,
                    ':swr_office_lma'         => $swr_office,
                    ':swr_ert_bunyut'         => $swr_bunyut,
                    ':swr_ert_bek'            => $swr_ert_bek,
                    ':swr_bengkirai'          => $swr_bengkirai,
                    ':antena_jenis'           => $antena_jenis,
                    ':antena_ukuran'          => $antena_ukuran,
                    ':keterangan'             => $keterangan,
                ]);
                header("Location: radio_sdt.php?status=success_add");
                exit;
            } catch (PDOException $e) {
                $errorMessage = 'Gagal menambahkan data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'update') {
        $id               = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $no_lambung       = trim($_POST['no_lambung'] ?? '');
        $status_unit_sdt  = trim($_POST['status_unit_sdt'] ?? 'RFU');
        $status_pasang    = trim($_POST['status_pemasangan_radio'] ?? 'TERPASANG');
        $status_radio     = trim($_POST['status_radio'] ?? 'AKTIF');
        $tipe_radio       = trim($_POST['tipe_radio'] ?? 'XiR3688');
        $id_radio         = trim($_POST['id_radio'] ?? '');
        $serial_number    = trim($_POST['serial_number_radio'] ?? '');
        $swr_road1        = trim($_POST['swr_road1_tcm'] ?? '');
        $swr_road2        = trim($_POST['swr_road2_tcm'] ?? '');
        $swr_haulrod      = trim($_POST['swr_haulrod_bek'] ?? '');
        $swr_office       = trim($_POST['swr_office_lma'] ?? '');
        $swr_bunyut       = trim($_POST['swr_ert_bunyut'] ?? '');
        $swr_ert_bek      = trim($_POST['swr_ert_bek'] ?? '');
        $swr_bengkirai    = trim($_POST['swr_bengkirai'] ?? '');
        $antena_jenis     = trim($_POST['antena_jenis'] ?? '');
        $antena_ukuran    = trim($_POST['antena_ukuran'] ?? '');
        $keterangan       = trim($_POST['keterangan'] ?? '');

        if ($id && !empty($no_lambung)) {
            try {
                $sql = "UPDATE radio_sdt SET 
                        no_lambung = :no_lambung,
                        status_unit_sdt = :status_unit_sdt,
                        status_pemasangan_radio = :status_pemasangan_radio,
                        status_radio = :status_radio,
                        tipe_radio = :tipe_radio,
                        id_radio = :id_radio,
                        serial_number_radio = :serial_number_radio,
                        swr_road1_tcm = :swr_road1_tcm,
                        swr_road2_tcm = :swr_road2_tcm,
                        swr_haulrod_bek = :swr_haulrod_bek,
                        swr_office_lma = :swr_office_lma,
                        swr_ert_bunyut = :swr_ert_bunyut,
                        swr_ert_bek = :swr_ert_bek,
                        swr_bengkirai = :swr_bengkirai,
                        antena_jenis = :antena_jenis,
                        antena_ukuran = :antena_ukuran,
                        keterangan = :keterangan
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':no_lambung'             => $no_lambung,
                    ':status_unit_sdt'        => $status_unit_sdt,
                    ':status_pemasangan_radio'=> $status_pasang,
                    ':status_radio'           => $status_radio,
                    ':tipe_radio'             => $tipe_radio,
                    ':id_radio'               => $id_radio,
                    ':serial_number_radio'    => $serial_number,
                    ':swr_road1_tcm'          => $swr_road1,
                    ':swr_road2_tcm'          => $swr_road2,
                    ':swr_haulrod_bek'        => $swr_haulrod,
                    ':swr_office_lma'         => $swr_office,
                    ':swr_ert_bunyut'         => $swr_bunyut,
                    ':swr_ert_bek'            => $swr_ert_bek,
                    ':swr_bengkirai'          => $swr_bengkirai,
                    ':antena_jenis'           => $antena_jenis,
                    ':antena_ukuran'          => $antena_ukuran,
                    ':keterangan'             => $keterangan,
                    ':id'                     => $id
                ]);
                header("Location: radio_sdt.php?status=success_edit");
                exit;
            } catch (PDOException $e) {
                $errorMessage = 'Gagal memperbarui data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM radio_sdt WHERE id = :id");
                $stmt->execute([':id' => $id]);
                header("Location: radio_sdt.php?status=success_delete");
                exit;
            } catch (PDOException $e) {
                $errorMessage = 'Gagal menghapus data: ' . $e->getMessage();
            }
        }
    }
}

// Feedback status message
$statusParam = $_GET['status'] ?? '';
if ($statusParam === 'success_add') {
    $statusMessage = 'Data radio unit berhasil ditambahkan!';
} elseif ($statusParam === 'success_edit') {
    $statusMessage = 'Data radio unit berhasil diperbarui!';
} elseif ($statusParam === 'success_delete') {
    $statusMessage = 'Data radio unit berhasil dihapus!';
}

// --- Query Data & Filter ---
$search       = isset($_GET['q']) ? trim($_GET['q']) : '';
$filterUnit   = isset($_GET['unit_sdt']) ? trim($_GET['unit_sdt']) : '';
$filterRadio  = isset($_GET['status_radio']) ? trim($_GET['status_radio']) : '';
$filterTipe   = isset($_GET['tipe_radio']) ? trim($_GET['tipe_radio']) : '';

$whereClauses = [];
$params       = [];

if ($search !== '') {
    $whereClauses[] = "(no_lambung LIKE :search OR id_radio LIKE :search OR serial_number_radio LIKE :search OR tipe_radio LIKE :search OR keterangan LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($filterUnit !== '') {
    $whereClauses[] = "status_unit_sdt = :unit_sdt";
    $params[':unit_sdt'] = $filterUnit;
}
if ($filterRadio !== '') {
    $whereClauses[] = "status_radio = :status_radio";
    $params[':status_radio'] = $filterRadio;
}
if ($filterTipe !== '') {
    $whereClauses[] = "tipe_radio = :tipe_radio";
    $params[':tipe_radio'] = $filterTipe;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

$sql = "SELECT * FROM radio_sdt $whereSql ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$radios = $stmt->fetchAll();

// --- Statistik Ringkasan ---
$statTotal     = 0;
$statRFU       = 0;
$statBD        = 0;
$statAktif     = 0;
$statNonaktif  = 0;
$statXiR       = 0;
$statGM        = 0;

try {
    $statsAll = $pdo->query("SELECT status_unit_sdt, status_radio, tipe_radio FROM radio_sdt")->fetchAll();
    $statTotal = count($statsAll);
    foreach ($statsAll as $s) {
        if ($s['status_unit_sdt'] === 'RFU') $statRFU++;
        if ($s['status_unit_sdt'] === 'BD')  $statBD++;
        if ($s['status_radio'] === 'AKTIF')  $statAktif++;
        if ($s['status_radio'] === 'TIDAK AKTIF') $statNonaktif++;
        if (stripos($s['tipe_radio'], 'XiR') !== false) $statXiR++;
        if (stripos($s['tipe_radio'], 'GM') !== false)  $statGM++;
    }
} catch (PDOException $e) {
    // Abaikan jika tabel belum ada
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Radio & SWR Unit SDT - Asset Master</title>
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

        /* Styling Tabel Excel Matching */
        .table-radio {
            font-size: 0.82rem;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table-radio th {
            text-align: center;
            vertical-align: middle;
            font-weight: 700;
            letter-spacing: 0.3px;
            background-color: #fce4d6; /* Warna header peach persis di gambar */
            color: #212529;
            border: 1px solid #d9d9d9;
            padding: 8px 6px;
            white-space: nowrap;
        }
        .table-radio th.th-swr {
            background-color: #f8cbad;
        }
        .table-radio th.th-antena {
            background-color: #fce4d6;
        }
        .table-radio td {
            vertical-align: middle;
            border: 1px solid #e2e8f0;
            padding: 6px 8px;
            white-space: nowrap;
        }
        .table-radio tbody tr:hover {
            background-color: #f1f5f9;
        }

        /* Warna Status Persis Gambar */
        /* Styling Status Badges Interaktif (Bisa Diklik) */
        .status-badge-btn {
            cursor: pointer;
            user-select: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            transition: all 0.18s ease-in-out;
            border: 1px solid transparent;
        }
        .status-badge-btn:hover {
            transform: translateY(-1px) scale(1.06);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.18);
            filter: brightness(0.93);
            border-color: rgba(0, 0, 0, 0.15);
        }
        .status-badge-btn:active {
            transform: translateY(0) scale(0.96);
        }

        .badge-rfu {
            background-color: #9bc2e6;
            color: #0c2d48;
            font-weight: 700;
            padding: 0.35em 0.65em;
            border-radius: 4px;
        }
        .badge-bd {
            background-color: #ff9999;
            color: #900000;
            font-weight: 700;
            padding: 0.35em 0.65em;
            border-radius: 4px;
        }
        .badge-aktif {
            background-color: #d9e1f2;
            color: #1f3864;
            font-weight: 700;
            padding: 0.35em 0.65em;
            border-radius: 4px;
        }
        .badge-tidak-aktif {
            background-color: #ffff99;
            color: #7f6000;
            font-weight: 700;
            padding: 0.35em 0.65em;
            border-radius: 4px;
        }
        .badge-terpasang {
            background-color: #b4c6e7;
            color: #1b365d;
            font-weight: 600;
            padding: 0.35em 0.6em;
            border-radius: 4px;
        }
        .badge-belum-terpasang {
            background-color: #e2e8f0;
            color: #475569;
            font-weight: 600;
            padding: 0.35em 0.6em;
            border-radius: 4px;
        }
        .badge-dilepas {
            background-color: #fecaca;
            color: #991b1b;
            font-weight: 600;
            padding: 0.35em 0.6em;
            border-radius: 4px;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin-animation {
            display: inline-block;
            animation: spin 0.4s linear infinite;
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

        @media print {
            .sidebar, .navbar, .btn, .card-header, .alert, .stat-card, form {
                display: none !important;
            }
            #layout-wrapper, #content-wrapper {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .table-responsive {
                overflow: visible !important;
            }
            .table-radio {
                font-size: 8pt !important;
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
                            <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-broadcast-pin text-primary"></i> Data Pemasangan Radio & SWR Unit SDT
                            </h5>
                            <small class="text-secondary">Monitoring status radio rig, SWR channel, dan spesifikasi antena per unit lambung</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2">
                        <a href="radio_sdt.php?export=csv" class="btn btn-sm btn-outline-success" title="Download Excel (CSV)">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                        </a>
                        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary" title="Cetak Tabel">
                            <i class="bi bi-printer me-1"></i> Cetak
                        </button>
                        <button class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Data Radio
                        </button>
                    </div>
                </div>
            </header>

            <!-- Isi Halaman -->
            <main class="p-4">

                <!-- Alert Notifikasi -->
                <?php if (!empty($statusMessage)): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                        <div><?= htmlspecialchars($statusMessage); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                        <div><?= htmlspecialchars($errorMessage); ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Baris Statistik Ringkasan -->
                <div class="row g-3 mb-4 stat-card">
                    <!-- Total Unit Radio -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-primary border-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small fw-medium">Total Unit Radio</span>
                                    <h3 class="fw-bold mb-0 text-dark"><?= $statTotal; ?></h3>
                                    <small class="text-muted">Unit Terdaftar</small>
                                </div>
                                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                                    <i class="bi bi-truck fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Unit SDT -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-info border-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small fw-medium">Kondisi Unit SDT</span>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge-rfu" id="stat-rfu"><?= $statRFU; ?> RFU</span>
                                        <span class="badge-bd" id="stat-bd"><?= $statBD; ?> BD</span>
                                    </div>
                                    <small class="text-muted">Ready For Use vs Breakdown</small>
                                </div>
                                <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle">
                                    <i class="bi bi-shield-check fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Radio Aktif -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-success border-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small fw-medium">Status Operasional Radio</span>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge-aktif" id="stat-aktif"><?= $statAktif; ?> Aktif</span>
                                        <span class="badge-tidak-aktif <?= ($statNonaktif > 0) ? '' : 'd-none'; ?>" id="stat-nonaktif"><?= $statNonaktif; ?> Nonaktif</span>
                                    </div>
                                    <small class="text-muted">Status Transmit / Receive</small>
                                </div>
                                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                                    <i class="bi bi-reception-4 fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tipe Perangkat -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-warning border-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small fw-medium">Distribusi Tipe Radio</span>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-secondary"><?= $statXiR; ?> XiR3688</span>
                                        <span class="badge bg-dark"><?= $statGM; ?> GM338</span>
                                    </div>
                                    <small class="text-muted">Motorola Series</small>
                                </div>
                                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                                    <i class="bi bi-hdd-network fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kartu Tabel Utama & Filter -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
                        
                        <!-- Form Pencarian & Filter -->
                        <form method="GET" action="radio_sdt.php" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                            <div class="input-group input-group-sm" style="max-width: 280px;">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="q" class="form-control border-start-0" placeholder="Cari Lambung / SN / ID..." value="<?= htmlspecialchars($search); ?>">
                            </div>

                            <!-- Filter Unit SDT -->
                            <select name="unit_sdt" class="form-select form-select-sm" style="width: auto;">
                                <option value="">-- Status Unit --</option>
                                <option value="RFU" <?= ($filterUnit === 'RFU') ? 'selected' : ''; ?>>RFU (Ready)</option>
                                <option value="BD" <?= ($filterUnit === 'BD') ? 'selected' : ''; ?>>BD (Breakdown)</option>
                            </select>

                            <!-- Filter Radio -->
                            <select name="status_radio" class="form-select form-select-sm" style="width: auto;">
                                <option value="">-- Status Radio --</option>
                                <option value="AKTIF" <?= ($filterRadio === 'AKTIF') ? 'selected' : ''; ?>>AKTIF</option>
                                <option value="TIDAK AKTIF" <?= ($filterRadio === 'TIDAK AKTIF') ? 'selected' : ''; ?>>TIDAK AKTIF</option>
                            </select>

                            <!-- Filter Tipe Radio -->
                            <select name="tipe_radio" class="form-select form-select-sm" style="width: auto;">
                                <option value="">-- Tipe Radio --</option>
                                <option value="XiR3688" <?= ($filterTipe === 'XiR3688') ? 'selected' : ''; ?>>XiR3688</option>
                                <option value="GM338" <?= ($filterTipe === 'GM338') ? 'selected' : ''; ?>>GM338</option>
                            </select>

                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="bi bi-funnel me-1"></i> Terapkan
                            </button>

                            <?php if ($search !== '' || $filterUnit !== '' || $filterRadio !== '' || $filterTipe !== ''): ?>
                                <a href="radio_sdt.php" class="btn btn-sm btn-outline-danger" title="Reset Filter">
                                    <i class="bi bi-x-circle"></i> Reset
                                </a>
                            <?php endif; ?>
                        </form>

                        <div class="text-muted small">
                            Menampilkan <strong><?= count($radios); ?></strong> dari <strong><?= $statTotal; ?></strong> unit
                        </div>
                    </div>

                    <!-- Tabel Data dengan Format Matching Excel Gambar -->
                    <div class="table-responsive" style="max-height: 720px;">
                        <table class="table table-bordered table-hover table-radio mb-0">
                            <thead class="sticky-top shadow-sm">
                                <tr>
                                    <th rowspan="2" style="width: 40px;">NO</th>
                                    <th rowspan="2">NO<br>LAMBUNG</th>
                                    <th rowspan="2">STATUS<br>UNIT SDT</th>
                                    <th rowspan="2">STATUS<br>PEMASANGAN<br>RADIO</th>
                                    <th rowspan="2">STATUS<br>RADIO</th>
                                    <th rowspan="2">TIPE</th>
                                    <th rowspan="2">ID RADIO</th>
                                    <th rowspan="2">SERIAL NUMBER<br>RADIO</th>
                                    <!-- SWR Group (7 Channel) -->
                                    <th colspan="7" class="th-swr">SWR</th>
                                    <!-- Antena Group (2 Kolom) -->
                                    <th colspan="2" class="th-antena">ANTENA</th>
                                    <th rowspan="2">KETERANGAN</th>
                                    <th rowspan="2" style="width: 90px;">AKSI</th>
                                </tr>
                                <tr>
                                    <!-- Sub-kolom SWR -->
                                    <th class="th-swr" style="font-size: 0.72rem;">ROAD 1 TCM</th>
                                    <th class="th-swr" style="font-size: 0.72rem;">ROAD 2 TCM</th>
                                    <th class="th-swr" style="font-size: 0.72rem;">HAULROD BEK</th>
                                    <th class="th-swr" style="font-size: 0.72rem;">OFFICE LMA</th>
                                    <th class="th-swr" style="font-size: 0.72rem;">ERT BUNYUT</th>
                                    <th class="th-swr" style="font-size: 0.72rem;">ERT BEK</th>
                                    <th class="th-swr" style="font-size: 0.72rem;">BENGKIRAI</th>
                                    <!-- Sub-kolom ANTENA -->
                                    <th class="th-antena" style="font-size: 0.72rem;">JENIS</th>
                                    <th class="th-antena" style="font-size: 0.72rem;">UKURAN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($radios)): ?>
                                    <tr>
                                        <td colspan="19" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                            Tidak ada data radio unit yang cocok dengan filter atau pencarian.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php 
                                    $no = 1;
                                    foreach ($radios as $row): 
                                    ?>
                                        <tr>
                                            <td class="text-center text-muted fw-bold"><?= $row['no_urut'] ? $row['no_urut'] : $no; ?></td>
                                            <td class="text-center fw-bold text-dark fs-6"><?= htmlspecialchars($row['no_lambung']); ?></td>
                                            <td class="text-center">
                                                <span class="status-badge-btn <?= ($row['status_unit_sdt'] === 'BD') ? 'badge-bd' : 'badge-rfu'; ?>"
                                                      data-id="<?= $row['id']; ?>"
                                                      data-field="status_unit_sdt"
                                                      data-current="<?= htmlspecialchars($row['status_unit_sdt']); ?>"
                                                      title="Klik untuk ganti: RFU ↔ BD">
                                                    <span class="badge-text"><?= htmlspecialchars($row['status_unit_sdt']); ?></span>
                                                    <i class="bi bi-arrow-repeat opacity-50" style="font-size: 0.65rem;"></i>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                $pasangCls = 'badge-terpasang';
                                                if ($row['status_pemasangan_radio'] === 'BELUM TERPASANG') $pasangCls = 'badge-belum-terpasang';
                                                elseif ($row['status_pemasangan_radio'] === 'DILEPAS') $pasangCls = 'badge-dilepas';
                                                ?>
                                                <span class="status-badge-btn <?= $pasangCls; ?>"
                                                      data-id="<?= $row['id']; ?>"
                                                      data-field="status_pemasangan_radio"
                                                      data-current="<?= htmlspecialchars($row['status_pemasangan_radio']); ?>"
                                                      title="Klik untuk ganti status pemasangan">
                                                    <span class="badge-text"><?= htmlspecialchars($row['status_pemasangan_radio']); ?></span>
                                                    <i class="bi bi-arrow-repeat opacity-50" style="font-size: 0.65rem;"></i>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="status-badge-btn <?= ($row['status_radio'] === 'TIDAK AKTIF') ? 'badge-tidak-aktif' : 'badge-aktif'; ?>"
                                                      data-id="<?= $row['id']; ?>"
                                                      data-field="status_radio"
                                                      data-current="<?= htmlspecialchars($row['status_radio']); ?>"
                                                      title="Klik untuk ganti: AKTIF ↔ TIDAK AKTIF">
                                                    <span class="badge-text"><?= htmlspecialchars($row['status_radio']); ?></span>
                                                    <i class="bi bi-arrow-repeat opacity-50" style="font-size: 0.65rem;"></i>
                                                </span>
                                            </td>
                                            <td class="text-center fw-semibold text-secondary"><?= htmlspecialchars($row['tipe_radio']); ?></td>
                                            <td class="text-center fw-bold text-primary"><?= htmlspecialchars($row['id_radio']); ?></td>
                                            <td class="text-center font-monospace fw-semibold"><?= htmlspecialchars($row['serial_number_radio']); ?></td>
                                            
                                            <!-- SWR Channels -->
                                            <td class="text-center text-muted"><?= htmlspecialchars($row['swr_road1_tcm']) ?: '-'; ?></td>
                                            <td class="text-center text-muted"><?= htmlspecialchars($row['swr_road2_tcm']) ?: '-'; ?></td>
                                            <td class="text-center text-muted"><?= htmlspecialchars($row['swr_haulrod_bek']) ?: '-'; ?></td>
                                            <td class="text-center text-muted"><?= htmlspecialchars($row['swr_office_lma']) ?: '-'; ?></td>
                                            <td class="text-center text-muted"><?= htmlspecialchars($row['swr_ert_bunyut']) ?: '-'; ?></td>
                                            <td class="text-center text-muted"><?= htmlspecialchars($row['swr_ert_bek']) ?: '-'; ?></td>
                                            <td class="text-center text-muted"><?= htmlspecialchars($row['swr_bengkirai']) ?: '-'; ?></td>
                                            
                                            <!-- Antena -->
                                            <td class="text-center fw-semibold"><?= htmlspecialchars($row['antena_jenis']) ?: '-'; ?></td>
                                            <td class="text-center fw-semibold"><?= htmlspecialchars($row['antena_ukuran']) ?: '-'; ?></td>
                                            
                                            <!-- Keterangan -->
                                            <td class="small text-muted"><?= htmlspecialchars($row['keterangan'] ?? ''); ?></td>
                                            
                                            <!-- Aksi -->
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary btn-edit" 
                                                            data-data='<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>'
                                                            title="Edit Data">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-delete" 
                                                            data-id="<?= $row['id']; ?>"
                                                            data-lambung="<?= htmlspecialchars($row['no_lambung']); ?>"
                                                            title="Hapus Data">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                    $no++;
                                    endforeach; 
                                    ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- ==================== MODAL TAMBAH DATA ==================== -->
    <div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="radio_sdt.php">
                    <input type="hidden" name="action" value="create">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold" id="modalTambahLabel">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Data Radio Unit SDT
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Bagian 1: Identitas Unit & Radio -->
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-truck me-1 text-primary"></i> Identitas Unit & Radio</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">No Lambung <span class="text-danger">*</span></label>
                                    <input type="text" name="no_lambung" class="form-control" placeholder="Contoh: 8144" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Status Unit SDT</label>
                                    <select name="status_unit_sdt" class="form-select">
                                        <option value="RFU" selected>RFU (Ready For Use)</option>
                                        <option value="BD">BD (Breakdown)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Pemasangan Radio</label>
                                    <select name="status_pemasangan_radio" class="form-select">
                                        <option value="TERPASANG" selected>TERPASANG</option>
                                        <option value="BELUM TERPASANG">BELUM TERPASANG</option>
                                        <option value="DILEPAS">DILEPAS</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Status Radio</label>
                                    <select name="status_radio" class="form-select">
                                        <option value="AKTIF" selected>AKTIF</option>
                                        <option value="TIDAK AKTIF">TIDAK AKTIF</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tipe Radio</label>
                                    <input type="text" name="tipe_radio" class="form-control" list="tipeRadioOptions" value="XiR3688" placeholder="XiR3688 / GM338">
                                    <datalist id="tipeRadioOptions">
                                        <option value="XiR3688">
                                        <option value="GM338">
                                    </datalist>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">ID Radio</label>
                                    <input type="text" name="id_radio" class="form-control" placeholder="Contoh: B844 / 8144">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Serial Number (S/N)</label>
                                    <input type="text" name="serial_number_radio" class="form-control font-monospace" placeholder="Contoh: 866TBD0396">
                                </div>
                            </div>
                        </div>

                        <!-- Bagian 2: Nilai SWR (Standing Wave Ratio) -->
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-activity me-1 text-danger"></i> Pengukuran SWR Channel</h6>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Road 1 TCM</label>
                                    <input type="text" name="swr_road1_tcm" class="form-control form-control-sm" placeholder="Nilai SWR">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Road 2 TCM</label>
                                    <input type="text" name="swr_road2_tcm" class="form-control form-control-sm" placeholder="Nilai SWR">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Haulrod BEK</label>
                                    <input type="text" name="swr_haulrod_bek" class="form-control form-control-sm" placeholder="Nilai SWR">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Office LMA</label>
                                    <input type="text" name="swr_office_lma" class="form-control form-control-sm" placeholder="Nilai SWR">
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-secondary">ERT Bunyut</label>
                                    <input type="text" name="swr_ert_bunyut" class="form-control form-control-sm" placeholder="Nilai SWR">
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-secondary">ERT BEK</label>
                                    <input type="text" name="swr_ert_bek" class="form-control form-control-sm" placeholder="Nilai SWR">
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-secondary">Bengkirai</label>
                                    <input type="text" name="swr_bengkirai" class="form-control form-control-sm" placeholder="Nilai SWR">
                                </div>
                            </div>
                        </div>

                        <!-- Bagian 3: Antena & Keterangan -->
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-broadcast me-1 text-success"></i> Spesifikasi Antena & Catatan</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jenis Antena</label>
                                    <select name="antena_jenis" class="form-select">
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="PANJANG">PANJANG</option>
                                        <option value="PENDEK">PENDEK</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Ukuran Antena</label>
                                    <input type="text" name="antena_ukuran" class="form-control" placeholder="Contoh: 117CM / 64CM">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Keterangan Tambahan</label>
                                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Catatan kondisi perangkat..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL EDIT DATA ==================== -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="radio_sdt.php">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header bg-warning-subtle text-dark border-bottom">
                        <h5 class="modal-title fw-bold" id="modalEditLabel">
                            <i class="bi bi-pencil-square me-1 text-warning"></i> Edit Data Radio Unit SDT
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- Identitas Unit & Radio -->
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-truck me-1 text-primary"></i> Identitas Unit & Radio</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">No Lambung <span class="text-danger">*</span></label>
                                    <input type="text" name="no_lambung" id="edit_no_lambung" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Status Unit SDT</label>
                                    <select name="status_unit_sdt" id="edit_status_unit_sdt" class="form-select">
                                        <option value="RFU">RFU (Ready For Use)</option>
                                        <option value="BD">BD (Breakdown)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Pemasangan Radio</label>
                                    <select name="status_pemasangan_radio" id="edit_status_pasang" class="form-select">
                                        <option value="TERPASANG">TERPASANG</option>
                                        <option value="BELUM TERPASANG">BELUM TERPASANG</option>
                                        <option value="DILEPAS">DILEPAS</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Status Radio</label>
                                    <select name="status_radio" id="edit_status_radio" class="form-select">
                                        <option value="AKTIF">AKTIF</option>
                                        <option value="TIDAK AKTIF">TIDAK AKTIF</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Tipe Radio</label>
                                    <input type="text" name="tipe_radio" id="edit_tipe_radio" class="form-control" list="tipeRadioOptions">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">ID Radio</label>
                                    <input type="text" name="id_radio" id="edit_id_radio" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Serial Number (S/N)</label>
                                    <input type="text" name="serial_number_radio" id="edit_serial_number" class="form-control font-monospace">
                                </div>
                            </div>
                        </div>

                        <!-- Nilai SWR -->
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-activity me-1 text-danger"></i> Pengukuran SWR Channel</h6>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Road 1 TCM</label>
                                    <input type="text" name="swr_road1_tcm" id="edit_swr_road1" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Road 2 TCM</label>
                                    <input type="text" name="swr_road2_tcm" id="edit_swr_road2" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Haulrod BEK</label>
                                    <input type="text" name="swr_haulrod_bek" id="edit_swr_haulrod" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-secondary">Office LMA</label>
                                    <input type="text" name="swr_office_lma" id="edit_swr_office" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-secondary">ERT Bunyut</label>
                                    <input type="text" name="swr_ert_bunyut" id="edit_swr_bunyut" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-secondary">ERT BEK</label>
                                    <input type="text" name="swr_ert_bek" id="edit_swr_ert_bek" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small text-secondary">Bengkirai</label>
                                    <input type="text" name="swr_bengkirai" id="edit_swr_bengkirai" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Antena & Keterangan -->
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-broadcast me-1 text-success"></i> Spesifikasi Antena & Catatan</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jenis Antena</label>
                                    <select name="antena_jenis" id="edit_antena_jenis" class="form-select">
                                        <option value="">-- Pilih Jenis --</option>
                                        <option value="PANJANG">PANJANG</option>
                                        <option value="PENDEK">PENDEK</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Ukuran Antena</label>
                                    <input type="text" name="antena_ukuran" id="edit_antena_ukuran" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Keterangan Tambahan</label>
                                    <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning px-4 fw-bold">Perbarui Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL KONFIRMASI HAPUS ==================== -->
    <div class="modal fade" id="modalHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="radio_sdt.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="modal-body text-center p-4">
                        <div class="text-danger mb-3">
                            <i class="bi bi-exclamation-circle-fill display-4"></i>
                        </div>
                        <h6 class="fw-bold mb-2">Hapus Data Radio?</h6>
                        <p class="text-secondary small mb-4">
                            Apakah Anda yakin ingin menghapus data radio unit lambung <strong id="delete_lambung" class="text-dark"></strong>? Tindakan ini tidak dapat dibatalkan.
                        </p>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-light px-3" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger px-4 fw-bold">Ya, Hapus</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notifikasi Cepat Saat Ganti Status -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
        <div id="statusToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2 py-2" id="toastMessage">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <span>Status berhasil diubah</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar Mobile
        const sidebar = document.getElementById('sidebarMenu');
        const backdrop = document.getElementById('sidebarBackdrop');
        const toggleBtn = document.getElementById('sidebarToggle');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            });
        }

        // ==================== INTERACTIVE STATUS BADGE TOGGLE ====================
        const statusToastEl = document.getElementById('statusToast');
        const toastMessage  = document.getElementById('toastMessage');
        const statusToast   = new bootstrap.Toast(statusToastEl, { delay: 2200 });

        document.querySelectorAll('.status-badge-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                if (btn.classList.contains('is-updating')) return;

                const id         = btn.getAttribute('data-id');
                const field      = btn.getAttribute('data-field');
                const currentVal = btn.getAttribute('data-current');
                const icon       = btn.querySelector('i');

                btn.classList.add('is-updating');
                if (icon) icon.classList.add('spin-animation');

                try {
                    const formData = new FormData();
                    formData.append('action', 'toggle_status');
                    formData.append('id', id);
                    formData.append('field', field);

                    const response = await fetch('radio_sdt.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        const newVal = result.new_value;
                        btn.setAttribute('data-current', newVal);

                        const textSpan = btn.querySelector('.badge-text');
                        if (textSpan) textSpan.textContent = newVal;

                        // Perbarui styling badge berdasarkan field dan nilai baru
                        if (field === 'status_unit_sdt') {
                            btn.className = `status-badge-btn ${newVal === 'BD' ? 'badge-bd' : 'badge-rfu'}`;
                            btn.title = 'Klik untuk ganti: RFU ↔ BD';
                        } else if (field === 'status_radio') {
                            btn.className = `status-badge-btn ${newVal === 'TIDAK AKTIF' ? 'badge-tidak-aktif' : 'badge-aktif'}`;
                            btn.title = 'Klik untuk ganti: AKTIF ↔ TIDAK AKTIF';
                        } else if (field === 'status_pemasangan_radio') {
                            let cls = 'badge-terpasang';
                            if (newVal === 'BELUM TERPASANG') cls = 'badge-belum-terpasang';
                            else if (newVal === 'DILEPAS') cls = 'badge-dilepas';
                            btn.className = `status-badge-btn ${cls}`;
                            btn.title = 'Klik untuk ganti status pemasangan';
                        }

                        // Perbarui angka counter statistik secara real-time
                        if (result.stats) {
                            const elRfu      = document.getElementById('stat-rfu');
                            const elBd       = document.getElementById('stat-bd');
                            const elAktif    = document.getElementById('stat-aktif');
                            const elNonaktif = document.getElementById('stat-nonaktif');
                            
                            if (elRfu) elRfu.textContent = `${result.stats.rfu} RFU`;
                            if (elBd) elBd.textContent = `${result.stats.bd} BD`;
                            if (elAktif) elAktif.textContent = `${result.stats.aktif} Aktif`;
                            if (elNonaktif) {
                                elNonaktif.textContent = `${result.stats.nonaktif} Nonaktif`;
                                if (result.stats.nonaktif > 0) {
                                    elNonaktif.classList.remove('d-none');
                                } else {
                                    elNonaktif.classList.add('d-none');
                                }
                            }
                        }

                        // Perbarui cache data pada tombol Edit di baris yang sama
                        const tr = btn.closest('tr');
                        if (tr) {
                            const editBtn = tr.querySelector('.btn-edit');
                            if (editBtn) {
                                try {
                                    const rowData = JSON.parse(editBtn.getAttribute('data-data'));
                                    rowData[field] = newVal;
                                    editBtn.setAttribute('data-data', JSON.stringify(rowData));
                                } catch (err) {}
                            }
                        }

                        // Tampilkan toast notifikasi
                        let fieldName = 'Status';
                        if (field === 'status_unit_sdt') fieldName = 'Status Unit SDT';
                        else if (field === 'status_pemasangan_radio') fieldName = 'Status Pemasangan';
                        else if (field === 'status_radio') fieldName = 'Status Radio';

                        toastMessage.innerHTML = `<i class="bi bi-check-circle-fill text-success fs-5"></i> <span>Unit <strong>${result.no_lambung}</strong>: ${fieldName} diubah ke <strong>${newVal}</strong></span>`;
                        statusToast.show();
                    } else {
                        alert(result.message || 'Gagal mengubah status');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Terjadi kesalahan koneksi saat mengubah status.');
                } finally {
                    btn.classList.remove('is-updating');
                    if (icon) icon.classList.remove('spin-animation');
                }
            });
        });

        // Handle Edit Modal populate
        const modalEdit = new bootstrap.Modal(document.getElementById('modalEdit'));
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', () => {
                const data = JSON.parse(btn.getAttribute('data-data'));
                document.getElementById('edit_id').value = data.id;
                document.getElementById('edit_no_lambung').value = data.no_lambung || '';
                document.getElementById('edit_status_unit_sdt').value = data.status_unit_sdt || 'RFU';
                document.getElementById('edit_status_pasang').value = data.status_pemasangan_radio || 'TERPASANG';
                document.getElementById('edit_status_radio').value = data.status_radio || 'AKTIF';
                document.getElementById('edit_tipe_radio').value = data.tipe_radio || 'XiR3688';
                document.getElementById('edit_id_radio').value = data.id_radio || '';
                document.getElementById('edit_serial_number').value = data.serial_number_radio || '';
                
                document.getElementById('edit_swr_road1').value = data.swr_road1_tcm || '';
                document.getElementById('edit_swr_road2').value = data.swr_road2_tcm || '';
                document.getElementById('edit_swr_haulrod').value = data.swr_haulrod_bek || '';
                document.getElementById('edit_swr_office').value = data.swr_office_lma || '';
                document.getElementById('edit_swr_bunyut').value = data.swr_ert_bunyut || '';
                document.getElementById('edit_swr_ert_bek').value = data.swr_ert_bek || '';
                document.getElementById('edit_swr_bengkirai').value = data.swr_bengkirai || '';
                
                document.getElementById('edit_antena_jenis').value = data.antena_jenis || '';
                document.getElementById('edit_antena_ukuran').value = data.antena_ukuran || '';
                document.getElementById('edit_keterangan').value = data.keterangan || '';

                modalEdit.show();
            });
        });

        // Handle Delete Modal
        const modalHapus = new bootstrap.Modal(document.getElementById('modalHapus'));
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const lambung = btn.getAttribute('data-lambung');
                document.getElementById('delete_id').value = id;
                document.getElementById('delete_lambung').textContent = lambung;
                modalHapus.show();
            });
        });
    </script>
</body>
</html>
