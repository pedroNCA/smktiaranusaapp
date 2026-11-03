-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 25, 2026 at 11:18 PM
-- Server version: 9.1.0
-- PHP Version: 8.4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_humanis`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_bidang`
--

CREATE TABLE `tb_bidang` (
  `id_bidang` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `Bidang` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_indikator`
--

CREATE TABLE `tb_indikator` (
  `id_indikator` int NOT NULL,
  `bidang` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `indikator` text COLLATE utf8mb4_general_ci NOT NULL,
  `bobot` float DEFAULT '0',
  `target` float NOT NULL,
  `jenis_target` varchar(10) COLLATE utf8mb4_general_ci DEFAULT '=',
  `satuan` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `prioritas` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'P1',
  `frekuensi` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Semester',
  `pic` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_notifikasi`
--

CREATE TABLE `tb_notifikasi` (
  `id_notifikasi` int NOT NULL,
  `nama_penerima` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pesan` text COLLATE utf8mb4_general_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '#',
  `dibaca` int DEFAULT '0' COMMENT '0 = Belum dibaca, 1 = Sudah dibaca',
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_pelaporan`
--

CREATE TABLE `tb_pelaporan` (
  `id_pelaporan` int NOT NULL,
  `id_indikator` int NOT NULL,
  `nama_pic` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bidang` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `capaian` decimal(10,2) NOT NULL,
  `nilai_humanis` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bukti_gdrive` text COLLATE utf8mb4_general_ci,
  `refleksi_kendala` text COLLATE utf8mb4_general_ci,
  `rencana_tindak_lanjut` text COLLATE utf8mb4_general_ci,
  `status_verifikasi` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Menunggu Persetujuan',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `arahan_kepsek` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengaturan`
--

CREATE TABLE `tb_pengaturan` (
  `id_pengaturan` int NOT NULL,
  `nama_sekolah` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `tahun_ajaran` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `kepala_sekolah` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nrks` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `kompetensi_keahlian_1` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kompetensi_keahlian_2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `favicon` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tipe_sekolah` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `no_telepon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_sekolah` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `slogan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `npsn` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_mutu_global` int DEFAULT '85',
  `smtp_host` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smtp_user` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smtp_pass` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `smtp_port` int DEFAULT '587',
  `refleksi_laporan` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_pengaturan`
--

INSERT INTO `tb_pengaturan` (`id_pengaturan`, `nama_sekolah`, `tahun_ajaran`, `kepala_sekolah`, `nrks`, `alamat`, `kompetensi_keahlian_1`, `kompetensi_keahlian_2`, `logo`, `favicon`, `tipe_sekolah`, `no_telepon`, `email_sekolah`, `website`, `slogan`, `npsn`, `target_mutu_global`, `smtp_host`, `smtp_user`, `smtp_pass`, `smtp_port`, `refleksi_laporan`) VALUES
(1, 'SMK Tiara Nusa', '2026/2027', 'Mudofar, S.Pd.I, M.Pd, Gr', '21023L1060266241217519', 'Jl. Situ Jatijajar No. 04 Rt003/007 Kel.Jatijajar Tapos Depok', 'Teknik Jaringan Komputer dan Telekomunikasi', 'Manajemen Perkantoran dan Layanan Bisnis', 'logo_1787417596.png', 'favicon_1787417596.ico', 'SMK', '021-87919619', 'smktiaranusa@yahoo.com', 'smktiaranusa.sch.id', 'HUMANIS', '69769431', 95, '', 'operator', 'b3BlcmF0b3IxMjM=', 587, 'qeq2qeq2');

-- --------------------------------------------------------

--
-- Table structure for table `tb_users`
--

CREATE TABLE `tb_users` (
  `id_user` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_lengkap` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('Kepala Sekolah','Wakil Kepala','Guru/PIC','Operator') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bidang` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_users`
--

INSERT INTO `tb_users` (`id_user`, `username`, `email`, `password`, `nama_lengkap`, `role`, `bidang`, `foto`) VALUES
(84, 'mudofar', '', '$2y$12$/TybzPiDVFS2IUYQZyIi9OzMdR6bhB2cWwQLwnNuSrUI0B/5xl8cm', 'Mudofar, S.Pd.I, M.Pd, Gr', 'Kepala Sekolah', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_bidang`
--
ALTER TABLE `tb_bidang`
  ADD PRIMARY KEY (`id_bidang`),
  ADD UNIQUE KEY `Bidang` (`Bidang`),
  ADD UNIQUE KEY `Bidang_2` (`Bidang`),
  ADD UNIQUE KEY `Bidang_3` (`Bidang`),
  ADD UNIQUE KEY `Bidang_4` (`Bidang`),
  ADD UNIQUE KEY `Bidang_5` (`Bidang`),
  ADD UNIQUE KEY `Bidang_6` (`Bidang`),
  ADD UNIQUE KEY `Bidang_7` (`Bidang`),
  ADD UNIQUE KEY `Bidang_8` (`Bidang`);

--
-- Indexes for table `tb_indikator`
--
ALTER TABLE `tb_indikator`
  ADD PRIMARY KEY (`id_indikator`),
  ADD KEY `fk_indikator_bidang` (`bidang`);

--
-- Indexes for table `tb_notifikasi`
--
ALTER TABLE `tb_notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`);

--
-- Indexes for table `tb_pelaporan`
--
ALTER TABLE `tb_pelaporan`
  ADD PRIMARY KEY (`id_pelaporan`),
  ADD KEY `id_indikator` (`id_indikator`),
  ADD KEY `fk_pelaporan_user` (`nama_pic`);

--
-- Indexes for table `tb_pengaturan`
--
ALTER TABLE `tb_pengaturan`
  ADD PRIMARY KEY (`id_pengaturan`);

--
-- Indexes for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `nama_lengkap` (`nama_lengkap`),
  ADD UNIQUE KEY `nama_lengkap_2` (`nama_lengkap`),
  ADD UNIQUE KEY `nama_lengkap_3` (`nama_lengkap`),
  ADD UNIQUE KEY `nama_lengkap_4` (`nama_lengkap`),
  ADD UNIQUE KEY `nama_lengkap_5` (`nama_lengkap`),
  ADD KEY `fk_user_bidang` (`bidang`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_indikator`
--
ALTER TABLE `tb_indikator`
  MODIFY `id_indikator` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT for table `tb_notifikasi`
--
ALTER TABLE `tb_notifikasi`
  MODIFY `id_notifikasi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `tb_pelaporan`
--
ALTER TABLE `tb_pelaporan`
  MODIFY `id_pelaporan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tb_pengaturan`
--
ALTER TABLE `tb_pengaturan`
  MODIFY `id_pengaturan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_indikator`
--
ALTER TABLE `tb_indikator`
  ADD CONSTRAINT `fk_indikator_bidang` FOREIGN KEY (`bidang`) REFERENCES `tb_bidang` (`Bidang`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_pelaporan`
--
ALTER TABLE `tb_pelaporan`
  ADD CONSTRAINT `fk_pelaporan_user` FOREIGN KEY (`nama_pic`) REFERENCES `tb_users` (`nama_lengkap`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_pelaporan_ibfk_1` FOREIGN KEY (`id_indikator`) REFERENCES `tb_indikator` (`id_indikator`) ON DELETE CASCADE;

--
-- Constraints for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD CONSTRAINT `fk_user_bidang` FOREIGN KEY (`bidang`) REFERENCES `tb_bidang` (`Bidang`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
