-- ==================================================================
-- Migration v10: Kolom `no_hp` pada tabel `dosen` - ditemukan lewat
-- perbandingan database live vs hasil migrasi (iclabs_migration_check),
-- ditambahkan langsung ke database TANPA file migrasi sebelumnya (pola
-- yang sama seperti kasus v8 - lihat catatan di file itu). Belum dipakai
-- kode manapun saat ini (grep app/ & public/assets/js/ tidak menemukan
-- referensi `no_hp`) - ditambahkan di sini murni supaya struktur database
-- hasil deploy ulang lewat migrate.php tetap identik dengan database live.
--
-- ADDITIVE (kolom baru, nullable) - tidak memengaruhi endpoint yang ada.
--
-- Cara pakai: php migrate.php
-- ==================================================================

SET @has_no_hp := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'dosen'
      AND COLUMN_NAME  = 'no_hp'
);

SET @ddl_no_hp := IF(
    @has_no_hp > 0,
    'SELECT "Kolom dosen.no_hp sudah ada, skip." AS info',
    'ALTER TABLE `dosen` ADD COLUMN `no_hp` VARCHAR(20) DEFAULT NULL AFTER `email`'
);

PREPARE stmt FROM @ddl_no_hp;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
