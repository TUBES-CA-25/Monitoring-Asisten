-- ==================================================================
-- Migration v11: Kolom `nidn_nip` pada tabel `profile` - dipakai oleh
-- Admin & Kepala Lab di halaman Edit Profil sendiri (common/edit_profile.php,
-- field wajib "NIDN/NIP"). Kolom ini SEBELUMNYA TIDAK PERNAH ADA di skema -
-- form sudah mengirim nilainya sejak lama, tapi controller/model tidak
-- pernah membacanya sama sekali (lihat UserModel::updateSelfProfile()),
-- sehingga apa pun yang diketik admin di field ini selalu hilang begitu
-- disimpan. Ditemukan lewat audit menyeluruh alur simpan profil (poin 6).
--
-- ADDITIVE (kolom baru, nullable) - tidak memengaruhi endpoint yang ada.
--
-- Cara pakai: php migrate.php
-- ==================================================================

SET @has_nidn_nip := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'profile'
      AND COLUMN_NAME  = 'nidn_nip'
);

SET @ddl_nidn_nip := IF(
    @has_nidn_nip > 0,
    'SELECT "Kolom profile.nidn_nip sudah ada, skip." AS info',
    'ALTER TABLE `profile` ADD COLUMN `nidn_nip` VARCHAR(30) DEFAULT NULL AFTER `nim`'
);

PREPARE stmt FROM @ddl_nidn_nip;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
