-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 30 Sep 2026 pada 06.16
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ekstrakurikuler`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ekskul_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status_hadir` enum('hadir','izin','sakit','alfa') DEFAULT 'hadir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `absensi`
--

INSERT INTO `absensi` (`id`, `siswa_id`, `user_id`, `ekskul_id`, `tanggal`, `status_hadir`) VALUES
(3, 3, 4, 2, '2026-09-24', 'hadir');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ekstrakurikuler`
--

CREATE TABLE `ekstrakurikuler` (
  `id` int(11) NOT NULL,
  `nama_ekskul` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `pembina` varchar(100) DEFAULT NULL,
  `jadwal` varchar(100) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `ekstrakurikuler`
--

INSERT INTO `ekstrakurikuler` (`id`, `nama_ekskul`, `deskripsi`, `pembina`, `jadwal`, `lokasi`, `created_at`) VALUES
(1, 'puisi', NULL, 'andi', 'senin,11;00', 'aula', '2026-09-11 12:01:03'),
(2, 'pidato', NULL, 'buk atta', 'selasa,10.00', 'di kelas rpl 1', '2026-09-18 02:27:48'),
(3, 'pramuka', NULL, 'ibu rina', 'sabtu,14.00-16.00', 'lapangan sekolah', '2026-09-23 09:28:57'),
(4, 'PMR', NULL, 'ibu sari', 'jum\'at,14.00-16.00', 'Ruangan PMR', '2026-09-23 09:29:58'),
(5, 'paskibra', NULL, 'ibu fitri', 'senin,15.00-17.00', 'lapangan sekolah', '2026-09-23 09:31:17'),
(6, 'Futsal', NULL, 'Bapak rudi', 'Rabu,15.30-17.00', 'Lapangan futsal', '2026-09-23 09:33:13'),
(7, 'Voli', NULL, 'bapak anto', 'kamis,15.30-17.00', 'Lapangan Voli', '2026-09-23 09:34:46'),
(8, 'Seni musik', NULL, 'Bapak dedi', 'selasa,15.00-16.30', 'Ruang musik', '2026-09-23 09:36:29'),
(9, 'tari', NULL, 'buk rahmi', 'rabu,10.00-12.30', 'ruang tari', '2026-09-23 09:37:14'),
(10, 'Basket', NULL, 'bapak reno', 'sabtu,15.00-17.30', 'Lapangan basket', '2026-09-23 09:39:19'),
(11, 'fotografi', NULL, 'ibu deveyna', 'rabu,15.30-17.30', 'Ruang multimedia', '2026-09-23 09:41:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `ekskul_id` int(11) NOT NULL,
  `hari` varchar(20) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `jam` varchar(20) NOT NULL,
  `lokasi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id`, `ekskul_id`, `hari`, `jam_mulai`, `jam_selesai`, `jam`, `lokasi`) VALUES
(1, 2, 'Selasa', '01:59:00', '03:00:00', '', 'kelas');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembina_ekskul`
--

CREATE TABLE `pembina_ekskul` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ekskul_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pembina_ekskul`
--

INSERT INTO `pembina_ekskul` (`id`, `user_id`, `ekskul_id`) VALUES
(1, 6, 1),
(2, 7, 2),
(3, 8, 4),
(4, 12, 7);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pendaftaran`
--

CREATE TABLE `pendaftaran` (
  `id` int(11) NOT NULL,
  `ekskul_id` int(11) NOT NULL,
  `tanggal_daftar` date NOT NULL,
  `status` enum('menunggu','diterima','ditolak') DEFAULT 'menunggu',
  `siswa_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pendaftaran`
--

INSERT INTO `pendaftaran` (`id`, `ekskul_id`, `tanggal_daftar`, `status`, `siswa_id`) VALUES
(5, 2, '0000-00-00', 'diterima', 3),
(6, 1, '0000-00-00', 'menunggu', 4),
(7, 2, '2026-09-24', 'menunggu', 5),
(8, 2, '2026-09-24', 'menunggu', 6),
(9, 1, '2026-09-24', 'menunggu', 3),
(10, 7, '2026-09-24', 'diterima', 7);

-- --------------------------------------------------------

--
-- Struktur dari tabel `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nis` varchar(30) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `jenis_kelamin` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `siswa`
--

INSERT INTO `siswa` (`id`, `nama_siswa`, `username`, `password`, `user_id`, `nis`, `kelas`, `jenis_kelamin`, `alamat`) VALUES
(2, 'deveyna', '', '', 3, NULL, NULL, NULL, NULL),
(3, 'tasya', '', '', 4, '151108', 'XII PPLG 2', 'Perempuan', 'Padang sikabu'),
(4, 'cate', '', '', 5, NULL, NULL, NULL, NULL),
(5, 'deveyna', '', '', 9, NULL, NULL, NULL, NULL),
(6, 'ketrin', '', '', 10, '2342678', 'X DKV 2', 'Perempuan', 'Koto nan godang'),
(7, 'widya', '', '', 11, '879065', 'XII PPLG 2', 'Perempuan', 'blok m');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','siswa','pembina') NOT NULL DEFAULT 'siswa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$Z/g8ro.r2VqQvOj7v9xmMegqxaZDRLfD8DZSiPmF6/tOnY7iI5mv2', 'admin', '2026-09-10 01:45:56'),
(4, 'tasya', 'tasya@gmail.com', '$2y$10$dEeCnTvLh8x/iHix4k5OT.rAU1ske/6jUcpXtXEfnHUgavRWKnYsO', 'siswa', '2026-09-18 12:34:19'),
(5, 'cate', 'cate@gmail.com', '$2y$10$lqjXqqqer.c4CEvlx4hLP.csygPwG683Cw.SuygSVLieGgJhgPG2u', 'siswa', '2026-09-19 14:45:59'),
(6, 'Andi', 'andi@ekskul.local', '$2y$12$R31MJHsZoc19DAusS9/YVeqp6NMkO8G.P/pOilFAt3tuihFGT6jYy', 'pembina', '2026-09-23 02:39:53'),
(7, 'Buk Atta', 'bukatta@ekskul.local', '$2y$12$UJ2HIgQBz0eaP8Hn5AhSzOWEBHBqgbOmk72k4nY2JmKWcsv2fSTnm', 'pembina', '2026-09-23 02:39:53'),
(8, 'ibu sari', 'sari@ekskul.local', '$2y$10$2eiakObsb.gQ45Pvt3ASaehAPqioDjYgZRKjHyhNWsOQHYa6J268y', 'pembina', '2026-09-23 10:01:15'),
(9, 'deveyna', 'deveyna@gmail.com', '$2y$10$WPHDd2ogg.drQRJKyaRmw.xAs9hABV4B.RAqomFZx6g.hwNIURpwC', 'siswa', '2026-09-24 01:23:41'),
(10, 'ketrin', 'ketrin@gmail.com', '$2y$10$YcdsaX6LoJKVuXPHFAs.IO1Pw6FPF89VMwIyxR8Wzl4OugJELjDFa', 'siswa', '2026-09-24 02:02:57'),
(11, 'widya', 'widya@gmail.com', '$2y$10$CzBpvbmEmjBT7K/tFqlzqOlg2/gi4cHGYjjHFMWvHxIYD0RthWGya', 'siswa', '2026-09-24 06:57:16'),
(12, 'bapak anto', 'anto@skul.voli', '$2y$10$2Da2NMi8KlNNOffUydi2XOLS8rBuSB2Mm2FICVoZv74ZVxpvuapWq', 'pembina', '2026-09-24 06:59:35');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `ekstrakurikuler`
--
ALTER TABLE `ekstrakurikuler`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ekskul_id` (`ekskul_id`);

--
-- Indeks untuk tabel `pembina_ekskul`
--
ALTER TABLE `pembina_ekskul`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pembina_ekskul` (`user_id`,`ekskul_id`),
  ADD KEY `fk_pembina_ekskul_ekskul` (`ekskul_id`);

--
-- Indeks untuk tabel `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ekskul_id` (`ekskul_id`);

--
-- Indeks untuk tabel `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `ekstrakurikuler`
--
ALTER TABLE `ekstrakurikuler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pembina_ekskul`
--
ALTER TABLE `pembina_ekskul`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pendaftaran`
--
ALTER TABLE `pendaftaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`ekskul_id`) REFERENCES `ekstrakurikuler` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembina_ekskul`
--
ALTER TABLE `pembina_ekskul`
  ADD CONSTRAINT `fk_pembina_ekskul_ekskul` FOREIGN KEY (`ekskul_id`) REFERENCES `ekstrakurikuler` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembina_ekskul_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD CONSTRAINT `pendaftaran_ibfk_2` FOREIGN KEY (`ekskul_id`) REFERENCES `ekstrakurikuler` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
