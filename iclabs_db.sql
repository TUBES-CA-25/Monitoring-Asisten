-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 01 Feb 2026 pada 11.05
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `iclabs_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','User','Kepala Lab') NOT NULL DEFAULT 'User',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id_user`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'super@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kepala Lab', '2026-01-03 05:23:53'),
(2, 'admin@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', '2026-01-03 05:23:53'),
(3, 'user@iclabs.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User', '2026-01-03 05:23:53'),
(4, 'firly@iclabs.com', '$2y$12$lFHuRuoExW9RSSBUZm4QgeZMom7v5iLKJfBAtJB68d7qPEOH1gRCm', 'User', '2026-01-27 03:37:51'),
(8, 'tasya@gmail.com', '$2y$10$FrIG2OhNk62WfTMDizJrmeF.BQU.YmZ19205mRlfqMtbCKEbIUr0u', 'User', '2026-01-30 10:12:55'),
(9, '13020230030@student.umi.ac.id', '$2y$10$q9kGmvEEf6uAMJ91aFPDWONhLwONdpYUbbCKbMtN7pgMvRO7CFZ5m', 'User', '2026-02-01 04:57:53'),
(10, '13020230049@student.umi.ac.id', '$2y$10$QyMQNxnOoftbzz5.x8MVB.2ZnTcwxGacG0pJstdLKz69GBCMMAY02', 'User', '2026-02-01 04:58:52'),
(11, '13020230081@student.umi.ac.id', '$2y$10$SJDVzEEr5P2K3PNrjUBj3uQXFDVTj4iv/ZLhGniqRT8eV5ZA.RtJi', 'User', '2026-02-01 05:01:41'),
(19, '13020230100@student.umi.ac.id', '$2y$10$rm4yLUk0/iV.2nN.Tlp8nuHBuN.GO8TSUyyER.3PBF2adUvqD40SG', 'User', '2026-02-01 05:54:57'),
(33, '13020230187@student.umi.ac.id', '$2y$10$4zT8rlhAnc8pHquvg4YC7efuBQUlQli4ni3rSibOT.9V4rDI3f4Ga', 'User', '2026-02-01 07:10:27'),
(35, '13020230193@student.umi.ac.id', '$2y$10$NFvx02lWowjrNMpfqQEG/OwWQr9WJTGp9bbBPhWXNuB/tnIJQEN2G', 'User', '2026-02-01 07:12:49'),
(37, '13020230232@student.umi.ac.id', '$2y$10$vWqCfRRXRJL.ikWsgTcr2OQGQZAahZejqvvyG6483ggH4FMXsse6q', 'User', '2026-02-01 07:44:17'),
(38, '13020230253@student.umi.ac.id', '$2y$10$kOgGxV7dXM6yxGLmV31GHO74LBC7IVBnDanyNeI1u1O0nR7KUQWEm', 'User', '2026-02-01 07:59:46'),
(39, '13020230290@student.umi.ac.id', '$2y$10$nlGgwsUchs1Bp0yuLVpE5OjP3X5X7TGX6lkAvDCpitQoyq9eMuP6O', 'User', '2026-02-01 08:01:17'),
(40, '13020230306@student.umi.ac.id', '$2y$10$xXBeox3.rzXf9K1d1wf4juq0us8hg/TQoNMECMLa6fOYGpMOxLRU2', 'User', '2026-02-01 08:02:30'),
(41, '13020230319@student.umi.ac.id', '$2y$10$02mMjh.v3HRmc/4eFfCot.BFKmcVyvzh/3dBKMfded0b/T9/.a5Sa', 'User', '2026-02-01 08:03:12'),
(42, '13020230309@student.umi.ac.id', '$2y$10$BZa3BeZSoA4Ul7EWm9s31O4q1ozwm5R3nZO9QZzscUrAr/HBuZBW6', 'User', '2026-02-01 08:04:06'),
(47, '13020230268@student.umi.ac.id', '$2y$10$8u06kLLKpdst8a87F8x/KucwntC7Kg4jOczKy6DOt3.PE9wWIpbL6', 'User', '2026-02-01 09:36:55'),
(48, '13020230096@student.umi.ac.id', '$2y$10$L86Qk6Z/3Nkr9cRy0dbSouOS8k4q5wJrhgLm2/CmImIyGJIWVihAO', 'User', '2026-02-01 09:43:20'),
(49, '13020230217@student.umi.ac.id', '$2y$10$TsPoSH/wm.5gg4oSyolEQuvTs0G0b6EoBKWr146j3V8hSP8qq/Q2K', 'User', '2026-02-01 09:58:03'),
(50, '13020230255@student.umi.ac.id', '$2y$10$kPeLjXXEEk4BuulF.5tUJugoaALORSrLPLeTIcwcKFeMekZnns8i2', 'User', '2026-02-01 09:59:02'),
(51, '13020230241@student.umi.ac.id', '$2y$10$56L78F05Mtx0hOhBSl/00u.dUqcKOn9ZEITnEXZfZCVttYFS.fgC.', 'User', '2026-02-01 10:00:02'),
(52, '13020230297@student.umi.ac.id', '$2y$10$Y8USa.xFrfgYafzSLtU37.w.WZysJmxmfCpwGHUHQTTpSFxFmOCxS', 'User', '2026-02-01 10:01:00'),
(53, '13020230244@student.umi.ac.id', '$2y$10$EO..kSWasJ30zJnXWokw4.vpT2rUKmLCbyisuieo69xkWi3LIxW4G', 'User', '2026-02-01 10:02:09'),
(54, '13120230033@student.umi.ac.id', '$2y$10$eLjv4r62WzhnFt9UnYxQqeKPt2W2PAy5w0DjFGOiM1TFhYu2PD4Ze', 'User', '2026-02-01 10:04:18');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
