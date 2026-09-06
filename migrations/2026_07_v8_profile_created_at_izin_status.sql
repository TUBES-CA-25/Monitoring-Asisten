-- ==================================================================
-- Migration v8: Kolom & nilai yang sebelumnya ditambahkan langsung ke
-- database pengembangan TANPA file migrasi (ditemukan lewat perbandingan
-- dump database lama vs sekarang - lihat iclabs_db_lama*.sql vs iclabs_db.sql)
-- ==================================================================
-- 1. `profile.created_at` - dipakai LogbookModel::getUnifiedLogbook() dan
--    UserModel::calculateRealAlpha() sebagai titik mulai default sebelum
--    reset presensi (attendance_reset_at, ditambahkan di v7, override-nya
--    jika ADA & lebih baru).
-- 2. `presensi.status` ENUM - nilai 'Izin' ditambahkan (sebelumnya hanya
--    'Hadir','Alpa','Terlambat'). Dipakai AttendanceModel saat mencatat
--    presensi pada hari asisten sedang izin/sakit yang disetujui.
--
-- Kompatibilitas API mobile: keduanya ADDITIVE (kolom baru / nilai enum
-- baru) - endpoint yang memakai SELECT * (AttendanceApi, dll) otomatis
-- ikut menerima kolom ini tanpa perlu perubahan kode.
--
-- Cara pakai:
--   Jalankan skrip ini SATU KALI pada database yang sudah ada (atau lewat
--   `php migrate.php` di root project, yang menjalankan semua migrasi
--   migrations/*.sql secara berurutan & otomatis melewati yang sudah
--   diterapkan). Aman dijalankan berulang (idempotent, cek information_schema).
-- ==================================================================

-- 1. profile.created_at
SET @has_created_at := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'profile'
      AND COLUMN_NAME  = 'created_at'
);

SET @has_reset_at := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'profile'
      AND COLUMN_NAME  = 'attendance_reset_at'
);

SET @ddl_created_at := IF(
    @has_created_at > 0,
    'SELECT "Kolom profile.created_at sudah ada, skip." AS info',
    IF(
        @has_reset_at > 0,
        'ALTER TABLE `profile` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `attendance_reset_at`',
        'ALTER TABLE `profile` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP'
    )
);

PREPARE stmt FROM @ddl_created_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. presensi.status - tambah nilai enum 'Izin'
SET @has_izin_value := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'presensi'
      AND COLUMN_NAME  = 'status'
      AND COLUMN_TYPE LIKE '%Izin%'
);

SET @ddl_izin := IF(
    @has_izin_value > 0,
    'SELECT "Nilai ''Izin'' sudah ada di enum presensi.status, skip." AS info',
    "ALTER TABLE `presensi` MODIFY COLUMN `status` ENUM('Hadir','Alpa','Terlambat','Izin') DEFAULT 'Hadir'"
);

PREPARE stmt FROM @ddl_izin;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
