-- ============================================================
-- ICLABS Migration v7: Perbaikan konsistensi indikator kehadiran
-- Jalankan sekali setelah backup database.
-- ============================================================

-- 1. Tambah kolom attendance_reset_at ke profile
SET @has_reset_at := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'profile'
      AND COLUMN_NAME  = 'attendance_reset_at'
);

SET @ddl_reset_at := IF(
    @has_reset_at > 0,
    'SELECT "Kolom profile.attendance_reset_at sudah ada, skip." AS info',
    'ALTER TABLE `profile` ADD COLUMN `attendance_reset_at` DATETIME NULL DEFAULT NULL COMMENT \'Waktu terakhir data presensi di-reset. calculateRealAlpha mulai dari tanggal ini, bukan dari created_at.\' AFTER `is_completed`'
);

PREPARE stmt FROM @ddl_reset_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Tambah kolom data_izin & jumlah_izin ke attendance_recycle_bin
SET @has_data_izin := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'attendance_recycle_bin'
      AND COLUMN_NAME  = 'data_izin'
);

SET @ddl_data_izin := IF(
    @has_data_izin > 0,
    'SELECT "Kolom attendance_recycle_bin.data_izin sudah ada, skip." AS info',
    'ALTER TABLE `attendance_recycle_bin` ADD COLUMN `data_izin` LONGTEXT NULL DEFAULT NULL COMMENT \'JSON array data izin yang turut di-reset\' AFTER `data_logbook`'
);

PREPARE stmt FROM @ddl_data_izin;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_jumlah_izin := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'attendance_recycle_bin'
      AND COLUMN_NAME  = 'jumlah_izin'
);

SET @ddl_jumlah_izin := IF(
    @has_jumlah_izin > 0,
    'SELECT "Kolom attendance_recycle_bin.jumlah_izin sudah ada, skip." AS info',
    'ALTER TABLE `attendance_recycle_bin` ADD COLUMN `jumlah_izin` INT DEFAULT 0 COMMENT \'Jumlah record izin yang diarsipkan\' AFTER `jumlah_logbook`'
);

PREPARE stmt FROM @ddl_jumlah_izin;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
