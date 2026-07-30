-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 30, 2026 at 11:57 AM
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
-- Table structure for table `attendance_recycle_bin`
--

CREATE TABLE `attendance_recycle_bin` (
  `id_bin` int(11) NOT NULL,
  `reset_scope` enum('all','single') NOT NULL DEFAULT 'single' COMMENT '"all" = reset semua asisten, "single" = satu asisten',
  `reset_label` varchar(255) NOT NULL COMMENT 'Nama tampilan: nama asisten (single) atau "Semua Asisten" (all)',
  `id_profil` int(11) DEFAULT NULL COMMENT 'id_profil asisten (NULL jika scope=all)',
  `nama_asisten` varchar(150) DEFAULT NULL,
  `jabatan_asisten` varchar(100) DEFAULT NULL,
  `date_data_start` date DEFAULT NULL COMMENT 'Tanggal presensi paling awal yang di-reset',
  `date_data_end` date DEFAULT NULL COMMENT 'Tanggal presensi paling akhir yang di-reset',
  `date_reset` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Waktu reset dilakukan',
  `jumlah_presensi` int(11) DEFAULT 0,
  `jumlah_logbook` int(11) DEFAULT 0,
  `jumlah_izin` int(11) DEFAULT 0 COMMENT 'Jumlah record izin yang diarsipkan',
  `data_presensi` longtext DEFAULT NULL COMMENT 'JSON array seluruh baris presensi yang di-reset',
  `data_logbook` longtext DEFAULT NULL COMMENT 'JSON array seluruh baris logbook yang di-reset',
  `data_izin` longtext DEFAULT NULL COMMENT 'JSON array data izin yang turut di-reset',
  `id_admin` int(11) NOT NULL COMMENT 'id_user Admin yang melakukan reset',
  `status` enum('archived','restored','deleted') NOT NULL DEFAULT 'archived' COMMENT '"archived"=tersimpan di bin, "restored"=sudah dikembalikan, "deleted"=dihapus permanen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Recycle bin untuk presensi & logbook yang di-reset';

--
-- Dumping data for table `attendance_recycle_bin`
--

INSERT INTO `attendance_recycle_bin` (`id_bin`, `reset_scope`, `reset_label`, `id_profil`, `nama_asisten`, `jabatan_asisten`, `date_data_start`, `date_data_end`, `date_reset`, `jumlah_presensi`, `jumlah_logbook`, `jumlah_izin`, `data_presensi`, `data_logbook`, `data_izin`, `id_admin`, `status`) VALUES
(19, 'all', 'Semua Asisten', 3, 'Nurfajri Mukmin Saputra', 'Asisten 2', '2026-07-13', '2026-07-13', '2026-07-13 21:48:50', 1, 1, 0, '[{\"id_presensi\":59,\"id_profil\":3,\"tanggal\":\"2026-07-13\",\"waktu_presensi\":\"07:00:00\",\"foto_presensi\":\"admin_manual.jpg\",\"waktu_pulang\":\"19:00:00\",\"foto_pulang\":null,\"status\":\"Hadir\",\"late_minutes\":null,\"work_duration\":null,\"id_logbook\":16,\"detail_aktivitas\":\"\",\"is_verified\":1}]', '[{\"id_presensi\":59,\"id_profil\":3,\"tanggal\":\"2026-07-13\",\"waktu_presensi\":\"07:00:00\",\"foto_presensi\":\"admin_manual.jpg\",\"waktu_pulang\":\"19:00:00\",\"foto_pulang\":null,\"status\":\"Hadir\",\"late_minutes\":null,\"work_duration\":null,\"id_logbook\":16,\"detail_aktivitas\":\"\",\"is_verified\":1}]', NULL, 2, 'restored'),
(28, 'single', 'Nurfajri Mukmin Saputra', 3, 'Nurfajri Mukmin Saputra', 'Asisten 2', '2026-07-13', '2026-07-17', '2026-07-30 09:40:36', 3, 3, 0, '[{\"id_presensi\":60,\"id_profil\":3,\"tanggal\":\"2026-07-13\",\"waktu_presensi\":\"09:54:00\",\"foto_presensi\":\"admin_manual.jpg\",\"waktu_pulang\":\"21:54:00\",\"foto_pulang\":null,\"status\":\"Hadir\",\"late_minutes\":null,\"work_duration\":null,\"id_logbook\":17,\"detail_aktivitas\":\"\",\"is_verified\":1},{\"id_presensi\":63,\"id_profil\":3,\"tanggal\":\"2026-07-14\",\"waktu_presensi\":\"07:45:00\",\"foto_presensi\":\"admin_manual.jpg\",\"waktu_pulang\":\"16:45:00\",\"foto_pulang\":null,\"status\":\"Hadir\",\"late_minutes\":null,\"work_duration\":null,\"id_logbook\":20,\"detail_aktivitas\":\"\",\"is_verified\":1},{\"id_presensi\":62,\"id_profil\":3,\"tanggal\":\"2026-07-17\",\"waktu_presensi\":\"07:44:00\",\"foto_presensi\":\"admin_manual.jpg\",\"waktu_pulang\":\"16:00:00\",\"foto_pulang\":null,\"status\":\"Hadir\",\"late_minutes\":null,\"work_duration\":null,\"id_logbook\":19,\"detail_aktivitas\":\"\",\"is_verified\":1}]', '[{\"id_presensi\":60,\"id_profil\":3,\"tanggal\":\"2026-07-13\",\"waktu_presensi\":\"09:54:00\",\"foto_presensi\":\"admin_manual.jpg\",\"waktu_pulang\":\"21:54:00\",\"foto_pulang\":null,\"status\":\"Hadir\",\"late_minutes\":null,\"work_duration\":null,\"id_logbook\":17,\"detail_aktivitas\":\"\",\"is_verified\":1},{\"id_presensi\":63,\"id_profil\":3,\"tanggal\":\"2026-07-14\",\"waktu_presensi\":\"07:45:00\",\"foto_presensi\":\"admin_manual.jpg\",\"waktu_pulang\":\"16:45:00\",\"foto_pulang\":null,\"status\":\"Hadir\",\"late_minutes\":null,\"work_duration\":null,\"id_logbook\":20,\"detail_aktivitas\":\"\",\"is_verified\":1},{\"id_presensi\":62,\"id_profil\":3,\"tanggal\":\"2026-07-17\",\"waktu_presensi\":\"07:44:00\",\"foto_presensi\":\"admin_manual.jpg\",\"waktu_pulang\":\"16:00:00\",\"foto_pulang\":null,\"status\":\"Hadir\",\"late_minutes\":null,\"work_duration\":null,\"id_logbook\":19,\"detail_aktivitas\":\"\",\"is_verified\":1}]', '[]', 2, 'restored'),
(30, 'single', 'ZZ Test Deactivate Dummy', 99901, 'ZZ Test Deactivate Dummy', 'Asisten 2', NULL, NULL, '2026-07-30 09:54:49', 0, 0, 0, NULL, NULL, '[]', 2, 'deleted');

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `id_dosen` int(11) NOT NULL,
  `nidn` varchar(20) DEFAULT NULL,
  `nama_dosen` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`id_dosen`, `nidn`, `nama_dosen`, `email`, `no_hp`, `created_at`) VALUES
(44, '0919027301', 'Dr. Ir. Purnawansyah, M.Kom', 'purnawansyah@umi.ac.id', '08114190273', '2026-06-14 22:31:10'),
(45, '0922078101', 'Ir. Yulita Salim, S.Kom., M.T., MTA', 'yulita.salim@umi.ac.id', '08114111289', '2026-06-14 22:31:10'),
(46, '0114000775', 'Dr. Ir. Harlinda L, S.Kom., M.M., M.Kom., MTA', 'harlinda@umi.ac.id', '081355471144', '2026-06-14 22:31:10'),
(47, '0916108403', 'Poetri Lestari LB, S.Kom., M.T., MTA', 'poetrilestari@umi.ac.id', '081355001102', '2026-06-14 22:31:10'),
(48, '0915087302', 'Dr. Andi Sumardin, S.Ag., M.A', NULL, '081342153027', '2026-06-14 22:31:10'),
(49, '0910126901', 'Dr. Tasrif Hasanuddin, S.T., M.Cs', 'tasrif.hasanuddin@umi.ac.id', '085241519190', '2026-06-14 22:31:10'),
(50, '0428077401', 'Dr. Ir. Dolly Indra, S.Kom., M.M.SI., MTA', 'dolly.indra@umi.ac.id', '081343720253', '2026-06-14 22:31:10'),
(51, '0913038506', 'Herman, S.Kom., M.Cs., MTA', 'herman@umi.ac.id', '085242515346', '2026-06-14 22:31:10'),
(52, '0931018001', 'Ir. Abdul Rachman Manga\', S.Kom., M.T., MTA', 'abdulrachman.manga@umi.ac.id', '081355196209', '2026-06-14 22:31:10'),
(53, '0920098801', 'Ir. Huzain Azis, S.Kom., M.Cs., MTA', 'huzain.azis@umi.ac.id', '08114484875', '2026-06-14 22:31:10'),
(54, '0917068601', 'Ir. Dedy Atmajaya, S.Kom., M.Eng., MTA', 'dedy.atmajaya@umi.ac.id', '082393165687', '2026-06-14 22:31:10'),
(55, '0911098601', 'Ir. Farniwati Fattah, S.T., M.T., MTA', 'farniwati.fattah@umi.ac.id', '08981109756', '2026-06-14 22:31:10'),
(56, '0906078701', 'Mardiyyah Hasnawi, S.Kom., M.T., MTA', 'mardiyyah.hasnawi@umi.ac.id', '0895339494747', '2026-06-14 22:31:10'),
(57, '0906048205', 'Lilis Nur Hayati, S.Kom., M.Eng., MTA', 'lilis.nurhayati@umi.ac.id', '0895323999757', '2026-06-14 22:31:10'),
(58, '0922088701', 'Siska Anraeni, S.Kom., M.T., MCF.', 'siska.anraeni@umi.ac.id', '0811446400', '2026-06-14 22:31:10'),
(59, '0919056501', 'Dr. Ramdan Satra, S.Kom., M.Kom., MTA', 'ramdan@umi.ac.id', '085255680963', '2026-06-14 22:31:10'),
(60, '0920107601', 'Muh. Aliyazid Mude, S.Kom., M.Kom.', 'aliyazid.mude@umi.ac.id', '085244802842', '2026-06-14 22:31:10'),
(61, '0915028503', 'Irawati, S.Kom., M.T., MTA', 'irawan2801@gmail.com', '085255372151', '2026-06-14 22:31:10'),
(62, '0919018501', 'Ir. St. Hajrah Mansyur, S.Kom., M.Cs., MTA', 'hajrah.mansyur@umi.ac.id', '082187200036', '2026-06-14 22:31:10'),
(63, '0926048704', 'Syahrul Mubarak, S.Kom., M.Kom., MTA', 'syahrul.mubarak@umi.ac.id', '085242750931', '2026-06-14 22:31:10'),
(64, '0915068601', 'Ir. Nia Kurniati, M.Kom., MTA', 'nia.kurniati@umi.ac.id', '085242850385', '2026-06-14 22:31:10'),
(65, '0924048501', 'Sugiarti, S.Kom., M.Kom., MTA', 'sugiarti.sugiarti@umi.ac.id', '085298565844', '2026-06-14 22:31:10'),
(66, '0906128504', 'Ir. Erick Irawadi Alwi, S.Kom., M.Eng., MTA', 'erick.alwi@umi.ac.id', '081341588887', '2026-06-14 22:31:10'),
(67, '0921018902', 'Lutfi Budi Ilmawan, S.Kom., M.Cs., MTA', 'lutfibudi.ilmawan@umi.ac.id', '082333888571', '2026-06-14 22:31:10'),
(68, '0924069001', 'Herdianti, S.Si., M.Eng., MTA', 'herdianti.darwis@umi.ac.id', '081355801732', '2026-06-14 22:31:10'),
(69, '0922078801', 'Fitriyani Umar, S.Si., M.Eng., MTA', 'fitryani.umar@umi.ac.id', '085243853522', '2026-06-14 22:31:10'),
(70, '0922118003', 'Ir. Lukman Syafie, S.Si., M.Si., MTA', 'lukman.syafie@umi.ac.id', '085242809809', '2026-06-14 22:31:10'),
(71, '0907018602', 'Wistiani Astuti, S.Kom., M.T., MTA', 'wistiani.astuti@umi.ac.id', '085255837113', '2026-06-14 22:31:10'),
(72, '0908089202', 'A Ulfa Tenripada Syahar, S.Kom., M.Kom., MTA', 'a.ulfah@umi.ac.id', '082246813008', '2026-06-14 22:31:10'),
(73, '2107057202', 'Ihwana Asad S.Ag., M.Sc. P.hD., MTA', 'ihwana.asad@umi.ac.id', '081264187451', '2026-06-14 22:31:10'),
(74, '0908099401', 'Andi Widya Mufila Gaffar, S.T., M.Kom., MTA', 'widya.mufila@umi.ac.id', '081340386432', '2026-06-14 22:31:10'),
(75, '0911039301', 'Ramdaniah, S.Kom., M.T., MTA', 'ramdaniah@umi.ac.id', '085341666232', '2026-06-14 22:31:10'),
(76, '0909029203', 'Muhammad Arfah Asis, S.Kom., M.T., MTA', 'muh.arfah.asis@umi.ac.id', '082192777092', '2026-06-14 22:31:10'),
(77, '0924049303', 'Amaliah Faradibah, S.Kom., M.Kom., MTA., MCF', 'amaliah.faradibah@umi.ac.id', '085342466535', '2026-06-14 22:31:10'),
(78, '0918109501', 'Sitti Rahmah Jabir, S.M., M.Sc., MTA., MCF', 'rahmahjabir@umi.ac.id', '081214614662', '2026-06-14 22:31:10'),
(79, '0901019302', 'Dewi Widyawati, S.Kom., M.Kom., MTA., MCF', 'dewiwidyawati@umi.ac.id', '085338545458', '2026-06-14 22:31:10'),
(80, '0922067801', 'Hadriana Iddas, S.T., M.T., Ph.D', 'hadriana.iddas@umi.ac.id', '+818027786706', '2026-06-14 22:31:10'),
(81, NULL, 'Syariful Mujaddid, S.Kom., M.T', NULL, '081243166371', '2026-06-14 22:31:10'),
(82, NULL, 'Fahmi, S.Kom., M.T', NULL, '081242370123', '2026-06-14 22:31:10'),
(83, NULL, 'Fadly Kasim, S.T., M.Kom', NULL, '082191101213', '2026-06-14 22:31:10'),
(84, NULL, 'Rabiatul Adawiyah, S.Si., M.Si', NULL, '0895806750326', '2026-06-14 22:31:10'),
(85, NULL, 'Suwito Pomalingo, S.Kom., M.Kom., MTA', NULL, '08112651713', '2026-06-14 22:31:10'),
(86, NULL, 'Muhammad Nasry Ashar, S.Kom., M.Kom', NULL, '082348742304', '2026-06-14 22:31:10');

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
  `id_dosen` int(11) DEFAULT NULL,
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

INSERT INTO `jadwal_asisten` (`id_jadwal_asisten`, `id_profil`, `prodi`, `mata_kuliah`, `dosen`, `id_dosen`, `kelas_lab`, `frekuensi`, `ruangan_lab`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `start_time`, `end_time`) VALUES
(22, 3, NULL, 'Jaringan', NULL, NULL, NULL, NULL, 'Lab Terpadu', 5, '2026-01-02', '2026-02-27', 'mingguan', '07:15:00', '09:30:00'),
(23, 3, NULL, 'Analisis Visualisasi Data', 'Suwito Pomalingo, S.Kom., M.Kom., MTA', 85, 'A1', NULL, 'Laboratorium Computer Vision', 2, '2026-06-16', '2026-06-16', 'sekali', '07:30:00', '10:00:00'),
(24, 46, NULL, 'Pemrograman Web', 'Irawati, S.Kom., M.T., MTA', 61, 'B2', NULL, 'Laboratorium Data Science', 2, '2026-06-16', '2026-06-16', 'sekali', '15:40:00', '18:10:00'),
(25, 51, NULL, 'UI/UX', 'Dewi Widyawati, S.Kom., M.Kom., MTA., MCF', 79, 'A1', NULL, 'Laboratorium Multimedia', 1, '2026-06-22', '2026-06-22', 'sekali', '09:40:00', '12:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_full`
--

CREATE TABLE `jadwal_full` (
  `id_jadwal` int(11) NOT NULL,
  `id_jadwal_lab` int(11) DEFAULT NULL,
  `id_jadwal_kuliah` int(11) DEFAULT NULL,
  `id_jadwal_piket` int(11) DEFAULT NULL,
  `id_jadwal_asisten` int(11) DEFAULT NULL,
  `google_event_id` varchar(255) DEFAULT NULL,
  `sync_status` enum('synced','failed','skipped') NOT NULL DEFAULT 'skipped',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_full`
--

INSERT INTO `jadwal_full` (`id_jadwal`, `id_jadwal_lab`, `id_jadwal_kuliah`, `id_jadwal_piket`, `id_jadwal_asisten`, `google_event_id`, `sync_status`, `created_at`) VALUES
(6, NULL, NULL, NULL, 23, NULL, 'skipped', '2026-06-14 22:41:45'),
(7, NULL, NULL, NULL, 24, NULL, 'skipped', '2026-06-15 03:27:54'),
(9, NULL, NULL, 6, NULL, NULL, 'skipped', '2026-06-15 04:09:15'),
(10, NULL, 21, NULL, NULL, NULL, 'skipped', '2026-06-15 04:13:16'),
(11, 4, NULL, NULL, NULL, NULL, 'skipped', '2026-06-15 04:40:44'),
(12, NULL, NULL, NULL, 25, NULL, 'skipped', '2026-06-15 04:47:19');

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
  `id_dosen` int(11) DEFAULT NULL,
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

INSERT INTO `jadwal_kuliah` (`id_jadwal_kuliah`, `id_profil`, `matkul`, `tipe`, `dosen`, `id_dosen`, `kelas`, `ruangan`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `start_time`, `end_time`) VALUES
(21, 3, 'Pemrograman Mobile', 'Teori', 'Muhammad Arfah Asis, S.Kom., M.T., MTA', 76, 'A1', 'Laboratorium Compute', 2, '2026-06-16', '2026-06-16', 'sekali', '07:00:00', '09:40:00');

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
(4, 'Jalan Sehat HUT UMI', 'Kampus II UMI', '2026-06-16', '2026-06-16', 2, '07:00:00', '10:00:00', 'sekali', '2026-06-15 04:40:44');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_lab_sync`
--

CREATE TABLE `jadwal_lab_sync` (
  `id_sync` int(11) NOT NULL,
  `id_jadwal_lab` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `google_event_id` varchar(255) DEFAULT NULL,
  `sync_status` enum('synced','failed','skipped') NOT NULL DEFAULT 'skipped'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_lab_sync`
--

INSERT INTO `jadwal_lab_sync` (`id_sync`, `id_jadwal_lab`, `id_user`, `google_event_id`, `sync_status`) VALUES
(28, 4, 3, NULL, 'skipped'),
(29, 4, 55, NULL, 'skipped'),
(30, 4, 56, NULL, 'skipped'),
(31, 4, 57, NULL, 'skipped'),
(32, 4, 58, NULL, 'skipped'),
(33, 4, 59, NULL, 'skipped'),
(34, 4, 60, NULL, 'skipped'),
(35, 4, 61, NULL, 'skipped'),
(36, 4, 62, NULL, 'skipped'),
(37, 4, 63, NULL, 'skipped'),
(38, 4, 64, NULL, 'skipped'),
(39, 4, 65, NULL, 'skipped'),
(40, 4, 66, NULL, 'skipped'),
(41, 4, 67, NULL, 'skipped'),
(42, 4, 68, NULL, 'skipped'),
(43, 4, 69, NULL, 'skipped'),
(44, 4, 70, NULL, 'skipped'),
(45, 4, 71, NULL, 'skipped'),
(46, 4, 72, NULL, 'skipped'),
(47, 4, 73, NULL, 'skipped'),
(48, 4, 74, NULL, 'skipped'),
(49, 4, 75, NULL, 'skipped'),
(50, 4, 76, NULL, 'skipped'),
(51, 4, 77, NULL, 'skipped'),
(52, 4, 78, NULL, 'skipped'),
(53, 4, 79, NULL, 'skipped'),
(54, 4, 80, NULL, 'skipped');

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
(6, 38, 'Monitoring Laboratorium', 2, '2026-06-16', '2026-06-16', 'sekali', '07:00:00', '18:20:00');

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
(7, 'Laboratorium Computer Networking', 'Laboratorium Computer Networking', 'Fakultas Ilmu Komputer UMI'),
(8, 'Laboratorium Terpadu Fakultas Ilmu Komputer Universitas Muslim Indonesia', 'Lokasi umum/terpadu untuk kegiatan piket', 'Fakultas Ilmu Komputer UMI');

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

--
-- Dumping data for table `logbook`
--

INSERT INTO `logbook` (`id_logbook`, `id_profil`, `id_presensi`, `detail_aktivitas`, `keterangan`, `is_verified`) VALUES
(18, 46, 61, '', NULL, 1),
(22, 45, 68, '', NULL, 1),
(23, 45, 69, '', NULL, 1),
(24, 3, 70, '', NULL, 1),
(25, 3, 71, '', NULL, 1),
(26, 3, 72, '', NULL, 1);

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
  `status` enum('Hadir','Alpa','Terlambat','Izin') DEFAULT 'Hadir',
  `late_minutes` int(11) DEFAULT NULL,
  `work_duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `presensi`
--

INSERT INTO `presensi` (`id_presensi`, `id_profil`, `tanggal`, `waktu_presensi`, `foto_presensi`, `waktu_pulang`, `foto_pulang`, `status`, `late_minutes`, `work_duration`) VALUES
(61, 46, '2026-07-13', '11:51:00', 'admin_manual.jpg', '23:51:00', NULL, 'Hadir', NULL, NULL),
(68, 45, '2026-07-29', '08:46:00', 'admin_manual.jpg', '14:48:00', NULL, 'Hadir', NULL, NULL),
(69, 45, '2026-07-28', '14:48:00', 'admin_manual.jpg', '20:52:00', NULL, 'Hadir', NULL, NULL),
(70, 3, '2026-07-13', '07:00:00', 'admin_manual.jpg', '19:00:00', NULL, 'Hadir', 0, NULL),
(71, 3, '2026-07-14', '07:45:00', 'admin_manual.jpg', '16:45:00', NULL, 'Hadir', 0, NULL),
(72, 3, '2026-07-17', '07:44:00', 'admin_manual.jpg', '16:00:00', NULL, 'Hadir', 0, NULL);

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
  `angkatan` varchar(4) DEFAULT NULL,
  `prodi` varchar(255) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `jabatan` enum('Kepala Lab','Laboran','Koordinator Asisten','Asisten 1','Asisten 2','Asisten Pendamping') DEFAULT NULL,
  `peminatan` varchar(255) DEFAULT NULL,
  `photo_profile` varchar(255) DEFAULT 'default.jpg',
  `is_completed` tinyint(1) DEFAULT 0 COMMENT '0=Belum Lengkap, 1=Sudah Lengkap',
  `attendance_reset_at` datetime DEFAULT NULL COMMENT 'Waktu terakhir data presensi di-reset. calculateRealAlpha mulai dari tanggal ini, bukan dari created_at.',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile`
--

INSERT INTO `profile` (`id_profil`, `id_user`, `id_lab`, `nim`, `nama`, `kelas`, `angkatan`, `prodi`, `alamat`, `no_telp`, `jenis_kelamin`, `jabatan`, `peminatan`, `photo_profile`, `is_completed`, `attendance_reset_at`, `created_at`) VALUES
(1, 1, NULL, NULL, ' Ir. Huzain Azis, S.Kom., M.Cs., MTA.', NULL, NULL, NULL, 'Jl. Urip Sumoharjo No.km.5, Panaikang, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 90231, Indonesia', '08114484875', 'L', 'Kepala Lab', NULL, '1769533666_6978f0e27cab8.jpeg', 1, NULL, '2026-01-03 13:23:53'),
(2, 2, NULL, NULL, 'Fatimah AR. Tuasamu, S.Kom., MTA, MCF', NULL, NULL, '', 'Jl. Urip Sumoharjo No.km.5, Panaikang, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 90231, Indonesia', '08534186497', 'P', 'Laboran', NULL, '1768721611_696c8ccb66f2c.jpeg', 1, NULL, '2026-01-03 13:23:53'),
(3, 3, 1, '13120230033', 'Nurfajri Mukmin Saputra', 'A1', NULL, 'Sistem Informasi', 'Kabupaten Bantaeng, Provinsi Sulawesi Selatan', '0853332084', 'L', 'Asisten 2', 'Multimedia', '1768722382_696c8fceac85d.jpeg', 1, NULL, '2026-01-03 13:23:53'),
(31, 55, NULL, '13020240021', 'M Rivaldi Juliadin', NULL, NULL, NULL, 'Jln. Pampang 5', '081282575933', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(32, 56, NULL, '13020240331', 'Ghiffary Agys Al Baihaqy', NULL, NULL, NULL, 'Jl. Bung Perumahan Pesona Bukit Maghfirah F/9', '082346950561', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(33, 57, NULL, '13020240012', 'Saefullah Ahmad Ariiq. Sr', NULL, NULL, NULL, 'Jln. Daya raya Perum Graha Cendekia blok C No.20', '088704259516', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(34, 58, NULL, '13020240333', 'Muh. Fahmi Ashar', NULL, NULL, NULL, 'jl. Yusuf Bauty, Manggarupi Kab. Gowa', '088242980774', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(35, 59, NULL, '13020240014', 'Rayhan Firrizqi', NULL, NULL, NULL, 'Jl. Sukaria Raya, lorong 8', '082397851510', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(36, 60, NULL, '13020240060', 'Muhammad Nabil Bassalam', NULL, NULL, NULL, 'Telkomas', '085953707203', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(37, 61, NULL, '13120240038', 'Karima', NULL, NULL, NULL, 'Kec. Moncongloe', '081944264168', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(38, 62, NULL, '13020240263', 'Arya Bintang Kusuma Wijaya', NULL, NULL, NULL, 'Paccerakkang', '081245588197', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(39, 63, NULL, '13020240028', 'Nurul Qamri Ramadhina', NULL, NULL, NULL, 'Jl. Timah 3 Blok A27 No12 Ballaparang, Rappocini', '085754534342', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(40, 64, NULL, '13020240009', 'Kharisma Suchy Aisyah', NULL, NULL, NULL, 'Jl. Swadaya No.6, Masale, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 90231', '082199153095', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(41, 65, NULL, '13020240041', 'NAJIYAH N. NGABITO', NULL, NULL, NULL, 'Jalan Pampang V No. 5, Pampang, Panakkukang, Makassar', '085342009360', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(42, 66, NULL, '13020240184', 'Rendi Pratama', NULL, NULL, NULL, 'Cozy Living, Jl. Perintis Kemerdekaan 8 Lrg. 3, Kec. Tamalanrea, Kota Makassar, Sulawesi Selatan, Kamar', '081241456546', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(43, 67, NULL, '13020240206', 'MEKAR WANGI. R', 'B4', NULL, NULL, 'Bumi Antang Permai', '081289026799', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(44, 68, NULL, '13020240048', 'Rahmawati', 'B1', NULL, NULL, 'Maros Tanralili', '082140700737', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(45, 69, NULL, '13020230224', 'Andi Ahsan Ashuri', 'A7', NULL, NULL, 'Pettarani, Makassar', '085657376669', 'L', 'Asisten 2', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(46, 70, NULL, '13020230251', 'Andi Ikhlas Mallomo', 'A7', NULL, NULL, 'Jl. Printis Kemerdekaan 3', '0882019450791', 'L', 'Asisten 2', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(47, 71, NULL, '13020240237', 'Ahmad Fadyl Sapri', 'A6', NULL, NULL, 'Jl. Inspeksi PAM timur', '082393172077', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(48, 72, NULL, '13020230219', 'Andi Rifqi Aunur Rahman', 'A7', NULL, NULL, 'Perumnas BTP Blok H.lama No.509, Tamalanrea, Kec. Tamalanrea, Kota Makassar, Sulawesi Selatan 90245', '088246700573', 'L', 'Asisten 2', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(49, 73, NULL, '13020240203', 'Muhammad Sa\'Ad Wahid', 'A6', '2024', 'Teknik Informatika', 'Jl. Urip Sumoharjo no.86', '082288873939', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(50, 74, NULL, '13020240338', 'Fadia Syakinah Amalia', 'B1', NULL, NULL, 'Jl. Racing Sinri Jala No.27b, Karampuang, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 90231', '082298470695', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(51, 75, NULL, '13020220323', 'Dewi Ernita Rahma', 'B5', NULL, NULL, 'Jl. Kakatua II, Lr 3 No. 29 D, RT 007/RW 004, Kel. Parang, Kec. Mamajang, Kota Makassar, Sulawesi Selatan 90133', '085216090040', 'P', 'Asisten 1', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(52, 76, NULL, '13020240152', 'Tiara Mulya Pratiwi', 'B3', NULL, NULL, 'Jl. Moh. Jufri X, Tammua, Kec. Tallo, Kota Makassar, Sulawesi Selatan.', '082193739474', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(53, 77, NULL, '13020240004', 'Nurul Aulia Badawi', 'B1-Ti', NULL, NULL, 'Perumahan Bumi Findaria Mas 2 Blok K No. 118 Jl. Poros Pammajengan Moncongloe, Kec. Moncongloe, Kab. Maros, Sulawesi Selatan', '082136293919', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(54, 78, NULL, '13020240161', 'Nur Alisa', 'B3', NULL, NULL, 'Maros, jl.Damai Ongkoe', '0882022416033', 'P', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(55, 79, NULL, '13020240305', 'Rahmat Setiawan Rahman', 'A4', NULL, NULL, 'Jalan Baji Ampe 1, Kecamatan Mamajang, Kota Makassar', '089637457854', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42'),
(56, 80, NULL, '13020240036', 'Nendra Rizkullah Izzatul Ibad', 'A1', NULL, NULL, 'Makassar, Makassar City, South Sulawesi, Indonesia', '081244960404', 'L', 'Asisten Pendamping', NULL, 'default.jpg', 0, NULL, '2026-06-15 06:32:42');

-- --------------------------------------------------------

--
-- Table structure for table `qr_code`
--

CREATE TABLE `qr_code` (
  `id_qr` int(11) NOT NULL,
  `tipe` enum('Presensi','Pulang') NOT NULL,
  `token_code` varchar(255) NOT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `valid_until` datetime NOT NULL,
  `used_by_user_id` int(11) DEFAULT NULL COMMENT 'id_user yang pertama kali meng-scan token ini. NULL = belum terpakai.',
  `used_at` datetime DEFAULT NULL COMMENT 'Waktu pertama kali token di-scan.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_code`
--

INSERT INTO `qr_code` (`id_qr`, `tipe`, `token_code`, `generated_at`, `valid_until`, `used_by_user_id`, `used_at`) VALUES
(653, 'Presensi', 'a00db844f98e19e5943d5b012bd2e3598c6cd9733b311273', '2026-07-30 17:49:40', '2026-07-30 17:52:40', NULL, NULL),
(654, 'Pulang', 'e48b4ea267fc2893e5068056206ff7c5a136deb4208ea1d5', '2026-07-30 17:49:40', '2026-07-30 17:52:40', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recycle_bin_conflicts`
--

CREATE TABLE `recycle_bin_conflicts` (
  `id_conflict` int(11) NOT NULL,
  `id_bin` int(11) NOT NULL,
  `id_profil` int(11) NOT NULL,
  `nama_asisten` varchar(150) DEFAULT NULL,
  `tanggal` date NOT NULL COMMENT 'Tanggal yang bermasalah (sudah ada data baru di presensi)',
  `conflict_type` varchar(50) DEFAULT 'presensi_overlap' COMMENT 'Jenis konflik: presensi_overlap, logbook_duplicate',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recycle_bin_conflicts`
--

INSERT INTO `recycle_bin_conflicts` (`id_conflict`, `id_bin`, `id_profil`, `nama_asisten`, `tanggal`, `conflict_type`, `created_at`) VALUES
(1, 28, 3, 'Nurfajri Mukmin Saputra', '2026-07-13', 'presensi_overlap', '2026-07-30 11:22:06');

-- --------------------------------------------------------

--
-- Table structure for table `reset_log`
--

CREATE TABLE `reset_log` (
  `id_reset` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL COMMENT 'id_user Admin yang melakukan reset',
  `scope` enum('all','single') NOT NULL DEFAULT 'all',
  `id_profil` int(11) DEFAULT NULL COMMENT 'terisi jika scope=single',
  `nama_asisten` varchar(150) DEFAULT NULL,
  `jumlah_presensi` int(11) DEFAULT 0 COMMENT 'baris presensi yang dihapus',
  `jumlah_logbook` int(11) DEFAULT 0 COMMENT 'baris logbook yang dihapus',
  `zip_filename` varchar(255) DEFAULT NULL COMMENT 'nama file ZIP yang diunduh',
  `reset_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','User','Kepala Lab') NOT NULL DEFAULT 'User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_account` varchar(20) DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id_user`, `email`, `password`, `role`, `created_at`, `status_account`) VALUES
(1, 'super@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Lab', '2026-01-03 05:23:53', 'ACTIVE'),
(2, 'admin@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '2026-01-03 05:23:53', 'ACTIVE'),
(3, 'user@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-01-03 05:23:53', 'ACTIVE'),
(55, '13020240021@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(56, '13020240331@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(57, 'ariiqsaefullah@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(58, '13020240333@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(59, '13020240014@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(60, '13020240060@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(61, 'karima51rima@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(62, '13020240263@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(63, '13020240028@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(64, '13020240009@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(65, '13020240041@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(66, '13020240184@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(67, '13020240206@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(68, '13020240048@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(69, '13020230224@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(70, '13020230251@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(71, '13020240237@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(72, '13020230219@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(73, '13020240203@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(74, '13020240338@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(75, 'dewiernitarahma.iclabs@umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(76, '13020240152@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(77, '13020240004@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(78, '13020240161@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(79, '13020240305@student.umi.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE'),
(80, 'nendrarizkullah@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-06-14 22:32:42', 'ACTIVE');

-- --------------------------------------------------------

--
-- Table structure for table `user_google_token`
--

CREATE TABLE `user_google_token` (
  `id_token` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `access_token` text NOT NULL,
  `refresh_token` text NOT NULL,
  `expires_in` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_recycle_bin`
--
ALTER TABLE `attendance_recycle_bin`
  ADD PRIMARY KEY (`id_bin`),
  ADD KEY `idx_bin_profil` (`id_profil`),
  ADD KEY `idx_bin_scope` (`reset_scope`),
  ADD KEY `idx_bin_status` (`status`),
  ADD KEY `idx_bin_date` (`date_reset`);

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id_dosen`),
  ADD UNIQUE KEY `nidn` (`nidn`);

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
  ADD KEY `id_profil` (`id_profil`),
  ADD KEY `id_dosen` (`id_dosen`);

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
  ADD KEY `id_profil` (`id_profil`),
  ADD KEY `id_dosen` (`id_dosen`);

--
-- Indexes for table `jadwal_lab`
--
ALTER TABLE `jadwal_lab`
  ADD PRIMARY KEY (`id_jadwal_lab`);

--
-- Indexes for table `jadwal_lab_sync`
--
ALTER TABLE `jadwal_lab_sync`
  ADD PRIMARY KEY (`id_sync`),
  ADD UNIQUE KEY `uniq_jadwal_user` (`id_jadwal_lab`,`id_user`);

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
-- Indexes for table `recycle_bin_conflicts`
--
ALTER TABLE `recycle_bin_conflicts`
  ADD PRIMARY KEY (`id_conflict`),
  ADD KEY `id_bin` (`id_bin`);

--
-- Indexes for table `reset_log`
--
ALTER TABLE `reset_log`
  ADD PRIMARY KEY (`id_reset`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_google_token`
--
ALTER TABLE `user_google_token`
  ADD PRIMARY KEY (`id_token`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_recycle_bin`
--
ALTER TABLE `attendance_recycle_bin`
  MODIFY `id_bin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id_dosen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `izin`
--
ALTER TABLE `izin`
  MODIFY `id_izin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `jadwal_asisten`
--
ALTER TABLE `jadwal_asisten`
  MODIFY `id_jadwal_asisten` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `jadwal_full`
--
ALTER TABLE `jadwal_full`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  MODIFY `id_jadwal_kuliah` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `jadwal_lab`
--
ALTER TABLE `jadwal_lab`
  MODIFY `id_jadwal_lab` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `jadwal_lab_sync`
--
ALTER TABLE `jadwal_lab_sync`
  MODIFY `id_sync` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  MODIFY `id_jadwal_piket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lab`
--
ALTER TABLE `lab`
  MODIFY `id_lab` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `logbook`
--
ALTER TABLE `logbook`
  MODIFY `id_logbook` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id_presensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `profile`
--
ALTER TABLE `profile`
  MODIFY `id_profil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99902;

--
-- AUTO_INCREMENT for table `qr_code`
--
ALTER TABLE `qr_code`
  MODIFY `id_qr` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=655;

--
-- AUTO_INCREMENT for table `recycle_bin_conflicts`
--
ALTER TABLE `recycle_bin_conflicts`
  MODIFY `id_conflict` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reset_log`
--
ALTER TABLE `reset_log`
  MODIFY `id_reset` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99902;

--
-- AUTO_INCREMENT for table `user_google_token`
--
ALTER TABLE `user_google_token`
  MODIFY `id_token` int(11) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `fk_jadwal_asisten_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE SET NULL,
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
  ADD CONSTRAINT `fk_jadwal_kuliah_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE SET NULL,
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

--
-- Constraints for table `recycle_bin_conflicts`
--
ALTER TABLE `recycle_bin_conflicts`
  ADD CONSTRAINT `recycle_bin_conflicts_ibfk_1` FOREIGN KEY (`id_bin`) REFERENCES `attendance_recycle_bin` (`id_bin`) ON DELETE CASCADE;

--
-- Constraints for table `reset_log`
--
ALTER TABLE `reset_log`
  ADD CONSTRAINT `reset_log_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `user` (`id_user`);

--
-- Constraints for table `user_google_token`
--
ALTER TABLE `user_google_token`
  ADD CONSTRAINT `user_google_token_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
