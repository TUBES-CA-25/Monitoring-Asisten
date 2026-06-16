-- ==================================================================
-- Migration: Tahap 5 — Tambah kolom `angkatan` pada tabel `profile`
-- ==================================================================
-- Latar belakang:
--   Sesuai SPESIFIKASI_ICLABS_WEB_V3 (Modul Profile -> Asisten) dan
--   RANCANGAN_PERUBAHAN_ICLABS_WEB_V3 (poin 7 - Role Based Profile
--   Management), data profil Asisten harus mencakup "angkatan"
--   (tahun masuk/cohort), selain Nama, NIM (stambuk), Kelas,
--   Peminatan, Laboratorium, dan Jabatan yang sudah ada.
--
-- Cara pakai:
--   Jalankan skrip ini SATU KALI pada database ICLABS-WEB yang sudah
--   ada (XAMPP/MAMP/production). Aman dijalankan berulang karena
--   memakai pengecekan IF NOT EXISTS pada information_schema.
-- ==================================================================

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'profile'
      AND COLUMN_NAME  = 'angkatan'
);

SET @ddl := IF(
    @col_exists = 0,
    'ALTER TABLE `profile` ADD COLUMN `angkatan` VARCHAR(4) DEFAULT NULL AFTER `kelas`',
    'SELECT "Kolom angkatan sudah ada, skip." AS info'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
