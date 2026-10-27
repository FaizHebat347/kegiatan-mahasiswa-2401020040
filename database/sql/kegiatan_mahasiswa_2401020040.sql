-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 27, 2026 at 04:27 AM
-- Server version: 8.4.7
-- PHP Version: 8.4.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kegiatan_mahasiswa_2401020040`
--

-- --------------------------------------------------------

--
-- Table structure for table `jenis_kegiatan`
--

DROP TABLE IF EXISTS `jenis_kegiatan`;
CREATE TABLE IF NOT EXISTS `jenis_kegiatan` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama_jenis` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jenis_kegiatan_nama` (`nama_jenis`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jenis_kegiatan`
--

INSERT INTO `jenis_kegiatan` (`id`, `nama_jenis`) VALUES
(1, 'Akademik'),
(2, 'Sosial');

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan`
--

DROP TABLE IF EXISTS `kegiatan`;
CREATE TABLE IF NOT EXISTS `kegiatan` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_kegiatan` char(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_kegiatan` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_pic` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kuota` smallint UNSIGNED NOT NULL,
  `jenis_kegiatan_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kegiatan_kode` (`kode_kegiatan`),
  UNIQUE KEY `uq_kegiatan_email_pic` (`email_pic`),
  KEY `fk_kegiatan_jenis_kegiatan` (`jenis_kegiatan_id`)
) ;

--
-- Dumping data for table `kegiatan`
--

INSERT INTO `kegiatan` (`id`, `kode_kegiatan`, `nama_kegiatan`, `email_pic`, `kuota`, `jenis_kegiatan_id`, `created_at`, `updated_at`) VALUES
(1, 'KG00000001', 'Pelatihan Web', 'web@kampus.ac.id', 40, 1, '2026-10-27 03:54:59', '2026-10-27 03:54:59'),
(2, 'KG00000002', 'Seminar Karier', 'karier@kampus.ac.id', 100, 1, '2026-10-27 03:54:59', '2026-10-27 03:54:59'),
(3, 'KG00000003', 'Bakti Pesisir', 'pesisir@kampus.ac.id', 60, 2, '2026-10-27 03:54:59', '2026-10-27 03:54:59');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD CONSTRAINT `fk_kegiatan_jenis_kegiatan` FOREIGN KEY (`jenis_kegiatan_id`) REFERENCES `jenis_kegiatan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
