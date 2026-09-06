-- ==================================================================
-- Migration v12: Perbaikan referensi foto profil placeholder yang sudah
-- tidak ada filenya - "public/uploads/profile/default.jpg" diganti nama
-- filenya secara manual menjadi "default.webp", tapi:
--   1. Kolom `profile`.`photo_profile` masih ber-DEFAULT string
--      'default.jpg' (dipakai MySQL sendiri kalau kolom tidak diisi
--      eksplisit saat INSERT).
--   2. Baris-baris LAMA yang photo_profile-nya literal 'default.jpg'
--      (karena disimpan begitu oleh kode sebelum konstanta
--      DEFAULT_PROFILE_PHOTO ditambahkan) tetap merujuk ke nama file lama.
-- Keduanya membuat foto placeholder "hilang" (fallback ke avatar acak)
-- meski sebenarnya cuma nama filenya yang tidak sinkron.
--
-- ADDITIVE & aman dijalankan berulang - hanya mengubah DEFAULT kolom dan
-- baris yang MEMANG masih 'default.jpg'.
--
-- Cara pakai: php migrate.php
-- ==================================================================

ALTER TABLE `profile` ALTER COLUMN `photo_profile` SET DEFAULT 'default.webp';

UPDATE `profile` SET `photo_profile` = 'default.webp' WHERE `photo_profile` = 'default.jpg';
