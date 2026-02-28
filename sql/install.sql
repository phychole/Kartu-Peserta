-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 28 Feb 2026 pada 13.34
-- Versi server: 8.4.3
-- Versi PHP: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `psaj_kartu_peserta`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `app_settings`
--

CREATE TABLE `app_settings` (
  `key` varchar(100) NOT NULL,
  `value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `app_settings`
--

INSERT INTO `app_settings` (`key`, `value`, `updated_at`) VALUES
('kode_kabupaten', '30', '2026-02-28 13:25:30'),
('kode_sekolah', '0104', '2026-02-28 13:25:30'),
('tahun_pelajaran', '2025 - 2026', '2026-02-28 13:25:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peserta`
--

CREATE TABLE `peserta` (
  `id` int NOT NULL,
  `nomor_peserta` varchar(50) NOT NULL,
  `nama` varchar(200) NOT NULL,
  `kelas` varchar(100) NOT NULL,
  `ruang` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `school_profile`
--

CREATE TABLE `school_profile` (
  `id` int NOT NULL,
  `line1` varchar(150) NOT NULL,
  `line2` varchar(150) NOT NULL,
  `line3` varchar(150) NOT NULL,
  `line4` varchar(200) NOT NULL,
  `line5` varchar(200) NOT NULL,
  `line6` varchar(150) NOT NULL,
  `logo_filename` varchar(255) DEFAULT NULL,
  `logo_width_mm` int DEFAULT '16',
  `kepsek_nama` varchar(150) NOT NULL,
  `kepsek_nip` varchar(50) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `school_profile`
--

INSERT INTO `school_profile` (`id`, `line1`, `line2`, `line3`, `line4`, `line5`, `line6`, `logo_filename`, `logo_width_mm`, `kepsek_nama`, `kepsek_nip`, `updated_at`) VALUES
(1, 'PEMERINTAH PROVINSI JAWA TIMUR', 'DINAS PENDIDIKAN', 'SMK NEGERI 2 LUMAJANG', 'Jalan Gajah Mada No.- Telp./Fax. (0334) 881925', 'Email: smkn_02lmj@yahoo.co.id | http://www.smkn2lumajang.sch.id', 'LUMAJANG', 'logo-jatim.svg', 16, 'Muhammad Saiful Bahri, S.Ag, M.Pd.I', '197401052003121004', '2026-02-28 13:33:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$ZNjeUIEs0HRFwSIrL2ClAe58TA8WWJCl4AFtrhwnAi1U6DWwmrTwe', '2026-02-28 13:29:44');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `peserta`
--
ALTER TABLE `peserta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_peserta` (`nomor_peserta`),
  ADD KEY `idx_kelas` (`kelas`),
  ADD KEY `idx_ruang` (`ruang`);

--
-- Indeks untuk tabel `school_profile`
--
ALTER TABLE `school_profile`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `peserta`
--
ALTER TABLE `peserta`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
