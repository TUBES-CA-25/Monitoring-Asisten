-- ==================================================================
-- Migration: Tahap 20 — Modul Dosen (Master Data Dosen Pengampu)
-- ==================================================================
-- Latar belakang:
--   Sebelumnya `jadwal_kuliah.dosen` dan `jadwal_asisten.dosen` adalah
--   VARCHAR bebas, sehingga nama dosen yang sama bisa tertulis berbeda-beda
--   (contoh nyata di data: "Lutfi Budi Ilmawan, S.Kom., M.Cs., MTA" vs
--   "Lutfi Budiawan", dan "A Ulfah Tenripada Syahar, S.Kom.,M.Kom., MTA" vs
--   "A Ulfa Tenripada Syahar, S.Kom., M.Kom., MTA"). Migrasi ini menambahkan
--   tabel master `dosen` + kolom `id_dosen` (FK) di kedua tabel jadwal,
--   agar input dosen pengampu menjadi dropdown dengan data seragam.
--
--   Kolom `dosen` (VARCHAR) di kedua tabel TETAP DIPERTAHANKAN sebagai
--   "cache" nama yang otomatis disinkronkan dari `dosen.nama_dosen` setiap
--   kali jadwal disimpan lewat dropdown - sehingga SEMUA kode yang membaca
--   `dosen` (PDF export, API mobile, dll) TIDAK PERLU diubah.
--
-- Cara pakai:
--   Jalankan skrip ini SATU KALI pada database ICLABS-WEB yang sudah ada.
--   Aman dijalankan berulang (idempotent).
-- ==================================================================

-- 1. Tabel master dosen ------------------------------------------------
CREATE TABLE IF NOT EXISTS `dosen` (
  `id_dosen` int(11) NOT NULL AUTO_INCREMENT,
  `nama_dosen` varchar(150) NOT NULL,
  `nidn` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_dosen`),
  UNIQUE KEY `nama_dosen` (`nama_dosen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Data dosen Internal FIKOM (43 dosen) -------------------------------
-- INSERT IGNORE: aman dijalankan berulang - baris dengan nama_dosen yang
-- sudah ada (UNIQUE KEY) akan dilewati, bukan error.
INSERT IGNORE INTO `dosen` (`nama_dosen`, `nidn`, `email`) VALUES
('Dr. Ir. Purnawansyah, M.Kom', '0919027301', 'purnawansyah@umi.ac.id'),
('Ir. Yulita Salim, S.Kom., M.T., MTA', '0922078101', 'yulita.salim@umi.ac.id'),
('Dr. Ir. Harlinda L, S.Kom., M.M., M.Kom., MTA', '0114000775', 'harlinda@umi.ac.id'),
('Poetri Lestari LB, S.Kom., M.T., MTA', '0916108403', 'poetrilestari@umi.ac.id'),
('Dr. Andi Sumardin, S.Ag., M.A', '0915087302', NULL),
('Dr. Tasrif Hasanuddin, S.T., M.Cs', '0910126901', 'tasrif.hasanuddin@umi.ac.id'),
('Dr. Ir. Dolly Indra, S.Kom., M.M.SI., MTA', '0428077401', 'dolly.indra@umi.ac.id'),
('Herman, S.Kom., M.Cs., MTA', '0913038506', 'herman@umi.ac.id'),
('Ir. Abdul Rachman Manga'', S.Kom., M.T., MTA', '0931018001', 'abdulrachman.manga@umi.ac.id'),
('Ir. Huzain Azis, S.Kom., M.Cs., MTA', '0920098801', 'huzain.azis@umi.ac.id'),
('Ir. Dedy Atmajaya, S.Kom., M.Eng., MTA', '0917068601', 'dedy.atmajaya@umi.ac.id'),
('Ir. Farniwati Fattah, S.T., M.T., MTA', '0911098601', 'farniwati.fattah@umi.ac.id'),
('Mardiyyah Hasnawi, S.Kom., M.T., MTA', '0906078701', 'mardiyyah.hasnawi@umi.ac.id'),
('Lilis Nur Hayati, S.Kom., M.Eng., MTA', '0906048205', 'lilis.nurhayati@umi.ac.id'),
('Siska Anraeni, S.Kom., M.T., MCF.', '0922088701', 'siska.anraeni@umi.ac.id'),
('Dr. Ramdan Satra, S.Kom., M.Kom., MTA', '0919056501', 'ramdan@umi.ac.id'),
('Muh. Aliyazid Mude, S.Kom., M.Kom.', '0920107601', 'aliyazid.mude@umi.ac.id'),
('Irawati, S.Kom., M.T., MTA', '0915028503', 'irawan2801@gmail.com'),
('Ir. St. Hajrah Mansyur, S.Kom., M.Cs., MTA', '0919018501', 'hajrah.mansyur@umi.ac.id'),
('Syahrul Mubarak, S.Kom., M.Kom., MTA', '0926048704', 'syahrul.mubarak@umi.ac.id'),
('Ir. Nia Kurniati, M.Kom., MTA', '0915068601', 'nia.kurniati@umi.ac.id'),
('Sugiarti, S.Kom., M.Kom., MTA', '0924048501', 'sugiarti.sugiarti@umi.ac.id'),
('Ir. Erick Irawadi Alwi, S.Kom., M.Eng., MTA', '0906128504', 'erick.alwi@umi.ac.id'),
('Lutfi Budi Ilmawan, S.Kom., M.Cs., MTA', '0921018902', 'lutfibudi.ilmawan@umi.ac.id'),
('Herdianti, S.Si., M.Eng., MTA', '0924069001', 'herdianti.darwis@umi.ac.id'),
('Fitriyani Umar, S.Si., M.Eng., MTA', '0922078801', 'fitryani.umar@umi.ac.id'),
('Ir. Lukman Syafie, S.Si., M.Si., MTA', '0922118003', 'lukman.syafie@umi.ac.id'),
('Wistiani Astuti, S.Kom., M.T., MTA', '0907018602', 'wistiani.astuti@umi.ac.id'),
('A Ulfa Tenripada Syahar, S.Kom., M.Kom., MTA', '0908089202', 'a.ulfah@umi.ac.id'),
('Ihwana Asad S.Ag., M.Sc. P.hD., MTA', '2107057202', 'ihwana.asad@umi.ac.id'),
('Andi Widya Mufila Gaffar, S.T., M.Kom., MTA', '0908099401', 'widya.mufila@umi.ac.id'),
('Ramdaniah, S.Kom., M.T., MTA', '0911039301', 'ramdaniah@umi.ac.id'),
('Muhammad Arfah Asis, S.Kom., M.T., MTA', '0909029203', 'muh.arfah.asis@umi.ac.id'),
('Amaliah Faradibah, S.Kom., M.Kom., MTA., MCF', '0924049303', 'amaliah.faradibah@umi.ac.id'),
('Sitti Rahmah Jabir, S.M., M.Sc., MTA., MCF', '0918109501', 'rahmahjabir@umi.ac.id'),
('Dewi Widyawati, S.Kom., M.Kom., MTA., MCF', '0901019302', 'dewiwidyawati@umi.ac.id'),
('Hadriana Iddas, S.T., M.T., Ph.D', '0922067801', 'hadriana.iddas@umi.ac.id'),
('Syariful Mujaddid, S.Kom., M.T', NULL, NULL),
('Fahmi, S.Kom., M.T', NULL, NULL),
('Fadly Kasim, S.T., M.Kom', NULL, NULL),
('Rabiatul Adawiyah, S.Si., M.Si', NULL, NULL),
('Suwito Pomalingo, S.Kom., M.Kom., MTA', NULL, NULL),
('Muhammad Nasry Ashar, S.Kom., M.Kom', NULL, NULL);


-- 3. Kolom id_dosen di jadwal_kuliah & jadwal_asisten -------------------
SET @has_col_jk := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jadwal_kuliah' AND COLUMN_NAME = 'id_dosen'
);
SET @ddl_jk := IF(@has_col_jk > 0,
    'SELECT "Kolom jadwal_kuliah.id_dosen sudah ada, skip." AS info',
    'ALTER TABLE `jadwal_kuliah` ADD COLUMN `id_dosen` int(11) DEFAULT NULL AFTER `dosen`'
);
PREPARE stmt FROM @ddl_jk; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_col_ja := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jadwal_asisten' AND COLUMN_NAME = 'id_dosen'
);
SET @ddl_ja := IF(@has_col_ja > 0,
    'SELECT "Kolom jadwal_asisten.id_dosen sudah ada, skip." AS info',
    'ALTER TABLE `jadwal_asisten` ADD COLUMN `id_dosen` int(11) DEFAULT NULL AFTER `dosen`'
);
PREPARE stmt FROM @ddl_ja; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 4. Foreign key constraints --------------------------------------------
-- ON DELETE SET NULL: jika baris dosen master dihapus (di luar scope saat
-- ini - belum ada UI hapus dosen), jadwal terkait TIDAK ikut terhapus,
-- hanya id_dosen jadi NULL (kolom `dosen` VARCHAR/cache tetap menyimpan
-- nama lama sebagai jejak).
SET @has_fk_jk := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jadwal_kuliah' AND CONSTRAINT_NAME = 'fk_jadwal_kuliah_dosen'
);
SET @ddl_fk_jk := IF(@has_fk_jk > 0,
    'SELECT "FK fk_jadwal_kuliah_dosen sudah ada, skip." AS info',
    'ALTER TABLE `jadwal_kuliah` ADD CONSTRAINT `fk_jadwal_kuliah_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE SET NULL'
);
PREPARE stmt FROM @ddl_fk_jk; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_fk_ja := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jadwal_asisten' AND CONSTRAINT_NAME = 'fk_jadwal_asisten_dosen'
);
SET @ddl_fk_ja := IF(@has_fk_ja > 0,
    'SELECT "FK fk_jadwal_asisten_dosen sudah ada, skip." AS info',
    'ALTER TABLE `jadwal_asisten` ADD CONSTRAINT `fk_jadwal_asisten_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`id_dosen`) ON DELETE SET NULL'
);
PREPARE stmt FROM @ddl_fk_ja; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 5. Migrasi data lama: cocokkan dosen (VARCHAR, di-TRIM) dengan dosen.nama_dosen
--    secara EXACT MATCH. Aman dijalankan berulang (hanya mengisi yang masih NULL).
UPDATE `jadwal_kuliah` jk
JOIN `dosen` d ON TRIM(jk.dosen) = d.nama_dosen
SET jk.id_dosen = d.id_dosen
WHERE jk.id_dosen IS NULL;

UPDATE `jadwal_asisten` ja
JOIN `dosen` d ON TRIM(ja.dosen) = d.nama_dosen
SET ja.id_dosen = d.id_dosen
WHERE ja.id_dosen IS NULL;


-- ==================================================================
-- 6. REVIEW MANUAL - jalankan SETELAH migrasi di atas selesai.
-- ==================================================================
-- Query di bawah menampilkan baris jadwal yang punya nilai `dosen` (teks)
-- tapi TIDAK ketemu pasangannya secara exact-match di tabel `dosen`
-- (kemungkinan salah ketik / beda format gelar, mis. "Lutfi Budiawan" yang
-- seharusnya "Lutfi Budi Ilmawan, S.Kom., M.Cs., MTA").
--
-- Untuk setiap baris yang muncul, pilih salah satu:
--   a) UPDATE jadwal_kuliah/jadwal_asisten SET dosen = '<nama yang benar,
--      sesuai salah satu nama_dosen di tabel dosen>' WHERE id_jadwal_... = <id>;
--      lalu jalankan ulang query UPDATE ... JOIN di atas (langkah 5) agar
--      id_dosen ikut terisi.
--   b) Atau jika dosen tersebut memang BELUM ada di tabel master, tambahkan
--      dulu: INSERT INTO dosen (nama_dosen, nidn, email) VALUES (...);
--      lalu jalankan ulang langkah 5.
--
-- SELECT id_jadwal_kuliah AS id, dosen AS nama_tertulis, 'kuliah' AS jenis
-- FROM jadwal_kuliah WHERE id_dosen IS NULL AND dosen IS NOT NULL AND TRIM(dosen) != ''
-- UNION ALL
-- SELECT id_jadwal_asisten AS id, dosen AS nama_tertulis, 'asisten' AS jenis
-- FROM jadwal_asisten WHERE id_dosen IS NULL AND dosen IS NOT NULL AND TRIM(dosen) != '';
