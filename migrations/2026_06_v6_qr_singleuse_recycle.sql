-- ============================================================
-- ICLABS V6 Migration: QR Single-Use + Recycle Bin
-- Apply manually after backup.
-- ============================================================

-- 1. Kolom untuk mencatat user yang sudah memakai QR token ini
SET @has_used_by := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'qr_code'
      AND COLUMN_NAME  = 'used_by_user_id'
);

SET @ddl_used_by := IF(
    @has_used_by > 0,
    'SELECT "Kolom qr_code.used_by_user_id sudah ada, skip." AS info',
    'ALTER TABLE `qr_code` ADD COLUMN `used_by_user_id` INT NULL DEFAULT NULL COMMENT \'id_user yang pertama kali meng-scan token ini. NULL = belum terpakai.\' AFTER `valid_until`'
);

PREPARE stmt FROM @ddl_used_by;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_used_at := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'qr_code'
      AND COLUMN_NAME  = 'used_at'
);

SET @ddl_used_at := IF(
    @has_used_at > 0,
    'SELECT "Kolom qr_code.used_at sudah ada, skip." AS info',
    'ALTER TABLE `qr_code` ADD COLUMN `used_at` DATETIME NULL DEFAULT NULL COMMENT \'Waktu pertama kali token di-scan.\' AFTER `used_by_user_id`'
);

PREPARE stmt FROM @ddl_used_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Tabel recycle bin untuk data presensi & logbook yang di-reset
CREATE TABLE IF NOT EXISTS `attendance_recycle_bin` (
    `id_bin`          INT AUTO_INCREMENT PRIMARY KEY,

    -- Metadata reset
    `reset_scope`     ENUM('all','single') NOT NULL DEFAULT 'single'
                      COMMENT '"all" = reset semua asisten, "single" = satu asisten',
    `reset_label`     VARCHAR(255) NOT NULL
                      COMMENT 'Nama tampilan: nama asisten (single) atau "Semua Asisten" (all)',
    `id_profil`       INT NULL
                      COMMENT 'id_profil asisten (NULL jika scope=all)',
    `nama_asisten`    VARCHAR(150) NULL,
    `jabatan_asisten` VARCHAR(100) NULL,

    -- Rentang tanggal data yang di-reset
    `date_data_start` DATE NULL
                      COMMENT 'Tanggal presensi paling awal yang di-reset',
    `date_data_end`   DATE NULL
                      COMMENT 'Tanggal presensi paling akhir yang di-reset',
    `date_reset`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                      COMMENT 'Waktu reset dilakukan',

    -- Jumlah baris
    `jumlah_presensi` INT DEFAULT 0,
    `jumlah_logbook`  INT DEFAULT 0,

    -- Data presensi & logbook (JSON)
    `data_presensi`   LONGTEXT NULL
                      COMMENT 'JSON array seluruh baris presensi yang di-reset',
    `data_logbook`    LONGTEXT NULL
                      COMMENT 'JSON array seluruh baris logbook yang di-reset',

    -- Siapa yang melakukan reset
    `id_admin`        INT NOT NULL
                      COMMENT 'id_user Admin yang melakukan reset',

    -- Status bin entry
    `status`          ENUM('archived','restored','deleted') NOT NULL DEFAULT 'archived'
                      COMMENT '"archived"=tersimpan di bin, "restored"=sudah dikembalikan, "deleted"=dihapus permanen',

    INDEX idx_bin_profil   (`id_profil`),
    INDEX idx_bin_scope    (`reset_scope`),
    INDEX idx_bin_status   (`status`),
    INDEX idx_bin_date     (`date_reset`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Recycle bin untuk presensi & logbook yang di-reset';

-- 3. Tabel conflict log (diisi saat restore bertumbukan dengan data baru)
CREATE TABLE IF NOT EXISTS `recycle_bin_conflicts` (
    `id_conflict`    INT AUTO_INCREMENT PRIMARY KEY,
    `id_bin`         INT NOT NULL,
    `id_profil`      INT NOT NULL,
    `nama_asisten`   VARCHAR(150),
    `tanggal`        DATE NOT NULL
                     COMMENT 'Tanggal yang bermasalah (sudah ada data baru di presensi)',
    `conflict_type`  VARCHAR(50) DEFAULT 'presensi_overlap'
                     COMMENT 'Jenis konflik: presensi_overlap, logbook_duplicate',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_bin`) REFERENCES `attendance_recycle_bin`(`id_bin`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
