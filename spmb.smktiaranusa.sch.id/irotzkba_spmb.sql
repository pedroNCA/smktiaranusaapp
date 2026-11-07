-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 07, 2026 at 06:24 PM
-- Server version: 10.6.28-MariaDB-cll-lve-log
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `irotzkba_spmb`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `registration_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('SKL_Ijazah','NISN','Akta_Lahir','Kartu_Keluarga') NOT NULL,
  `drive_file_id` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(50) NOT NULL,
  `size` int(11) NOT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `registration_id`, `type`, `drive_file_id`, `file_name`, `mime_type`, `size`, `status`, `notes`, `uploaded_at`) VALUES
(19, 9, 'Akta_Lahir', 'Berkas_Pendaftar/SPMB-2026-0021_pedro-jose-antonio_akta-lahir.png', 'SPMB-2026-0021_pedro-jose-antonio_akta-lahir.png', 'image/png', 126609, 'PENDING', NULL, '2026-11-06 21:28:01'),
(20, 9, 'Kartu_Keluarga', 'Berkas_Pendaftar/SPMB-2026-0021_pedro-jose-antonio_kartu-keluarga.png', 'SPMB-2026-0021_pedro-jose-antonio_kartu-keluarga.png', 'image/png', 126609, 'PENDING', NULL, '2026-11-06 21:28:06');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `category` enum('Calon Siswa','Orang Tua','Alumni','Umum') DEFAULT 'Umum',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `question`, `answer`, `category`, `created_at`, `updated_at`) VALUES
(1, 'Kapan Pendaftaran Gelombang 1 dibuka ?', 'Gelombang 1 dibuka tanggal 1 Oktober 2026', 'Umum', '2026-09-30 18:02:54', '2026-09-30 18:02:54');

-- --------------------------------------------------------

--
-- Table structure for table `majors`
--

CREATE TABLE `majors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `majors`
--

INSERT INTO `majors` (`id`, `name`, `code`, `created_at`, `updated_at`) VALUES
(1, 'Teknik Jaringan Komputer dan Telekomunikasi', 'TJKT', NULL, NULL),
(2, 'Manajemen Perkantoran dan Layanan Bisnis', 'MPLB', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `otp_verifications`
--

CREATE TABLE `otp_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `otp` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_verifications`
--

INSERT INTO `otp_verifications` (`id`, `user_id`, `otp`, `expires_at`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 1, '$2y$12$VIWUDBOu7Qktwub9i4PcHe/uuxIdH4VdHbfYxQT5nL1qLXdKZOuVm', '2026-09-28 07:59:57', 0, '2026-09-28 00:54:57', '2026-09-28 00:54:57'),
(2, 1, '$2y$12$UAqcLRmSm1V3cJN2/C1ov.EzFbAK7miNAVbg5McDOp//Y0E3AEmQO', '2026-09-28 08:11:05', 0, '2026-09-28 01:06:05', '2026-09-28 01:06:05'),
(17, 21, '$2y$12$/m97iqjbQny7xuEh8bSqxOySLyPcZiDLal4vrMdiqdbWOSXtfcTR6', '2026-11-07 04:23:35', 1, '2026-11-06 21:18:35', '2026-11-06 21:19:16');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `registration_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `proof_path` varchar(255) DEFAULT NULL,
  `status` enum('PENDING','VERIFIED','REJECTED') DEFAULT 'PENDING',
  `notes` text DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `registration_id`, `amount`, `proof_path`, `status`, `notes`, `verified_by`, `verified_at`, `created_at`, `updated_at`) VALUES
(10, 9, 0.00, 'pembayaran/bukti_Pedro_Jose_Antonio_07-11-2026_04-24-35.png', 'VERIFIED', NULL, 1, '2026-11-06 21:26:18', '2026-11-06 21:24:42', '2026-11-06 21:26:18');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `spmb_id` varchar(50) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_id` bigint(20) UNSIGNED DEFAULT NULL,
  `period_id` bigint(20) UNSIGNED NOT NULL,
  `wave` varchar(20) DEFAULT NULL,
  `entry_path` varchar(50) DEFAULT NULL,
  `major` varchar(100) DEFAULT NULL,
  `recommendation_status` varchar(50) DEFAULT NULL,
  `recommendation_name` varchar(150) DEFAULT NULL,
  `recommendation_class` varchar(50) DEFAULT NULL,
  `status` enum('DRAFT','SUBMITTED','WAITING_PAYMENT','PAYMENT_REVIEW','VERIFIED','ACCEPTED','DOCUMENT_REVIEW','REJECTED') DEFAULT 'DRAFT',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `check_formulir` tinyint(1) NOT NULL DEFAULT 0,
  `check_dp` tinyint(1) NOT NULL DEFAULT 0,
  `check_tpa` tinyint(1) NOT NULL DEFAULT 0,
  `check_observasi` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `spmb_id`, `user_id`, `student_id`, `period_id`, `wave`, `entry_path`, `major`, `recommendation_status`, `recommendation_name`, `recommendation_class`, `status`, `created_at`, `updated_at`, `payment_proof`, `check_formulir`, `check_dp`, `check_tpa`, `check_observasi`) VALUES
(9, 'SPMB-2026-0021', 21, 7, 2, NULL, 'Reguler', 'TJKT', 'Siswa Aktif', 'adwasadaw', 'XII TJKT 1', 'ACCEPTED', '2026-11-06 21:20:36', '2026-11-06 21:28:56', 'pembayaran/bukti_Pedro_Jose_Antonio_07-11-2026_04-24-35.png', 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `registration_costs`
--

CREATE TABLE `registration_costs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registration_costs`
--

INSERT INTO `registration_costs` (`id`, `name`, `amount`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Seragam', 600000.00, 'blabla', '2026-09-30 19:12:26', '2026-09-30 19:12:26');

-- --------------------------------------------------------

--
-- Table structure for table `registration_periods`
--

CREATE TABLE `registration_periods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registration_periods`
--

INSERT INTO `registration_periods` (`id`, `name`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'Gelombang 1', '2026-10-01', '2027-02-28', 1, '2026-10-03 09:42:42', '2026-10-03 09:42:42'),
(4, 'Gelombang 2', '2027-03-01', '2027-06-30', 0, '2026-10-30 23:10:08', '2026-10-30 23:10:08');

-- --------------------------------------------------------

--
-- Table structure for table `registration_requirements`
--

CREATE TABLE `registration_requirements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` enum('Umum','Reguler','Beasiswa') DEFAULT 'Umum',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration_schedules`
--

CREATE TABLE `registration_schedules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Menunggu',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registration_schedules`
--

INSERT INTO `registration_schedules` (`id`, `title`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Gelombang 1', '2026-10-01', '2027-02-28', 'Dibuka', '2026-09-30 17:32:46', '2026-09-30 17:32:46'),
(2, 'Gelombang 2', '2027-03-01', NULL, 'Menunggu', '2026-09-30 17:33:05', '2026-09-30 17:33:05');

-- --------------------------------------------------------

--
-- Table structure for table `school_contacts`
--

CREATE TABLE `school_contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `instagram` varchar(150) DEFAULT NULL,
  `facebook` varchar(150) DEFAULT NULL,
  `youtube` varchar(150) DEFAULT NULL,
  `gmaps_link` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_contacts`
--

INSERT INTO `school_contacts` (`id`, `address`, `phone`, `email`, `instagram`, `facebook`, `youtube`, `gmaps_link`, `created_at`, `updated_at`) VALUES
(1, 'Jalan Situ Jatijajar, No.04, RT 07, RW 03, Jatijajar, Tapos, KOTA DEPOK, JAWA BARAT, 16451, Indonesia', '085182565755', 'ppdbtinus@gmail.com', 'https://www.instagram.com/smktinus/', 'https://www.facebook.com/smktiaranusa', 'https://www.youtube.com/channel/UCdcZJDXl2HZynOBCqXRUKMg', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3087.1713538209438!2d106.85730167366592!3d-6.420339993570576!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69ebab4f22697f%3A0xcade1b151998ab4e!2sSMK%20Tiara%20Nusa!5e1!3m2!1sen!2sid!4v1790817523613!5m2!1sen!2sid', '2026-10-01 01:13:13', '2026-09-30 18:20:03');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('8Tt0O8cHOfc6PYVAoSV8D3xWd0xbijehtx9NwTvB', NULL, '157.15.77.122', 'Python/3.11 aiohttp/3.9.2', 'eyJfdG9rZW4iOiI3dXA4bnJnUDRqUWpPQVcwRmtDdDVIRjM3QjREdENoazhuWFNjTG5IIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9zcG1iLnNta3RpYXJhbnVzYS5zY2guaWRcL3NwbWIiLCJyb3V0ZSI6InNwbWIuYmVyYW5kYSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1794043953),
('9hNA71ugZuZThJ9E9UhvmX7iF8BmH7Ul9b7kDuYW', NULL, '157.15.77.122', 'Python/3.11 aiohttp/3.9.2', 'eyJfdG9rZW4iOiJSRkhhWVpaZlUwQ2NabjJROExURThsSzE2c1FtaXdvc1pVR1FPUXBrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9zcG1iLnNta3RpYXJhbnVzYS5zY2guaWRcL3NwbWIiLCJyb3V0ZSI6InNwbWIuYmVyYW5kYSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1794045758),
('AiZi02NTNnjqahQk8cdVqBP9vTpzqjH1GZBVRS8W', NULL, '157.15.77.122', 'Python/3.11 aiohttp/3.9.2', 'eyJfdG9rZW4iOiJZNG9CeGNnRUIxYXd6cjZDcGlyTmdzNk02Qkl1ZHVENUlKeW1VS2F1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9zcG1iLnNta3RpYXJhbnVzYS5zY2guaWRcL3NwbWIiLCJyb3V0ZSI6InNwbWIuYmVyYW5kYSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1794049358),
('H2BaXsV6IitGXc4AH9rZLiS7aFcZclcRcyNL4RCr', NULL, '157.15.77.122', 'Python/3.11 aiohttp/3.9.2', 'eyJfdG9rZW4iOiJxYTNCNDBtenRtbjdEaGVqSnVDV0ZNV21Temp4TmIwYTRWMk14djNDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9zcG1iLnNta3RpYXJhbnVzYS5zY2guaWRcL3NwbWIiLCJyb3V0ZSI6InNwbWIuYmVyYW5kYSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1794040353),
('lLsysat2bKxH5CqZFii6dwiAx04mN2ohsLAZnhfB', NULL, '157.15.77.122', 'Python/3.11 aiohttp/3.9.2', 'eyJfdG9rZW4iOiJqdWFwN1Flc0dHY2pFRVREbEJ2N0hIUEY0Nk1Icm1pcG1vV3c1c1Q2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9zcG1iLnNta3RpYXJhbnVzYS5zY2guaWRcL3NwbWIiLCJyb3V0ZSI6InNwbWIuYmVyYW5kYSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1794038553),
('tueUvpuGIWJzqoy0xr7iBDdp69XooeuyMCc1OByk', NULL, '157.15.77.122', 'Python/3.11 aiohttp/3.9.2', 'eyJfdG9rZW4iOiI4YkdtbGNjYXphNTVwaG1UUHpMZFhSN1VVZk1uNllvQlJKZmVjbENGIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9zcG1iLnNta3RpYXJhbnVzYS5zY2guaWRcL3NwbWIiLCJyb3V0ZSI6InNwbWIuYmVyYW5kYSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1794047555),
('YyZ0cgbjMeusaayeLb9ITy9lHLllEP1aSYaauiuH', NULL, '157.15.77.122', 'Python/3.11 aiohttp/3.9.2', 'eyJfdG9rZW4iOiJWb3I3SkNJVWhmMUoxaGpuYXVIOUZ2dFVuVzZ0T3l2NnBkRWxDYjVyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9zcG1iLnNta3RpYXJhbnVzYS5zY2guaWRcL3NwbWIiLCJyb3V0ZSI6InNwbWIuYmVyYW5kYSJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19', 1794042156);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `nik` varchar(255) DEFAULT NULL,
  `school_origin` varchar(150) DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('L','P') DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `special_needs` tinyint(1) DEFAULT 0,
  `child_number` int(11) DEFAULT NULL,
  `siblings_count` int(11) DEFAULT NULL,
  `birth_cert_no` varchar(100) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `distance_to_school` decimal(5,2) DEFAULT NULL,
  `time_to_school` int(11) DEFAULT NULL,
  `transportation` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `nisn`, `nik`, `school_origin`, `birth_place`, `birth_date`, `gender`, `religion`, `special_needs`, `child_number`, `siblings_count`, `birth_cert_no`, `height`, `weight`, `distance_to_school`, `time_to_school`, `transportation`, `created_at`, `updated_at`) VALUES
(7, 21, '2312123131', '1211232132131312', 'SMPN 12 Depok', 'KOTA JAKARTA SELATAN', '2026-11-07', 'L', 'Islam', 0, 3, 4, '12321312313123aw212312', 165.00, 22.00, 2.30, 42, 'Kendaraan Pribadi', '2026-11-06 21:21:43', '2026-11-06 21:23:08');

-- --------------------------------------------------------

--
-- Table structure for table `student_addresses`
--

CREATE TABLE `student_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `village` varchar(100) DEFAULT NULL,
  `rt` varchar(5) DEFAULT NULL,
  `rw` varchar(5) DEFAULT NULL,
  `subdistrict` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_addresses`
--

INSERT INTO `student_addresses` (`id`, `student_id`, `village`, `rt`, `rw`, `subdistrict`, `district`, `city`, `province`, `postal_code`, `created_at`, `updated_at`) VALUES
(6, 7, 'KAMPUNG TENGAH', '04', '23', 'KOTO BARU', 'Sidamukti', 'KOTA SUNGAI PENUH', 'JAMBI', '12312', '2026-11-06 21:21:43', '2026-11-06 21:21:43');

-- --------------------------------------------------------

--
-- Table structure for table `student_parents`
--

CREATE TABLE `student_parents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('ayah','ibu','wali') NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `birth_year` year(4) DEFAULT NULL,
  `special_needs` tinyint(1) DEFAULT 0,
  `occupation` varchar(100) DEFAULT NULL,
  `education` varchar(50) DEFAULT NULL,
  `monthly_income` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_parents`
--

INSERT INTO `student_parents` (`id`, `student_id`, `type`, `name`, `birth_year`, `special_needs`, `occupation`, `education`, `monthly_income`, `created_at`, `updated_at`) VALUES
(10, 7, 'ayah', 'pedro', '1998', 0, 'adwwadw', 'S3', '5.000.001 - 10.000.000', '2026-11-06 21:22:44', '2026-11-06 21:22:44'),
(11, 7, 'ibu', 'sefsf', '1995', 0, 'adawda', 'S1-D4', '3.500.001 - 5.000.000', '2026-11-06 21:22:44', '2026-11-06 21:22:44'),
(12, 7, 'wali', 'wesfes', '2003', 1, 'sfsesesefsd', 'SMP/Sederajat', '5.000.001 - 10.000.000', '2026-11-06 21:22:44', '2026-11-06 21:22:44');

-- --------------------------------------------------------

--
-- Table structure for table `tpa_exam_sessions`
--

CREATE TABLE `tpa_exam_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `registration_id` bigint(20) UNSIGNED NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ends_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `question_order` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`question_order`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tpa_questions`
--

CREATE TABLE `tpa_questions` (
  `id` int(11) NOT NULL,
  `mapel` enum('Matematika','Bahasa Indonesia','Bahasa Inggris') NOT NULL,
  `question` text NOT NULL,
  `option_a` text NOT NULL,
  `option_b` text NOT NULL,
  `option_c` text NOT NULL,
  `option_d` text NOT NULL,
  `option_e` text DEFAULT NULL,
  `image` text DEFAULT NULL,
  `correct_answer` enum('A','B','C','D','E') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tpa_questions`
--

INSERT INTO `tpa_questions` (`id`, `mapel`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `option_e`, `image`, `correct_answer`, `created_at`, `updated_at`) VALUES
(48, 'Bahasa Indonesia', 'Apa sinonim dari kata \'indah\'?', 'Cantik', 'Buruk', 'Kotor', 'Gelap', 'Lemah', NULL, 'A', '2026-10-30 09:55:06', '2026-10-30 09:55:06'),
(49, 'Bahasa Indonesia', 'Lawan kata dari \'rajin\' adalah ....', 'Tekun', 'Malas', 'Pintar', 'Cepat', 'Hemat', NULL, 'B', '2026-10-30 09:55:07', '2026-10-30 09:55:07'),
(50, 'Bahasa Indonesia', 'Kalimat yang diakhiri tanda tanya disebut kalimat ....', 'Perintah', 'Berita', 'Tanya', 'Seru', 'Ajakan', NULL, 'C', '2026-10-30 09:55:07', '2026-10-30 09:55:07'),
(51, 'Bahasa Indonesia', 'Manakah yang termasuk kata kerja?', 'Meja', 'Buku', 'Merah', 'Menulis', 'Sekolah', NULL, 'D', '2026-10-30 09:55:07', '2026-10-30 09:55:07'),
(52, 'Bahasa Indonesia', 'Huruf kapital digunakan pada awal ....', 'Kata sifat', 'Kata kerja', 'Kata ulang', 'Kata depan', 'Kalimat', NULL, 'E', '2026-10-30 09:55:07', '2026-10-30 09:55:07'),
(53, 'Matematika', 'Berapakah hasil dari 7 + 5?', '10', '11', '12', '13', '14', NULL, 'C', '2026-10-30 09:55:07', '2026-10-30 09:55:07'),
(54, 'Matematika', 'Berapakah hasil dari 9 × 3?', '18', '21', '24', '27', '30', NULL, 'D', '2026-10-30 09:55:07', '2026-10-30 09:55:07'),
(55, 'Matematika', 'Berapakah hasil dari 20 − 8?', '12', '13', '14', '15', '16', NULL, 'A', '2026-10-30 09:55:07', '2026-10-30 09:55:07'),
(56, 'Matematika', 'Berapakah hasil dari 36 ÷ 6?', '4', '5', '6', '7', '8', NULL, 'C', '2026-10-30 09:55:08', '2026-10-30 09:55:08'),
(57, 'Matematika', 'Berapa nilai dari 5²?', '10', '15', '20', '25', '30', NULL, 'D', '2026-10-30 09:55:08', '2026-10-30 09:55:08'),
(58, 'Bahasa Inggris', 'Apa arti kata \'apple\' dalam bahasa Indonesia?', 'Jeruk', 'Pisang', 'Mangga', 'Anggur', 'Apel', NULL, 'E', '2026-10-30 09:55:08', '2026-10-30 09:55:08'),
(59, 'Bahasa Inggris', 'Apa bahasa Inggris dari \'buku\'?', 'Book', 'Pen', 'Table', 'Chair', 'Bag', NULL, 'A', '2026-10-30 09:55:08', '2026-10-30 09:55:08'),
(60, 'Bahasa Inggris', 'What is the opposite of \'hot\'?', 'Warm', 'Cold', 'Big', 'Tall', 'Fast', NULL, 'B', '2026-10-30 09:55:08', '2026-10-30 09:55:08'),
(61, 'Bahasa Inggris', 'Complete the sentence: \'She ... a student.\'', 'Am', 'Are', 'Is', 'Be', 'Do', NULL, 'C', '2026-10-30 09:55:08', '2026-10-30 09:55:08'),
(62, 'Bahasa Inggris', 'Apa arti kata \'thank you\'?', 'Selamat pagi', 'Sampai jumpa', 'Maaf', 'Terima kasih', 'Tolong', NULL, 'D', '2026-10-30 09:55:08', '2026-10-30 09:55:08');

-- --------------------------------------------------------

--
-- Table structure for table `tpa_results`
--

CREATE TABLE `tpa_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `registration_id` bigint(20) UNSIGNED NOT NULL,
  `score_matematika` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score_b_indonesia` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score_b_inggris` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status_kelulusan_tpa` varchar(50) NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tpa_settings`
--

CREATE TABLE `tpa_settings` (
  `id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `duration_minutes` int(11) DEFAULT 60,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tpa_settings`
--

INSERT INTO `tpa_settings` (`id`, `is_active`, `duration_minutes`, `updated_at`) VALUES
(1, 0, 60, '2026-10-31 02:02:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','operator','verifikator','admin','super_admin') DEFAULT 'student',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `whatsapp`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin SPMB', 'ppdbtinus@gmail.com', '2026-09-30 07:14:14', NULL, '$2y$12$YEzJgvvbkrd88M8nzQpp4e0mXhJ/aWNycpGo.6nzdMzUQXo7.cawm', 'super_admin', NULL, '2026-09-28 04:43:35', '2026-11-02 19:09:52'),
(21, 'Pedro Jose Antonio', 'pedrokhaira.26@gmail.com', '2026-11-06 21:19:16', '085770197256', '$2y$12$pFUKIr7BU3UvLWr.z/E/ru5gHTu0dai07Q04KZEwNSCyPcZYznu8a', 'student', NULL, '2026-11-06 21:18:34', '2026-11-06 21:19:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `majors`
--
ALTER TABLE `majors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `otp_verifications_user_id_foreign` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_registration_id_foreign` (`registration_id`),
  ADD KEY `payments_verified_by_foreign` (`verified_by`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `registrations_spmb_id_unique` (`spmb_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `period_id` (`period_id`),
  ADD KEY `registrations_user_id_foreign` (`user_id`);

--
-- Indexes for table `registration_costs`
--
ALTER TABLE `registration_costs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registration_periods`
--
ALTER TABLE `registration_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registration_requirements`
--
ALTER TABLE `registration_requirements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registration_schedules`
--
ALTER TABLE `registration_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `school_contacts`
--
ALTER TABLE `school_contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `student_addresses`
--
ALTER TABLE `student_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_parents`
--
ALTER TABLE `student_parents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `tpa_exam_sessions`
--
ALTER TABLE `tpa_exam_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tpa_exam_sessions_registration_id_unique` (`registration_id`);

--
-- Indexes for table `tpa_questions`
--
ALTER TABLE `tpa_questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tpa_results`
--
ALTER TABLE `tpa_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tpa_results_registration_unique` (`registration_id`);

--
-- Indexes for table `tpa_settings`
--
ALTER TABLE `tpa_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `majors`
--
ALTER TABLE `majors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `registration_costs`
--
ALTER TABLE `registration_costs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `registration_periods`
--
ALTER TABLE `registration_periods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `registration_requirements`
--
ALTER TABLE `registration_requirements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `registration_schedules`
--
ALTER TABLE `registration_schedules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `school_contacts`
--
ALTER TABLE `school_contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `student_addresses`
--
ALTER TABLE `student_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student_parents`
--
ALTER TABLE `student_parents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tpa_exam_sessions`
--
ALTER TABLE `tpa_exam_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tpa_questions`
--
ALTER TABLE `tpa_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `tpa_results`
--
ALTER TABLE `tpa_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tpa_settings`
--
ALTER TABLE `tpa_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `otp_verifications`
--
ALTER TABLE `otp_verifications`
  ADD CONSTRAINT `otp_verifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_registration_id_foreign` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `registrations`
--
ALTER TABLE `registrations`
  ADD CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`period_id`) REFERENCES `registration_periods` (`id`),
  ADD CONSTRAINT `registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_addresses`
--
ALTER TABLE `student_addresses`
  ADD CONSTRAINT `student_addresses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_parents`
--
ALTER TABLE `student_parents`
  ADD CONSTRAINT `student_parents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tpa_exam_sessions`
--
ALTER TABLE `tpa_exam_sessions`
  ADD CONSTRAINT `tpa_exam_sessions_registration_id_foreign` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tpa_results`
--
ALTER TABLE `tpa_results`
  ADD CONSTRAINT `tpa_results_registration_fk` FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
