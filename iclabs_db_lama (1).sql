-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 03 Jun 2026 pada 04.35
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.1.17

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
-- Struktur dari tabel `dosen`
--
-- [BARU - Modul Dosen V3] Tabel master dosen pengampu, dipakai sebagai
-- sumber dropdown "Dosen Pengampu" di form Jadwal Kuliah & Jadwal Asisten
-- (lihat kolom id_dosen pada jadwal_kuliah/jadwal_asisten di bawah).
--

CREATE TABLE `dosen` (
  `id_dosen` int(11) NOT NULL,
  `nama_dosen` varchar(150) NOT NULL,
  `nidn` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `dosen`
--

INSERT INTO `dosen` (`nama_dosen`, `nidn`, `email`) VALUES
('Dr. Ir. Purnawansyah, M.Kom', '0919027301', 'purnawansyah@umi.ac.id'),
('Ir. Yulita Salim, S.Kom., M.T., MTA', '0922078101', 'yulita.salim@umi.ac.id'),
('Dr. Ir. Harlinda L, S.Kom., M.M., M.Kom., MTA', '0114000775', 'harlinda@umi.ac.id'),
('Poetri Lestari LB, S.Kom., M.T., MTA', '0916108403', 'poetrilestari@umi.ac.id'),
('Dr. Andi Sumardin, S.Ag., M.A', '0915087302', NULL),
('Dr. Tasrif Hasanuddin, S.T., M.Cs', '0910126901', 'tasrif.hasanuddin@umi.ac.id'),
('Dr. Ir. Dolly Indra, S.Kom., M.M.SI., MTA', '0428077401', 'dolly.indra@umi.ac.id'),
('Herman, S.Kom., M.Cs., MTA', '0913038506', 'herman@umi.ac.id'),
('Ir. Abdul Rachman Manga'', S.Kom., M.T., MTA', '0931018001', 'abdulrachman.manga@umi.ac.id'),
('Ir. Huzain Azis, S.Kom., M.Cs., MTA', '0920098801', 'huzain.azis@umi.ac.id'),
('Ir. Dedy Atmajaya, S.Kom., M.Eng., MTA', '0917068601', 'dedy.atmajaya@umi.ac.id'),
('Ir. Farniwati Fattah, S.T., M.T., MTA', '0911098601', 'farniwati.fattah@umi.ac.id'),
('Mardiyyah Hasnawi, S.Kom., M.T., MTA', '0906078701', 'mardiyyah.hasnawi@umi.ac.id'),
('Lilis Nur Hayati, S.Kom., M.Eng., MTA', '0906048205', 'lilis.nurhayati@umi.ac.id'),
('Siska Anraeni, S.Kom., M.T., MCF.', '0922088701', 'siska.anraeni@umi.ac.id'),
('Dr. Ramdan Satra, S.Kom., M.Kom., MTA', '0919056501', 'ramdan@umi.ac.id'),
('Muh. Aliyazid Mude, S.Kom., M.Kom.', '0920107601', 'aliyazid.mude@umi.ac.id'),
('Irawati, S.Kom., M.T., MTA', '0915028503', 'irawan2801@gmail.com'),
('Ir. St. Hajrah Mansyur, S.Kom., M.Cs., MTA', '0919018501', 'hajrah.mansyur@umi.ac.id'),
('Syahrul Mubarak, S.Kom., M.Kom., MTA', '0926048704', 'syahrul.mubarak@umi.ac.id'),
('Ir. Nia Kurniati, M.Kom., MTA', '0915068601', 'nia.kurniati@umi.ac.id'),
('Sugiarti, S.Kom., M.Kom., MTA', '0924048501', 'sugiarti.sugiarti@umi.ac.id'),
('Ir. Erick Irawadi Alwi, S.Kom., M.Eng., MTA', '0906128504', 'erick.alwi@umi.ac.id'),
('Lutfi Budi Ilmawan, S.Kom., M.Cs., MTA', '0921018902', 'lutfibudi.ilmawan@umi.ac.id'),
('Herdianti, S.Si., M.Eng., MTA', '0924069001', 'herdianti.darwis@umi.ac.id'),
('Fitriyani Umar, S.Si., M.Eng., MTA', '0922078801', 'fitryani.umar@umi.ac.id'),
('Ir. Lukman Syafie, S.Si., M.Si., MTA', '0922118003', 'lukman.syafie@umi.ac.id'),
('Wistiani Astuti, S.Kom., M.T., MTA', '0907018602', 'wistiani.astuti@umi.ac.id'),
('A Ulfa Tenripada Syahar, S.Kom., M.Kom., MTA', '0908089202', 'a.ulfah@umi.ac.id'),
('Ihwana Asad S.Ag., M.Sc. P.hD., MTA', '2107057202', 'ihwana.asad@umi.ac.id'),
('Andi Widya Mufila Gaffar, S.T., M.Kom., MTA', '0908099401', 'widya.mufila@umi.ac.id'),
('Ramdaniah, S.Kom., M.T., MTA', '0911039301', 'ramdaniah@umi.ac.id'),
('Muhammad Arfah Asis, S.Kom., M.T., MTA', '0909029203', 'muh.arfah.asis@umi.ac.id'),
('Amaliah Faradibah, S.Kom., M.Kom., MTA., MCF', '0924049303', 'amaliah.faradibah@umi.ac.id'),
('Sitti Rahmah Jabir, S.M., M.Sc., MTA., MCF', '0918109501', 'rahmahjabir@umi.ac.id'),
('Dewi Widyawati, S.Kom., M.Kom., MTA., MCF', '0901019302', 'dewiwidyawati@umi.ac.id'),
('Hadriana Iddas, S.T., M.T., Ph.D', '0922067801', 'hadriana.iddas@umi.ac.id'),
('Syariful Mujaddid, S.Kom., M.T', NULL, NULL),
('Fahmi, S.Kom., M.T', NULL, NULL),
('Fadly Kasim, S.T., M.Kom', NULL, NULL),
('Rabiatul Adawiyah, S.Si., M.Si', NULL, NULL),
('Suwito Pomalingo, S.Kom., M.Kom., MTA', NULL, NULL),
('Muhammad Nasry Ashar, S.Kom., M.Kom', NULL, NULL);

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
(3, 3, 'Izin', '2026-01-27', '2026-01-27', 'Nge-date', 'izin_3_1769488811.pdf', 'Approved'),
(4, 17, 'Izin', '2026-05-29', '2026-05-29', 'Pengajuan izin absensi praktikum via aplikasi mobile.', 'izin_17_1780063361.jpg', 'Approved'),
(5, 3, 'Izin', '2026-05-29', '2026-05-29', 'Pengajuan izin absensi praktikum via aplikasi mobile.', 'izin_3_1780063849.jpg', 'Rejected'),
(6, 10, 'Izin', '2026-05-29', '2026-05-29', 'Pengajuan izin absensi praktikum via aplikasi mobile.', 'izin_10_1780063937.jpg', 'Approved'),
(7, 17, 'Izin', '2026-05-30', '2026-05-30', 'Pengajuan izin absensi praktikum via aplikasi mobile.', 'izin_17_1780096406.jpg', 'Approved');

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
-- Dumping data untuk tabel `jadwal_asisten`
--

INSERT INTO `jadwal_asisten` (`id_jadwal_asisten`, `id_profil`, `prodi`, `mata_kuliah`, `dosen`, `id_dosen`, `kelas_lab`, `frekuensi`, `ruangan_lab`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `start_time`, `end_time`) VALUES
(22, 3, NULL, 'Jaringan', NULL, NULL, NULL, NULL, 'Lab Terpadu', 5, '2026-01-02', '2026-02-27', 'mingguan', '07:15:00', '09:30:00');

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
  `google_event_id` varchar(255) DEFAULT NULL,
  `sync_status` enum('synced','failed','skipped') NOT NULL DEFAULT 'skipped',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal_full`
--

INSERT INTO `jadwal_full` (`id_jadwal`, `id_jadwal_lab`, `id_jadwal_kuliah`, `id_jadwal_piket`, `id_jadwal_asisten`, `google_event_id`, `created_at`) VALUES
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
-- Dumping data untuk tabel `jadwal_kuliah`
--

INSERT INTO `jadwal_kuliah` (`id_jadwal_kuliah`, `id_profil`, `matkul`, `tipe`, `dosen`, `id_dosen`, `kelas`, `ruangan`, `hari`, `tanggal`, `tanggal_selesai`, `model_perulangan`, `start_time`, `end_time`) VALUES
(1, 3, 'Pemrograman Berorientasi Objek', 'Teori', 'Lutfi Budi Ilmawan, S.Kom., M.Cs., MTA', 24, 'A1', 'Lab Startup', 4, '2026-01-01', '2026-02-05', 'mingguan', '07:00:00', '09:30:00'),
(2, 3, 'Pemrograman Web', 'Teori', 'A Ulfah Tenripada Syahar, S.Kom.,M.Kom., MTA', NULL, 'A1', 'Lab IoT', 2, '2026-01-06', '2026-01-27', 'mingguan', '09:40:00', '00:10:00'),
(16, 17, 'Pemrograman Berorientasi Objek ', 'Teori', 'Lutfi Budiawan', NULL, 'B2', 'Lab IoT', 1, '2026-02-02', '2026-06-29', 'mingguan', '13:00:00', '15:40:00'),
(17, 17, 'Sistem Kendali', 'Teori', 'Dr. Ir. Dolly Indra, S.Kom.,M.MSi.,MTA.', NULL, 'A2', '408', 1, '2026-02-02', '2026-06-29', 'mingguan', '09:40:00', '11:20:00'),
(18, 17, 'Pengenalan Pola', 'Teori', 'Wistiani Astuti, S.Kom.,MT.,MTA', NULL, 'C2', '305', 2, '2026-02-03', '2026-06-30', 'mingguan', '09:40:00', '12:10:00'),
(19, 17, 'Manajemen Resiko', 'Teori', 'Ir. Nia Kurniawati, S.Kom.,M.Kom.,MTA.', NULL, 'C2', '304', 2, '2026-02-03', '2026-07-01', 'mingguan', '13:00:00', '14:40:00'),
(20, 17, 'Sistem Pakar', 'Teori', 'Siska Angreani', NULL, 'C3', '304', 3, '2026-02-04', '2026-07-01', 'mingguan', '10:30:00', '12:10:00');

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
-- Struktur dari tabel `jadwal_lab_sync`
--
-- [BARU - Modul 5 lanjutan V3] Melacak status sinkronisasi Google Calendar
-- PER ASISTEN untuk setiap Jadwal Lab/Umum (satu jadwal -> banyak salinan
-- event, satu per asisten yang sudah menghubungkan akun Google).
--

CREATE TABLE `jadwal_lab_sync` (
  `id_sync` int(11) NOT NULL,
  `id_jadwal_lab` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `google_event_id` varchar(255) DEFAULT NULL,
  `sync_status` enum('synced','failed','skipped') NOT NULL DEFAULT 'skipped'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 3, 6, 'Belajar Mandiri', NULL, 1),
(3, 27, 40, 'Belajar Mandiri', NULL, 0),
(4, 18, 41, 'Bersihkan Lab Iot dan Startup', NULL, 0),
(7, 3, 44, 'Hello StartUp', NULL, 0),
(9, 17, 49, 'Hello bruh', NULL, 0),
(10, 17, 50, 'Hello Brother', NULL, 0),
(11, 17, 51, 'Hello Kak Farid', NULL, 0),
(12, 3, 52, 'Hello bruh', NULL, 0),
(13, 17, 53, 'Belajar Mandiri', NULL, 0),
(14, 10, 56, 'Hello brouh', NULL, 0),
(15, 17, 58, 'Hello Juni', NULL, 0);

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
  `status` enum('Hadir','Alpa','Terlambat','Izin') DEFAULT 'Hadir',
  `late_minutes` int(11) DEFAULT NULL,
  `work_duration` int(11) DEFAULT NULL
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
(14, 4, '2026-02-01', '20:56:12', '4_1769950572.png', NULL, NULL, 'Hadir'),
(15, 3, '2026-05-15', '02:59:06', NULL, '10:42:28', NULL, 'Hadir'),
(16, 17, '2026-05-15', '10:43:41', NULL, '10:51:55', NULL, 'Hadir'),
(17, 4, '2026-05-15', '10:53:39', NULL, NULL, NULL, 'Hadir'),
(18, 8, '2026-05-15', '10:57:04', NULL, '11:09:38', NULL, 'Hadir'),
(19, 9, '2026-05-15', '11:11:01', NULL, NULL, NULL, 'Hadir'),
(20, 10, '2026-05-15', '16:09:47', NULL, '16:09:57', NULL, 'Hadir'),
(21, 3, '2026-05-16', '07:22:03', NULL, '10:59:54', NULL, 'Hadir'),
(22, 17, '2026-05-16', '11:09:17', NULL, '11:09:31', NULL, 'Hadir'),
(23, 8, '2026-05-16', '11:12:56', NULL, '11:13:11', NULL, 'Hadir'),
(24, 10, '2026-05-16', '11:19:49', NULL, '11:20:03', NULL, 'Hadir'),
(25, 9, '2026-05-16', '14:44:28', NULL, '14:44:35', NULL, 'Hadir'),
(26, 18, '2026-05-16', '14:50:45', NULL, '14:59:11', NULL, 'Hadir'),
(27, 21, '2026-05-16', '15:08:36', NULL, '15:10:45', NULL, 'Hadir'),
(28, 17, '2026-05-17', '14:03:07', NULL, NULL, NULL, 'Hadir'),
(29, 3, '2026-05-17', '14:05:14', NULL, '14:05:30', NULL, 'Hadir'),
(30, 29, '2026-05-17', '22:15:17', 'absen_1779027317_6a09cd758711d.jpg', '22:21:32', 'absen_1779027692_6a09ceece4927.jpg', 'Hadir'),
(31, 11, '2026-05-17', '22:27:07', 'absen_1779028027_6a09d03b948ba.jpg', NULL, NULL, 'Hadir'),
(32, 3, '2026-05-18', '06:59:30', '3_1779058770.png', '07:05:30', '3_1779059130.png', 'Hadir'),
(33, 17, '2026-05-18', '07:27:32', '17_1779060452.png', NULL, NULL, 'Hadir'),
(34, 10, '2026-05-18', '08:09:57', '10_1779062997.png', '08:22:22', '10_1779063742.png', 'Hadir'),
(35, 17, '2026-05-19', '15:10:01', '17_1779174601.png', '15:49:28', '17_1779176968.png', 'Hadir'),
(36, 17, '2026-05-21', '17:29:16', '17_1779355756.png', '17:30:15', '17_1779355815.png', 'Hadir'),
(37, 3, '2026-05-21', '17:46:10', '3_1779356770.png', '17:46:27', '3_1779356787.png', 'Hadir'),
(38, 10, '2026-05-21', '18:30:47', '10_1779359447.png', '18:30:58', '10_1779359458.png', 'Hadir'),
(39, 29, '2026-05-21', '18:48:10', '29_1779360490.png', '18:48:44', '29_1779360524.png', 'Hadir'),
(40, 27, '2026-05-21', '18:53:47', '27_1779360827.png', '18:54:08', '27_1779360848.png', 'Hadir'),
(41, 18, '2026-05-21', '19:37:33', '18_1779363453.png', '19:38:17', '18_1779363497.png', 'Hadir'),
(42, 17, '2026-05-22', '04:36:00', '17_1779395816.png', '04:37:00', '17_1779395837.png', 'Hadir'),
(44, 3, '2026-05-22', '14:48:53', '3_1779432533.png', '14:49:17', '3_1779432557.png', 'Hadir'),
(46, 17, '2026-05-24', '01:20:44', '17_1779556844.png', NULL, NULL, 'Hadir'),
(47, 3, '2026-05-24', '12:45:25', '3_1779597925.png', NULL, NULL, 'Hadir'),
(48, 3, '2026-05-25', '12:19:29', '3_1779682769.png', NULL, NULL, 'Hadir'),
(49, 17, '2026-05-25', '12:30:49', '17_1779683449.png', '12:50:49', '17_1779684649.png', 'Hadir'),
(50, 17, '2026-05-26', '13:01:13', '17_1779771673.png', '13:02:26', '17_1779771746.png', 'Hadir'),
(51, 17, '2026-05-27', '15:26:03', '17_1779866763.png', '17:59:40', '17_1779875980.png', 'Hadir'),
(52, 3, '2026-05-27', '18:28:27', '3_1779877707.png', '18:28:59', '3_1779877739.png', 'Hadir'),
(53, 17, '2026-05-29', '08:48:14', '17_1780015694.png', '21:12:21', '17_1780060341.png', 'Hadir'),
(54, 3, '2026-05-29', '09:21:05', '3_1780017665.png', NULL, NULL, 'Hadir'),
(55, 3, '2026-05-30', '15:55:05', '3_1780127705.png', NULL, NULL, 'Hadir'),
(56, 10, '2026-05-30', '17:10:47', '10_1780132247.png', '17:34:37', '10_1780133677.png', 'Hadir'),
(57, 17, '2026-06-01', '13:37:32', '17_1780292252.png', NULL, NULL, 'Hadir'),
(58, 17, '2026-06-02', '14:04:59', '17_1780380299.png', '17:53:19', '17_1780393999.png', 'Hadir');

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
  `angkatan` varchar(4) DEFAULT NULL,
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
(17, 38, NULL, '13020230253', 'Zaki Falihin Ayyubi', 'A7', 'Teknik Informatika', 'JL.H.Kalla Perumahan Bumi Panaikang Mas Blok C11', '08875295115', 'L', 'Asisten 2', 'Mobile Developer', '1779557015_foto.png', 1),
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
(217, 'Presensi', 'eec1ef3ec81bf0fb0a9110a86effc155', '2026-06-02 13:57:14', '2026-06-03 13:57:14'),
(218, 'Pulang', 'bf592570f6e241e21ac94b66017b63dd', '2026-06-02 13:57:14', '2026-06-03 13:57:14');

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
(38, '13020230253@student.umi.ac.id', '$2y$10$aTQex..iDPoEmQiwrCn0iezgyGB1HC7i8eRDyOz1WtrObgQPuIu/O', 'User', '2026-02-01 07:59:46'),
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
-- Indeks untuk tabel `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`id_dosen`),
  ADD UNIQUE KEY `nama_dosen` (`nama_dosen`);

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
  ADD KEY `id_profil` (`id_profil`),
  ADD KEY `id_dosen` (`id_dosen`);

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
  ADD KEY `id_profil` (`id_profil`),
  ADD KEY `id_dosen` (`id_dosen`);

--
-- Indeks untuk tabel `jadwal_lab`
--
ALTER TABLE `jadwal_lab`
  ADD PRIMARY KEY (`id_jadwal_lab`);

--
-- Indeks untuk tabel `jadwal_lab_sync`
--
ALTER TABLE `jadwal_lab_sync`
  ADD PRIMARY KEY (`id_sync`),
  ADD UNIQUE KEY `uniq_jadwal_user` (`id_jadwal_lab`, `id_user`);

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
-- AUTO_INCREMENT untuk tabel `dosen`
--
ALTER TABLE `dosen`
  MODIFY `id_dosen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT untuk tabel `izin`
--
ALTER TABLE `izin`
  MODIFY `id_izin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `jadwal_asisten`
--
ALTER TABLE `jadwal_asisten`
  MODIFY `id_jadwal_asisten` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `jadwal_full`
--
ALTER TABLE `jadwal_full`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `jadwal_kuliah`
--
ALTER TABLE `jadwal_kuliah`
  MODIFY `id_jadwal_kuliah` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `jadwal_lab`
--
ALTER TABLE `jadwal_lab`
  MODIFY `id_jadwal_lab` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `jadwal_lab_sync`
--
ALTER TABLE `jadwal_lab_sync`
  MODIFY `id_sync` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id_logbook` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id_presensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT untuk tabel `profile`
--
ALTER TABLE `profile`
  MODIFY `id_profil` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `qr_code`
--
ALTER TABLE `qr_code`
  MODIFY `id_qr` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

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
  ADD CONSTRAINT `jadwal_asisten_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jadwal_asisten_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `jadwal_kuliah_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jadwal_kuliah_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE SET NULL;

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

ALTER TABLE users ADD COLUMN IF NOT EXISTS status_account VARCHAR(20) DEFAULT 'ACTIVE';
