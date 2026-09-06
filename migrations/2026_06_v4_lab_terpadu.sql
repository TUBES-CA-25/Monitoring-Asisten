-- ==================================================================
-- Tambah entri "Laboratorium Terpadu Fakultas Ilmu Komputer
-- Universitas Muslim Indonesia" ke tabel `lab`.
--
-- Tujuan: dipakai sebagai lokasi DEFAULT untuk Jadwal Piket (lihat
-- handleTypeChange() di admin/schedule.js) - admin tetap bisa memilih
-- laboratorium lain dari dropdown yang sama untuk kondisi tertentu.
-- ==================================================================

INSERT INTO `lab` (`nama_lab`, `deskripsi`, `lokasi`)
SELECT 'Laboratorium Terpadu Fakultas Ilmu Komputer Universitas Muslim Indonesia', 'Lokasi umum/terpadu untuk kegiatan piket', 'Fakultas Ilmu Komputer UMI'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `lab` WHERE `nama_lab` = 'Laboratorium Terpadu Fakultas Ilmu Komputer Universitas Muslim Indonesia'
);
