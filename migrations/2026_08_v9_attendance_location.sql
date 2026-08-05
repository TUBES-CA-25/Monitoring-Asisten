-- ==================================================================
-- Migration v9: Kolom lokasi presensi (datang & pulang) pada tabel
-- `presensi` - dipakai oleh AdminController::saveLogbookAdmin() /
-- LogbookModel::saveLogAdmin() saat Admin mengubah data kehadiran lewat
-- halaman Logbook, supaya setiap entri "Hadir" yang diedit admin tetap
-- tercatat lokasinya (default lokasi lab, lihat DEFAULT_ATTENDANCE_LOCATION
-- di app/config/config.php) selain foto buktinya.
--
-- ADDITIVE (kolom baru, nullable) - endpoint yang memakai SELECT *
-- (AttendanceApi, dll) otomatis ikut menerima kolom ini tanpa perlu
-- perubahan kode.
--
-- Cara pakai: php migrate.php
-- ==================================================================

SET @has_lokasi_masuk := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'presensi'
      AND COLUMN_NAME  = 'lokasi_masuk'
);

SET @ddl_lokasi_masuk := IF(
    @has_lokasi_masuk > 0,
    'SELECT "Kolom presensi.lokasi_masuk sudah ada, skip." AS info',
    'ALTER TABLE `presensi` ADD COLUMN `lokasi_masuk` VARCHAR(255) DEFAULT NULL AFTER `foto_presensi`'
);

PREPARE stmt FROM @ddl_lokasi_masuk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_lokasi_pulang := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'presensi'
      AND COLUMN_NAME  = 'lokasi_pulang'
);

SET @ddl_lokasi_pulang := IF(
    @has_lokasi_pulang > 0,
    'SELECT "Kolom presensi.lokasi_pulang sudah ada, skip." AS info',
    'ALTER TABLE `presensi` ADD COLUMN `lokasi_pulang` VARCHAR(255) DEFAULT NULL AFTER `foto_pulang`'
);

PREPARE stmt FROM @ddl_lokasi_pulang;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
