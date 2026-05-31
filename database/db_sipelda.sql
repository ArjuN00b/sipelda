-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for db_sipelda
CREATE DATABASE IF NOT EXISTS `db_sipelda` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `db_sipelda`;

-- Dumping structure for table db_sipelda.pengaduan
CREATE TABLE IF NOT EXISTS `pengaduan` (
  `id_pengaduan` int NOT NULL AUTO_INCREMENT,
  `tgl_pengaduan` datetime DEFAULT CURRENT_TIMESTAMP,
  `id_user` int NOT NULL,
  `judul_laporan` varchar(255) NOT NULL,
  `isi_laporan` text NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('menunggu','diproses','selesai') DEFAULT 'menunggu',
  PRIMARY KEY (`id_pengaduan`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `pengaduan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_sipelda.pengaduan: ~6 rows (approximately)
REPLACE INTO `pengaduan` (`id_pengaduan`, `tgl_pengaduan`, `id_user`, `judul_laporan`, `isi_laporan`, `foto`, `status`) VALUES
	(3, '2026-05-27 16:58:56', 3, 'Ketertiban Lalu Lintas & Parkir - Depan Gacoan Manukan', 'Ada orang nariki parkir sembarangan dengan tarif yang gila gilaan mohon ditindaklanjuti\n\n📍 Titik Koordinat Peta:\nhttps://www.google.com/maps?q=-7.247639042052534,112.64624021846821', '1779875936_kamera.jpeg', 'selesai'),
	(4, '2026-05-27 17:00:52', 3, 'Penerangan Jalan Umum (PJU) - Pertigaan MERR [ANONIM]', 'tolong tambahkan penerangannya soslnya gelap bgt\n\n📍 Titik Koordinat Peta:\nhttps://www.google.com/maps?q=-7.282108611993052,112.78084732292523', '1779876052_galeri_EmpathytoPrototypingFlow20260524031611.png', 'menunggu'),
	(5, '2026-05-27 17:02:39', 4, 'Keamanan & Ketertiban - Indomaret Simomulyo [PRIVAT]', 'ada orang nyetel sound horeg mengganggu warga tolong ditindak\n\n📍 Titik Koordinat Peta:\nhttps://www.google.com/maps?q=-7.260651420460803,112.71303870688203', '', 'diproses'),
	(6, '2026-05-27 17:03:50', 4, 'Pelayanan Administrasi - kantor kelurahan', 'lemot kalau ngelayanin\n\n📍 Titik Koordinat Peta:\nhttps://www.google.com/maps?q=-7.2516231246801475,112.63561242536808', '1779876230_galeri_SpiderManMilesMoralesAkanPunyaVersiLiveActionscaled.jpg', 'diproses'),
	(7, '2026-05-27 17:06:28', 5, 'Fasilitas Umum - Perempatan Prapen [ANONIM]', 'trotoarnya rusak semua\n\n📍 Titik Koordinat Peta:\nhttps://www.google.com/maps?q=-7.3059421889745275,112.76161143907726', '', 'selesai'),
	(8, '2026-05-27 17:07:28', 5, 'Bantuan Sosial (Bansos) - indomaret', 'bansosnya mana wok\n\n📍 Titik Koordinat Peta:\nhttps://www.google.com/maps?q=-7.252986574397318,112.63543970276757', '1779876448_kamera.jpeg', 'diproses');

-- Dumping structure for table db_sipelda.tanggapan
CREATE TABLE IF NOT EXISTS `tanggapan` (
  `id_tanggapan` int NOT NULL AUTO_INCREMENT,
  `id_pengaduan` int NOT NULL,
  `id_admin` int NOT NULL,
  `tgl_tanggapan` datetime DEFAULT CURRENT_TIMESTAMP,
  `isi_tanggapan` text NOT NULL,
  PRIMARY KEY (`id_tanggapan`),
  KEY `id_pengaduan` (`id_pengaduan`),
  KEY `id_admin` (`id_admin`),
  CONSTRAINT `tanggapan_ibfk_1` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE,
  CONSTRAINT `tanggapan_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_sipelda.tanggapan: ~5 rows (approximately)
REPLACE INTO `tanggapan` (`id_tanggapan`, `id_pengaduan`, `id_admin`, `tgl_tanggapan`, `isi_tanggapan`) VALUES
	(2, 8, 2, '2026-05-27 17:08:26', 'baik sabar ya wok\r\n'),
	(3, 7, 2, '2026-05-27 17:08:39', 'masalah sudah selesai nanti dulu ya wok\r\n'),
	(4, 5, 2, '2026-05-27 17:08:47', 'oke'),
	(5, 3, 2, '2026-05-27 17:09:00', 'baik masalah sudah selesai'),
	(6, 6, 2, '2026-05-27 18:25:28', 'sabar ye');

-- Dumping structure for table db_sipelda.users
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `role` enum('admin','masyarakat') DEFAULT 'masyarakat',
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `nik` (`nik`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table db_sipelda.users: ~5 rows (approximately)
REPLACE INTO `users` (`id_user`, `nik`, `nama_lengkap`, `username`, `password`, `no_telp`, `foto_profil`, `role`) VALUES
	(2, NULL, 'ADMIN1', 'admin1', '$2y$12$SOsYAKF9xiXGnLHEoqfL1eEqU1PbUSQkU7Ov7ABENwpgU7CKBWMFq', '088187654321', NULL, 'admin'),
	(3, NULL, 'Arjuna Sandya Raissa Naryama', 'arjunasrn', '$2y$12$Z9T8/WehchpVfuMd1eiUau8/LXL/0xxIRFeA2O4Gr1zpbHfqv9xIC', '08819408505', NULL, 'masyarakat'),
	(4, NULL, 'Muhammad Fakhri Anshari Yusaf', 'mfakhri', '$2y$12$F/iUIlmy7W5/krncw7C7cOg/a2lxWuYjGUkNd/LqiG7wfAEOlTt4i', '081230333108', NULL, 'masyarakat'),
	(5, NULL, 'Jauza Aida Alifah', 'jauzaaa', '$2y$12$6ZvWlR.pge2fAwl9OAxldO8ecJpNpVjAbNeM0TRLsswY5fyLwqLAW', '089509565788', NULL, 'masyarakat'),
	(6, NULL, 'ADMIN2', 'admin2', '$2y$12$e2WsORwa7Zff.qYUvWZb3OurHlQQt3s5sJiNzx/LeHtM0ADHlDkXm', '08169674205', NULL, 'admin');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
