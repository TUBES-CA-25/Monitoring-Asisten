-- ============================================================
-- ICLABS Migration v7: Perbaikan konsistensi indikator kehadiran
-- Jalankan sekali setelah backup database.
-- ============================================================

-- 1. Tambah kolom attendance_reset_at ke profile
--    Digunakan oleh calculateRealAlpha sebagai titik mulai perhitungan
--    alpha setelah reset, sehingga alpha = 0 setelah reset presensi.
ALTER TABLE `profile`
    ADD COLUMN IF NOT EXISTS `attendance_reset_at` DATETIME NULL DEFAULT NULL
    COMMENT 'Waktu terakhir data presensi di-reset. calculateRealAlpha mulai dari tanggal ini, bukan dari created_at.'
    AFTER `is_completed`;

-- 2. Tambah kolom data_izin ke attendance_recycle_bin
--    Agar izin juga diarsipkan (dan bisa di-restore) saat reset.
ALTER TABLE `attendance_recycle_bin`
    ADD COLUMN IF NOT EXISTS `data_izin` LONGTEXT NULL DEFAULT NULL
    COMMENT 'JSON array data izin yang turut di-reset'
    AFTER `data_logbook`,
    ADD COLUMN IF NOT EXISTS `jumlah_izin` INT DEFAULT 0
    COMMENT 'Jumlah record izin yang diarsipkan'
    AFTER `jumlah_logbook`;
