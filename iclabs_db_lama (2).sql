-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 01 Feb 2026 pada 21.52
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

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
-- Struktur dari tabel `izin`
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

--
-- Dumping data untuk tabel `izin`
--

INSERT INTO `izin` (`id_izin`, `id_profil`, `tipe`, `start_date`, `end_date`, `deskripsi`, `file_bukti`, `status_approval`) VALUES
(2, 3, 'Sakit', '2026-01-26', '2026-01-26', 'Sakit', 'sakit_3_1769357537.pdf', 'Approved'),
(3, 3, 'Izin', '2026-01-27', '2026-01-27', 'Nge-date', 'izin_3_1769488811.pdf', 'Approved');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_asisten`
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
-- Dumping data untuk tabel `jadwal_asisten`
--

INSERT INTO `jadwal_asisten` (`id_jadwal_asisten`, `id_profil`, `prodi`, `mata_kuliah`, `dosen`, `kelas_lab`, `frekuensi`, `ruangan_lab`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `start_time`, `end_time`) VALUES
(22, 3, NULL, 'Jaringan', NULL, NULL, NULL, 'Lab Terpadu', 5, '2026-01-02', '2026-02-27', 'mingguan', '07:15:00', '09:30:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_full`
--

CREATE TABLE `jadwal_full` (
  `id_jadwal` int(11) NOT NULL,
  `id_jadwal_lab` int(11) DEFAULT NULL,
  `id_jadwal_kuliah` int(11) DEFAULT NULL,
  `id_jadwal_piket` int(11) DEFAULT NULL,
  `id_jadwal_asisten` int(11) DEFAULT NULL,
  `google_calendar_API` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal_full`
--

INSERT INTO `jadwal_full` (`id_jadwal`, `id_jadwal_lab`, `id_jadwal_kuliah`, `id_jadwal_piket`, `id_jadwal_asisten`, `google_calendar_API`, `created_at`) VALUES
(2, NULL, 1, NULL, NULL, NULL, '2026-01-23 07:28:46'),
(3, NULL, 2, NULL, NULL, NULL, '2026-01-23 07:33:59');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_kuliah`
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
-- Dumping data untuk tabel `jadwal_kuliah`
--

INSERT INTO `jadwal_kuliah` (`id_jadwal_kuliah`, `id_profil`, `matkul`, `tipe`, `dosen`, `kelas`, `ruangan`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `start_time`, `end_time`) VALUES
(1, 3, 'Pemrograman Berorientasi Objek', 'Teori', 'Lutfi Budi Ilmawan, S.Kom., M.Cs., MTA', 'A1', 'Lab Startup', 4, '2026-01-01', '2026-02-05', 'mingguan', '07:00:00', '09:30:00'),
(2, 3, 'Pemrograman Web', 'Teori', 'A Ulfah Tenripada Syahar, S.Kom.,M.Kom., MTA', 'A1', 'Lab IoT', 2, '2026-01-06', '2026-01-27', 'mingguan', '09:40:00', '00:10:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_lab`
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
-- Dumping data untuk tabel `jadwal_lab`
--

INSERT INTO `jadwal_lab` (`id_jadwal_lab`, `nama_kegiatan`, `lokasi`, `tanggal`, `tanggal_selesai`, `hari`, `jam_mulai`, `jam_selesai`, `model_perulangan`, `created_at`) VALUES
(1, 'Test', 'Lab Terpadu', '2026-01-29', '2026-01-29', 4, '11:00:00', '12:00:00', 'sekali', '2026-01-22 15:08:01'),
(2, 'Tes Polisi', 'Lab Terpadu', '2026-01-27', '2026-01-27', 2, '07:00:00', '17:00:00', 'sekali', '2026-01-22 15:18:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal_piket`
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
-- Dumping data untuk tabel `jadwal_piket`
--

INSERT INTO `jadwal_piket` (`id_jadwal_piket`, `id_profil`, `subjek`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `jam_mulai`, `jam_selesai`) VALUES
(5, 3, 'Piket Harian', 2, '2026-01-06', '2026-02-24', 'mingguan', '07:00:00', '23:59:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lab`
--

CREATE TABLE `lab` (
  `id_lab` int(11) NOT NULL,
  `nama_lab` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lab`
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
-- Struktur dari tabel `logbook`
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
-- Dumping data untuk tabel `logbook`
--

INSERT INTO `logbook` (`id_logbook`, `id_profil`, `id_presensi`, `detail_aktivitas`, `keterangan`, `is_verified`) VALUES
(2, 3, 6, 'Belajar Mandiri', NULL, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `presensi`
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
-- Dumping data untuk tabel `presensi`
--

INSERT INTO `presensi` (`id_presensi`, `id_profil`, `tanggal`, `waktu_presensi`, `foto_presensi`, `waktu_pulang`, `foto_pulang`, `status`) VALUES
(3, 3, '2026-01-23', '18:07:59', 'att_3_1769162879.jpg', NULL, NULL, 'Hadir'),
(4, 3, '2026-01-24', '18:47:59', 'in_3_1769251679.jpg', '18:48:37', 'out_3_1769251717.jpg', 'Hadir'),
(5, 4, '2026-01-27', '12:38:40', 'in_4_1769488720.jpg', '12:45:33', 'out_4_1769489133.jpg', 'Hadir'),
(6, 3, '2026-01-22', '07:00:00', 'admin_edit_1769667528.png', '16:30:00', NULL, 'Hadir'),
(7, 3, '2026-01-29', '14:32:11', 'in_3_1769668331.jpg', NULL, NULL, 'Hadir'),
(8, 4, '2026-01-30', '13:44:26', 'in_4_1769751866.jpg', '13:45:32', 'out_4_1769751932.jpg', 'Hadir'),
(9, 8, '2026-01-30', '18:14:47', 'in_8_1769768087.jpg', '18:18:58', 'out_8_1769768338.jpg', 'Hadir'),
(10, 4, '2026-01-31', '13:29:17', 'in_4_1769837357.jpg', '14:41:37', 'out_4_1769841697.jpg', 'Hadir'),
(11, 4, '2026-02-01', '20:19:36', '4_1769948376.png', NULL, NULL, 'Hadir'),
(12, 4, '2026-02-01', '20:38:51', '4_1769949531.png', NULL, NULL, 'Hadir'),
(13, 4, '2026-02-01', '20:55:41', '4_1769950541.png', NULL, NULL, 'Hadir'),
(14, 4, '2026-02-01', '20:56:12', '4_1769950572.png', NULL, NULL, 'Hadir');

-- --------------------------------------------------------

--
-- Struktur dari tabel `profile`
--

CREATE TABLE `profile` (
  `id_profil` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_lab` int(11) DEFAULT NULL,
  `nim` varchar(20) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  `kelas` char(5) DEFAULT NULL,
  `prodi` varchar(255) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `jabatan` enum('Kepala Lab','Laboran','Koordinator Asisten','Asisten 1','Asisten 2','Asisten Pendamping') DEFAULT NULL,
  `peminatan` varchar(255) DEFAULT NULL,
  `photo_profile` varchar(255) DEFAULT 'default.jpg',
  `is_completed` tinyint(1) DEFAULT 0 COMMENT '0=Belum Lengkap, 1=Sudah Lengkap'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `profile`
--

INSERT INTO `profile` (`id_profil`, `id_user`, `id_lab`, `nim`, `nama`, `kelas`, `prodi`, `alamat`, `no_telp`, `jenis_kelamin`, `jabatan`, `peminatan`, `photo_profile`, `is_completed`) VALUES
(1, 1, NULL, NULL, ' Ir. Huzain Azis, S.Kom., M.Cs., MTA.', NULL, NULL, 'Jl. Urip Sumoharjo No.km.5, Panaikang, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 90231, Indonesia', '08114484875', 'L', 'Kepala Lab', NULL, '1769533666_6978f0e27cab8.jpeg', 1),
(2, 2, NULL, NULL, 'Fatimah AR. Tuasamu, S.Kom., MTA, MCF', NULL, '', 'Jl. Urip Sumoharjo No.km.5, Panaikang, Kec. Panakkukang, Kota Makassar, Sulawesi Selatan 90231, Indonesia', '08534186497', 'P', 'Laboran', NULL, '1768721611_696c8ccb66f2c.jpeg', 1),
(3, 3, 1, '13120230033', 'Nurfajri Mukmin Saputra', 'A1', 'Sistem Informasi', 'Kabupaten Bantaeng, Provinsi Sulawesi Selatan', '0853332084', 'L', 'Asisten 2', 'Multimedia', '1768722382_696c8fceac85d.jpeg', 1),
(4, 4, NULL, '13020230241', 'Firly Anastasya Hafid', 'B4', 'Teknik Informatika', 'Kota Makassar, Provinsi Sulawesi Selatan', '085954464608', 'P', 'Asisten 2', 'RPL', '1769488030_69783e9e20d73.jpeg', 1),
(8, 8, NULL, NULL, 'Tasya', NULL, NULL, NULL, NULL, NULL, 'Asisten Pendamping', NULL, 'default.jpg', 0),
(9, 9, NULL, NULL, 'Muhammad Nur Fuad', 'A1', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(10, 10, NULL, NULL, 'Ichwal', 'A2', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(11, 11, NULL, NULL, 'Aan Maulana Sampe ', 'A3', 'Teknik Informatika', NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(12, 19, NULL, NULL, 'M. Rizwan ', 'A3', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(13, 33, NULL, NULL, 'Nahwa Kaka Saputra Anggareksa ', 'A6', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(15, 35, NULL, NULL, 'Muhammad Rifky Saputra Scania ', 'A6', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(16, 37, NULL, NULL, 'Laode Muhammad Dhaifan Kasyfillah ', 'A7', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(17, 38, NULL, NULL, 'Zaki Falihin Ayyubi ', 'A7', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(18, 39, NULL, NULL, 'Muhammad Rafli', 'A6', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(19, 40, NULL, NULL, 'Raihan Nur Rizqillah ', 'A8', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(20, 41, NULL, NULL, 'Muh. Fatwah Fajriansyah M. ', 'A9', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(21, 42, NULL, NULL, 'Hendrawan ', 'A9', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(23, 47, NULL, NULL, 'Farah Tsabitaputri Az Zahra ', 'B4', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, '1769938614_697f1eb6f2937.jpeg', 0),
(24, 48, NULL, NULL, 'Thalita Sherly Putri Jasmin ', 'B2', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, '1769939000_697f20383e9f1.jpeg', 0),
(25, 49, NULL, NULL, 'Siti Safira Tawetubun', 'B3', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, '1769939883_697f23ab84128.jpeg', 0),
(26, 50, NULL, NULL, 'Sitti Lutfia ', 'B4', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(27, 51, NULL, NULL, 'Firli Anastasya Hafid', 'B4', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(28, 52, NULL, NULL, 'Sitti Nurhalimah', 'B4', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, '1769940060_697f245c980bd.jpeg', 0),
(29, 53, NULL, NULL, 'Rizqi Ananda Jalil ', 'B4', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0),
(30, 54, NULL, NULL, 'Nurfajri Mukmin Saputra ', 'A1', NULL, NULL, NULL, NULL, 'Asisten 2', NULL, 'default.jpg', 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `qr_code`
--

CREATE TABLE `qr_code` (
  `id_qr` int(11) NOT NULL,
  `tipe` enum('Presensi','Pulang') NOT NULL,
  `token_code` varchar(255) NOT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `valid_until` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `qr_code`
--

INSERT INTO `qr_code` (`id_qr`, `tipe`, `token_code`, `generated_at`, `valid_until`) VALUES
(168, 'Pulang', 'e7448f8582aaba80df813fd9a720ac48', '2026-01-31 23:35:20', '2026-02-01 23:35:20'),
(178, 'Presensi', '72a3ab04fa68005daa28dbdb36e8fa4b', '2026-02-01 12:32:27', '2026-02-02 12:32:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','User','Kepala Lab') NOT NULL DEFAULT 'User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id_user`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'super@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Lab', '2026-01-03 05:23:53'),
(2, 'admin@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '2026-01-03 05:23:53'),
(3, 'user@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-01-03 05:23:53'),
(4, 'firly@iclabs.com', '$2y$12$lFHuRuoExW9RSSBUZm4QgeZMom7v5iLKJfBAtJB68d7qPEOH1gRCm', 'User', '2026-01-27 03:37:51'),
(8, 'tasya@gmail.com', '$2y$10$FrIG2OhNk62WfTMDizJrmeF.BQU.YmZ19205mRlfqMtbCKEbIUr0u', 'User', '2026-01-30 10:12:55'),
(9, '13020230030@student.umi.ac.id', '$2y$10$q9kGmvEEf6uAMJ91aFPDWONhLwONdpYUbbCKbMtN7pgMvRO7CFZ5m', 'User', '2026-02-01 04:57:53'),
(10, '13020230049@student.umi.ac.id', '$2y$10$QyMQNxnOoftbzz5.x8MVB.2ZnTcwxGacG0pJstdLKz69GBCMMAY02', 'User', '2026-02-01 04:58:52'),
(11, '13020230081@student.umi.ac.id', '$2y$10$SJDVzEEr5P2K3PNrjUBj3uQXFDVTj4iv/ZLhGniqRT8eV5ZA.RtJi', 'User', '2026-02-01 05:01:41'),
(19, '13020230100@student.umi.ac.id', '$2y$10$rm4yLUk0/iV.2nN.Tlp8nuHBuN.GO8TSUyyER.3PBF2adUvqD40SG', 'User', '2026-02-01 05:54:57'),
(33, '13020230187@student.umi.ac.id', '$2y$10$4zT8rlhAnc8pHquvg4YC7efuBQUlQli4ni3rSibOT.9V4rDI3f4Ga', 'User', '2026-02-01 07:10:27'),
(35, '13020230193@student.umi.ac.id', '$2y$10$NFvx02lWowjrNMpfqQEG/OwWQr9WJTGp9bbBPhWXNuB/tnIJQEN2G', 'User', '2026-02-01 07:12:49'),
(37, '13020230232@student.umi.ac.id', '$2y$10$vWqCfRRXRJL.ikWsgTcr2OQGQZAahZejqvvyG6483ggH4FMXsse6q', 'User', '2026-02-01 07:44:17'),
(38, '13020230253@student.umi.ac.id', '$2y$10$kOgGxV7dXM6yxGLmV31GHO74LBC7IVBnDanyNeI1u1O0nR7KUQWEm', 'User', '2026-02-01 07:59:46'),
(39, '13020230290@student.umi.ac.id', '$2y$10$nlGgwsUchs1Bp0yuLVpE5OjP3X5X7TGX6lkAvDCpitQoyq9eMuP6O', 'User', '2026-02-01 08:01:17'),
(40, '13020230306@student.umi.ac.id', '$2y$10$xXBeox3.rzXf9K1d1wf4juq0us8hg/TQoNMECMLa6fOYGpMOxLRU2', 'User', '2026-02-01 08:02:30'),
(41, '13020230319@student.umi.ac.id', '$2y$10$02mMjh.v3HRmc/4eFfCot.BFKmcVyvzh/3dBKMfded0b/T9/.a5Sa', 'User', '2026-02-01 08:03:12'),
(42, '13020230309@student.umi.ac.id', '$2y$10$BZa3BeZSoA4Ul7EWm9s31O4q1ozwm5R3nZO9QZzscUrAr/HBuZBW6', 'User', '2026-02-01 08:04:06'),
(47, '13020230268@student.umi.ac.id', '$2y$10$8u06kLLKpdst8a87F8x/KucwntC7Kg4jOczKy6DOt3.PE9wWIpbL6', 'User', '2026-02-01 09:36:55'),
(48, '13020230096@student.umi.ac.id', '$2y$10$L86Qk6Z/3Nkr9cRy0dbSouOS8k4q5wJrhgLm2/CmImIyGJIWVihAO', 'User', '2026-02-01 09:43:20'),
(49, '13020230217@student.umi.ac.id', '$2y$10$TsPoSH/wm.5gg4oSyolEQuvTs0G0b6EoBKWr146j3V8hSP8qq/Q2K', 'User', '2026-02-01 09:58:03'),
(50, '13020230255@student.umi.ac.id', '$2y$10$kPeLjXXEEk4BuulF.5tUJugoaALORSrLPLeTIcwcKFeMekZnns8i2', 'User', '2026-02-01 09:59:02'),
(51, '13020230241@student.umi.ac.id', '$2y$10$56L78F05Mtx0hOhBSl/00u.dUqcKOn9ZEITnEXZfZCVttYFS.fgC.', 'User', '2026-02-01 10:00:02'),
(52, '13020230297@student.umi.ac.id', '$2y$10$Y8USa.xFrfgYafzSLtU37.w.WZysJmxmfCpwGHUHQTTpSFxFmOCxS', 'User', '2026-02-01 10:01:00'),
(53, '13020230244@student.umi.ac.id', '$2y$10$EO..kSWasJ30zJnXWokw4.vpT2rUKmLCbyisuieo69xkWi3LIxW4G', 'User', '2026-02-01 10:02:09'),
(54, '13120230033@student.umi.ac.id', '$2y$10$eLjv4r62WzhnFt9UnYxQqeKPt2W2PAy5w0DjFGOiM1TFhYu2PD4Ze', 'User', '2026-02-01 10:04:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_google_token`
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
-- Indeks untuk tabel `izin`
--
ALTER TABLE `izin`
  ADD PRIMARY KEY (`id_izin`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indeks untuk tabel `jadwal_asisten`
--
ALTER TABLE `jadwal_asisten`
  ADD PRIMARY KEY (`id_jadwal_asisten`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indeks untuk tabel `jadwal_full`
--
ALTER TABLE `jadwal_full`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `id_jadwal_kuliah` (`id_jadwal_kuliah`),
  ADD KEY `id_jadwal_piket` (`id_jadwal_piket`),
  ADD KEY `id_jadwal_asisten` (`id_jadwal_asisten`);

--
-- Indeks untuk tabel `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  ADD PRIMARY KEY (`id_jadwal_kuliah`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indeks untuk tabel `jadwal_lab`
--
ALTER TABLE `jadwal_lab`
  ADD PRIMARY KEY (`id_jadwal_lab`);

--
-- Indeks untuk tabel `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  ADD PRIMARY KEY (`id_jadwal_piket`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indeks untuk tabel `lab`
--
ALTER TABLE `lab`
  ADD PRIMARY KEY (`id_lab`);

--
-- Indeks untuk tabel `logbook`
--
ALTER TABLE `logbook`
  ADD PRIMARY KEY (`id_logbook`),
  ADD KEY `id_profil` (`id_profil`),
  ADD KEY `id_presensi` (`id_presensi`);

--
-- Indeks untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id_presensi`),
  ADD KEY `id_profil` (`id_profil`);

--
-- Indeks untuk tabel `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`id_profil`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_lab` (`id_lab`);

--
-- Indeks untuk tabel `qr_code`
--
ALTER TABLE `qr_code`
  ADD PRIMARY KEY (`id_qr`),
  ADD KEY `token_code` (`token_code`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `user_google_token`
--
ALTER TABLE `user_google_token`
  ADD PRIMARY KEY (`id_token`),
  ADD UNIQUE KEY `id_user` (`id_user`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `izin`
--
ALTER TABLE `izin`
  MODIFY `id_izin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `jadwal_asisten`
--
ALTER TABLE `jadwal_asisten`
  MODIFY `id_jadwal_asisten` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `jadwal_full`
--
ALTER TABLE `jadwal_full`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  MODIFY `id_jadwal_kuliah` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `jadwal_lab`
--
ALTER TABLE `jadwal_lab`
  MODIFY `id_jadwal_lab` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  MODIFY `id_jadwal_piket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `lab`
--
ALTER TABLE `lab`
  MODIFY `id_lab` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `logbook`
--
ALTER TABLE `logbook`
  MODIFY `id_logbook` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id_presensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `profile`
--
ALTER TABLE `profile`
  MODIFY `id_profil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `qr_code`
--
ALTER TABLE `qr_code`
  MODIFY `id_qr` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT untuk tabel `user_google_token`
--
ALTER TABLE `user_google_token`
  MODIFY `id_token` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `izin`
--
ALTER TABLE `izin`
  ADD CONSTRAINT `izin_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal_asisten`
--
ALTER TABLE `jadwal_asisten`
  ADD CONSTRAINT `jadwal_asisten_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal_full`
--
ALTER TABLE `jadwal_full`
  ADD CONSTRAINT `jadwal_full_ibfk_1` FOREIGN KEY (`id_jadwal_kuliah`) REFERENCES `jadwal_kuliah` (`id_jadwal_kuliah`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_full_ibfk_2` FOREIGN KEY (`id_jadwal_piket`) REFERENCES `jadwal_piket` (`id_jadwal_piket`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_full_ibfk_3` FOREIGN KEY (`id_jadwal_asisten`) REFERENCES `jadwal_asisten` (`id_jadwal_asisten`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  ADD CONSTRAINT `jadwal_kuliah_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal_piket`
--
ALTER TABLE `jadwal_piket`
  ADD CONSTRAINT `jadwal_piket_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `logbook`
--
ALTER TABLE `logbook`
  ADD CONSTRAINT `logbook_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE,
  ADD CONSTRAINT `logbook_ibfk_2` FOREIGN KEY (`id_presensi`) REFERENCES `presensi` (`id_presensi`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `profile_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `profile_ibfk_2` FOREIGN KEY (`id_lab`) REFERENCES `lab` (`id_lab`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `user_google_token`
--
ALTER TABLE `user_google_token`
  ADD CONSTRAINT `user_google_token_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
