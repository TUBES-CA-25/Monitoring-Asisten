-- ===========================================================================
-- ICLABS - Baseline Database Schema (Clean & Sanitized)
-- ICo Labs Monitoring System | FIKOM Universitas Muslim Indonesia
-- ===========================================================================
-- File ini memuat struktur tabel awal (baseline) dan data master dasar
-- (lab dan akun default sistem) tanpa data riil pengguna/mahasiswa.
-- Setelah skema ini diimpor, jalankan `php migrate.php` untuk menerapkan
-- seluruh migrasi inkremental terbaru (migrations/*.sql).
-- ===========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1. Tabel Master Laboratorium
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lab` (
  `id_lab` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lab` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_lab`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 2. Tabel Pengguna (User Accounts)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','User','Kepala Lab') NOT NULL DEFAULT 'User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 3. Tabel Profil Pengguna / Asisten
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `profile` (
  `id_profil` int(11) NOT NULL AUTO_INCREMENT,
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
  `is_completed` tinyint(1) DEFAULT 0 COMMENT '0=Belum Lengkap, 1=Sudah Lengkap',
  PRIMARY KEY (`id_profil`),
  UNIQUE KEY `nim` (`nim`),
  KEY `id_user` (`id_user`),
  KEY `id_lab` (`id_lab`),
  CONSTRAINT `profile_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE,
  CONSTRAINT `profile_ibfk_2` FOREIGN KEY (`id_lab`) REFERENCES `lab` (`id_lab`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 4. Tabel Presensi Kehadiran
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `presensi` (
  `id_presensi` int(11) NOT NULL AUTO_INCREMENT,
  `id_profil` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_presensi` time DEFAULT NULL,
  `foto_presensi` varchar(255) DEFAULT NULL,
  `waktu_pulang` time DEFAULT NULL,
  `foto_pulang` varchar(255) DEFAULT NULL,
  `status` enum('Hadir','Alpa','Terlambat','Izin') DEFAULT 'Hadir',
  PRIMARY KEY (`id_presensi`),
  KEY `id_profil` (`id_profil`),
  CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 5. Tabel Logbook Aktivitas Harian
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `logbook` (
  `id_logbook` int(11) NOT NULL AUTO_INCREMENT,
  `id_profil` int(11) NOT NULL,
  `id_presensi` int(11) NOT NULL,
  `detail_aktivitas` text NOT NULL,
  `keterangan` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_logbook`),
  KEY `id_profil` (`id_profil`),
  KEY `id_presensi` (`id_presensi`),
  CONSTRAINT `logbook_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE,
  CONSTRAINT `logbook_ibfk_2` FOREIGN KEY (`id_presensi`) REFERENCES `presensi` (`id_presensi`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 6. Tabel Pengajuan Izin / Sakit
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `izin` (
  `id_izin` int(11) NOT NULL AUTO_INCREMENT,
  `id_profil` int(11) NOT NULL,
  `tipe` enum('Izin','Sakit') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `file_bukti` varchar(255) DEFAULT NULL,
  `status_approval` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  PRIMARY KEY (`id_izin`),
  KEY `id_profil` (`id_profil`),
  CONSTRAINT `izin_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 7. Tabel Jadwal Kuliah Asisten
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jadwal_kuliah` (
  `id_jadwal_kuliah` int(11) NOT NULL AUTO_INCREMENT,
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
  `end_time` time NOT NULL,
  PRIMARY KEY (`id_jadwal_kuliah`),
  KEY `id_profil` (`id_profil`),
  CONSTRAINT `jadwal_kuliah_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 8. Tabel Jadwal Asistensi Praktikum
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jadwal_asisten` (
  `id_jadwal_asisten` int(11) NOT NULL AUTO_INCREMENT,
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
  `end_time` time NOT NULL,
  PRIMARY KEY (`id_jadwal_asisten`),
  KEY `id_profil` (`id_profil`),
  CONSTRAINT `jadwal_asisten_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 9. Tabel Jadwal Piket Lab
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jadwal_piket` (
  `id_jadwal_piket` int(11) NOT NULL AUTO_INCREMENT,
  `id_profil` int(11) NOT NULL,
  `subjek` varchar(150) NOT NULL,
  `hari` tinyint(4) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `model_perulangan` enum('sekali','mingguan','rentang') DEFAULT 'sekali',
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  PRIMARY KEY (`id_jadwal_piket`),
  KEY `id_profil` (`id_profil`),
  CONSTRAINT `jadwal_piket_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 10. Tabel Jadwal Kegiatan Lab
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jadwal_lab` (
  `id_jadwal_lab` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kegiatan` varchar(255) NOT NULL,
  `lokasi` varchar(100) DEFAULT 'Lab Terpadu',
  `tanggal` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `hari` int(1) DEFAULT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `model_perulangan` enum('sekali','mingguan','rentang') DEFAULT 'sekali',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_jadwal_lab`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 11. Tabel Jadwal Gabungan (Full Calendar Sync)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jadwal_full` (
  `id_jadwal` int(11) NOT NULL AUTO_INCREMENT,
  `id_jadwal_lab` int(11) DEFAULT NULL,
  `id_jadwal_kuliah` int(11) DEFAULT NULL,
  `id_jadwal_piket` int(11) DEFAULT NULL,
  `id_jadwal_asisten` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_jadwal`),
  KEY `id_jadwal_lab` (`id_jadwal_lab`),
  KEY `id_jadwal_kuliah` (`id_jadwal_kuliah`),
  KEY `id_jadwal_piket` (`id_jadwal_piket`),
  KEY `id_jadwal_asisten` (`id_jadwal_asisten`),
  CONSTRAINT `jadwal_full_ibfk_1` FOREIGN KEY (`id_jadwal_kuliah`) REFERENCES `jadwal_kuliah` (`id_jadwal_kuliah`) ON DELETE CASCADE,
  CONSTRAINT `jadwal_full_ibfk_2` FOREIGN KEY (`id_jadwal_piket`) REFERENCES `jadwal_piket` (`id_jadwal_piket`) ON DELETE CASCADE,
  CONSTRAINT `jadwal_full_ibfk_3` FOREIGN KEY (`id_jadwal_asisten`) REFERENCES `jadwal_asisten` (`id_jadwal_asisten`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 12. Tabel Token Google Calendar OAuth
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_google_token` (
  `id_token` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `access_token` text NOT NULL,
  `refresh_token` text NOT NULL,
  `expires_in` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_token`),
  UNIQUE KEY `id_user` (`id_user`),
  CONSTRAINT `user_google_token_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 13. Tabel Smart QR Code Kehadiran
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qr_code` (
  `id_qr` int(11) NOT NULL AUTO_INCREMENT,
  `tipe` enum('Presensi','Pulang') NOT NULL,
  `token_code` varchar(255) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `valid_until` datetime NOT NULL,
  PRIMARY KEY (`id_qr`),
  KEY `token_code` (`token_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 14. Tabel Notifikasi
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification` (
  `id_notification` int(11) NOT NULL AUTO_INCREMENT,
  `id_profil` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('Info','Warning','Error','Success','Reminder') DEFAULT 'Info',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notification`),
  KEY `id_profil` (`id_profil`),
  KEY `created_at` (`created_at`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- 15. Tabel Device (Push Notification / Mobile)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `device` (
  `id_device` int(11) NOT NULL AUTO_INCREMENT,
  `id_profil` int(11) NOT NULL,
  `device_id` varchar(255) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `device_type` enum('ios','android','web') DEFAULT 'web',
  `fcm_token` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_device`),
  UNIQUE KEY `device_id` (`device_id`),
  KEY `id_profil` (`id_profil`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `device_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- DATA AWAL MASTER (LABORATORIUM)
-- ---------------------------------------------------------------------------
INSERT INTO `lab` (`id_lab`, `nama_lab`, `deskripsi`, `lokasi`) VALUES
(1, 'Laboratorium Software Engineering', 'Laboratorium Rekayasa Perangkat Lunak', 'Fakultas Ilmu Komputer UMI'),
(2, 'Laboratorium IoT & Embedded Systems', 'Laboratorium Internet of Things', 'Fakultas Ilmu Komputer UMI'),
(3, 'Laboratorium Computer Vision & AI', 'Laboratorium Computer Vision & Kecerdasan Buatan', 'Fakultas Ilmu Komputer UMI'),
(4, 'Laboratorium Data Science', 'Laboratorium Sains Data & Analitik', 'Fakultas Ilmu Komputer UMI'),
(5, 'Laboratorium Multimedia', 'Laboratorium Desain & Multimedia', 'Fakultas Ilmu Komputer UMI'),
(6, 'Laboratorium Microcontroller', 'Laboratorium Mikrokontroler & Robotika', 'Fakultas Ilmu Komputer UMI'),
(7, 'Laboratorium Computer Networking', 'Laboratorium Jaringan Komputer & Cyber Security', 'Fakultas Ilmu Komputer UMI')
ON DUPLICATE KEY UPDATE `nama_lab` = VALUES(`nama_lab`);

-- ---------------------------------------------------------------------------
-- DATA AWAL PENGGUNA DEFAULT (Password default: "password")
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ---------------------------------------------------------------------------
INSERT INTO `user` (`id_user`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'super@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Lab', NOW()),
(2, 'admin@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', NOW()),
(3, 'user@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', NOW())
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- ---------------------------------------------------------------------------
-- DATA AWAL PROFIL PENGGUNA DEFAULT
-- ---------------------------------------------------------------------------
INSERT INTO `profile` (`id_profil`, `id_user`, `id_lab`, `nim`, `nama`, `kelas`, `prodi`, `alamat`, `no_telp`, `jenis_kelamin`, `jabatan`, `peminatan`, `photo_profile`, `is_completed`) VALUES
(1, 1, NULL, NULL, 'Kepala Laboratorium FIKOM', NULL, 'Teknik Informatika', 'Kampus II UMI Makassar', '08114400001', 'L', 'Kepala Lab', 'Manajemen Lab', 'default.jpg', 1),
(2, 2, NULL, NULL, 'Administrator ICLABS', NULL, 'Teknik Informatika', 'Kampus II UMI Makassar', '08534100002', 'P', 'Laboran', 'Administrasi Lab', 'default.jpg', 1),
(3, 3, 1, '13020200001', 'Asisten Laboratorium Demo', 'A1', 'Teknik Informatika', 'Kampus II UMI Makassar', '08533300003', 'L', 'Asisten 1', 'Software Engineering', 'default.jpg', 1)
ON DUPLICATE KEY UPDATE `nama` = VALUES(`nama`);

SET FOREIGN_KEY_CHECKS = 1;
