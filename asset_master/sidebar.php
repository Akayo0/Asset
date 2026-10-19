<?php
// sidebar.php - Komponen Sidebar Navigasi Kiri
$currentPage = $currentPage ?? 'beranda';
?>
<!-- Sidebar Navigasi Kiri -->
<aside id="sidebarMenu" class="sidebar d-flex flex-column flex-shrink-0 text-white p-3 shadow">
    <!-- Brand / Logo Aplikasi -->
    <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none px-2 py-1">
        <div class="bg-primary bg-gradient rounded-3 p-2 me-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
            <i class="bi bi-truck-front-fill fs-5 text-white"></i>
        </div>
        <div>
            <div class="fs-5 fw-bold lh-1">Asset Master</div>
            <small class="text-secondary" style="font-size: 0.72rem;">Sistem Pendataan</small>
        </div>
    </a>

    <hr class="border-secondary my-3 opacity-25">

    <!-- Menu Utama -->
    <div class="text-uppercase text-secondary fw-semibold px-2 mb-2" style="font-size: 0.7rem; letter-spacing: 0.8px;">
        Menu Utama
    </div>

    <ul class="nav nav-pills flex-column mb-auto gap-1">
        <!-- 1. Beranda -->
        <li class="nav-item">
            <a href="index.php" class="nav-link text-white <?= ($currentPage === 'beranda') ? 'active bg-primary' : ''; ?> d-flex align-items-center">
                <i class="bi bi-house-door-fill me-2 fs-5"></i>
                <span class="fw-medium">Beranda</span>
            </a>
        </li>

        <!-- 2. Master Data (Untuk Nambah Data) -->
        <li class="nav-item">
            <a href="input.php" class="nav-link text-white <?= ($currentPage === 'master_data') ? 'active bg-primary' : ''; ?> d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-folder-plus me-2 fs-5"></i>
                    <span class="fw-medium">Master Data</span>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.68rem;">+ Tambah</span>
            </a>
        </li>

        <!-- 3. Data Radio & SWR (Fitur Baru) -->
        <li class="nav-item">
            <a href="radio_sdt.php" class="nav-link text-white <?= ($currentPage === 'radio_sdt') ? 'active bg-primary' : ''; ?> d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-broadcast-pin me-2 fs-5"></i>
                    <span class="fw-medium">Data Radio & SWR</span>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill" style="font-size: 0.68rem;">SDT</span>
            </a>
        </li>
    </ul>

    <hr class="border-secondary my-3 opacity-25">

    <!-- Profil Pengguna & Tombol Keluar -->
    <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-secondary bg-opacity-10 border border-secondary border-opacity-25">
        <div class="d-flex align-items-center overflow-hidden me-2">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 36px; height: 36px;">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="overflow-hidden">
                <div class="fw-bold text-truncate small text-white"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                <div class="text-secondary small text-truncate" style="font-size: 0.7rem;">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="btn btn-sm btn-outline-danger" title="Logout" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?');">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</aside>
