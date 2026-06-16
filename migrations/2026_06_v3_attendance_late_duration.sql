-- ==================================================================
-- Migration: Tahap 6 — Modul 1 (Attendance/Presensi System Update)
-- ==================================================================
-- Latar belakang:
--   Sesuai RANCANGAN_PERUBAHAN_ICLABS_WEB_V3 poin 1 (Attendance/Presensi
--   System Update), presensi kini menghitung keterlambatan & durasi kerja
--   secara otomatis:
--     - `late_minutes`  : jumlah menit terlambat saat check-in (0 jika
--                          tepat waktu/lebih awal).
--     - `work_duration` : durasi kerja dalam menit, dihitung saat
--                          check-out (selisih waktu_pulang - waktu_presensi).
--
--   Kolom `presensi.status` (ENUM 'Hadir','Alpa','Terlambat','Izin') yang
--   sudah ada DIPAKAI LANGSUNG sebagai "attendance_status" — TIDAK ada
--   kolom attendance_status baru/duplikat. Status ALPHA tetap dihitung
--   on-demand (mengikuti pola UserModel::calculateRealAlpha yang sudah
--   ada), bukan lewat insert otomatis/cron.
--
-- Kompatibilitas API mobile:
--   AttendanceApi::today()/history() memakai `SELECT *`, sehingga kedua
--   kolom baru ini otomatis ikut pada response tanpa perlu mengubah kode
--   API — bersifat ADDITIVE sesuai prinsip kompatibilitas mobile.
--
-- Cara pakai:
--   Jalankan skrip ini SATU KALI pada database ICLABS-WEB yang sudah ada.
--   Aman dijalankan berulang (idempotent, cek information_schema).
-- ==================================================================

SET @col_late := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'presensi'
      AND COLUMN_NAME  = 'late_minutes'
);

SET @ddl_late := IF(
    @col_late = 0,
    'ALTER TABLE `presensi` ADD COLUMN `late_minutes` INT(11) DEFAULT NULL AFTER `status`',
    'SELECT "Kolom late_minutes sudah ada, skip." AS info'
);

PREPARE stmt FROM @ddl_late;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


SET @col_dur := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'presensi'
      AND COLUMN_NAME  = 'work_duration'
);

SET @ddl_dur := IF(
    @col_dur = 0,
    'ALTER TABLE `presensi` ADD COLUMN `work_duration` INT(11) DEFAULT NULL AFTER `late_minutes`',
    'SELECT "Kolom work_duration sudah ada, skip." AS info'
);

PREPARE stmt FROM @ddl_dur;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
