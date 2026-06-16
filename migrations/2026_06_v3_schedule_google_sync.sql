-- ==================================================================
-- Migration: Tahap 11 — Modul 5 (Jadwal & Google Calendar Update)
-- ==================================================================
-- Latar belakang:
--   Sesuai RANCANGAN_PERUBAHAN_ICLABS_WEB_V3 poin 5, sinkronisasi Google
--   Calendar (Jadwal Asisten/Piket/Lab/Kuliah) kini melacak status sync
--   per item jadwal lewat 2 kolom di tabel `jadwal_full`:
--     - `google_event_id` : ID event Google Calendar (sebelumnya bernama
--                            `google_calendar_API`, di-rename agar sesuai
--                            penamaan di spesifikasi).
--     - `sync_status`     : ENUM('synced','failed','skipped')
--                              - synced  = event berhasil dibuat/diupdate
--                                          di Google Calendar.
--                              - failed  = sinkronisasi GAGAL (akun Google
--                                          terhubung tapi createEvent/
--                                          updateEvent error) - kandidat
--                                          "Retry failed sync".
--                              - skipped = pemilik jadwal belum
--                                          menghubungkan akun Google
--                                          (tidak ada token), sinkronisasi
--                                          tidak dicoba.
--
-- Cara pakai:
--   Jalankan skrip ini SATU KALI pada database ICLABS-WEB yang sudah ada.
--   Aman dijalankan berulang (idempotent, cek information_schema).
-- ==================================================================

-- 1. google_event_id: rename dari google_calendar_API jika ada, atau
--    tambah kolom baru jika belum ada sama sekali.
SET @has_event_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'jadwal_full'
      AND COLUMN_NAME  = 'google_event_id'
);

SET @has_old_col := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'jadwal_full'
      AND COLUMN_NAME  = 'google_calendar_API'
);

SET @ddl_event_id := IF(
    @has_event_id > 0,
    'SELECT "Kolom google_event_id sudah ada, skip." AS info',
    IF(
        @has_old_col > 0,
        'ALTER TABLE `jadwal_full` CHANGE COLUMN `google_calendar_API` `google_event_id` VARCHAR(255) DEFAULT NULL',
        'ALTER TABLE `jadwal_full` ADD COLUMN `google_event_id` VARCHAR(255) DEFAULT NULL'
    )
);

PREPARE stmt FROM @ddl_event_id;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- 2. sync_status: tambah kolom baru, default 'skipped' (data lama belum
--    pernah dicek statusnya).
SET @has_sync_status := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'jadwal_full'
      AND COLUMN_NAME  = 'sync_status'
);

SET @ddl_sync_status := IF(
    @has_sync_status > 0,
    'SELECT "Kolom sync_status sudah ada, skip." AS info',
    'ALTER TABLE `jadwal_full` ADD COLUMN `sync_status` ENUM(\'synced\',\'failed\',\'skipped\') NOT NULL DEFAULT \'skipped\' AFTER `google_event_id`'
);

PREPARE stmt FROM @ddl_sync_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
