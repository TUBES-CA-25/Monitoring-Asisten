-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 25, 2026 at 12:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.4.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `iclabs_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `izin`
--

CREATE TABLE `izin` (
  `id_izin` int(11) NOT NULL,
  `id_profil` int(11) NOT NULL,
  `tipe` enum('Izin','Sakit') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `file_bukti` varchar(255) DEFAULT NULL,
  `status_approval` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_asisten`
--

CREATE TABLE `jadwal_asisten` (
  `id_jadwal_asisten` int(11) NOT NULL,
  `id_profil` int(11) DEFAULT NULL,
  `prodi` varchar(50) DEFAULT NULL,
  `mata_kuliah` varchar(100) NOT NULL,
  `dosen` varchar(100) DEFAULT NULL,
  `kelas_lab` char(5) DEFAULT NULL,
  `frekuensi` varchar(15) DEFAULT NULL,
  `ruangan_lab` varchar(50) DEFAULT NULL,
  `hari` tinyint(4) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `model_perulangan` enum('sekali','mingguan','rentang') DEFAULT 'sekali',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_asisten`
--

INSERT INTO `jadwal_asisten` (`id_jadwal_asisten`, `id_profil`, `prodi`, `mata_kuliah`, `dosen`, `kelas_lab`, `frekuensi`, `ruangan_lab`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `start_time`, `end_time`) VALUES
(22, 3, NULL, 'Jaringan', NULL, NULL, NULL, 'Lab Terpadu', 5, '2026-01-02', '2026-02-27', 'mingguan', '07:15:00', '09:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_full`
--

CREATE TABLE `jadwal_full` (
  `id_jadwal` int(11) NOT NULL,
  `id_jadwal_kuliah` int(11) DEFAULT NULL,
  `id_jadwal_piket` int(11) DEFAULT NULL,
  `id_jadwal_asisten` int(11) DEFAULT NULL,
  `google_calendar_API` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_full`
--

INSERT INTO `jadwal_full` (`id_jadwal`, `id_jadwal_kuliah`, `id_jadwal_piket`, `id_jadwal_asisten`, `google_calendar_API`, `created_at`) VALUES
(2, 1, NULL, NULL, NULL, '2026-01-23 07:28:46'),
(3, 2, NULL, NULL, NULL, '2026-01-23 07:33:59');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_kuliah`
--

CREATE TABLE `jadwal_kuliah` (
  `id_jadwal_kuliah` int(11) NOT NULL,
  `id_profil` int(11) NOT NULL,
  `matkul` varchar(100) NOT NULL,
  `tipe` enum('Teori','Praktikum') DEFAULT 'Teori',
  `dosen` varchar(100) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `ruangan` varchar(20) DEFAULT NULL,
  `hari` tinyint(4) DEFAULT NULL COMMENT '1=Senin, 7=Minggu',
  `tanggal` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `model_perulangan` enum('sekali','mingguan','rentang') DEFAULT 'sekali',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_kuliah`
--

INSERT INTO `jadwal_kuliah` (`id_jadwal_kuliah`, `id_profil`, `matkul`, `tipe`, `dosen`, `kelas`, `ruangan`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `start_time`, `end_time`) VALUES
(1, 3, 'Pemrograman Berorientasi Objek', 'Teori', 'Lutfi Budi Ilmawan, S.Kom., M.Cs., MTA', 'A1', 'Lab Startup', 4, '2026-01-01', '2026-02-05', 'mingguan', '07:00:00', '09:30:00'),
(2, 3, 'Pemrograman Web', 'Teori', 'A Ulfah Tenripada Syahar, S.Kom.,M.Kom., MTA', 'A1', 'Lab IoT', 2, '2026-01-06', '2026-01-27', 'mingguan', '09:40:00', '00:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_lab`
--

CREATE TABLE `jadwal_lab` (
  `id_jadwal_lab` int(11) NOT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `lokasi` varchar(100) DEFAULT 'Lab Terpadu',
  `tanggal` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `hari` int(1) DEFAULT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `model_perulangan` enum('sekali','mingguan','rentang') DEFAULT 'sekali',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_lab`
--

INSERT INTO `jadwal_lab` (`id_jadwal_lab`, `nama_kegiatan`, `lokasi`, `tanggal`, `tanggal_selesai`, `hari`, `jam_mulai`, `jam_selesai`, `model_perulangan`, `created_at`) VALUES
(1, 'Test', 'Lab Terpadu', '2026-01-29', '2026-01-29', 4, '11:00:00', '12:00:00', 'sekali', '2026-01-22 15:08:01'),
(2, 'Tes Polisi', 'Lab Terpadu', '2026-01-27', '2026-01-27', 2, '07:00:00', '17:00:00', 'sekali', '2026-01-22 15:18:38');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_piket`
--

CREATE TABLE `jadwal_piket` (
  `id_jadwal_piket` int(11) NOT NULL,
  `id_profil` int(11) NOT NULL,
  `subjek` varchar(150) NOT NULL,
  `hari` tinyint(4) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `model_perulangan` enum('sekali','mingguan','rentang') DEFAULT 'sekali',
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_piket`
--

INSERT INTO `jadwal_piket` (`id_jadwal_piket`, `id_profil`, `subjek`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `jam_mulai`, `jam_selesai`) VALUES
(5, 3, 'Piket Harian', 2, '2026-01-06', '2026-02-24', 'mingguan', '07:00:00', '23:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `lab`
--

CREATE TABLE `lab` (
  `id_lab` int(11) NOT NULL,
  `nama_lab` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab`
--

INSERT INTO `lab` (`id_lab`, `nama_lab`, `deskripsi`, `lokasi`) VALUES
(1, 'Laboratorium Startup', 'Laboratorium Startup', 'Fakultas Ilmu Komputer UMI'),
(2, 'Laboratorium IoT', 'Laboratorium Internet of Things', 'Fakultas Ilmu Komputer UMI'),
(3, 'Laboratorium Computer Vision', 'Laboratorium Computer Vision', 'Fakultas Ilmu Komputer UMI'),
(4, 'Laboratorium Data Science', 'Laboratorium Data Science', 'Fakultas Ilmu Komputer UMI'),
(5, 'Laboratorium Multimedia', 'Laboratorium Multimedia', 'Fakultas Ilmu Komputer UMI'),
(6, 'Laboratorium Microcontroller', 'Laboratorium Microcontroller', 'Fakultas Ilmu Komputer UMI'),
(7, 'Laboratorium Computer Networking', 'Laboratorium Computer Networking', 'Fakultas Ilmu Komputer UMI');

-- --------------------------------------------------------

--
-- Table structure for table `logbook`
--

CREATE TABLE `logbook` (
  `id_logbook` int(11) NOT NULL,
  `id_profil` int(11) NOT NULL,
  `id_presensi` int(11) NOT NULL,
  `detail_aktivitas` text NOT NULL,
  `keterangan` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `presensi`
--

CREATE TABLE `presensi` (
  `id_presensi` int(11) NOT NULL,
  `id_profil` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_presensi` time DEFAULT NULL,
  `foto_presensi` varchar(255) DEFAULT NULL,
  `waktu_pulang` time DEFAULT NULL,
  `foto_pulang` varchar(255) DEFAULT NULL,
  `status` enum('Hadir','Alpa','Terlambat') DEFAULT 'Hadir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `presensi`
--

INSERT INTO `presensi` (`id_presensi`, `id_profil`, `tanggal`, `waktu_presensi`, `foto_presensi`, `waktu_pulang`, `foto_pulang`, `status`) VALUES
(1, 3, '2026-01-23', '16:26:10', 'att_3_1769156770.jpg', NULL, NULL, 'Hadir'),
(2, 3, '2026-01-23', '18:05:35', 'att_3_1769162735.jpg', NULL, NULL, 'Hadir'),
(3, 3, '2026-01-23', '18:07:59', 'att_3_1769162879.jpg', NULL, NULL, 'Hadir'),
(4, 3, '2026-01-24', '18:47:59', 'in_3_1769251679.jpg', '18:48:37', 'out_3_1769251717.jpg', 'Hadir');

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE `profile` (
  `id_profil` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_lab` int(11) DEFAULT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  `kelas` char(5) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `jabatan` enum('Kepala Lab','Laboran','Asisten Lab','Anggota') DEFAULT NULL,
  `peminatan` enum('RPL','Jaringan','Multimedia','AI') DEFAULT NULL,
  `photo_profile` varchar(255) DEFAULT 'default.jpg',
  `is_completed` tinyint(1) DEFAULT 0 COMMENT '0=Belum Lengkap, 1=Sudah Lengkap'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile`
--

INSERT INTO `profile` (`id_profil`, `id_user`, `id_lab`, `nim`, `nama`, `kelas`, `alamat`, `no_telp`, `jenis_kelamin`, `jabatan`, `peminatan`, `photo_profile`, `is_completed`) VALUES
(1, 1, NULL, 'SA001', 'Super Admin', NULL, NULL, NULL, 'L', 'Kepala Lab', NULL, 'default.jpg', 0),
(2, 2, 1, 'Laboran', 'Fatimah AR. Tuasamu, S.Kom., MTA, MCF', NULL, 'Jl. Urip Sumoharjo No.km.5, Panaikang, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 90231, Indonesia', '08534186497', 'P', 'Laboran', NULL, '1768721611_696c8ccb66f2c.jpeg', 0),
(3, 3, 1, '13120230033', 'Nurfajri Mukmin Saputra', NULL, 'Kabupaten Bantaeng, Provinsi Sulawesi Selatan', '0853332084', 'L', 'Asisten Lab', 'Multimedia', '1768722382_696c8fceac85d.jpeg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `qr_code`
--

CREATE TABLE `qr_code` (
  `id_qr` int(11) NOT NULL,
  `tipe` enum('Presensi','Pulang') NOT NULL,
  `token_code` varchar(255) NOT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `valid_until` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_code`
--

INSERT INTO `qr_code` (`id_qr`, `tipe`, `token_code`, `generated_at`, `valid_until`) VALUES
(1, 'Presensi', 'TOKEN-MASUK-12345', '2026-01-03 13:23:53', '2026-01-03 13:28:53'),
(2, 'Pulang', 'TOKEN-PULANG-67890', '2026-01-03 13:23:53', '2026-01-04 13:23:53'),
(3, 'Presensi', 'b3a34cf82cb7c5446c6448be81f9e1cc', '2026-01-09 18:12:38', '2026-01-09 18:17:38'),
(4, 'Pulang', '0059fa6908c1f8108a14d6cd114845ef', '2026-01-09 18:12:38', '2026-01-10 18:12:38'),
(5, 'Presensi', '07f7dae8a7281f8c357321aa1e07ac56', '2026-01-09 18:19:00', '2026-01-09 18:24:00'),
(6, 'Presensi', '97bcf271ea808319750743f9fa2f4d4c', '2026-01-09 18:59:25', '2026-01-09 19:04:25'),
(7, 'Presensi', '9a523680d2d5a0ed663cd684db5e911a', '2026-01-09 19:07:38', '2026-01-09 19:12:38'),
(8, 'Presensi', 'd520c2adfab43233a9fef24c830dd471', '2026-01-15 00:03:11', '2026-01-15 00:08:11'),
(9, 'Pulang', '6156911345590039f23333b7041e09fe', '2026-01-15 00:03:11', '2026-01-16 00:03:11'),
(10, 'Presensi', 'bfc5419a78fd6c410443464eaa95b0bd', '2026-01-15 00:14:05', '2026-01-15 00:19:05'),
(11, 'Presensi', '5b246ce0167e5bea98d3a903a88e9d88', '2026-01-15 00:28:49', '2026-01-15 00:33:49'),
(12, 'Presensi', 'a71dbfac36038e588c9c8a68fd57b815', '2026-01-15 10:26:31', '2026-01-15 10:31:31'),
(13, 'Presensi', '3e227aa8e1a73524c08ad3fb9fbadb38', '2026-01-15 10:34:38', '2026-01-15 10:39:38'),
(14, 'Presensi', '894a497c78fda58dd195698409439e43', '2026-01-15 11:19:56', '2026-01-15 11:24:56'),
(15, 'Presensi', 'ebba6d61163f7b29c5b10b78cf84ec20', '2026-01-15 11:24:58', '2026-01-15 11:29:58'),
(16, 'Presensi', 'd74f82d15382724386412763e2323390', '2026-01-15 12:08:30', '2026-01-15 12:13:30'),
(17, 'Presensi', 'b3f4b5b12046b44d975a9ccd9cd43f98', '2026-01-15 14:57:50', '2026-01-15 15:02:50'),
(18, 'Presensi', '0e10ceabbb7910cd790d55c51cdbd3fa', '2026-01-15 15:16:52', '2026-01-15 15:21:52'),
(19, 'Presensi', 'd291a50b37d3689650e1f30f8a8e0174', '2026-01-17 13:08:43', '2026-01-17 13:13:43'),
(20, 'Pulang', '3ac5aa4cdb833ef0289643a91dccdad3', '2026-01-17 13:08:43', '2026-01-18 13:08:43'),
(21, 'Presensi', '301c042d7a4c2822b68446eab1fc1566', '2026-01-17 13:14:57', '2026-01-17 13:19:57'),
(22, 'Presensi', 'e4a3a4047826e908b5934b653b70068b', '2026-01-17 14:32:06', '2026-01-17 14:37:06'),
(23, 'Presensi', 'eaa448d1e2289facd9dfb3db3c064549', '2026-01-17 14:57:41', '2026-01-17 15:02:41'),
(24, 'Presensi', '96ab9e5593c894686abe001217ad9d8f', '2026-01-17 17:06:37', '2026-01-17 17:11:37'),
(25, 'Presensi', '97fb8a691863bdd38f67f22aa30cc573', '2026-01-17 17:24:39', '2026-01-17 17:29:39'),
(26, 'Presensi', '1ad40d2016b43479262a20848f08669c', '2026-01-18 12:48:57', '2026-01-18 12:53:57'),
(27, 'Presensi', '71b2ec4ea7808a8aecd40c256778139e', '2026-01-18 14:34:45', '2026-01-18 14:39:45'),
(28, 'Pulang', '2cfa7acb7b5e60693c3b4c874dbde6bc', '2026-01-18 14:34:45', '2026-01-19 14:34:45'),
(29, 'Presensi', '9286f280a287d580c1cb9047ba5ee82b', '2026-01-18 15:00:36', '2026-01-18 15:05:36'),
(30, 'Presensi', '4e7d685c7e29a7b76c99135769326b13', '2026-01-18 15:29:49', '2026-01-18 15:34:49'),
(31, 'Presensi', 'a800058cfe0ba1309e118c5476361d1f', '2026-01-18 15:35:36', '2026-01-18 15:40:36'),
(32, 'Presensi', 'aff15b9dfe17f32ed7832378fbf987e0', '2026-01-18 15:46:52', '2026-01-18 15:51:52'),
(33, 'Presensi', '215c827544e476fca8b31e825e8bd220', '2026-01-18 16:15:59', '2026-01-18 16:20:59'),
(34, 'Presensi', '8ee35c2881f0658b912b0fd329c9bc20', '2026-01-18 16:26:22', '2026-01-18 16:31:22'),
(35, 'Presensi', '916bf195e5611429433716979b571639', '2026-01-18 16:37:18', '2026-01-18 16:42:18'),
(36, 'Presensi', 'a166541d4ee8c50001556b3b32afad52', '2026-01-18 16:54:32', '2026-01-18 16:59:32'),
(37, 'Presensi', 'd34720a1889491e78b188875067eaf53', '2026-01-18 17:01:45', '2026-01-18 17:06:45'),
(38, 'Presensi', 'b206d718bed946176b5b24231a8d5272', '2026-01-18 17:12:02', '2026-01-18 17:17:02'),
(39, 'Presensi', 'fc59b5a5435535d74c6eae8eb617b75f', '2026-01-18 17:59:41', '2026-01-18 18:04:41'),
(40, 'Presensi', '50d92628e1a23852a0d50a967a0974f4', '2026-01-18 18:52:34', '2026-01-18 18:57:34'),
(41, 'Presensi', '582981e24e5c076fe57d6b21096822d4', '2026-01-18 18:58:30', '2026-01-18 19:03:30'),
(42, 'Presensi', '1a0cd4372a5ba9e2b3165dbd1d346654', '2026-01-18 19:04:12', '2026-01-18 19:09:12'),
(43, 'Presensi', '75e5e5616b229acb04e2f79d1f007754', '2026-01-18 19:30:37', '2026-01-18 19:35:37'),
(44, 'Presensi', 'd1dbcaa0542bc1a5f3a51ebc7950de27', '2026-01-18 20:21:16', '2026-01-18 20:26:16'),
(45, 'Presensi', '0e7333a6d96e0a179de62e43ea21e046', '2026-01-18 21:10:13', '2026-01-18 21:15:13'),
(46, 'Presensi', 'd0911f4e13c99c5bac398e2a0a43f5b4', '2026-01-18 21:41:57', '2026-01-18 21:46:57'),
(47, 'Presensi', '27c14e606e1e75e5039dba93494e10f2', '2026-01-18 22:23:16', '2026-01-18 22:28:16'),
(48, 'Presensi', '17dfc8205bcafcc11d2ade199281d692', '2026-01-19 16:34:31', '2026-01-19 16:39:31'),
(49, 'Pulang', '452fd3a44a4b9908735044d59cee497b', '2026-01-19 16:34:31', '2026-01-20 16:34:31'),
(50, 'Presensi', '7cc403159248aa35b921d15bc4d69874', '2026-01-19 17:36:50', '2026-01-19 17:41:50'),
(51, 'Presensi', 'e08798f63d0ab65b571fa628924c210a', '2026-01-19 17:41:50', '2026-01-19 17:46:50'),
(52, 'Presensi', 'ccd948554dbb849155ab29f20d2edacb', '2026-01-19 17:58:36', '2026-01-19 18:03:36'),
(53, 'Presensi', 'bb9f034649679a8ee40fa88f648d96e4', '2026-01-19 19:18:53', '2026-01-19 19:23:53'),
(54, 'Presensi', '02aafe109e6eed8e9a2e17865129226f', '2026-01-19 22:10:13', '2026-01-19 22:15:13'),
(55, 'Presensi', '2ec73b6eaa87ceabb3926c8e9867aa42', '2026-01-19 23:05:58', '2026-01-19 23:10:58'),
(56, 'Presensi', '77eb3f4b1a4a9a07aa0a34b428219268', '2026-01-20 00:34:55', '2026-01-20 00:39:55'),
(57, 'Presensi', '64680facda889195dd131aa6fd221fc9', '2026-01-20 22:01:16', '2026-01-20 22:06:16'),
(58, 'Pulang', 'e31a08f6d7ba878656c5eb9256cbaf75', '2026-01-20 22:01:16', '2026-01-21 22:01:16'),
(59, 'Presensi', '825fc4bf0ada1fe503295930d479fe16', '2026-01-21 15:56:56', '2026-01-21 16:01:56'),
(60, 'Presensi', '9a2c69bcabc28713ae6e62ba14b478d2', '2026-01-21 16:38:46', '2026-01-21 16:43:46'),
(61, 'Presensi', '4da0d015d0963216141c030bd4039e80', '2026-01-21 17:28:23', '2026-01-21 17:33:23'),
(62, 'Presensi', '99732c2f9749d3e4c502c21e11475872', '2026-01-21 17:33:44', '2026-01-21 17:38:44'),
(63, 'Presensi', '3495252713d4f3f612df785b9be06db0', '2026-01-21 22:11:06', '2026-01-21 22:16:06'),
(64, 'Pulang', 'd62d29010f8b0d2d8b62412849acf580', '2026-01-21 22:11:06', '2026-01-22 22:11:06'),
(65, 'Presensi', 'cf5417baa27b1718a498ca8eea997e44', '2026-01-21 23:44:24', '2026-01-21 23:49:24'),
(66, 'Presensi', 'e05f96e146353dc81cb1f987e853031a', '2026-01-22 15:18:29', '2026-01-22 15:23:29'),
(67, 'Presensi', '7045c198bdb3ac732b6ca728aaaba4cd', '2026-01-22 17:42:30', '2026-01-22 17:47:30'),
(68, 'Presensi', '800fe72b44e28c99f95f047dc7c3d557', '2026-01-23 01:48:23', '2026-01-23 01:53:23'),
(69, 'Pulang', '73b6f8ac5acde1bf534964a29d8dc626', '2026-01-23 01:48:23', '2026-01-24 01:48:23'),
(70, 'Presensi', '87fded19f0cf069afec7450373f39e52', '2026-01-23 15:46:13', '2026-01-23 15:51:13'),
(71, 'Presensi', 'b34238756c4d1911dc87278c730056be', '2026-01-23 16:24:56', '2026-01-23 16:29:56'),
(72, 'Presensi', '4a79b94ad08a0ce72b835f668f56ea91', '2026-01-23 17:15:43', '2026-01-23 17:20:43'),
(73, 'Presensi', '83842fce35c32660c87572e3efb508a9', '2026-01-23 17:25:11', '2026-01-23 17:30:11'),
(74, 'Presensi', 'fb520e15f0a5546bbf161f60bb2cb751', '2026-01-23 17:34:17', '2026-01-23 17:39:17'),
(75, 'Presensi', 'c03119ce94c0ee5f9fc51aa3ec988df8', '2026-01-23 17:47:01', '2026-01-23 17:52:01'),
(76, 'Presensi', '89287fe1d42fe35110649c49c900333f', '2026-01-23 17:56:24', '2026-01-23 18:01:24'),
(77, 'Presensi', 'b27e135c100e4d03568f4053aebcbf0e', '2026-01-23 18:03:51', '2026-01-23 18:08:51'),
(78, 'Presensi', '2a266a2f476dc9379aee044bd6266412', '2026-01-23 18:09:32', '2026-01-23 18:14:32'),
(79, 'Presensi', 'af013a0382f85b5a7253415c7fb89a84', '2026-01-23 18:16:10', '2026-01-23 18:21:10'),
(80, 'Presensi', 'fd7485f9b4386355558d3bb09c66ec6f', '2026-01-23 23:51:14', '2026-01-23 23:52:14'),
(81, 'Presensi', 'c00af2d41d9b0e5610eb4ffe6d046bc6', '2026-01-23 23:54:14', '2026-01-23 23:55:14'),
(82, 'Presensi', '2788ef08fc8be0ac6e914a6a58862161', '2026-01-24 00:04:52', '2026-01-24 00:09:52'),
(83, 'Presensi', '5cfd1542e479b9c7f02adbdf1369c22a', '2026-01-24 15:17:21', '2026-01-24 15:22:21'),
(84, 'Pulang', 'dd3a3ad9320c2b0346d78e4fefa34d68', '2026-01-24 15:17:21', '2026-01-25 15:17:21'),
(85, 'Presensi', '32717271d4d9aa80aedb749fbb6d60ad', '2026-01-24 17:24:23', '2026-01-24 17:29:23'),
(86, 'Presensi', '378057a4d09f6627f424fbc6f401fd6a', '2026-01-24 17:58:57', '2026-01-24 18:03:57'),
(87, 'Presensi', '466d1cea9630ed63e19844d91ac64e53', '2026-01-24 18:03:57', '2026-01-24 18:08:57'),
(88, 'Presensi', 'ea48e491ea251cd952e7d77f6f6d968e', '2026-01-24 18:08:27', '2026-01-24 18:13:27'),
(89, 'Presensi', '24d062ab75f193e3c81fb38236b3fb06', '2026-01-24 18:12:57', '2026-01-24 18:17:57'),
(90, 'Presensi', 'e153cd4681c07f44f96847eb9262c503', '2026-01-24 18:17:27', '2026-01-24 18:22:27'),
(91, 'Presensi', '762b3a08d611068552a573345e8a0d52', '2026-01-24 18:22:32', '2026-01-24 18:27:32'),
(92, 'Presensi', 'bfa17b65687c59a1613f2fbb84a0b180', '2026-01-24 18:31:32', '2026-01-24 18:36:32'),
(93, 'Presensi', '4953b331328d1e8d926ef143fec7d32d', '2026-01-24 18:46:25', '2026-01-24 18:51:25'),
(94, 'Presensi', 'b653af29bc6a4755fbe477566f02e5a8', '2026-01-24 18:59:51', '2026-01-24 19:04:51'),
(95, 'Presensi', 'a68013ae7bc640703a6c2fd37318f0ba', '2026-01-24 23:17:17', '2026-01-24 23:22:17'),
(96, 'Presensi', '7c970f29b94ab95ced96f5090b34dc09', '2026-01-25 01:22:50', '2026-01-25 01:27:50'),
(97, 'Presensi', '47e3fcddb2a9617c2787303ffafc325f', '2026-01-25 19:05:26', '2026-01-25 19:10:26'),
(98, 'Pulang', 'e5953e0b5c7c67d392b1e38da02c1834', '2026-01-25 19:05:26', '2026-01-26 19:05:26');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Super Admin','Admin','User') NOT NULL DEFAULT 'User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'super@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', '2026-01-03 05:23:53'),
(2, 'admin@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '2026-01-03 05:23:53'),
(3, 'user@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-01-03 05:23:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `izin`
--
ALTER TABLE `izin`
  ADD PRIMARY KEY (`id_izin`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indexes for table `jadwal_asisten`
--
ALTER TABLE `jadwal_asisten`
  ADD PRIMARY KEY (`id_jadwal_asisten`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indexes for table `jadwal_full`
--
ALTER TABLE `jadwal_full`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `id_jadwal_kuliah` (`id_jadwal_kuliah`),
  ADD KEY `id_jadwal_piket` (`id_jadwal_piket`),
  ADD KEY `id_jadwal_asisten` (`id_jadwal_asisten`);

--
-- Indexes for table `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  ADD PRIMARY KEY (`id_jadwal_kuliah`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indexes for table `jadwal_lab`
--
ALTER TABLE `jadwal_lab`
  ADD PRIMARY KEY (`id_jadwal_lab`);

--
-- Indexes for table `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  ADD PRIMARY KEY (`id_jadwal_piket`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indexes for table `lab`
--
ALTER TABLE `lab`
  ADD PRIMARY KEY (`id_lab`);

--
-- Indexes for table `logbook`
--
ALTER TABLE `logbook`
  ADD PRIMARY KEY (`id_logbook`),
  ADD KEY `id_profil` (`id_profil`),
  ADD KEY `id_presensi` (`id_presensi`);

--
-- Indexes for table `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id_presensi`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indexes for table `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`id_profil`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_lab` (`id_lab`);

--
-- Indexes for table `qr_code`
--
ALTER TABLE `qr_code`
  ADD PRIMARY KEY (`id_qr`),
  ADD KEY `token_code` (`token_code`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `izin`
--
ALTER TABLE `izin`
  MODIFY `id_izin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jadwal_asisten`
--
ALTER TABLE `jadwal_asisten`
  MODIFY `id_jadwal_asisten` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `jadwal_full`
--
ALTER TABLE `jadwal_full`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  MODIFY `id_jadwal_kuliah` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jadwal_lab`
--
ALTER TABLE `jadwal_lab`
  MODIFY `id_jadwal_lab` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  MODIFY `id_jadwal_piket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lab`
--
ALTER TABLE `lab`
  MODIFY `id_lab` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `logbook`
--
ALTER TABLE `logbook`
  MODIFY `id_logbook` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id_presensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `profile`
--
ALTER TABLE `profile`
  MODIFY `id_profil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `qr_code`
--
ALTER TABLE `qr_code`
  MODIFY `id_qr` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `izin`
--
ALTER TABLE `izin`
  ADD CONSTRAINT `izin_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal_asisten`
--
ALTER TABLE `jadwal_asisten`
  ADD CONSTRAINT `jadwal_asisten_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal_full`
--
ALTER TABLE `jadwal_full`
  ADD CONSTRAINT `jadwal_full_ibfk_1` FOREIGN KEY (`id_jadwal_kuliah`) REFERENCES `jadwal_kuliah` (`id_jadwal_kuliah`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_full_ibfk_2` FOREIGN KEY (`id_jadwal_piket`) REFERENCES `jadwal_piket` (`id_jadwal_piket`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_full_ibfk_3` FOREIGN KEY (`id_jadwal_asisten`) REFERENCES `jadwal_asisten` (`id_jadwal_asisten`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  ADD CONSTRAINT `jadwal_kuliah_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Constraints for table `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  ADD CONSTRAINT `jadwal_piket_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Constraints for table `logbook`
--
ALTER TABLE `logbook`
  ADD CONSTRAINT `logbook_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE,
  ADD CONSTRAINT `logbook_ibfk_2` FOREIGN KEY (`id_presensi`) REFERENCES `presensi` (`id_presensi`) ON DELETE CASCADE;

--
-- Constraints for table `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Constraints for table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `profile_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `profile_ibfk_2` FOREIGN KEY (`id_lab`) REFERENCES `lab` (`id_lab`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
