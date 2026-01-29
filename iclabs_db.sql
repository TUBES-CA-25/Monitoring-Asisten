-- 1. Buat Database
CREATE DATABASE IF NOT EXISTS iclabs_db;
USE iclabs_db;

-- ==========================================
-- BAGIAN 1: TABEL MASTER
-- ==========================================

-- Tabel: Lab
CREATE TABLE lab (
    id_lab INT AUTO_INCREMENT PRIMARY KEY,
    nama_lab VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    lokasi VARCHAR(255)
) ENGINE=InnoDB;

-- Tabel: User
CREATE TABLE user (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Hash Bcrypt
    role ENUM('Super Admin', 'Admin', 'User') NOT NULL DEFAULT 'User',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel: QR_Code
-- Catatan: Logika generate ulang (5 menit / 24 jam) ditangani oleh Controller PHP (Cron Job/Loop).
-- Tabel ini hanya menyimpan data token yang valid beserta waktu kadaluarsanya.
CREATE TABLE qr_code (
    id_qr INT AUTO_INCREMENT PRIMARY KEY,
    tipe ENUM('Presensi', 'Pulang') NOT NULL,
    token_code VARCHAR(255) NOT NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    valid_until DATETIME NOT NULL, -- Kolom vital untuk pengecekan validitas (5 min vs 24 hours)
    INDEX (token_code) -- Indexing untuk pencarian cepat saat scan
) ENGINE=InnoDB;

-- ==========================================
-- BAGIAN 2: DATA DIRI & KEGIATAN
-- ==========================================

-- Tabel: Profile
CREATE TABLE profile (
    id_profil INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_lab INT NULL,
    nim VARCHAR(20) UNIQUE,
    nama VARCHAR(150) NOT NULL,
    kelas CHAR(5),
    alamat VARCHAR(255),
    no_telp VARCHAR(15),
    jenis_kelamin ENUM('L', 'P'),
    jabatan ENUM('Kepala Lab', 'Laboran', 'Asisten Lab', 'Anggota'),
    peminatan ENUM('RPL', 'Jaringan', 'Multimedia', 'AI') NULL,
    photo_profile VARCHAR(255) DEFAULT 'default.jpg',
    is_completed TINYINT(1) DEFAULT 0 COMMENT '0=Belum Lengkap, 1=Sudah Lengkap',
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_lab) REFERENCES lab(id_lab) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Tabel: Presensi
CREATE TABLE presensi (
    id_presensi INT AUTO_INCREMENT PRIMARY KEY,
    id_profil INT NOT NULL,
    tanggal DATE NOT NULL,
    waktu_presensi TIME,
    foto_presensi VARCHAR(255),
    waktu_pulang TIME,
    foto_pulang VARCHAR(255),
    status ENUM('Hadir', 'Alpa', 'Terlambat') DEFAULT 'Hadir',
    FOREIGN KEY (id_profil) REFERENCES profile(id_profil) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel: Izin
CREATE TABLE izin (
    id_izin INT AUTO_INCREMENT PRIMARY KEY,
    id_profil INT NOT NULL,
    tipe ENUM('Izin', 'Sakit') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    deskripsi TEXT,
    file_bukti VARCHAR(255),
    status_approval ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    FOREIGN KEY (id_profil) REFERENCES profile(id_profil) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel: Logbook
CREATE TABLE logbook (
    id_logbook INT AUTO_INCREMENT PRIMARY KEY,
    id_profil INT NOT NULL,
    id_presensi INT NOT NULL,
    detail_aktivitas TEXT NOT NULL,
    keterangan TEXT,
    is_verified BOOLEAN DEFAULT 0,
    FOREIGN KEY (id_profil) REFERENCES profile(id_profil) ON DELETE CASCADE,
    FOREIGN KEY (id_presensi) REFERENCES presensi(id_presensi) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- BAGIAN 3: PENJADWALAN
-- ==========================================

-- Tabel: Jadwal Kuliah (Jadwal Pribadi)
CREATE TABLE jadwal_kuliah (
    id_jadwal_kuliah INT AUTO_INCREMENT PRIMARY KEY,
    id_profil INT NOT NULL,
    matkul VARCHAR(100) NOT NULL,
    tipe ENUM('Teori', 'Praktikum') DEFAULT 'Teori',
    dosen VARCHAR(100),
    ruangan VARCHAR(20),
    hari TINYINT NULL COMMENT '1=Senin, 7=Minggu',
    tanggal DATE NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (id_profil) REFERENCES profile(id_profil) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel: Jadwal Piket
CREATE TABLE jadwal_piket (
    id_jadwal_piket INT AUTO_INCREMENT PRIMARY KEY,
    id_profil INT NOT NULL,
    subjek VARCHAR(150) NOT NULL,
    hari TINYINT NULL,
    tanggal DATE NULL,
    FOREIGN KEY (id_profil) REFERENCES profile(id_profil) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel: Jadwal Asisten (Jadwal Jaga Lab)
CREATE TABLE jadwal_asisten (
    id_jadwal_asisten INT AUTO_INCREMENT PRIMARY KEY,
    id_profil INT NOT NULL,
    prodi VARCHAR(50),
    mata_kuliah VARCHAR(100) NOT NULL,
    dosen VARCHAR(100),
    kelas_lab CHAR(5),
    frekuensi VARCHAR(15),
    ruangan_lab VARCHAR(50),
    hari TINYINT NULL,
    tanggal DATE NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (id_profil) REFERENCES profile(id_profil) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel: Jadwal Full (Agregasi)
CREATE TABLE jadwal_full (
    id_jadwal INT AUTO_INCREMENT PRIMARY KEY,
    id_jadwal_kuliah INT NULL,
    id_jadwal_piket INT NULL,
    id_jadwal_asisten INT NULL,
    google_calendar_API VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_jadwal_kuliah) REFERENCES jadwal_kuliah(id_jadwal_kuliah) ON DELETE CASCADE,
    FOREIGN KEY (id_jadwal_piket) REFERENCES jadwal_piket(id_jadwal_piket) ON DELETE CASCADE,
    FOREIGN KEY (id_jadwal_asisten) REFERENCES jadwal_asisten(id_jadwal_asisten) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================
-- BAGIAN 4: SEEDING DATA (DENGAN PASSWORD 'password')
-- ==========================================

-- 1. Insert Lab
INSERT INTO lab (nama_lab, deskripsi, lokasi) VALUES 
('Lab RPL', 'Rekayasa Perangkat Lunak', 'Gedung A'),
('Lab Jaringan', 'Network & Security', 'Gedung B');

-- 2. Insert User (Password default untuk semua: "password")
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO user (email, password, role) VALUES 
('super@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin'),
('admin@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'),
('user@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User');

-- 3. Insert Profile
INSERT INTO profile (id_user, id_lab, nim, nama, jabatan, jenis_kelamin) VALUES 
(1, NULL, 'SA001', 'Super Admin', 'Kepala Lab', 'L'),
(2, 1, 'ADM001', 'Admin RPL', 'Laboran', 'P'),
(3, 1, '1302021', 'Andi Asisten', 'Asisten Lab', 'L');

-- 4. Insert QR Code Dummy (Untuk testing logika waktu)
-- Token Masuk (Presensi): Valid 5 Menit dari sekarang
INSERT INTO qr_code (tipe, token_code, generated_at, valid_until) VALUES 
('Presensi', 'TOKEN-MASUK-12345', NOW(), DATE_ADD(NOW(), INTERVAL 5 MINUTE));

-- Token Pulang: Valid 24 Jam dari sekarang (Sehari sekali)
INSERT INTO qr_code (tipe, token_code, generated_at, valid_until) VALUES 
('Pulang', 'TOKEN-PULANG-67890', NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR));

-- 5. Insert Jadwal Dummy
INSERT INTO jadwal_asisten (id_profil, prodi, mata_kuliah, dosen, ruangan_lab, tanggal, start_time, end_time) VALUES
(3, 'TI', 'Web Dev', 'Pak Budi', 'Lab RPL', CURDATE(), '08:00', '10:00');

INSERT INTO jadwal_full (id_jadwal_asisten) VALUES (LAST_INSERT_ID());

-- 1. Tambahkan kolom 'hari' dan izinkan tanggal NULL untuk Jadwal Kuliah
-- ALTER TABLE jadwal_kuliah 
-- ADD COLUMN hari TINYINT NULL COMMENT '1=Senin, 7=Minggu' AFTER ruangan,
-- MODIFY COLUMN tanggal DATE NULL;

-- 2. Lakukan hal yang sama untuk Jadwal Asisten & Piket (Agar konsisten)
-- ALTER TABLE jadwal_asisten 
-- ADD COLUMN hari TINYINT NULL AFTER ruangan_lab,
-- MODIFY COLUMN tanggal DATE NULL;

-- ALTER TABLE jadwal_piket 
-- ADD COLUMN hari TINYINT NULL AFTER subjek,
-- MODIFY COLUMN tanggal DATE NULL;

-- ALTER TABLE profile 
-- ADD COLUMN is_completed TINYINT(1) DEFAULT 0 COMMENT '0=Belum Lengkap, 1=Sudah Lengkap';