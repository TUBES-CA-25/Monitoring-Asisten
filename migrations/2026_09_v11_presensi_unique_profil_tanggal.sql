-- ==================================================================
-- Migration v11: Unique Constraint pada tabel presensi
-- Menghilangkan race condition pada saat check-in ganda (DATA-01).
--
-- Sebelum membuat unique index, bersihkan data duplikat terdahulu (jika ada)
-- dengan mempertahankan baris id_presensi terkecil.
--
-- Idempoten: aman dijalankan berulang kali via php migrate.php.
-- ==================================================================

-- 1. Bersihkan duplikat terdahulu jika ada
DELETE p1 FROM `presensi` p1
INNER JOIN `presensi` p2 
WHERE p1.id_profil = p2.id_profil 
  AND p1.tanggal = p2.tanggal 
  AND p1.id_presensi > p2.id_presensi;

-- 2. Pasang indeks unik komposit
SET @has_uq_presensi := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'presensi'
      AND INDEX_NAME   = 'uq_profil_tanggal'
);

SET @ddl_uq_presensi := IF(
    @has_uq_presensi > 0,
    'SELECT "Unique index uq_profil_tanggal sudah ada, skip." AS info',
    'ALTER TABLE `presensi` ADD UNIQUE KEY `uq_profil_tanggal` (`id_profil`, `tanggal`)'
);

PREPARE stmt FROM @ddl_uq_presensi;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
