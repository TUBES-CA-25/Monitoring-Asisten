-- ============================================================
-- ICLABS V5 Migration: Account Status + Reset Tracking
-- Apply manually after backup.
-- ============================================================

-- 1. Kolom status akun user (ACTIVE/INACTIVE)
SET @has_status_account := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'user'
      AND COLUMN_NAME  = 'status_account'
);

SET @ddl_status_account := IF(
    @has_status_account > 0,
    'SELECT "Kolom user.status_account sudah ada, skip." AS info',
    'ALTER TABLE `user` ADD COLUMN `status_account` ENUM(\'ACTIVE\',\'INACTIVE\') NOT NULL DEFAULT \'ACTIVE\' AFTER `role`'
);

PREPARE stmt FROM @ddl_status_account;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Tabel log setiap kali reset presensi dilakukan
--    (global = semua asisten, single = satu asisten tertentu)
CREATE TABLE IF NOT EXISTS `reset_log` (
    `id_reset`    INT AUTO_INCREMENT PRIMARY KEY,
    `id_admin`    INT NOT NULL COMMENT 'id_user Admin yang melakukan reset',
    `scope`       ENUM('all','single') NOT NULL DEFAULT 'all',
    `id_profil`   INT NULL COMMENT 'terisi jika scope=single',
    `nama_asisten` VARCHAR(150) NULL,
    `jumlah_presensi` INT DEFAULT 0 COMMENT 'baris presensi yang dihapus',
    `jumlah_logbook`  INT DEFAULT 0 COMMENT 'baris logbook yang dihapus',
    `zip_filename`    VARCHAR(255) NULL COMMENT 'nama file ZIP yang diunduh',
    `reset_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_admin`) REFERENCES `user`(`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
