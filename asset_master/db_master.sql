-- ========================================================
-- Database: db_master
-- Aplikasi: Pendataan Asset Master
-- ========================================================

CREATE DATABASE IF NOT EXISTS `db_master`;
USE `db_master`;

-- --------------------------------------------------------
-- Struktur Tabel `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Data Default User (admin / password)
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `password`) 
VALUES ('admin', '$2y$10$7KfdS90X8bwm7uj9NyeO.eWx.hjgshTKiACjWIE0Jg5BetaT7C8O.')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- --------------------------------------------------------
-- Struktur Tabel `master_no_lambung` (Daftar Nomor Lambung)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `master_no_lambung` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_lambung` INT NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Isi default angka 8143 sampai 8164
INSERT IGNORE INTO `master_no_lambung` (`no_lambung`) VALUES 
(8143), (8144), (8145), (8146), (8147), (8148), (8149), (8150),
(8151), (8152), (8153), (8154), (8155), (8156), (8157), (8158),
(8159), (8160), (8161), (8162), (8163), (8164);

-- --------------------------------------------------------
-- Struktur Tabel `assets`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sdt` VARCHAR(100) NOT NULL,
    `no_lambung` INT NOT NULL,
    `merk` VARCHAR(100) DEFAULT 'Mercedez Benz',
    `tipe` VARCHAR(100) NOT NULL,
    `foto` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Struktur Tabel `radio_sdt` (Monitoring Radio & SWR Unit)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `radio_sdt` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_urut` INT NULL,
    `no_lambung` VARCHAR(50) NOT NULL,
    `status_unit_sdt` VARCHAR(50) NOT NULL DEFAULT 'RFU',
    `status_pemasangan_radio` VARCHAR(50) NOT NULL DEFAULT 'TERPASANG',
    `status_radio` VARCHAR(50) NOT NULL DEFAULT 'AKTIF',
    `tipe_radio` VARCHAR(100) NOT NULL DEFAULT 'XiR3688',
    `id_radio` VARCHAR(50) NOT NULL,
    `serial_number_radio` VARCHAR(100) NOT NULL,
    `swr_road1_tcm` VARCHAR(50) NULL DEFAULT '',
    `swr_road2_tcm` VARCHAR(50) NULL DEFAULT '',
    `swr_haulrod_bek` VARCHAR(50) NULL DEFAULT '',
    `swr_office_lma` VARCHAR(50) NULL DEFAULT '',
    `swr_ert_bunyut` VARCHAR(50) NULL DEFAULT '',
    `swr_ert_bek` VARCHAR(50) NULL DEFAULT '',
    `swr_bengkirai` VARCHAR(50) NULL DEFAULT '',
    `antena_jenis` VARCHAR(50) NULL DEFAULT '',
    `antena_ukuran` VARCHAR(50) NULL DEFAULT '',
    `keterangan` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_radio_lambung` (`no_lambung`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

