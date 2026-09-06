-- ===============================================
-- SQL untuk membuat tabel yang masih kurang
-- Run di MySQL/MariaDB untuk database iclabs_db
-- ===============================================

-- Tabel notification (untuk sistem notifikasi)
CREATE TABLE IF NOT EXISTS `notification` (
  `id_notification` int(11) NOT NULL AUTO_INCREMENT,
  `id_profil` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('Info','Warning','Error','Success','Reminder') DEFAULT 'Info',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notification`),
  KEY `id_profil` (`id_profil`),
  KEY `created_at` (`created_at`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel device (untuk multi-device support & push notifications)
CREATE TABLE IF NOT EXISTS `device` (
  `id_device` int(11) NOT NULL AUTO_INCREMENT,
  `id_profil` int(11) NOT NULL,
  `device_id` varchar(255) NOT NULL UNIQUE,
  `device_name` varchar(255) NOT NULL,
  `device_type` enum('ios','android','web') DEFAULT 'web',
  `fcm_token` varchar(500) DEFAULT NULL COMMENT 'Firebase Cloud Messaging Token',
  `is_active` tinyint(1) DEFAULT 1,
  `registered_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_device`),
  KEY `id_profil` (`id_profil`),
  KEY `device_id` (`device_id`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `device_ibfk_1` FOREIGN KEY (`id_profil`) REFERENCES `profile` (`id_profil`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tambahkan index untuk performa query
ALTER TABLE `notification` ADD INDEX `idx_profil_read_created` (`id_profil`, `is_read`, `created_at`);
ALTER TABLE `device` ADD INDEX `idx_profil_active_updated` (`id_profil`, `is_active`, `last_updated`);

-- Sample data untuk testing
INSERT INTO `notification` (`id_profil`, `title`, `message`, `type`) VALUES
(17, 'Izin Approve', 'Izin Anda telah disetujui oleh Kepala Lab', 'Success'),
(17, 'Jadwal Baru', 'Ada jadwal kuliah baru untuk minggu depan', 'Info'),
(17, 'Reminder', 'Jangan lupa untuk check-in hari ini', 'Reminder');

-- ===============================================
-- Selesai - Jalankan semua SQL di atas
-- ===============================================
