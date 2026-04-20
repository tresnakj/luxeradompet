-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 05, 2026 at 01:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crypto_wallet_backup`
--

-- --------------------------------------------------------

--
-- Table structure for table `air_drop`
--

CREATE TABLE `air_drop` (
  `id` int(11) NOT NULL,
  `id_alamat_dompet` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `jumlah_bonus` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `air_drop`
--

INSERT INTO `air_drop` (`id`, `id_alamat_dompet`, `tanggal`, `jumlah_bonus`, `created_at`) VALUES
(1, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-21', 0.03650000, '2026-03-03 11:18:00'),
(2, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-21', 0.42240000, '2026-03-03 11:22:00'),
(3, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-21', 0.03440000, '2026-03-03 11:23:00'),
(4, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-21', 0.42240000, '2026-03-03 11:23:00'),
(5, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-22', 0.00210000, '2026-03-03 11:25:00'),
(6, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-22', 0.03620000, '2026-03-03 11:25:00'),
(7, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-22', 0.42240000, '2026-03-03 11:26:00'),
(8, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-22', 0.03560000, '2026-03-03 11:26:00'),
(9, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-22', 0.42240000, '2026-03-03 11:27:00'),
(10, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-23', 0.00220000, '2026-03-03 11:27:00'),
(11, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-23', 0.03670000, '2026-03-03 11:28:00'),
(12, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-23', 0.42240000, '2026-03-03 11:28:00'),
(13, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-23', 0.03670000, '2026-03-03 11:29:00'),
(14, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-23', 0.42240000, '2026-03-03 11:29:00'),
(15, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-24', 0.00240000, '2026-03-03 11:29:00'),
(16, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-24', 0.03900000, '2026-03-03 11:30:00'),
(17, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-24', 0.42240000, '2026-03-03 11:30:00'),
(18, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-24', 0.03600000, '2026-03-03 11:30:00'),
(19, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-24', 0.42240000, '2026-03-03 11:31:00'),
(20, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-25', 0.00260000, '2026-03-03 11:31:00'),
(21, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-25', 0.03720000, '2026-03-03 11:32:00'),
(22, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-25', 0.42240000, '2026-03-03 11:32:00'),
(23, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-25', 0.03490000, '2026-03-03 11:32:00'),
(24, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-25', 0.42240000, '2026-03-03 11:33:00'),
(25, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-26', 0.00280000, '2026-03-03 11:33:00'),
(26, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-26', 0.03600000, '2026-03-03 11:34:00'),
(27, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-26', 0.42240000, '2026-03-03 11:34:00'),
(28, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-26', 0.03600000, '2026-03-03 11:34:00'),
(29, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-26', 0.42240000, '2026-03-03 11:34:00'),
(30, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-27', 0.00290000, '2026-03-03 11:35:00'),
(31, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-27', 0.03490000, '2026-03-03 11:35:00'),
(32, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-27', 0.42240000, '2026-03-03 11:35:00'),
(33, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-27', 0.03550000, '2026-03-03 11:35:00'),
(34, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-27', 0.42240000, '2026-03-03 11:36:00'),
(35, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-28', 0.00310000, '2026-03-03 11:36:00'),
(36, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-28', 0.03610000, '2026-03-03 11:36:00'),
(37, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-28', 0.42240000, '2026-03-03 11:37:00'),
(38, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-28', 0.03640000, '2026-03-03 11:37:00'),
(39, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-02-28', 0.42240000, '2026-03-03 11:37:00'),
(40, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-01', 0.00330000, '2026-03-03 11:38:00'),
(41, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-01', 0.03620000, '2026-03-03 11:38:00'),
(42, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-01', 0.42240000, '2026-03-03 11:38:00'),
(43, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-01', 0.03620000, '2026-03-03 11:39:00'),
(44, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-01', 0.42240000, '2026-03-03 11:39:00'),
(45, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-02', 0.00340000, '2026-03-03 11:39:00'),
(46, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-02', 0.03710000, '2026-03-03 11:40:00'),
(47, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-02', 0.42240000, '2026-03-03 11:40:00'),
(48, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-02', 0.03800000, '2026-03-03 11:40:00'),
(49, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-02', 0.42240000, '2026-03-03 11:40:00'),
(50, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-03', 0.00360000, '2026-03-03 11:41:00'),
(51, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-03', 0.03710000, '2026-03-03 11:41:00'),
(52, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-03', 0.42240000, '2026-03-03 11:41:00'),
(53, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-03', 0.03730000, '2026-03-03 23:09:00'),
(54, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-03', 0.42240000, '2026-03-03 23:10:00'),
(55, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', '2026-03-03', 0.00380000, '2026-03-03 23:11:00'),
(56, '0x5b03F8FA73E60104604E8645dc5B8c26B77fA6c2', '2026-03-03', 0.00480000, '2026-03-03 23:18:00'),
(57, '0x5b03F8FA73E60104604E8645dc5B8c26B77fA6c2', '2026-03-03', 0.00400000, '2026-03-03 23:18:00'),
(58, '0x5b03F8FA73E60104604E8645dc5B8c26B77fA6c2', '2026-03-03', 0.00800000, '2026-03-03 23:19:00'),
(59, '0x5b03F8FA73E60104604E8645dc5B8c26B77fA6c2', '2026-03-03', 0.09080000, '2026-03-03 23:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `dompet`
--

CREATE TABLE `dompet` (
  `id` int(11) NOT NULL,
  `id_alamat_dompet` varchar(255) NOT NULL,
  `nama_dompet` varchar(100) NOT NULL,
  `nama_pemilik` varchar(100) DEFAULT NULL,
  `frasa_pemulihan` text NOT NULL,
  `qr_code_pemulihan` varchar(255) DEFAULT NULL,
  `kata_sandi_dompet` varchar(255) NOT NULL,
  `kode_referal` varchar(255) NOT NULL,
  `jaringan_dari` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dompet`
--

INSERT INTO `dompet` (`id`, `id_alamat_dompet`, `nama_dompet`, `nama_pemilik`, `frasa_pemulihan`, `qr_code_pemulihan`, `kata_sandi_dompet`, `kode_referal`, `jaringan_dari`, `created_at`) VALUES
(5, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', 'tresnakusumajaya', 'Nyoman Tresna Kusumajaya', 'ranch bubble thumb course absurd right vague today stage quit jelly library', 'assets/uploads/qr_codes/1772523799_WhatsApp Image 2026-03-03 at 14.55.19.jpeg', '@Aptx4869', 'https://xeradao.com/Login/index?invit=0x312bb60dd2734ebfb200931bdebc772fc49b6849', NULL, '2026-03-03 07:43:19'),
(6, '0x5b03F8FA73E60104604E8645dc5B8c26B77fA6c2', 'L1', 'Nyoman Tresna Kusumajaya', 'intact label unfair auction gym cook method debris reform carbon near shield', 'assets/uploads/qr_codes/1772523887_WhatsApp Image 2026-03-03 at 15.02.47.jpeg', '@Aptx4869', 'https://xeradao.com/Login/index?invit=0x5b03f8fa73e60104604e8645dc5b8c26b77fa6c2', 'https://xeradao.com/Login/index?invit=0x312bb60dd2734ebfb200931bdebc772fc49b6849', '2026-03-03 07:44:47'),
(7, '0x82B142785a8746D85B2D9Ad34E4f9fe2Ea0a732E', 'L2', 'Nyoman Tresna Kusumajaya', 'small name scrap hill sponsor outer decorate laugh hunt belt slush frame', 'assets/uploads/qr_codes/1772523961_WhatsApp Image 2026-03-03 at 15.10.23.jpeg', '@Aptx4869', 'https://xeradao.com/Login/index?invit=0x82b142785a8746d85b2d9ad34e4f9fe2ea0a732e', 'https://xeradao.com/Login/index?invit=0x5b03f8fa73e60104604e8645dc5b8c26b77fa6c2', '2026-03-03 07:46:01'),
(8, '0xeBf5Ac69d67E37Fe5e744738Be12E83058E6502f', 'L3', 'Nyoman Tresna Kusumajaya', 'add pioneer cousin nest initial myself soldier surround energy reduce donor wise', 'assets/uploads/qr_codes/1772531038_WhatsApp Image 2026-03-03 at 17.39.20.jpeg', '@Aptx4869', 'https://xeradao.com/Login/index?invit=0xebf5ac69d67e37fe5e744738be12e83058e6502f', 'https://xeradao.com/Login/index?invit=0x82b142785a8746d85b2d9ad34e4f9fe2ea0a732e', '2026-03-03 09:43:58'),
(9, '0x663fFce49108887841069b1f8674D3663B6A717f', 'L4', 'Nyoman Tresna Kusumajaya', 'guess pair night power right kidney curtain bid gas job voyage behave', 'assets/uploads/qr_codes/1772532015_WhatsApp Image 2026-03-03 at 17.56.14.jpeg', '@Aptx4869', 'https://xeradao.com/Login/index?invit=0x663ffce49108887841069b1f8674d3663b6a717f', 'https://xeradao.com/Login/index?invit=0xebf5ac69d67e37fe5e744738be12e83058e6502f', '2026-03-03 10:00:15'),
(10, '0xc0CCEa578f8975544EE66C040c6E737bC661255c', 'L5', 'Nyoman Tresna Kusumajaya', 'depart uncle place crouch marriage discover worth decline symptom planet drastic photo', 'assets/uploads/qr_codes/1772532747_WhatsApp Image 2026-03-03 at 18.09.24.jpeg', '@Aptx4869', 'https://xeradao.com/Login/index?invit=0xc0ccea578f8975544ee66c040c6e737bc661255c', 'https://xeradao.com/Login/index?invit=0x663ffce49108887841069b1f8674d3663b6a717f', '2026-03-03 10:12:27'),
(11, '0xAf2Eff7B9cE6458B48e90e7ED28947Bbb83468F5', 'R1', 'Nyoman Tresna Kusumajaya', 'define scare law husband balcony achieve enable secret struggle dignity angry angle', 'assets/uploads/qr_codes/1772552670_WhatsApp Image 2026-03-03 at 23.43.46.jpeg', '@Aptx4869', 'https://xeradao.com/Login/index?invit=0xaf2eff7b9ce6458b48e90e7ed28947bbb83468f5', 'https://xeradao.com/Login/index?invit=0x312bb60dd2734ebfb200931bdebc772fc49b6849', '2026-03-03 15:44:30'),
(12, '0xA17fCA227d0727ef1eC76Af07942924F71CE6785', 'tabunganku', 'Razly Rizaldi', '', NULL, '', 'https://xeradao.com/Login/index?invit=0xa17fca227d0727ef1ec76af07942924f71ce6785', 'https://xeradao.com/Login/index?invit=0x312bb60dd2734ebfb200931bdebc772fc49b6849', '2026-03-03 17:43:05'),
(13, '0x9132ff7F82A940c6d906f1Db5473741C6A0a0F93', 'Backup Dompet 2', 'Razly Rizaldi', '', NULL, '', 'https://xeradao.com/Login/index?invit=0x9132ff7f82a940c6d906f1db5473741c6a0a0f93', 'https://xeradao.com/Login/index?invit=0xa17fca227d0727ef1ec76af07942924f71ce6785', '2026-03-03 18:14:43'),
(14, '0x13ED5149c68465A722ba7cA9196B3D2f985ffb73', 'R2', 'Nyoman Tresna Kusumajaya', 'license half hurt unveil pole then certain level soon much frequent dust', 'assets/uploads/qr_codes/1772609411_WhatsApp Image 2026-03-04 at 14.58.33.jpeg', '@Aptx4869', 'https://xeradao.com/Login/index?invit=0x13ed5149c68465a722ba7ca9196b3d2f985ffb73', 'https://xeradao.com/Login/index?invit=0xaf2eff7b9ce6458b48e90e7ed28947bbb83468f5', '2026-03-04 07:30:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`) VALUES
(3, 'tresnakj', '$2y$10$p8v8QxLjJMERKV3oTHDdG.ophzCJR4UlMoS8i5EWfcjwXTMJkhV0O', '2026-03-04 01:08:15');

-- --------------------------------------------------------

--
-- Table structure for table `xera_stacking`
--

CREATE TABLE `xera_stacking` (
  `id` int(11) NOT NULL,
  `tanggal_input` datetime DEFAULT NULL,
  `id_alamat_dompet` varchar(255) NOT NULL,
  `xera_koin` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `stacking_duration` int(11) NOT NULL,
  `stacking_xera` decimal(20,8) NOT NULL DEFAULT 0.00000000,
  `jumlah_invest_rp` decimal(20,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `xera_stacking`
--

INSERT INTO `xera_stacking` (`id`, `tanggal_input`, `id_alamat_dompet`, `xera_koin`, `stacking_duration`, `stacking_xera`, `jumlah_invest_rp`, `created_at`, `updated_at`) VALUES
(2, NULL, '0x312Bb60Dd2734EBfB200931Bdebc772Fc49b6849', 12.00000000, 360, 12.00000000, 5000000.00, '2026-02-10 08:32:00', '2026-03-03 09:33:18'),
(3, NULL, '0x5b03F8FA73E60104604E8645dc5B8c26B77fA6c2', 2.00000000, 360, 2.00000000, 1000000.00, '2026-03-03 09:00:00', '2026-03-03 16:01:50'),
(4, NULL, '0xA17fCA227d0727ef1eC76Af07942924F71CE6785', 10.00000000, 360, 10.00000000, 5000000.00, '2026-03-03 11:08:00', '2026-03-03 18:09:06'),
(5, NULL, '0x9132ff7F82A940c6d906f1Db5473741C6A0a0F93', 4.00000000, 360, 4.00000000, 2000000.00, '2026-03-03 11:15:00', '2026-03-03 18:15:58'),
(6, NULL, '0xAf2Eff7B9cE6458B48e90e7ED28947Bbb83468F5', 8.00000000, 360, 8.00000000, 4000000.00, '2026-03-04 00:25:00', '2026-03-04 00:25:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `air_drop`
--
ALTER TABLE `air_drop`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_alamat_dompet` (`id_alamat_dompet`),
  ADD KEY `idx_tanggal` (`tanggal`);

--
-- Indexes for table `dompet`
--
ALTER TABLE `dompet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_alamat_dompet` (`id_alamat_dompet`),
  ADD UNIQUE KEY `kode_referal` (`kode_referal`),
  ADD KEY `idx_jaringan` (`jaringan_dari`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `xera_stacking`
--
ALTER TABLE `xera_stacking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_alamat_dompet` (`id_alamat_dompet`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `air_drop`
--
ALTER TABLE `air_drop`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `dompet`
--
ALTER TABLE `dompet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `xera_stacking`
--
ALTER TABLE `xera_stacking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `air_drop`
--
ALTER TABLE `air_drop`
  ADD CONSTRAINT `air_drop_ibfk_1` FOREIGN KEY (`id_alamat_dompet`) REFERENCES `dompet` (`id_alamat_dompet`) ON DELETE CASCADE;

--
-- Constraints for table `dompet`
--
ALTER TABLE `dompet`
  ADD CONSTRAINT `dompet_ibfk_1` FOREIGN KEY (`jaringan_dari`) REFERENCES `dompet` (`kode_referal`) ON DELETE SET NULL;

--
-- Constraints for table `xera_stacking`
--
ALTER TABLE `xera_stacking`
  ADD CONSTRAINT `xera_stacking_ibfk_1` FOREIGN KEY (`id_alamat_dompet`) REFERENCES `dompet` (`id_alamat_dompet`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
