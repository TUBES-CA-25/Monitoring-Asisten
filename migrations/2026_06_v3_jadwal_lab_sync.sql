-- ==================================================================
-- Migration: Tahap 12 — Modul 5 lanjutan (Auto-add Jadwal Lab ke
-- Google Calendar setiap asisten)
-- ==================================================================
-- Latar belakang:
--   Sebelumnya, Jadwal Lab/Umum disinkronkan sebagai SATU event di Google
--   Calendar Admin pembuat, dengan SEMUA asisten diundang sebagai
--   "attendees" (perlu accept undangan).
--
--   Sekarang:
--     - Event "master" tetap di kalender Admin, tapi attendee-nya
--       berubah jadi user dengan role='Kepala Lab' (perlu approve
--       undangan, sesuai permintaan).
--     - Untuk setiap asisten (role='User') yang sudah menghubungkan akun
--       Google (punya baris di `user_google_token`), dibuatkan SALINAN
--       event terpisah LANGSUNG di kalender mereka sendiri (memakai token
--       OAuth milik asisten tersebut) - otomatis muncul tanpa perlu
--       approve undangan.
--
--   Karena satu Jadwal Lab -> banyak salinan event (satu per asisten),
--   `jadwal_full` (yang hanya menyimpan SATU google_event_id/sync_status
--   per jadwal) tidak cukup. Tabel baru `jadwal_lab_sync` menyimpan status
--   sinkronisasi PER ASISTEN per Jadwal Lab.
--
-- Cara pakai:
--   Jalankan skrip ini SATU KALI pada database ICLABS-WEB yang sudah ada.
--   Aman dijalankan berulang (CREATE TABLE IF NOT EXISTS).
-- ==================================================================

CREATE TABLE IF NOT EXISTS `jadwal_lab_sync` (
    `id_sync` INT(11) NOT NULL AUTO_INCREMENT,
    `id_jadwal_lab` INT(11) NOT NULL,
    `id_user` INT(11) NOT NULL,
    `google_event_id` VARCHAR(255) DEFAULT NULL,
    `sync_status` ENUM('synced','failed','skipped') NOT NULL DEFAULT 'skipped',
    PRIMARY KEY (`id_sync`),
    UNIQUE KEY `uniq_jadwal_user` (`id_jadwal_lab`, `id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
