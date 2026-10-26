-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               12.3.2-MariaDB - Source distribution
-- Server OS:                    osx10.13
-- HeidiSQL Version:             12.21.1.1
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for fsktm_pace_db
CREATE DATABASE IF NOT EXISTS `fsktm_pace_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `fsktm_pace_db`;

-- Dumping structure for table fsktm_pace_db.cache
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.cache: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.cache_locks
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.cache_locks: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.courses
CREATE TABLE IF NOT EXISTS `courses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `credits` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `course_type_id` bigint(20) unsigned DEFAULT NULL,
  `mqf_sc_id` bigint(20) unsigned DEFAULT NULL,
  `vbe_id` bigint(20) unsigned DEFAULT NULL,
  `prerequisites` text DEFAULT NULL,
  `synopsis` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `course_load` decimal(5,1) DEFAULT NULL,
  `semester` tinyint(3) unsigned DEFAULT NULL,
  `course_plan_id` bigint(20) unsigned DEFAULT NULL,
  `sdg_id` bigint(20) unsigned DEFAULT NULL,
  `alternative_group` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courses_code_unique` (`code`),
  KEY `courses_department_id_foreign` (`department_id`),
  KEY `courses_program_id_foreign` (`program_id`),
  KEY `courses_course_type_id_foreign` (`course_type_id`),
  KEY `courses_mqf_sc_id_foreign` (`mqf_sc_id`),
  KEY `courses_vbe_id_foreign` (`vbe_id`),
  KEY `courses_course_plan_id_index` (`course_plan_id`),
  KEY `courses_sdg_id_index` (`sdg_id`),
  CONSTRAINT `courses_course_type_id_foreign` FOREIGN KEY (`course_type_id`) REFERENCES `course_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `courses_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `courses_mqf_sc_id_foreign` FOREIGN KEY (`mqf_sc_id`) REFERENCES `mqf_sc` (`id`) ON DELETE SET NULL,
  CONSTRAINT `courses_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `courses_vbe_id_foreign` FOREIGN KEY (`vbe_id`) REFERENCES `vbe` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.courses: ~88 rows (approximately)
INSERT INTO `courses` (`id`, `code`, `name`, `credits`, `department_id`, `program_id`, `course_type_id`, `mqf_sc_id`, `vbe_id`, `prerequisites`, `synopsis`, `is_active`, `created_at`, `updated_at`, `course_load`, `semester`, `course_plan_id`, `sdg_id`, `alternative_group`) VALUES
	(6, 'BIC 10203', 'Algorithms and Programming', 3, NULL, 6, 3, NULL, 3, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 1, 1, 9, NULL),
	(7, 'BIC 10503', 'Computer Architecture', 3, NULL, 6, 3, 3, 2, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 1, 1, 9, NULL),
	(8, 'BIC 21102', 'Professional Ethics and Occupational Safety', 2, NULL, 6, 4, 7, 1, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 2.0, 1, 1, 9, NULL),
	(9, 'BIM 10103', 'Fundamentals of Multimedia Computing', 3, NULL, 6, 5, NULL, 1, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 1, 1, 9, NULL),
	(13, 'UQ* 1**01-2', 'Co-Curriculum II', 1, NULL, 6, 2, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 3.0, 2, 1, 9, NULL),
	(14, 'BIC 10403', 'Data Structure', 3, NULL, 6, 4, NULL, 4, 'BIC 10203', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 2, 1, 9, NULL),
	(15, 'BIC 21003', 'System Analysis and Design', 3, NULL, 6, 3, 6, 12, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 2, 1, 9, NULL),
	(16, 'BIC 31502', 'Creativity and Innovation', 2, NULL, 6, 3, 2, 16, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 2.0, 2, 1, 9, NULL),
	(17, 'BIM 10203', 'Digital Audio and Video Technology', 3, NULL, 6, 5, NULL, 14, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 2, 1, 9, NULL),
	(18, 'BIM 10603', 'Computer Graphics', 3, NULL, 6, 4, NULL, 4, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 2, 1, 9, NULL),
	(21, 'BIC 10103', 'Discrete Structure', 3, NULL, 6, 4, 1, 3, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 3, 1, 9, NULL),
	(22, 'BIC 20903', 'Object-Oriented Programming', 3, NULL, 6, 4, 3, 15, 'BIC 10203', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 3, 1, 9, NULL),
	(23, 'BIC 22003', 'Human-Computer Interaction', 3, NULL, 6, 4, 7, 12, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 3, 1, 9, NULL),
	(24, 'BIC 20803', 'Operating System', 3, NULL, 6, 3, 8, 4, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 3, 1, 9, NULL),
	(25, 'BIM 10303', '3D Modelling', 3, NULL, 6, 5, NULL, 12, 'BIM10603', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 3, 1, 9, NULL),
	(26, 'BIC 21203', 'Web Development', 3, NULL, 6, 4, 8, 15, 'BIC 10203', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 4, 1, 9, NULL),
	(27, 'BIC 21303', 'Computer Networking', 3, NULL, 6, 3, 1, 13, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 4, 1, 9, NULL),
	(28, 'BIC 21403', 'Database', 3, NULL, 6, 3, 7, 14, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 4, 1, 9, NULL),
	(29, 'BIC 31903', 'Technopreneur', 3, NULL, 6, 3, 8, 15, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 4, 1, 9, NULL),
	(30, 'BIM 20703', 'Computer Animation', 3, NULL, 6, 5, NULL, 12, 'BIM10303', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 4, 1, 9, NULL),
	(31, 'BIM 20903', 'Multimedia Software Development', 3, NULL, 6, 5, NULL, 4, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 4, 1, 9, NULL),
	(33, 'BIC 31602', 'Degree Project I', 2, NULL, 6, 6, 5, 2, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 2.0, 5, 1, 9, NULL),
	(34, 'BIC 32203', 'Mobile Application Development', 3, NULL, 6, 4, NULL, 12, 'BIC 20903', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 5, 1, 9, NULL),
	(35, 'BIM 30702', 'Multimedia Project Management', 2, NULL, 6, 5, NULL, 3, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 2.0, 5, 1, 9, NULL),
	(36, 'BIM 30803', 'Virtual Reality', 3, NULL, 6, 5, NULL, 14, 'BIM 10303', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 5, 1, 9, NULL),
	(37, 'BIM 33103', 'Computer Games Development', 3, NULL, 6, 5, 4, 14, 'BIM 20703', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 5, 1, 9, NULL),
	(38, 'BIS 10103', 'Fundamentals of Information Security', 3, NULL, 6, 4, NULL, 1, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 5, 1, 9, NULL),
	(39, 'BIC 31704', 'Degree Project II', 4, NULL, 6, 6, 6, 16, 'BIC 31602', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 6, 1, 9, NULL),
	(40, 'BIC 32303', 'Parallel and Distributed Computing', 3, NULL, 6, 4, NULL, 14, 'BIC 21303', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 6, 1, 9, NULL),
	(41, 'BIM 33303', 'Digital Media Processing', 3, NULL, 6, 4, NULL, 4, 'BIM10603', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 6, 1, 9, NULL),
	(42, 'BIM 33603', 'Special Topics in Multimedia Computing', 3, NULL, 6, 4, NULL, 13, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 4.0, 6, 1, 9, NULL),
	(43, 'BIT 34503', 'Sains Data', 3, NULL, 3, 8, NULL, 12, NULL, NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(44, 'BIC 41812', 'Industrial Training', 12, NULL, 6, 7, NULL, 13, '80%', NULL, 1, '2026-10-18 15:22:22', '2026-10-18 15:22:22', 12.0, 7, 1, 9, NULL),
	(77, 'UHB 13102', 'English for General Communication', 2, NULL, 3, 2, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 2.0, 1, 5, 9, NULL),
	(78, 'UQI 10102', 'Pengajian Islam', 2, NULL, 3, 2, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 2.0, 1, 5, 9, NULL),
	(79, 'UQI 10202', 'Pengajian Moral', 2, NULL, 3, 2, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 2.0, 1, 5, 9, NULL),
	(80, 'UQU 11202', 'Integriti dan Anti Rasuah', 2, NULL, 3, 2, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 2.0, 1, 5, 9, NULL),
	(81, 'UQ* 1**01-1', 'Co-Curriculum I', 1, NULL, 3, 2, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 2.0, 1, 5, 9, NULL),
	(82, 'BIK 10103', 'Prinsip Kejuruteraan Perisian', 3, NULL, 3, 4, NULL, 1, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 1, 5, 9, NULL),
	(83, 'BIK 10203', 'Algoritma dan Pengaturcaraan', 3, NULL, 3, 3, NULL, 3, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 1, 5, 9, NULL),
	(84, 'BIK 10303', 'Senibina Komputer', 3, NULL, 3, 3, 3, 2, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 1, 5, 9, NULL),
	(85, 'BIK 20803', 'Kreativiti dan Inovasi', 3, NULL, 3, 5, 2, 16, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 1, 5, 9, NULL),
	(86, 'BIC 10003', 'Fundamentals of Mathematics For Computer Science', 0, NULL, 3, 1, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 3.0, 1, 5, 9, NULL),
	(87, 'UQU 12102', 'Aspirasi Negara Bangsa', 2, NULL, 3, 2, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 2.0, 2, 5, 9, NULL),
	(88, 'BIK 10503', 'Pembangunan Perisian', 3, NULL, 3, 4, 3, 15, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 2, 5, 9, NULL),
	(89, 'BIK 10602', 'Struktur Diskrit', 3, NULL, 3, 3, 1, 3, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 2, 5, 9, NULL),
	(90, 'BIK 10703', 'Sistem Pengoperasian', 3, NULL, 3, 3, 8, 4, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 2, 5, 9, NULL),
	(91, 'BIK 10803', 'Kejuruteraan Keperluan', 3, NULL, 3, 4, 7, 2, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 2, 5, 9, NULL),
	(92, 'BIK 10903', 'Struktur Data', 3, NULL, 3, 3, NULL, 4, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 2, 5, 9, NULL),
	(93, 'BIK 10403', 'Analisis dan Reka Bentuk Sistem', 3, NULL, 3, 3, 6, 12, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 2, 5, 9, NULL),
	(94, 'UHB 23102', 'English for Technical Communication', 2, NULL, 3, 2, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 3.0, 3, 5, 9, NULL),
	(95, 'UQ* 1***1', 'Ko-Kurikulum II', 1, NULL, 3, 2, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 3.0, 3, 5, 9, NULL),
	(96, 'BIK 20103', 'Rangkaian Komputer', 3, NULL, 3, 3, 1, 13, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 3, 5, 9, NULL),
	(97, 'BIK 20703', 'Etika Profesional dan Keselamatan Pekerjaan', 3, NULL, 3, 4, 7, 1, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 3, 5, 9, NULL),
	(98, 'BIK 20303', 'Reka Bentuk Perisian', 3, NULL, 3, 4, NULL, 12, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 3, 5, 9, NULL),
	(99, 'BIK 20404', 'Pembangunan Web', 4, NULL, 3, 4, 8, 15, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 6.0, 3, 5, 9, NULL),
	(100, 'BIK 11003', 'Pangkalan Data', 3, NULL, 3, 3, 7, 14, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 3, 5, 9, NULL),
	(101, 'BIK 20503', 'Jaminan Kualiti Perisian', 3, NULL, 3, 4, NULL, 14, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 4, 5, 9, NULL),
	(102, 'BIK 20603', 'Pengurusan Projek Perisian', 3, NULL, 3, 4, NULL, 3, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 4, 5, 9, NULL),
	(103, 'BIK 20203', 'Kejuruteraan Sistem Perisian', 3, NULL, 3, 4, NULL, 2, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 4, 5, 9, NULL),
	(104, 'UQI 11902', 'Falsafah dan Cabaran Semasa', 2, NULL, 3, 3, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 2.0, 4, 5, 9, NULL),
	(105, 'BIK 20903', 'Teknokeusahawanan', 3, NULL, 3, 5, 8, 15, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 4, 5, 9, NULL),
	(106, 'UHB 1**02', 'Bahasa Asing', 2, NULL, 3, 5, NULL, 4, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 2.0, 4, 5, 9, NULL),
	(107, 'BI* 3**03-1', 'Elektif I', 3, NULL, 3, 9, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 4, 5, 9, NULL),
	(108, 'UHB 33102', 'English for Professional Communication', 2, NULL, 3, 5, 5, 2, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 3.0, 5, 5, 9, NULL),
	(109, 'BIK 30103', 'Keselamatan Kejuruteraan Perisian', 3, NULL, 3, 4, NULL, 15, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 5, 5, 9, NULL),
	(110, 'BIK 30202', 'Projek Sarjana Muda I', 2, NULL, 3, 6, 5, 2, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 2.0, 5, 5, 9, NULL),
	(111, 'BIK 30303', 'Pengukuran Perisian', 3, NULL, 3, 4, NULL, 1, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 5, 5, 9, NULL),
	(112, 'BI* 3**03-2', 'Elektif II', 3, NULL, 3, 9, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 5, 5, 9, NULL),
	(113, 'BI* 3**03-3', 'Elektif III', 3, NULL, 3, 9, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 5, 5, 9, NULL),
	(114, 'BIK 30404', 'Projek Sarjana Muda II', 4, NULL, 3, 6, 6, 16, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 6, 5, 9, NULL),
	(115, 'BIK 30503', 'Evolusi dan Penyenggaraan', 3, NULL, 3, 4, NULL, 14, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 6, 5, 9, NULL),
	(116, 'BI* 3**03-4', 'Elektif IV', 3, NULL, 3, 9, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 6, 5, 9, NULL),
	(117, 'BI* 3**03-5', 'Elektif V', 3, NULL, 3, 9, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 6, 5, 9, NULL),
	(118, 'BI* 3**03-6', 'Elektif VI', 3, NULL, 3, 9, NULL, NULL, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 6, 5, 9, NULL),
	(119, 'BIK 41812', 'Industrial Training', 12, NULL, 3, 7, NULL, NULL, '80%', NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 12.0, 7, 5, 9, NULL),
	(120, 'BIK 30603', 'Pengaturcaraan DotNet', 3, NULL, 3, 8, NULL, 15, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(121, 'BIK 30703', 'Sistem Teragih', 3, NULL, 3, 8, NULL, 14, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(122, 'BIK 30803', 'Aplikasi Sistem Masa Nyata', 3, NULL, 3, 8, NULL, 3, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(123, 'BIK 30903', 'Kaedah Formal', 3, NULL, 3, 8, NULL, 4, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(124, 'BIK 31003', 'Topik-topik Khas Kejuruteraan Perisian', 3, NULL, 3, 8, NULL, 13, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(125, 'BIK 31103', 'Pembangunan Aplikasi Mudah Alih', 3, NULL, 3, 8, NULL, 12, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(126, 'BIT 34403', 'Pembelajaran Mendalam', 3, NULL, 3, 8, NULL, 15, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(127, 'BIT 34303', 'Pembelajaran Mesin', 3, NULL, 3, 8, NULL, 16, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(128, 'BIT 30303', 'Sistem Bantuan Keputusan', 3, NULL, 3, 8, NULL, 4, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(129, 'BIT 33603', 'Perlombongan Data', 3, NULL, 3, 8, 4, 1, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(130, 'BIT 20903', 'Kecerdasan Buatan', 3, NULL, 3, 8, NULL, 2, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL),
	(131, 'UQU 40103', 'Profesional@Works', 3, NULL, 3, 8, NULL, 1, NULL, NULL, 1, '2026-10-18 15:33:21', '2026-10-18 15:33:21', 4.0, 99, 5, 9, NULL);

-- Dumping structure for table fsktm_pace_db.course_plans
CREATE TABLE IF NOT EXISTS `course_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `version` varchar(255) DEFAULT NULL,
  `total_credits` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_plans_code_unique` (`code`),
  KEY `course_plans_program_id_foreign` (`program_id`),
  CONSTRAINT `course_plans_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.course_plans: ~5 rows (approximately)
INSERT INTO `course_plans` (`id`, `program_id`, `code`, `name`, `version`, `total_credits`, `description`, `created_at`, `updated_at`) VALUES
	(1, 6, 'BIM20252026-LOCAL', 'BIM Local Students- Starting 20252026', '2026', 120, NULL, '2026-10-18 14:53:33', '2026-10-18 15:18:24'),
	(2, 6, 'BIM20252026-INT', 'BIM International Students - Starting 20252026', '2026', 120, NULL, '2026-10-18 15:18:08', '2026-10-18 15:18:08'),
	(3, 2, 'BIW20252026-LOCAL', 'BIW Local Students - Starting 20252026', '2026', 120, NULL, '2026-10-18 15:19:19', '2026-10-18 15:19:19'),
	(4, 2, 'BIW20252026-INT', 'BIW International Students - Starting 20252026', '2026', 120, NULL, '2026-10-18 15:19:54', '2026-10-18 15:19:54'),
	(5, 3, 'BIK20252026-LOCAL', 'BIK 20252026 local students', '2026', 120, NULL, '2026-10-18 15:23:35', '2026-10-18 15:23:35');

-- Dumping structure for table fsktm_pace_db.course_types
CREATE TABLE IF NOT EXISTS `course_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_types_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.course_types: ~9 rows (approximately)
INSERT INTO `course_types` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'CT00', 'Reinforcement Course', NULL, '2026-10-18 14:54:37', '2026-10-18 14:54:37'),
	(2, 'CT01', 'MPU / University Compulsory Courses', 'Mata Pelajaran Umum / Mata Pelajaran Wajib Universiti', '2026-10-18 14:57:44', '2026-10-18 14:57:44'),
	(3, 'CT02', 'Computing Core Courses', 'Kursus Teras Komputeran', '2026-10-18 14:58:14', '2026-10-18 14:58:14'),
	(4, 'CT03', 'Program Discipline Core Courses', 'Kursus Teras Disiplin Program', '2026-10-18 14:58:43', '2026-10-18 14:58:43'),
	(5, 'CT04', 'Specialization Courses', 'Kursus Pengkhususan', '2026-10-18 14:59:27', '2026-10-18 14:59:27'),
	(6, 'CT05', 'Final Year Project', 'Projek Tahun Akhir', '2026-10-18 14:59:54', '2026-10-18 14:59:54'),
	(7, 'CT06', 'Industrial Training', 'Latihan Industri', '2026-10-18 15:00:14', '2026-10-18 15:00:14'),
	(8, 'CT07', 'Elective Courses', NULL, '2026-10-18 15:00:43', '2026-10-18 15:00:43'),
	(9, 'CT07A', 'Elective Option', NULL, '2026-10-18 15:01:01', '2026-10-18 15:01:01');

-- Dumping structure for table fsktm_pace_db.departments
CREATE TABLE IF NOT EXISTS `departments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.departments: ~3 rows (approximately)
INSERT INTO `departments` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'JKP', 'Jabatan Kejuruteraan Perisian', NULL, '2026-10-18 14:47:09', '2026-10-18 14:47:09'),
	(2, 'JKMTW', 'Jabatan Keselamatan Maklumat dan Teknologi Web', NULL, '2026-10-18 14:47:19', '2026-10-18 14:47:19'),
	(3, 'JMM', 'Jabatan Multimedia', NULL, '2026-10-18 14:47:28', '2026-10-18 14:47:28');

-- Dumping structure for table fsktm_pace_db.failed_jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.failed_jobs: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.jobs: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.job_batches
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.job_batches: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.migrations: ~11 rows (approximately)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '0001_01_01_000000_create_users_table', 1),
	(2, '0001_01_01_000001_create_cache_table', 1),
	(3, '0001_01_01_000002_create_jobs_table', 1),
	(4, '2026_10_17_000000_create_pace_core_tables', 1),
	(5, '2026_10_18_000000_add_course_detail_fields_to_courses', 1),
	(6, '2026_10_18_010000_create_sdgs_table', 1),
	(7, '2026_10_18_020000_add_head_of_program_to_programs', 1),
	(8, '2026_10_18_030000_add_alternative_group_to_courses', 1),
	(9, '2026_10_18_040000_create_registration_submissions_table', 1),
	(10, '2026_10_18_050000_add_elective_slot_to_study_plan_entries', 1),
	(11, '2026_10_18_070000_create_offering_groups_table', 1);

-- Dumping structure for table fsktm_pace_db.mqf_sc
CREATE TABLE IF NOT EXISTS `mqf_sc` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mqf_sc_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.mqf_sc: ~8 rows (approximately)
INSERT INTO `mqf_sc` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'SC1', 'System thinking', 'Understanding how different parts of a system interact and how\r\nindividual actions can impact the whole system. This is essential for\r\ncomprehending the interconnectedness of environmental, social, and\r\neconomic issues.', '2026-10-18 15:02:43', '2026-10-18 15:02:43'),
	(2, 'SC2', 'Anticipatory Thinking', 'The ability to foresee potential future consequences of current\r\nactions and to develop strategies to mitigate negative impacts or\r\ncapitalize on opportunities.', '2026-10-18 15:03:04', '2026-10-18 15:03:04'),
	(3, 'SC3', 'Critical Thinking', 'Considering ethical and value-based perspectives in\r\ndecision-making, promoting responsible and sustainable practices.', '2026-10-18 15:03:26', '2026-10-18 15:03:26'),
	(4, 'SC4', 'Strategic Competency', 'Developing long-term plans and actions that align with sustainability\r\ngoals, including setting priorities and allocating resources effectively.', '2026-10-18 15:03:49', '2026-10-18 15:03:49'),
	(5, 'SC5', 'Collaboration', 'Working effectively with others from diverse backgrounds and\r\nperspectives to achieve shared sustainability objectives.', '2026-10-18 15:04:14', '2026-10-18 15:04:14'),
	(6, 'SC6', 'Integrated Problem-Solving', 'Analyzing information and arguments related to sustainability issues,\r\nidentifying biases, and forming well-reasoned judgments.', '2026-10-18 15:04:39', '2026-10-18 15:04:39'),
	(7, 'SC7', 'Self Awareness', 'Understanding one\'s own values, beliefs, and potential biases\r\nrelated to sustainability, and how these influence decision-making.', '2026-10-18 15:05:04', '2026-10-18 15:05:04'),
	(8, 'SC8', 'Normative', 'Applying knowledge and skills to address complex sustainability\r\nchallenges by considering multiple perspectives and finding holistic\r\nsolutions.', '2026-10-18 15:05:25', '2026-10-18 15:05:25');

-- Dumping structure for table fsktm_pace_db.offerings
CREATE TABLE IF NOT EXISTS `offerings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint(20) unsigned NOT NULL,
  `session` varchar(255) NOT NULL,
  `semester` varchar(255) NOT NULL,
  `section` varchar(255) NOT NULL DEFAULT '01',
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `course_plan_id` bigint(20) unsigned DEFAULT NULL,
  `year` tinyint(3) unsigned DEFAULT NULL,
  `lecturer_id` bigint(20) unsigned DEFAULT NULL,
  `coordinator_id` bigint(20) unsigned DEFAULT NULL,
  `advisee_group_id` bigint(20) unsigned DEFAULT NULL,
  `capacity` int(10) unsigned NOT NULL DEFAULT 40,
  `is_offered` tinyint(1) NOT NULL DEFAULT 1,
  `day` varchar(255) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `offerings_course_id_foreign` (`course_id`),
  KEY `offerings_program_id_foreign` (`program_id`),
  KEY `offerings_course_plan_id_foreign` (`course_plan_id`),
  KEY `offerings_lecturer_id_foreign` (`lecturer_id`),
  KEY `offerings_coordinator_id_foreign` (`coordinator_id`),
  KEY `offerings_advisee_group_id_foreign` (`advisee_group_id`),
  CONSTRAINT `offerings_advisee_group_id_foreign` FOREIGN KEY (`advisee_group_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offerings_coordinator_id_foreign` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offerings_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offerings_course_plan_id_foreign` FOREIGN KEY (`course_plan_id`) REFERENCES `course_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offerings_lecturer_id_foreign` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offerings_program_id_foreign` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.offerings: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.offering_groups
CREATE TABLE IF NOT EXISTS `offering_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `offering_id` bigint(20) unsigned NOT NULL,
  `advisor_id` bigint(20) unsigned NOT NULL,
  `section` varchar(255) DEFAULT NULL,
  `lecturer_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `offering_groups_offering_id_advisor_id_unique` (`offering_id`,`advisor_id`),
  KEY `offering_groups_advisor_id_foreign` (`advisor_id`),
  KEY `offering_groups_lecturer_id_foreign` (`lecturer_id`),
  CONSTRAINT `offering_groups_advisor_id_foreign` FOREIGN KEY (`advisor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `offering_groups_lecturer_id_foreign` FOREIGN KEY (`lecturer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `offering_groups_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.offering_groups: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.password_reset_tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.password_reset_tokens: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.plan_courses
CREATE TABLE IF NOT EXISTS `plan_courses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `course_plan_id` bigint(20) unsigned NOT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `placeholder_key` varchar(255) DEFAULT NULL,
  `year` tinyint(3) unsigned NOT NULL,
  `semester` tinyint(3) unsigned NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT 'required',
  `credits` tinyint(3) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plan_courses_course_plan_id_foreign` (`course_plan_id`),
  KEY `plan_courses_course_id_foreign` (`course_id`),
  CONSTRAINT `plan_courses_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `plan_courses_course_plan_id_foreign` FOREIGN KEY (`course_plan_id`) REFERENCES `course_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.plan_courses: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.pre_registrations
CREATE TABLE IF NOT EXISTS `pre_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `offering_id` bigint(20) unsigned NOT NULL,
  `session` varchar(255) NOT NULL,
  `semester` varchar(255) NOT NULL,
  `credits` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `note` text DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pre_registrations_user_id_offering_id_unique` (`user_id`,`offering_id`),
  KEY `pre_registrations_offering_id_foreign` (`offering_id`),
  KEY `pre_registrations_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `pre_registrations_offering_id_foreign` FOREIGN KEY (`offering_id`) REFERENCES `offerings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pre_registrations_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pre_registrations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.pre_registrations: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.programs
CREATE TABLE IF NOT EXISTS `programs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `head_of_program_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `level` varchar(255) DEFAULT NULL,
  `duration_years` decimal(3,1) NOT NULL DEFAULT 4.0,
  `total_credits` int(10) unsigned NOT NULL DEFAULT 120,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `programs_code_unique` (`code`),
  KEY `programs_department_id_foreign` (`department_id`),
  KEY `programs_head_of_program_id_foreign` (`head_of_program_id`),
  CONSTRAINT `programs_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `programs_head_of_program_id_foreign` FOREIGN KEY (`head_of_program_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.programs: ~6 rows (approximately)
INSERT INTO `programs` (`id`, `department_id`, `head_of_program_id`, `code`, `name`, `level`, `duration_years`, `total_credits`, `created_at`, `updated_at`) VALUES
	(1, 2, NULL, 'BIS', 'Bachelor of Computer Science (Information Security) With Honours', 'Degree', 3.5, 120, '2026-10-18 14:48:25', '2026-10-18 14:48:25'),
	(2, 2, NULL, 'BIW', 'Bachelor of Computer Science (Web Technology) With Honours', 'Degree', 3.5, 120, '2026-10-18 14:49:08', '2026-10-18 14:49:08'),
	(3, 1, NULL, 'BIK', 'Bachelor in Sofware Engineering with Honours', 'Degree', 3.5, 120, '2026-10-18 14:49:53', '2026-10-18 14:49:53'),
	(4, 1, NULL, 'BIP', 'Bachelor of Computer Science (Software Engineering) With Honours', 'Degree', 3.5, 120, '2026-10-18 14:51:02', '2026-10-18 14:51:02'),
	(5, 1, NULL, 'BIT', 'Bachelor of Information Technology with Honours', 'Degree', 3.0, 120, '2026-10-18 14:51:42', '2026-10-18 14:51:42'),
	(6, 3, NULL, 'BIM', 'Bachelor of Computer Science (Multimedia Computing) With Honours', 'Degree', 3.5, 120, '2026-10-18 14:52:22', '2026-10-18 14:52:22');

-- Dumping structure for table fsktm_pace_db.registration_submissions
CREATE TABLE IF NOT EXISTS `registration_submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `session` varchar(20) NOT NULL,
  `semester` varchar(10) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'submitted',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `registration_submissions_user_id_session_semester_unique` (`user_id`,`session`,`semester`),
  KEY `registration_submissions_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `registration_submissions_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `registration_submissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.registration_submissions: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.sdgs
CREATE TABLE IF NOT EXISTS `sdgs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sdgs_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.sdgs: ~17 rows (approximately)
INSERT INTO `sdgs` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'SDG1', 'No Poverty', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(2, 'SDG2', 'Zero Hunger', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(3, 'SDG3', 'Good Health and Well-being', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(4, 'SDG4', 'Quality Education', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(5, 'SDG5', 'Gender Equality', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(6, 'SDG6', 'Clean Water and Sanitation', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(7, 'SDG7', 'Affordable and Clean Energy', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(8, 'SDG8', 'Decent Work and Economic Growth', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(9, 'SDG9', 'Industry, Innovation and Infrastructure', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(10, 'SDG10', 'Reduced Inequalities', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(11, 'SDG11', 'Sustainable Cities and Communities', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(12, 'SDG12', 'Responsible Consumption and Production', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(13, 'SDG13', 'Climate Action', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(14, 'SDG14', 'Life Below Water', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(15, 'SDG15', 'Life on Land', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(16, 'SDG16', 'Peace, Justice and Strong Institutions', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06'),
	(17, 'SDG17', 'Partnerships for the Goals', NULL, '2026-10-18 05:38:06', '2026-10-18 05:38:06');

-- Dumping structure for table fsktm_pace_db.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.sessions: ~149 rows (approximately)
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('00rBSS9pjshbScrBoIwMgE8KeZfAMvcRlq3lISVH', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJpck9OTktHeDh6Z0ZBS0pCU1ZjVHNDZXZ5dXlLd0Jxam12eFBjSnpOIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609447),
	('09lbC6tBLHTBSPakBFLYvP88KCUkBzJ3zISPJSIO', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJOZXVublU5b2JTcTdFVTdNS2ExcFRaa3drWFUzcG1PUHdhbXJBaUFvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608558),
	('0DJPbEMoucElKREBDvJWEaIJyeyYzqC3O41sOtMs', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJoUzVhM21WQVhIZTJtZ0NXam1HRjJ2UEQydnZXaE1jZFRjMWdIVmpFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610918),
	('29KiKLEPmV1RDtnFspX1zl80K5tzpgDw2sJjB3Q8', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI1TEtqV1E2ZkJZdlVheXNkYVZEUmduak81SVQzeUZxOXI1REZ0Y1hSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610738),
	('2drIuWVQfRYqoE1Qdwte7yGvZTIrKeNUyYHf0REM', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ1aXV3MzZOaWtUcDlGY2lXNHY2UGQ0cnZJcVdndzM5WnY1ZmREZW1jIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611549),
	('2GcxaMweZ3CngQbb1YUPVXzb9yBbTXr72rawUJZA', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJqMVB0bTFrTWhQdVF2dnRkN3RjWXhrbmNMTVU0cjJjUkpwQWx4bTRwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608528),
	('2JJNFyGAI41lIB8i1jPWsxZ9eSCfHQqW1hWVMC7x', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJBUDN6VnVxUGlxRExPSk1md1l6NnFqSUQzQWVsclk5eGNFRGRpWUc3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610798),
	('2qmdlYUFURm2WiWHNpMwDCR5ROWDA5hd2TYmxkEe', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJxQVdYRERsekNSbjRNRWhhYkwyeTdBR2I3dmZvdWJFdlg4MlZGRVBVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610558),
	('2r64JgysQq7YZRIDpYt6zZF0YNzjWXuhFZBvWWnq', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI2WWV1UHBmYXlZU2g0OFdlRm5Vdm1sdDZ0RkoxSmFiU2g5YkQ0clo1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792619739),
	('343IOP2eHpaD0w4Ubr8AMaaVolo2d9PsJSVoMfnr', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJlQkVITGR6YXVNWnpxdzhZaUl4THNmRFZOWHVJMTVsQmZtbWNBbkJYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609327),
	('3HLfuHy25kSIbkuSASwE4Am0LL3R7TfC9xMxqhlW', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJDYm1ZZmNjVkN4RzFSTThVY3VXbjU2WlJ4Q0tyZ3docFFDSkFFb1FSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609207),
	('3UezyoZV742lL69NLgvqJU0BiqtTGBbzixFVyaiI', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJzMEUxbDgxaHlhcFkwdm9wbnRSYnJ6NHl5NVBQMGlmcFVhSzBjTWxoIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608457),
	('3Wndv9u85WjEIvt8BDWJ2ka6sCgEgcYGKjV68PEA', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJhZldNM2lDR0pnRzVxY1paYk0zWVp5OFBpS3lCNzN0b0p3ZGU3VzhXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608337),
	('4LugFGwWt8gwd4P0QXnTqzuP6qjMkfQnztTHpdMg', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJYT29rcHhMTHhCaFNnUThDODhNTDh2RHRMZ0pEN3dWc0U4T1dHMlA2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608895),
	('4mnjVJ5mgk6An9b8nFad0Kd7TXmzjWJxgavLNrmM', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJITm1qbFV1ZWc5YVlPZmZlMnp0QnY2TElhOGI2QlVKQ2g1OWpxZkZwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608955),
	('4YpU2Wws3O9uWqjiosmjgYM6DCYAqZ2TC023MFEH', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJLWEo0MWN4YnZSalNpSFA0NEtKWkxDRWhZVk84VzRFcVYya1hnWlVSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608228),
	('55bfxkz51DK2mfpWL8dqNnxIzTOhqiTqC6CSylo0', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJrcGU0NllaYWZ4Y0dDcTdCYkZOVXNJRzFwNnoxdXJmOXV4NmR0V3UxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608937),
	('5gA9bmDA1F3Gf5rCxWdTRW4kAt6ZQmuXFLmuciUv', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJYN2dVNFVuMVRkZFd4SGlVVXFramFucURuVVdEQkFqSkZKQ05ENXIwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611729),
	('5OrKXU3pJZJFhz6An9FiV72l0HHI5Wm0dWcC3zBz', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJKdXVkNG9sdEMwSGc0ZWk4aDd1VGpGbTdEMEJwdFpnbU9qeTl4R1BoIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611279),
	('5XuDeqgoDu83hmjAIKirmapbZIcqzc9ZTg5vFwDF', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJtV29jZW0zQVdBZmZSelNuN2xYZ1AxcGVGbVlWcTlWYlpBV1RreEtmIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611159),
	('6d9Do2oNTyavn5Qvapl8i5KwLyghbRzQgk7RPAyI', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJaMnVVeGxzY0FrUUsyQjdMcEthV1hxN2Nla283UWNNTklZNEJwY1JMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610108),
	('6pUIqvO8bD0lGtLOGbsBlYJsuUk2U7cK5voBxgN9', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJsUjNWUUlva09Hc3VnYjdwSjF0VjNmYjRCSWphYnc3SDVmRVdWekhCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610978),
	('6Rur2P0liGDnPYqZBrygReh4KkutFm9YRIlDyMlv', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJTVlpadmFsMUN0TkZBNXE3VThtUnZTVXFuWldpNTdMVmdKdFMwV3l3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608367),
	('6Sy42GhWe86D4drk8RSNQpAJ0GcKfVtb65A6GLBk', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiIwMkJsTUQ3QlV2MzhuOEZGaE1icDJTYzllemU0RjZyTWhjTTlZQkpvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609177),
	('6UShqkXactJPnS9yQsIOPtmHNsK2tzQ8XAM0xqPu', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJzdGt5UWNVdDZFem9SdTQ5U21HV0RIcWdqdWgyR0FpUDBtQ3o0NjRvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610258),
	('6Zcihk1M9wn6qrcAdC4naSWYi54h3Wb1lOtVmTUP', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJiSW5lenRlUEx0eGdOVTJEeXJ3UXdUN0pXMGxsdEZqOGF5dGF3dHJFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608258),
	('7E4Y1B7AaB6QK7r4O3qpQms03HGzKoHUR6KMM3wp', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJpRHgzNEFxSHhKSmYxTG5lN3ZMeWZldERYTEtYZnp3YzVHblJVOTBRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610018),
	('7FzfE0hyR5TxW38kIMJkhjgSNMgjWZPkBOIqnIpL', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJIVG1JTnp4TUNWajJKUXlFVmhDbFlXN1Q2VE9Xc2J6T01qVklzaDhwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609297),
	('7ipus9TPRKRWmuDuOJqiL5zfgOGAgRLUijpdMSW0', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJQZVpSRTFYZVkwbVY0clIweEpyamVOQ1pHdVA4RlVUaUtDVnRBdHFVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609087),
	('7lwvZYHZ8m371zhn0HQixElniT1UrxtLTmVzUQji', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJnNk1VeGJNSmpLOE1RcWZyM0tuOGg1WWVFNUh4WTJGZVFPcjZsRnBDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608307),
	('7qeAGZ8xeYNy67hvQaBOo20cbvLBlIcVeELfnQlL', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJhaTNjekhMTUNadlgzNU03Z0wwdHhyV2k2WlBTSDlBdEc1Qm9KekluIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610828),
	('7trh0TfUPNK8nM1wfrg9wBLhmiBq2u77upwzEYJ9', NULL, '127.0.0.1', 'curl/8.7.1', 'eyJfdG9rZW4iOiI0V05KRk4zNm1jVUZNWVFyUXkxa0JZcE04MmZVNVpLUHpBSEZzaGFpIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2Zza3RtLXBhY2UudGVzdFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792619837),
	('7YGglkkmpug8Hf2ksEOQn6K8eqjPJf3MVJl0emC3', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJFcHk0NjNkd2xnRWVJTnNuWG4ycE1BYnMyNjlXTnA1dDRweHpyT2tSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611519),
	('8LRHeKxqh1rOcEZUGc3bGmVrKSay0mVCosAevnoY', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJSM1dDRjdMOVpBYmlPYnhWclkwaENidFVjUzI0MXhUNWE4RUNLbXlRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610528),
	('9hQaerqpvQINMIc555AJGt48kwD3w7fnAy3Aw4pD', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJEWElhYngyRnlmZ0UydDNCTTdPdlF4NDBoQ0tnT2F4T0RXcDEzU0djIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792619799),
	('9i0AD4SlLOAekrWbobWgShsw3gULVgSlt2NyFCL3', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJvNkRzWVRWdjYxNGFzUWs5VzF0SUV1YU1JQnN1NGt2a1JVOW43bEFtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611429),
	('9mi03MF6w9O2Pzfaa8zM2U4nf2IWGyzloOzkz6C7', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiIxVWg0bEVWdFhUWjdicmIxYW5ROG90dm5tOTZza1B0bDV0TjhSTWpYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611639),
	('9yZFKE2jwaGZ6VaXRCSNPXtFphVllEEGu4vwaJ8F', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJWSmNxUHlBa2RUbmpmdWRWbFVLVUJ6RFhRdzRkMzRSSmVNd1YzQXIwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608348),
	('AHcXIx0iTBq2K67Ofv7lwITZkhNH2isdavBAJ6rF', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ6QUR6eDd6OVFSdkcwN1VPSUZrTEFtN0dqUFc5cGNFY29UOFYxWjNCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792619679),
	('ALky8sLVkXS6Y4I0qZZkrPfldPD28GOKykcsXbwX', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJOdEhJWGtGMXhLQktmSWdyaDBLVlpmNG5oY1ZQaWFYRGtCczRWMnZsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609958),
	('AQAYzL7fux300aZeMbJJlzGyrCx36uzisBs31PCV', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJla2hNS2w2eGpoa2Rvb0hYb1o0YUE0SVNwM1lWNHZvQlFNbGRyY09EIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608288),
	('aVBfbmHwwwRyR4VyBmcg4psf1rKDM5cxyVBToJlp', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJUc1ltSll6Q0c4bHhWZ2xRQ2NYa2M5dzNwQkdIRTRJR3hNMHM1U29SIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608985),
	('BBFBXFnFrhBAhC5rRVkHC6YSpXNqkkfazCSYZrVk', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJYN3RYRElySkZRdHh6WmZpR0Rpa1JxZWJtT1ZQcHhTZlVFbXVxc0JBIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608648),
	('bmt8nz0oDcJpudQxoL2ocGvw42Py9NLOnpDjK1IC', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiIzYzJOVGM3VWxOUEVrcHk2SUxrTjJpc21ETUdCNTVkOExpdFE4YWdyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610948),
	('bpTeyC4umyfkVw2JUlrveUfRkXeGpuLOVUNaQINp', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJWZHhnZDR3YUdYeE9TUzliY2NPbmUzU3ozZUhWMzlCNklsc1M0MHlMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610708),
	('bsqItF5O0x4yHpSRZwAkRkBUQNso4QEpLyOBzVvd', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJnMG9QcDVDQlBXN2RFV0RjbUlvWWdXaFZkNVdTYUh3MFNtTFF0Mnl6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611189),
	('CRYcw3l96xx93vrMn2rpzVqgCGMhdDtKzZ0M2et0', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ0VVpQREU3dDN3UVdTdG41dzNWSWZQajRPTzlLRnd1QmhhVWNwa0VmIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610888),
	('CTYjyL8fehwjjjKFtlG77aNIZoOrmg1xzfSAbWVF', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJLbUlFek40aGh2eXVWaUo3SnNQM2J4YWhPcEh6NXgwUG14c1dOUnJxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609387),
	('CZdtdDkOTzEmHvmDlqTFQx2fmgwCPLTBCRnq9bjb', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJrbHM4cVJOM29DSDF6RFVkUWZPMjV5QWpmZUp6TEx4Vm5aWVdDYWZFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608186),
	('d0AGEYSL5baAIX4k74zhPQbciaxzMjiGTELZElZS', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJzcVRGMnVkTGtjbE5EbXBhTTJSMHRCbTVVczJYakVNU00xTDBlTGR5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611309),
	('D4w7XmG8EouFdTsK7BDwAVUs06cw7xV44ycEo7br', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJNZVg2ZVplYndqbjhCR2o5T3RmVWRweGVnaGVwZ2NOM1I4Wmk1dEtjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611579),
	('DNMgx7P7d5lgm5Ob4Ji8f9sx0zfxVRlKDKU0vdIj', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiIzcm92eUx1VU9UcEdKdXJiSGxHTHZMcUU2T2JZQ1BQbXVQNzRsdGk0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610768),
	('dXwrsoXyq5fOUgpdQ8rqnZ0rb7Uo6Dt0vVJvV7Eq', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJnbWlhR2hJaXM3RXVJeWl1cHY1emZyNEw0ZExWdnk0S2U4Q0dVSU5xIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609657),
	('ESouDcT5HMWiG24YnpquIFF74eS4Ph1ofxGidPK1', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJJYXpydEt3bHFSUE85YWxFdkJMTDJsbndzUG5WNklWYUlmdUtudHBlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609357),
	('eyZT8Y9wZyTMggnEsnPmBPmTa8PrdVJd3TvmQAJ3', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJYYWNYUHEzM2swSEJJMDI4VEtib1J5YVBTeDR3YTFaNmN6UXN5UWcxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609597),
	('F87FX4zH3GcSpot0ZpY0oRjVjRAmSlKNZtwzkcK1', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJMeEFpR1AzSkZoZmdjSlFjYTM2T1RQOHdOYklBbG9aaVVDTUhNOFhuIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608607),
	('FOhYkwo0LTb9vKIPVb4oxfJCLXg6AbCji4tm94Z2', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJib0dFaHkwNUZzRFNvU2VodXVOZjRLSmlqNE9KeEVqeXc3aUR0YndLIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608637),
	('fqJvJGgWrnd1w5FxW0XQa49574KWiyy0Sl71LD00', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJhbnpiSDc3THlYYURaSUlBZmVGYThhQ2ZzenMweEF1UU9sN05GeVNZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609267),
	('fSwhHbsHMBje6hrpJwWRk0EO2OJhYS0foj6BglOj', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiIza1B3RkRIdDA2YTlYVTFqdlRubGN6SHpaZmFLQlJ3dkJWUjJqU05uIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609045),
	('fWSQNkuT7V9AQds7caRfXQDgvIsk6ZaD4VAIO53i', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJCY0Q3ZWxNODR0TFZUTWl1aFdGREUwcFhramZkRlM3Qzk1ZXpKY1h4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609718),
	('FxIFF8ZdxyhhM7pCSy9HUCATUiYxSptWtEeVyrAo', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJRN25BQ1JZMWdrV3dGVE4zbTRsVXp3T2ZzTm1rQWNac1EwbWNEMWVzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611249),
	('Gngi7LpAgXLzFbQn1SWGuGXF9zwCMYoTPKhxjtg2', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJXV29SaW11M2w4SVplWVdvSWpEZFNzc3RkTDI1ZTlJM0NKZFJJSllwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609477),
	('GtMR4hPW6uS9RPwrt0CPrhgg2AJs96RnMNF0juwb', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJTVU9IdXFQSUdRWlZSZEZhVDh4VmdKUGNueGhmSThiSXZ4dVFHMUFPIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610228),
	('h3z8B7nzKGNQuhFoGwFSMkbv9Dv3d83TcFcre524', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJlSkdMWUphTkljSmxuOHhJM3ozdVR6M2pUdXh6TUl2Um1XbWIzSVhlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611489),
	('HDnMzH4COhy69qN88j6s6MM9n2rBSvgCXpxCt15j', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJPQVdsemVzQmM1TjhUbUNaNDJxNUVVZXhtdVJHb2Zrb2NwSGZkWTQ2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610138),
	('heCE99mJiNZKX7dVEOz5Stg2GIjkazw1HrL9heW3', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI1T0FuTjhnOVNkOEN3QnQ2b1pVWlRkc1RuM3k0UU5NUzlIOXVvSUljIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609567),
	('hlKFxVg4pi9tS3Gfwy49yOayM94bSpD4xAaEtlnv', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI3aUE2eWNJY2F5eG5tSm9PQXJxRGpGN3lmQ0lFUVp4b1hiS1hXRlk2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608727),
	('hOXw0OtHbQq7SaZLhbtr7iQ0drUzYbnIpKK8V6oT', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ6M2hjMVRKQzVOV2padmRCcDJXVlo0ZERyelFPSzJDQWZNTk5BVU1BIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611669),
	('HVtpSEiLpzstcYQ6y7OS04edDEWVJl7AGHS3qNqI', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI1V0JsNTlQSFFlU1FtbjU2V3dQRTE4QUxBOFZFWWh5ZU5pZzYyTkd6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608156),
	('i5iv3m6pTngAw5lxRU6VPzOR4nvlS09mCk3lpelq', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ2MTdQV0pCVkRCQXJzeXBQTkdXOGM1cjVVejlSRlpBeUsxVERJVHVnIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608517),
	('I7DqzQdhF27xDkmYkvDqmuScQjgNEtttDFUAwKEm', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ5NHNDZU45U2hLem5zUXRDcnd5Qml1U1J4QUlXdDlVWmJlMHpaNU1LIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609015),
	('iCItRn3ZBBBIrgjsZ8YvyJZojdcQUUO9YyO3ZJJe', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI0WE9kaGRhdHFZY1pzemF5VUt3cktqU1F4R2I3MWZXWklRcUtnSmhSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611699),
	('inOYiiHgdfgW0vU0ETUXWamgJ6GI1kHndI3JxxGc', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJNYlBUZmIyUG9rNm1RdDB2VkVqcTFkVld0cmNmM2xyU3g2SnFWa3V4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609928),
	('j42YpzdH3YVZLGripwhqhAqQavE6OjjQ4JWLgzXp', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI0M1cxMnU2dmRkQ2NwaW5vdnRaV2xMd1ptN0FyTzhTMjFwRktEQTE2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610678),
	('jauva33uhhxuJxO5DHHmuTR9P8lNPwDNpAwe3921', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJybnBzZjRYN2pORHU2WVJnVWVvSGZzbU1pcXNxRlp5U0M0WW96SzBwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609057),
	('Jl2cpkhWYSRlPCEywPwN7BBohRWqZGF3mhj4G4hN', NULL, '127.0.0.1', 'HerdApplications', 'eyJfdG9rZW4iOiJvZ1F4VHlXeTFlT3Vsc2w5VkFkRmpjTjE2TjNVU3h2RzN3WFVQN1ZUIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2Zza3RtLXBhY2UudGVzdFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609062),
	('JLLOT5G20ovSVwlN5VlUsEdsuPgGEqm6c5J9JziF', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ4UlJRS0c0aWlxNEdPREVHSzlIOEhGVlNIaG5xQkJIdXRLaDFDRzc0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611339),
	('jMNC1ppJNuWUg8ztxc4BaqZVNXOB225XSDdogvhm', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJzRDhYWFRyRTBybFJRZ1BaSTIzTE1jZzBkY3VVSlFrR1JWOUptZERpIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608877),
	('jpK3TlZs8yQ24ioD175nDujZZ1J7hJHFkwps38AT', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ3NFd2YjZJaHBwSnBteFRXUnc0V1ZqVlFCS2R3eWNLeFJkWFVDQlpsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609627),
	('Js6vpl4vvay0wpC7cBFyXYF4LfJWyCSCb3WoF5e8', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJaQzR1NTFhNXRQSkRINllUbGNQZjNpbDFWNEdiS1pJS0Ntd1UwTENrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610498),
	('jsVzIWdmRtCcKbuRMcH4MMgHsw5fSQCekgricvI7', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI1SUdIUW1WaVBaOUtsSFNUc1pvZ1hCcmRlcktXY09VWjZlNVgxVEh6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792619889),
	('KbqngBGKLL6qfbf6z503wFq7oPwjXdXDU01R0HmV', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJaWkZnbTFRRE5CcldEN3JzZE9TMnZEakhnejI1ZlhHUHR2TUVPQjYzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609988),
	('kdl1I4IyixkKYFT6tUBbnqnkHpJUnHwYzpIQUoMr', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJGUHBQNE95NGc2VG5ZcmIxUWZTSnNIQXY3RVAzRDRuckt1SFJ4eGZNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608907),
	('KgCUcNrNPHLSEtJyHuoIPobLW4MH3Kfi4Dwu9IKK', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI0Q0dlR0lrMlB3RkZIM3NMeUlQV2pTSFB6VWFraGZ4RkUzQWs4MUFRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611038),
	('KrmwOsElgGfqRch7Ku7etzDDdhu8QvYOsiM3WjW9', NULL, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Safari', 'eyJfdG9rZW4iOiJqc0Y3MmtEeGs1VGhaN0dVRjkyOG9NOUNMUVB2bWFPSVNiWktkeVY3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2Zza3RtLXBhY2UudGVzdFwvYXBwbGUtdG91Y2gtaWNvbi5wbmciLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608156),
	('KzRK7zLLBwXvsHWulbz8n8wIufOC6yfEglWgfCEo', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJMMWpzcDB4TFd3YWU2dWxxM1hxQ2FrNnZJeXE0VThsVGlrQ1l3b1Z4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608277),
	('LNRd3mxsomqGQVLZxjxgtaGlRs4lv08UUtlikbHu', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiIzUnFVc2J1M3VTM0xIMndLckdVRHJqaUVMZk9SWUFPY3ZXWlVXdms1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610288),
	('lR22ZEOUBZxxGJ0ZTx8RZmHpEUWJyQpJbD7C82Ts', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJlR2pMWHNsbTZ1Wk9OMUx3aVRQdHNzelJrbTIwVFFGMlVKcWhFbTdaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611068),
	('M1qF8LrPrjPkQrVL843XfgA3c6vZVXEYuu64j0BQ', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJxcHc1aFJCT1ExVVFTQ0FHSnlraEhFcEoyQm0ycTI3dFF1Sm5ROFBsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611098),
	('M8iKfz7FJCHpVlCe9RufvNvBeFOduSCdd2h3PBkE', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJoeldveXJXbUw5cE5aNzY0QU00ZGs2bW81Sk94czZmZTBqTTlHeUtHIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609898),
	('MrNDURlS4qss0kEW2wz35Bl2FzzNbZNw4rG5Ygoq', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ3WXVWdEU0UHpWeGJ1SlRHSjhhQzNFTGFEZTRXWE92TW91SlAzYzlKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610468),
	('myw0cD05dvcVCed9OC7OACs9Lu9CRgH1pjstwFr2', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI0bkRDYXBoeW1JM2VqSW9yb01UWk05S2Q3N2pXMWVwdXVYdHhWSGE0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608547),
	('ngejnIVZz8KPo6QBbM3B2vFMtgIaIEZbtmLc2z47', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJBWlRmRmhhVURsRkhhUklXcGJUbFVIdlpWSFBwU1hTbGpJQ3VLdEtZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792619859),
	('nj60EMQKPeotBOAdQwFWE9DKubKcuF7cV1hhoz9r', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJFNVFNZE01THR0ZndLaDFqdDlOZ0NPWTBpSFBvVkpGZTI4SXVpWEtQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608697),
	('ntnRMTHjJUeseKefNoz0tU08JjEHXIQvFZNaAOGt', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJrblZJbzV6cGg5eUlXeGpPRWU2VlFtU0xtSU5SbEF0dW52N2I1aTdZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610648),
	('o0Y5IhruQpuwUOpaVkUcvztg9y0AmJTKxHxMan74', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJmYmY5WVhnRnN4Nmc1OHprd3lUQ0lBcGlpZjhDdjYwSk1kZXl6UkVNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610168),
	('okHwCs0W7zROSKqNyuujlqbzLq8zUax57DEIn46f', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJrWGZlclJHSG1WVXNOb09UWWpzSnAxQXNySDZDdGFiR2h4TEo2Q1lIIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608318),
	('oNIefcOZtbuC6aZYzxhaJsJwcJ63xVatRKkRWxC2', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI2YTN2eXppZk9acklSNVd1TE50emFRRUhQZTh4WmNhcWI4MURmQVg0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609838),
	('Ons4Smw277kjEnXDjDeV7qjGV5fFF2eCDSENRk6T', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI1MWp6VElhcVZRRDFhMkdGb1FwTEQyVW5lUk5Ld2V3bXg0NlpxSVVpIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608618),
	('oqVIP4eopsUgBItHluC7FGLtLMt6jOThLT5v7d3S', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ0ZEU5bTlkU2llTWM2UDZjM1VITk9WeUI4MzNUT3BhdnRxdWxBcmhWIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609147),
	('oVJFk1mJ7teToOB4CtgPbNV83EJDbm98W53V5HW0', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJDSWxWYkZvQWR1VUN5MDBQWUpRc3N1aDNUdzVVWVlicjhKS3hkRjZ3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609027),
	('P326qW2TQDxa4OFKPwM4bncntByCX7XMZVAZj1Wq', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJoRGF3WllpclQ2WmNoeE5BbGlydDVYbThSc1VnVDc4R0hPOUhsdXBEIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609688),
	('p8JEFEqFvfyhEBlFcEJ0wcXSEWrmbXwtehHP6lA9', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJVZnl5cnN4WFhQelZrR29rcHVJUzBSMEJvZnNVeEk5U2JIWUJJTEhGIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609868),
	('PAk9EXbpQRawPuM5Tx2ojt7cYyouHIgVesv2b9NL', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJSU3RaQmhDdTN0ZkN0YTJrVFRKcVNTZUJZTjZ0MDlSdzV4VkdiSVJRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610408),
	('pMRucXykubFuBeV0QoCeNg2kOAdv7O39UuhC7JDv', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ0cjlydzRmVEhMamt4T0tBWmN3SEFDOG5hbzV1U25DSURTbmNVNHFIIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609778),
	('PU062TGaHxMbxJxBdMd4pAEu5wLPG0BuQnt9BhnB', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJCRXQ3ekJUWjViMWtzVVEwMUN3WlFHRTRZOUlRRUJvYlZjeTJwVHRKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609117),
	('pxsqfTDxgaGlI02FSdxlnBjQX45TgbU9AoymbFCd', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJocDZCQkZHWEdHRzFDNjRlMTFkY01JbmIyWlR1VTM3MVBSeklxc0t4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610348),
	('qlLItBpgECzlLChFk8gdhtzWwo6TY7F5dLJ23dl4', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJoVmhMa3Rra3BrOEFORlVLUjVYb2FwYnVxcWJtZWVFR0RwbFgwcWVwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609237),
	('qn5lQu8r6wdgeqkQZsmMBeLJzBAYHh9ruolLNfKG', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJkOTNONVNIRlFWOG1iOXQ0MzB0UDRoTTJuRnpRdDljOWRieVpkWldDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608216),
	('qOX7TP4WePU4hGT7wIdX6hQRynaWwmCKLydw1VuA', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI4UDgwbkdGVjJrTFN5WGJUQ1ZFWGVBZWhnNVV1Q2dzVGdSWEY3TkJCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608468),
	('qWANHtXncZlxKgQWdE3oiOXMJSUnn7GmQi9RzNSy', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJiUkNmRXNBSVpGT3FWbm5qNXU2QkluQlprTmJxaXVZWHVzc2FGQzdaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611609),
	('qxUumXVPUr75E4meu7zy0yDp4ZEICKxVBlS3FHXY', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ4OVJydHBkekQ0QXhIUWhRUEdiTnFyaElabG9acHFaQUxnRm40YjVDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610198),
	('qZZDuk5YebBvHZGmM3M2UvcdpDvEtrhNviBz64wX', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJmUHExb3BvakdVYjBpQXNpTjNJQkJaUUk3R1U5Y1hFWlJsZVlpQ0t2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792619829),
	('rgx3SmZq6HX4xrNFrfjIsujDHcpPPYBMUK0b9YVF', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJiTXRGMGNvV3NVaEZWZlNSUmp4WDhqTHlvYjF2TjR6TDFIMmNETTUyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608247),
	('rZXT2cN4X2mgvXowHYCm6VdgedtqqmAdluz9RFs9', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJNQTJDVVV3MUM4UGl1ZkRtMGNpSERGT2I3c2tpQmxKWDR1TVFzblpFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610078),
	('sFzl6yLlghELZbWWZ4xNYHtlCJUMKntH1SAZeTz7', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ6YjBGaldPWEVXSmQ0cHhMcDY5c2tlUk1ZdnFFdHRNOGphTjBxZXBQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611399),
	('sqpMOLSjEYAgJ4S39nqjZWNraWZFrGvZ5ME5TdnV', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI1dDNOWTVQdDJkTEFEUjlQV1l5TVRoa3hGdVQzTjAxZG12dElwOHBKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610378),
	('sRrMfwjH23Nkg0yBstX900vRLbNAgWBXQasDDwuP', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI4Vm80VTJBTTNueTA1aldHb283dXlET2ZqakZDUFVFYWNUOFlqcnltIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611128),
	('SyNT7fXOiMaQWTglTgiBPYFykcKnsrK86RPpJM6B', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJoUFQ0V3N3VXZ1YjhGY3JFQmpLN3IxY3VjVGJ5MjFIcTNPeGowTE5kIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608438),
	('t71qqGNyZErpGNHWq5C2RSxbpUyBtyqfnGoeXhJt', NULL, '127.0.0.1', 'curl/8.7.1', 'eyJfdG9rZW4iOiI1OXZSdFhHWXo5QU54bDN0RThJb3BZWFdNdFpKS3FNbVdGeUtiVUkyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2Zza3RtLXBhY2UudGVzdFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609026),
	('TVqwxu5MLSznPmn0HYBvUzKdXnXUUHBDZRpluPo6', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJqaFduMzB4alo1M3hrT2VqZk1xMzNrVE93VjBrd0ZwZzhRU2RzRkNaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608588),
	('uArQXDQiH6qoAUEniwzvdrUZLqfUWBzkPcH0AlBB', NULL, '127.0.0.1', 'curl/8.7.1', 'eyJfdG9rZW4iOiJDdWd1N3FVNkw0OW1aY2RCWFFsMUI5M0RwcXhORzFtbXNFOUR1VzJ1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2Zza3RtLXBhY2UudGVzdFwvYWRtaW5cL2NvdXJzZXMiLCJyb3V0ZSI6ImFkbWluLmNvdXJzZXMuaW5kZXgifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJ1cmwiOnsiaW50ZW5kZWQiOiJodHRwOlwvXC9mc2t0bS1wYWNlLnRlc3RcL2FkbWluXC9jb3Vyc2VzIn19', 1792608483),
	('UBaKtNzkUKt6b4nMtDHi2ANFBJ8qKnYM5CrvpzUj', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiIzQk9kT0tyRHlDbk1vRlZYWEZHV1lpcjd3eVZqNnFpdVc2Wm5hM0VCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611759),
	('UCMPbRf4IYbFxeSZ8fWtU9a5JoXH5HcYBGN6Hasf', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJyTjBnWUJITTJKazR5aVowQU9NVDBhc3BHOTdTV2pBY2dUbko3cjdSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608577),
	('Ujmi5o78BZTHSREdLS6XhbXJArUq2LUHsoAMZXFi', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJiaTR1clJUWmxzMlVuTFQ5akF4ZVA1d0hIZzVteFFwTkhWV0V3WWJjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608925),
	('UoKnv8KrtI8EXJi624vqUkyubXhTEW8I442vsnBY', NULL, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15', 'eyJfdG9rZW4iOiJOSzZIMmgzd2JTQUY1Slg1QkVzOHlSWVA0d0NFT0JReDNpOG5jY1czIiwiX2ZsYXNoIjp7Im5ldyI6W10sIm9sZCI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvZnNrdG0tcGFjZS50ZXN0XC9sb2dpbiIsInJvdXRlIjoibG9naW4ifX0=', 1792619826),
	('uOlDXr4PEmlA7SiCxHAi2Xsh0xzijHuCMhMtdPF1', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJLNlhDdUZYMVVTMmVRZVZQOU5nMHNRMkpxUG1FQXJ5eVVMMmZIOThxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608487),
	('uSxPFLpXurY4lpeu0UC9NB3LoXtk1scWD657Xf6H', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJNbGxpaTVJNkxJN0EwQzZCbmgydVowNm42RENCc25hZDVOenhaeVh1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609537),
	('V8rFpAWWR07v8UCHLixza4vYOgGeQ0ucgq64rzFl', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJpYkhnejZTT3AxcnhkakM5TWJuZzNmdWpqSzJONVQzTnNWZEtNMEt1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608498),
	('Vd1Nv3hiYqdjsegEdvdI09wdAQtsF2f8qhTXDTN5', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI0M0JsOFJpM3pya3k0blZ6RGt1UUZkYlN2OFFld1Y4YnBEbkh0cnUwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609748),
	('VEQsxOC1H7Bejd5DDAg0M3rfUuNWaDKE4L0DyZZE', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJCeEFhbUtpUmV4c2FxN1paOGhNd2ozalZQSEFNeFk4R01YWGRqcmFBIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611008),
	('vKGHNiP1MmViBuP7XIZvWwT1iU2JumkJbIJyETAl', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiIweHJJSFBoY0V3ZzBaRW02OWtqTjBHbHZ6YlBWS2RHVVJRZFdjdVlIIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610438),
	('vX6T1LwtY9oUWjFlNvYSVc0Q3CMybaNYkg9FvQvq', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJSTmxsY0EweXl1RlozZEZiRXFBa2tXRlJkZVdNcDUxaWdGcVRoZjIxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610618),
	('vXEif4ZCQOvCe5sStcG4N3MZwB0ZDYOYIs8dqYRE', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJidUp5Q095S3kyMzFoY0xSamtuVzFxM3hYbWZqWVJTQVFVdXpCTW5PIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610318),
	('w1l8yEJFFi8jBJOa4K4heqoZPe1boAkaymflmmpZ', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJRM1FvcXF3cnZURE5udWlMckF5cHR6TXdCd1RLTFB2YkF3SWExS3F6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611369),
	('WubPNInhQKxdz3P9vQIxZws6rB2ldRE36oF1cKRv', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJOUU9lSDNsS2ZtcUFtclZhZWo3MEQ0UmNabEtaeHFkVm5oOXdjWWlVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609507),
	('xC1vSXA0CijicKJLGxCVdgrpx0XUsOQnk8MMdtdb', NULL, '127.0.0.1', 'HerdApplications', 'eyJfdG9rZW4iOiJxZDdPN2dEa3lmSEFJYkc2OVVYUXhwZnp2WWJiWTBUM1A0TGpxTEVEIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2Zza3RtLXBhY2UudGVzdFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792619901),
	('xeqYzBrs1CT55v2v1xfRLOsJFG8oDafuMvL1mXPz', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJBUDlxSTRSTmdReUZiMUhyN2pIWGZsdmE3MTA5b21iTndmZUxvd0FxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792619709),
	('XlvVrScUSG315XF0CYjVoYSRyYnBtnBx3SBQWCdh', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJUSko2WGhQeVFDNm5yTUlkQmdjWFh5THhuS2tUWmd3YTlwQzhFSEM1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611789),
	('xN0t7SvCsVTYDm1DPBtmogrcBHi4JwJjtU2PbtqI', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJvQnY3YmVjV1Z5MThUV3VoZnF2aGRlSmFIZUMybjFmMTNoU3ZoSzdNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610048),
	('XnnibLGmMjUZ6nJzehWTX7gNAkF4piSOXMGWPmBm', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJYOWpCd3F5bzJ2UG5Oc3psR0FaMnQ2ajlkZThOS1FRRkRWaUpBR21wIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608997),
	('xVeK9cZnrTfRyQJSKy70kPPSU01oEHi81eJVbBuL', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJabW85VHJPaWVGNDFqaGpsbE5vZWpXdU80NzAyWUcxNWwxSTBsRDk5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608667),
	('ylPNeMSCdYU8YILzobFANRIqYfNM3lXKhRY1h2LD', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJhRGRtMWZGUVozOFV3OEhTMkNXYlJxMUgzUEk5Vng1VklRV3VBU2RhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792608967),
	('zahSXu1FD0uuHaSJZU8re3vSz1dBN2Jkia1y2PkA', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJNVzRSeVpNQWVZNTRNaEJTdVNpOUpycUUwUmVRdFVuSDg3YzlQbEM2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609808),
	('ZAP6dkLHmLlvfUId2GgH7rUkLy5vAWSoBFeqpKoc', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJEVk5QZldZbm9yNE9DeDJRTVVlVjBjUlpZRWtyN1RDYzBjSmRSeEdDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792609417),
	('ZfKyorujQRZYhPqnTZjeYkLqFyZq1Gr6EKSIbB59', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJ1Mm5wSFdTcDVOWjY2c1huNzJkajlRTElXQU90OTd1eE5rMnZRajZLIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611219),
	('zn4eKgJjJsRjoi6Q3KGFKcDwMtwWVUT5W2vsoTHh', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI0Yk1oWHJlWTJucHRRbXdsV2FNRW9zRUJ0Y0xLM09jRWJyZUtRNGtMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792611459),
	('zqAiM9siy5bY9b8rYHhz4ZCTVS82dJ3Gb3ZbN93l', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiI3RGhBbjFYc0NrcU1lU2MyRWF4MnRPbHdrc1JzR0Y0U0lmZFFUemM5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610858),
	('ZqmokAjZhKBKpcLzacdhC12BBzjJHx8whWRXX3gV', NULL, '127.0.0.1', 'opencode/latest/2.0.32/cli', 'eyJfdG9rZW4iOiJVZGFxVU1aWjhSNlZOM2FrRFF0eG4wYXI3TEp6eE9Db0ZlWWpOMjZLIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9oZWFsdGgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=', 1792610588);

-- Dumping structure for table fsktm_pace_db.settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.settings: ~3 rows (approximately)
INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
	(1, 'app_id', 'fsktm-pace', '2026-10-17 07:38:35', '2026-10-17 07:38:35'),
	(2, 'active_session', '20262027', '2026-10-17 07:38:35', '2026-10-17 07:48:19'),
	(3, 'active_semester', '2', '2026-10-17 07:38:35', '2026-10-17 07:48:19');

-- Dumping structure for table fsktm_pace_db.study_plan_entries
CREATE TABLE IF NOT EXISTS `study_plan_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `course_plan_id` bigint(20) unsigned DEFAULT NULL,
  `course_id` bigint(20) unsigned DEFAULT NULL,
  `elective_slot_id` bigint(20) unsigned DEFAULT NULL,
  `placeholder_key` varchar(255) DEFAULT NULL,
  `year` tinyint(3) unsigned NOT NULL,
  `semester` tinyint(3) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'PLANNED',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `study_plan_entries_user_id_foreign` (`user_id`),
  KEY `study_plan_entries_course_plan_id_foreign` (`course_plan_id`),
  KEY `study_plan_entries_course_id_foreign` (`course_id`),
  KEY `study_plan_entries_elective_slot_id_foreign` (`elective_slot_id`),
  CONSTRAINT `study_plan_entries_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `study_plan_entries_course_plan_id_foreign` FOREIGN KEY (`course_plan_id`) REFERENCES `course_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `study_plan_entries_elective_slot_id_foreign` FOREIGN KEY (`elective_slot_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `study_plan_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.study_plan_entries: ~0 rows (approximately)

-- Dumping structure for table fsktm_pace_db.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'student',
  `student_id` varchar(255) DEFAULT NULL,
  `staff_id` varchar(255) DEFAULT NULL,
  `department_id` bigint(20) unsigned DEFAULT NULL,
  `program_id` bigint(20) unsigned DEFAULT NULL,
  `plan_id` bigint(20) unsigned DEFAULT NULL,
  `advisor_id` bigint(20) unsigned DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `is_hod` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `plan_status` varchar(255) NOT NULL DEFAULT 'draft',
  `plan_submitted_at` timestamp NULL DEFAULT NULL,
  `plan_reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `plan_review_note` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_student_id_unique` (`student_id`),
  UNIQUE KEY `users_staff_id_unique` (`staff_id`),
  KEY `users_role_index` (`role`),
  KEY `users_department_id_index` (`department_id`),
  KEY `users_program_id_index` (`program_id`),
  KEY `users_plan_id_index` (`plan_id`),
  KEY `users_advisor_id_index` (`advisor_id`)
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.users: ~139 rows (approximately)
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `role`, `student_id`, `staff_id`, `department_id`, `program_id`, `plan_id`, `advisor_id`, `phone`, `is_hod`, `status`, `plan_status`, `plan_submitted_at`, `plan_reviewed_by`, `plan_review_note`) VALUES
	(1, 'System Administrator', 'admin@fsktm.test', NULL, '$2y$12$crOFijfW2QX8N81lQSldMOjOzB6gKUdQztRn7DSkmcwkNGWaB1tVy', NULL, '2026-10-17 07:38:36', '2026-10-18 15:42:46', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, '07-9509007', 0, 'active', 'draft', NULL, NULL, NULL),
	(2, 'PROF. MADYA Ts. Dr. MOHD NORASRI BIN ISMAIL', 'norasri@uthm.edu.my', NULL, '$2y$12$bdxVfZg0Dvmz2ot0CdA3ZuC6LWzRv7phlJeM75ETNSxi3rn4n9JH.', NULL, '2026-10-18 15:34:15', '2026-10-18 15:35:32', 'advisor', NULL, 'norasri', 3, NULL, NULL, NULL, NULL, 1, 'active', 'draft', NULL, NULL, NULL),
	(3, 'PROF. Ts. Dr. MOHD FARHAN BIN MD. FUDZEE', 'farhan@uthm.edu.my', NULL, '$2y$12$x3wFu.kTieEKBGTfqfdjKuzqUHI.o70S6x1kRTjMa.vh4ms5ZVY8m', NULL, '2026-10-18 15:34:15', '2026-10-18 15:34:15', 'advisor', NULL, 'farhan', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(4, 'PROF. MADYA Dr. NOORHANIZA BINTI WAHID', 'nhaniza@uthm.edu.my', NULL, '$2y$12$vfIAwqDBnS8wtWIAmZbmEORDKlQWJTYMOeHCQVqeIHZG25DVSN//u', NULL, '2026-10-18 15:34:15', '2026-10-18 15:34:15', 'advisor', NULL, 'nhaniza', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(5, 'Dr. RAHAYU BINTI A HAMID', 'rahayu@uthm.edu.my', NULL, '$2y$12$r/TN/VLBryzoiLqBQNiHMOSEAALWz3M9tw.Z2aOvppStIjFtqTE7C', NULL, '2026-10-18 15:34:15', '2026-10-18 15:34:15', 'advisor', NULL, 'rahayu', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(6, 'PROF. MADYA Dr. NORHALINA BINTI SENAN', 'halina@uthm.edu.my', NULL, '$2y$12$AolC0LT3zrGChhYWkfPbSeqm6dStiyCcTTNHQB5mxnQ3ORSlyoI4C', NULL, '2026-10-18 15:34:15', '2026-10-18 15:34:15', 'advisor', NULL, 'halina', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(7, 'Dr. NORHANIFAH BINTI MURLI', 'hanifah@uthm.edu.my', NULL, '$2y$12$hFphOsis2ZCWafKsWMrgKeGqTi6SrtGs1dEBkUnan83X49inuM0xK', NULL, '2026-10-18 15:34:15', '2026-10-18 15:34:15', 'advisor', NULL, 'hanifah', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(8, 'Dr. CHE SAMIHAH BINTI CHE DALIM', 'samihah@uthm.edu.my', NULL, '$2y$12$p4U6dgJb5EjeL9wCq1/kHerAfQUXjq.3dpKq951qyYx8eeEU2bIm2', NULL, '2026-10-18 15:34:16', '2026-10-18 15:34:16', 'advisor', NULL, 'samihah', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(9, 'Dr. EZAK FADZRIN BIN AHMAD SHAUBARI', 'ezak@uthm.edu.my', NULL, '$2y$12$BAwYhTCkAdWW9YswiMs4P.vC04cWM.MysnD4m82EHptjUJqmoq29e', NULL, '2026-10-18 15:34:16', '2026-10-18 15:34:16', 'advisor', NULL, 'ezak', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(10, 'Dr. NORAZIAHTULHIDAYU BINTI KAMARUDIN', 'noraziah@uthm.edu.my', NULL, '$2y$12$QVLVJzRLfxNkCto/l9hU.e3h3fFJ5AzwtAqi7MxQtU/Sw3z.rEmfO', NULL, '2026-10-18 15:34:16', '2026-10-18 15:34:16', 'advisor', NULL, 'noraziah', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(11, 'ENCIK AZIZAN BIN ISMAIL', 'azizanis@uthm.edu.my', NULL, '$2y$12$LHOoUJ4vhL4L1sfhzk.rCu4tkaH87RjmhRwRXYU22BcZjULn3SeO6', NULL, '2026-10-18 15:34:16', '2026-10-18 15:34:16', 'advisor', NULL, 'azizanis', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(12, 'ENCIK MUHAMMAD FAKRI BIN OTHMAN', 'fakri@uthm.edu.my', NULL, '$2y$12$pQZ./fet49tWGWNm7zcDkedxEahMocKFHU90SMQOfTRLmO7LQq.ce', NULL, '2026-10-18 15:34:16', '2026-10-18 15:34:16', 'advisor', NULL, 'fakri', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(13, 'PUAN SURIAWATI BINTI SUPARJOH', 'suriati@uthm.edu.my', NULL, '$2y$12$72dKW86zYT4HeXig1zn5beRAx5E3syXexJPdgUlWn6XycqGQ5AU3m', NULL, '2026-10-18 15:34:17', '2026-10-18 15:34:17', 'advisor', NULL, 'suriati', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(14, 'PUAN NOR\'AISAH BINTI SUDIN', 'noraisah@uthm.edu.my', NULL, '$2y$12$rpH3ZdBHoAKoCvITuoHNueBvjk4voWdFYIsi/k5SNW/W0N3mBtm2a', NULL, '2026-10-18 15:34:17', '2026-10-18 15:34:17', 'advisor', NULL, 'noraisah', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(15, 'Dr. YAB LI YU', 'yabliyu@uthm.edu.my', NULL, '$2y$12$5R6s0UsUvfYO/kXAzhVa2uNuIZ4uTE8DCWseagOM11Ysmo40YEaIG', NULL, '2026-10-18 15:34:17', '2026-10-18 15:34:17', 'advisor', NULL, 'yabliyu', 3, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(16, 'Dr. NURUL ASWA BINTI OMAR', 'nurulaswa@uthm.edu.my', NULL, '$2y$12$1onm4Uv5linRcr17wUDP6Ok3uk936rghnlaj6d7rBrNjOXkO8wyfy', NULL, '2026-10-18 15:34:17', '2026-10-18 15:35:18', 'advisor', NULL, 'nurulaswa', 2, NULL, NULL, NULL, NULL, 1, 'active', 'draft', NULL, NULL, NULL),
	(17, 'PROF. Ts. Dr. HAIRULNIZAM BIN MAHDIN', 'hairuln@uthm.edu.my', NULL, '$2y$12$bVXF10FQG70OtQqu8Y6en.34EOoek16y4FH.I2tqFlrIEnVJNOomq', NULL, '2026-10-18 15:34:17', '2026-10-18 15:34:17', 'advisor', NULL, 'hairuln', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(18, 'PROF. MADYA Dr. KAMARUDDIN MALIK BIN MOHAMAD', 'malik@uthm.edu.my', NULL, '$2y$12$GJS3pIyHQXlDBbmoQtCnkeJCqDaKVRfiYv9TVA6pt2vlGoScaYxiK', NULL, '2026-10-18 15:34:18', '2026-10-18 15:34:18', 'advisor', NULL, 'malik', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(19, 'PROF. MADYA Dr. SHAHREEN BINTI KASIM', 'shahreen@uthm.edu.my', NULL, '$2y$12$7bq05KKzgiKkJpOdSUAeLOY2RrIS/C833CvZF3abuIzh539LOUEoW', NULL, '2026-10-18 15:34:18', '2026-10-18 15:34:18', 'advisor', NULL, 'shahreen', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(20, 'PROF. MADYA Ts. Dr. ISREDZA RAHMI BINTI A HAMID', 'rahmi@uthm.edu.my', NULL, '$2y$12$vnfnF5t/E6ziux5rRKDQjOUTbwH3ED32gcIasxIeGkHO1tix.fcV2', NULL, '2026-10-18 15:34:18', '2026-10-18 15:34:18', 'advisor', NULL, 'rahmi', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(21, 'Dr. NORYUSLIZA BINTI ABDULLAH', 'yusliza@uthm.edu.my', NULL, '$2y$12$fOvHVecy1zXfF1R8hmYcyuNQCsFNP0LoGOhf7XI9CZS1wNBvbySW.', NULL, '2026-10-18 15:34:18', '2026-10-18 15:34:18', 'advisor', NULL, 'yusliza', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(22, 'Dr. NORFARADILLA BINTI WAHID', 'faradila@uthm.edu.my', NULL, '$2y$12$GsPObXG730mgG8UTF7M4a.hZv3gOCXPQJXbzxh1uBr6paYaSNuOvC', NULL, '2026-10-18 15:34:18', '2026-10-18 15:34:18', 'advisor', NULL, 'faradila', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(23, 'Dr. SHAMSUL KAMAL BIN AHMAD KHALID', 'shamsulk@uthm.edu.my', NULL, '$2y$12$8A5vwKxSgknJ.GwYuXaWFeePLWBX41q.F/WNJi/bZOAMgQ0V/UO/K', NULL, '2026-10-18 15:34:18', '2026-10-18 15:34:18', 'advisor', NULL, 'shamsulk', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(24, 'Dr. CIK FERESA BINTI MOHD FOOZY', 'feresa@uthm.edu.my', NULL, '$2y$12$UhtOqcA.P9EiWLTs7wa/YepCPpEA/bgMgsEEh1mPEHm8x7eDBmEoC', NULL, '2026-10-18 15:34:19', '2026-10-18 15:34:19', 'advisor', NULL, 'feresa', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(25, 'Dr. NURUL AZMA BINTI ABDULLAH', 'azma@uthm.edu.my', NULL, '$2y$12$.PIyUtk2h1fVWkCmMDJWpeUg5.oDhvyoShx8FNhXztb5bUXgdK.A2', NULL, '2026-10-18 15:34:19', '2026-10-18 15:34:19', 'advisor', NULL, 'azma', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(26, 'Dr. NORDIANA BINTI RAHIM', 'nordiana@uthm.edu.my', NULL, '$2y$12$2oBIRt07l.Z5xcOIyC6cweuBgAv/K5PuO.Plb5wvw.1guJaK7xV2y', NULL, '2026-10-18 15:34:19', '2026-10-18 15:34:19', 'advisor', NULL, 'nordiana', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(27, 'Dr. SUHAIMI BIN ABD ISHAK', 'suhaimiabd@uthm.edu.my', NULL, '$2y$12$fYAjWA4kwHO/vmmX6uB71OVT.t.Co8JRmtTppJlEvaF.AbSetPl8e', NULL, '2026-10-18 15:34:19', '2026-10-18 15:34:19', 'advisor', NULL, 'suhaimiabd', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(28, 'Ts. Dr. NURUL HIDAYAH BINTI AB RAHMAN', 'hidayahar@uthm.edu.my', NULL, '$2y$12$F0/dVjjQl27hBxHEdvTZyOTMwe3BXxqoIJtzjouPqnd/CZ3Sj9vgW', NULL, '2026-10-18 15:34:19', '2026-10-18 15:34:19', 'advisor', NULL, 'hidayahar', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(29, 'Dr. SOFIA NAJWA BINTI RAMLI', 'sofianajwa@uthm.edu.my', NULL, '$2y$12$2Qq8MYyXa4qRbqSYlGwte.yDXVHiqhx.Ecl2cGWoayBgutLdXE9Ga', NULL, '2026-10-18 15:34:20', '2026-10-18 15:34:20', 'advisor', NULL, 'sofianajwa', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(30, 'Dr. NOOR ZURAIDIN BIN MOHD SAFAR', 'zuraidin@uthm.edu.my', NULL, '$2y$12$ZqKdsNM8XfvZd7HZW9TUv.mMmfRHU8BC89TG6bsPOAxKsqyrwf0eO', NULL, '2026-10-18 15:34:20', '2026-10-18 15:34:20', 'advisor', NULL, 'zuraidin', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(31, 'Dr. NUR ZIADAH BINTI HARUN', 'nurziadah@uthm.edu.my', NULL, '$2y$12$uCKfmMuQ2n2VlbjAvYzmFuz4qiJKA6aR8Pe33V888wG4cP3x5scz2', NULL, '2026-10-18 15:34:20', '2026-10-18 15:34:20', 'advisor', NULL, 'nurziadah', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(32, 'Dr. NOR BAKIAH BINTI ABD WARIF', 'norbakiah@uthm.edu.my', NULL, '$2y$12$rYTFfRMc75XjPRb/BtdHZO5yE/Mb.8t9wAPh6a9WEWdHVtkj.teTS', NULL, '2026-10-18 15:34:20', '2026-10-18 15:34:20', 'advisor', NULL, 'norbakiah', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(33, 'Dr. ROSMAMALMI BINTI MAT NAWI', 'rosmamalmi@uthm.edu.my', NULL, '$2y$12$QqRrOxnPkHF/RMAW6LXEDuG5fwyTRnXF2lj5aoXVn8o4BFnbHJJdG', NULL, '2026-10-18 15:34:20', '2026-10-18 15:34:20', 'advisor', NULL, 'rosmamalmi', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(34, 'Dr. FIRKHAN ALI BIN HAMID ALI', 'firkhan@uthm.edu.my', NULL, '$2y$12$PiAgD75pSR.Ga8kOR0MvS.zdmt14AZCdymMw3xhLoRpHNxCyGc5z.', NULL, '2026-10-18 15:34:20', '2026-10-18 15:34:20', 'advisor', NULL, 'firkhan', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(35, 'Ts. Dr. NAYEF ABDULWAHAB MOHAMMED ALDUAIS', 'nayef@uthm.edu.my', NULL, '$2y$12$bUQeWlEZK3hIKCdAD/jo9.K14LE3HAniuLwnF.Jfr43BeJ1WqviuW', NULL, '2026-10-18 15:34:21', '2026-10-18 15:34:21', 'advisor', NULL, 'nayef', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(36, 'Dr. SITI FADZLUN BINTI MD SALLEH', 'sfadzlun@uthm.edu.my', NULL, '$2y$12$wvTjbGUInNkHgtbl9VRc/OWTFOQoxkZBBmf0tnNJ15.GMP3qUT8q.', NULL, '2026-10-18 15:34:21', '2026-10-18 15:34:21', 'advisor', NULL, 'sfadzlun', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(37, 'Dr. NUR AFIQAH BINTI SAHADUN', 'nurafiqah@uthm.edu.my', NULL, '$2y$12$YTm2YDvqSIM75DimNLdEb.T0lzh2Fc69aZnClEowG67cC3O.tW.sq', NULL, '2026-10-18 15:34:21', '2026-10-18 15:34:21', 'advisor', NULL, 'nurafiqah', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(38, 'Ts. Dr. DEDEN WITARSYAH', 'deden@uthm.edu.my', NULL, '$2y$12$GDB/ZSFOPifajI/AlxuUUOF8cQ28bgVJCR.drGusfvEmSzQVOS8DO', NULL, '2026-10-18 15:34:21', '2026-10-18 15:34:21', 'advisor', NULL, 'deden', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(39, 'Dr. TAY XIN HUI', 'xhtay@uthm.edu.my', NULL, '$2y$12$1YmMHOy93QrSZegH0n9EU.iEvoHhMvv0sOCGoZghwM0lhI4eIrfGq', NULL, '2026-10-18 15:34:21', '2026-10-18 15:34:21', 'advisor', NULL, 'xhtay', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(40, 'PUAN HANAYANTI BINTI HAFIT', 'hana@uthm.edu.my', NULL, '$2y$12$3sAw1zmPlhpcXdPtNWLbE.DrNShGU1UsALJTSJh15YIP1gAwdTZMO', NULL, '2026-10-18 15:34:22', '2026-10-18 15:34:22', 'advisor', NULL, 'hana', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(41, 'PUAN ROZANAWATI BINTI DARMAN', 'zana@uthm.edu.my', NULL, '$2y$12$GUVgoBBt0y9yHb/SIPcMVOInCdDLGoW3A5ZN3s8Esn20Yfu43NX2G', NULL, '2026-10-18 15:34:22', '2026-10-18 15:34:22', 'advisor', NULL, 'zana', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(42, 'PUAN ZARINA BINTI TUKIRAN', 'zarin@uthm.edu.my', NULL, '$2y$12$ZDONy3tPwF9Miu4ELcDx.e.rNd80UhAtMaIXLtEz7OmMj1bD7lSI2', NULL, '2026-10-18 15:34:22', '2026-10-18 15:34:22', 'advisor', NULL, 'zarin', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(43, 'ENCIK KHAIRUL AMIN BIN MOHAMAD SUKRI', 'khairulm@uthm.edu.my', NULL, '$2y$12$0cKKAeCHw15hE.fuIGqFiunENJFLiZE0j7aY37qqPtK.B18Vvnx/e', NULL, '2026-10-18 15:34:22', '2026-10-18 15:34:22', 'advisor', NULL, 'khairulm', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(44, 'Ts. AHMAD TAJUDIN BIN BAHARIN', 'tajudin@uthm.edu.my', NULL, '$2y$12$9Xx8NWs3vt7S4lO0UUkwP.3bnhlAQ3MPxWCPDpzpr1z/sc2gUZGFG', NULL, '2026-10-18 15:34:22', '2026-10-18 15:34:22', 'advisor', NULL, 'tajudin', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(45, 'ROF. MADYA Ts. Dr. ZUBAILE BIN ABDULLAH', 'zubaile@uthm.edu.my', NULL, '$2y$12$WFqKUAJbV6jnGKrDHdObJeSDeZa1fQB4rOhqIV/fLX0JI4O2DE6dG', NULL, '2026-10-18 15:34:22', '2026-10-18 15:34:22', 'advisor', NULL, 'zubaile', 2, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(46, 'PROF. MADYA Dr. NUREIZE BINTI ARBAIY', 'nureize@uthm.edu.my', NULL, '$2y$12$PUiyU1ad2Oi.nuyoz2b/2eYFIaNdDMACGlDw0Rpzkml5XHtxpcdhi', NULL, '2026-10-18 15:34:23', '2026-10-18 15:35:41', 'advisor', NULL, 'nureize', 1, NULL, NULL, NULL, NULL, 1, 'active', 'draft', NULL, NULL, NULL),
	(47, 'PROF. Dr. ROZAIDA BINTI GHAZALI', 'rozaida@uthm.edu.my', NULL, '$2y$12$2RX3SD/O624zWxdJYurnVuo8QgQ.DQXofY1iL2bOwyivHY2IGb84.', NULL, '2026-10-18 15:34:23', '2026-10-18 15:34:23', 'advisor', NULL, 'rozaida', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(48, 'PROF. Dr. NAZRI BIN MOHD NAWI', 'nazri@uthm.edu.my', NULL, '$2y$12$5sIKnjhzRfiWTm/WYpSjJerNDNZL0wJfYa8CLyI5/8xq.GBRCFcP6', NULL, '2026-10-18 15:34:23', '2026-10-18 15:34:23', 'advisor', NULL, 'nazri', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(49, 'PROF. Dr. ABD SAMAD BIN HASAN BASARI', 'abdsamad@uthm.edu.my', NULL, '$2y$12$TjZK7b1Gjeurj3Ix6GE0EuMhQ0b.UJ5JxoL58Al2Ijz4MWcPwo2h2', NULL, '2026-10-18 15:34:23', '2026-10-18 15:34:23', 'advisor', NULL, 'abdsamad', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(50, 'PROF. MADYA Dr. NOOR AZAH BINTI SAMSUDIN', 'azah@uthm.edu.my', NULL, '$2y$12$MGH9Us4i/6BBoHi.iO2.ruLbgYZPyTfAakkfROb/qUKwx7xwTT/PS', NULL, '2026-10-18 15:34:23', '2026-10-18 15:34:23', 'advisor', NULL, 'azah', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(51, 'PROF. MADYA Dr. NORAINI BINTI IBRAHIM', 'noraini@uthm.edu.my', NULL, '$2y$12$vD5RDlcYcdSAIxULdy2XTOPszxWd48xHvf57SiggDMuCwM9fKcKte', NULL, '2026-10-18 15:34:24', '2026-10-18 15:34:24', 'advisor', NULL, 'noraini', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(52, 'PROF. MADYA Ts. Dr. AZIZUL AZHAR BIN RAMLI', 'azizulr@uthm.edu.my', NULL, '$2y$12$.jEkbidTLDRMErHe3g5ApuJgs9gGwj6FwNcKE.OiRiTsn.lezv3p6', NULL, '2026-10-18 15:34:24', '2026-10-18 15:34:24', 'advisor', NULL, 'azizulr', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(53, 'PROF. MADYA Dr. YANA MAZWIN BINTI MOHMAD HASSIM', 'yana@uthm.edu.my', NULL, '$2y$12$TeZ.w2FbyGzM9aoo/mTYFekIIMAtXiT5HWsNHZUgYQHxHSTE8L7Ba', NULL, '2026-10-18 15:34:24', '2026-10-18 15:34:24', 'advisor', NULL, 'yana', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(54, 'PROF. MADYA Ts. Dr. KHALID BIN ISA', 'halid@uthm.edu.my', NULL, '$2y$12$TPKyVg4fPTrwXU/r2ndujeUGlSWNfqnQHIoE74COjISGOik4ioDI.', NULL, '2026-10-18 15:34:24', '2026-10-18 15:34:24', 'advisor', NULL, 'halid', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(55, 'Dr. RADIAH BINTI MOHAMAD', 'radiah@uthm.edu.my', NULL, '$2y$12$d8UjPTi9rvxa3AA/JIZODenyfM7Enx0.Ds.UN8mG3RCjqhrJP2v2K', NULL, '2026-10-18 15:34:24', '2026-10-18 15:34:24', 'advisor', NULL, 'radiah', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(56, 'Dr. MOHAMAD AIZI BIN SALAMAT', 'aizi@uthm.edu.my', NULL, '$2y$12$Y/vCO3n0NcOcYimUCeXl9ebD.j5D59GBJnG0YDu937JJxs5GR//y6', NULL, '2026-10-18 15:34:25', '2026-10-18 15:34:25', 'advisor', NULL, 'aizi', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(57, 'Dr. SUHAILA BINTI MOHD. YASIN', 'ysuhaila@uthm.edu.my', NULL, '$2y$12$23/mhS9/S32RJCaEZyhenuUnwxqro/l5pEi6bZBtN9JYL2gkPW7MS', NULL, '2026-10-18 15:34:25', '2026-10-18 15:34:25', 'advisor', NULL, 'ysuhaila', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(58, 'Dr. NUR ARIFFIN BIN MOHD ZIN', 'ariffin@uthm.edu.my', NULL, '$2y$12$ND531h9DRAK3puzn4fQLgeQUSlkdjmp.yM6AubJ5pGPvWNSAX582W', NULL, '2026-10-18 15:34:25', '2026-10-18 15:34:25', 'advisor', NULL, 'ariffin', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(59, 'Dr. MOHAMAD FIRDAUS BIN AB. AZIZ', 'mdfirdaus@uthm.edu.my', NULL, '$2y$12$Ju1nNkvmdVsQfhNsQuYdye.eWu6YOcnG7cGMMD6NiPbkhL5MKBVCS', NULL, '2026-10-18 15:34:25', '2026-10-18 15:34:25', 'advisor', NULL, 'mdfirdaus', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(60, 'Dr. MOHD HAMDI IRWAN BIN HAMZAH', 'hamdi@uthm.edu.my', NULL, '$2y$12$zbv0vVL1H99shHFZML2TiuHUBGC8wAz/mtO7MaeI7Brs5e5QRgryS', NULL, '2026-10-18 15:34:25', '2026-10-18 15:34:25', 'advisor', NULL, 'hamdi', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(61, 'PROF. MADYA Ts. Dr. MOHD AMIN BIN MOHD YUNUS', 'aminy@uthm.edu.my', NULL, '$2y$12$C7u/9QQWd79ar.0m6XJAI.Bvf47Stz7AFcZ4pm1O5Y4gNMXgQJebC', NULL, '2026-10-18 15:34:25', '2026-10-18 15:34:25', 'advisor', NULL, 'aminy', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(62, 'Dr. NUREZAYANA BINTI ZAINAL', 'nurezayana@uthm.edu.my', NULL, '$2y$12$NR3qZmA6doAZfILnZK1XJuEoj8BZwLS6RSzWE87rWfFBPe47g9tfi', NULL, '2026-10-18 15:34:26', '2026-10-18 15:34:26', 'advisor', NULL, 'nurezayana', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(63, 'Dr. NORHAMREEZA BINTI ABDUL HAMID', 'hamreeza@uthm.edu.my', NULL, '$2y$12$NYW4KJTxHDirFe0bDHPhT.5ywvLeKQ8hGMsCs.MceFhzASmU4lot6', NULL, '2026-10-18 15:34:26', '2026-10-18 15:34:26', 'advisor', NULL, 'hamreeza', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(64, 'Dr. RABATUL ADUNI BINTI SULAIMAN', 'rabatul@uthm.edu.my', NULL, '$2y$12$NYo85bflPcfYeEFma74Yi.1oT3un7JSSZ3YCWWV8EVzFsJzbSzKje', NULL, '2026-10-18 15:34:26', '2026-10-18 15:34:26', 'advisor', NULL, 'rabatul', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(65, 'Dr. MOHD ZANES BIN SAHID', 'zanes@uthm.edu.my', NULL, '$2y$12$e3hT5UKx6FNbNeLozOVd8O4.dhYDcvsow9FgNdp1rbbW60N7DE/om', NULL, '2026-10-18 15:34:26', '2026-10-18 15:34:26', 'advisor', NULL, 'zanes', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(66, 'Ts. Dr. SUZIYANTI BINTI MARJUDI', 'suziyanti@uthm.edu.my', NULL, '$2y$12$.A8cNb0aYEMrNSilP.pONe/G2qXXGJFOKVv7tRZ1GaFG7247lzska', NULL, '2026-10-18 15:34:26', '2026-10-18 15:34:26', 'advisor', NULL, 'suziyanti', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(67, 'Ts. Dr. SHUHAIDA BINTI ISMAIL', 'shuhaida@uthm.edu.my', NULL, '$2y$12$Mzm2BjYlSJIQBWQ81N9vbeEbdMgj7YsPRWya2gJkYSD5rZmFyU6km', NULL, '2026-10-18 15:34:27', '2026-10-18 15:34:27', 'advisor', NULL, 'shuhaida', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(68, 'Ts. Dr. MAZIDAH BINTI MAT REJAB', 'mazidah@uthm.edu.my', NULL, '$2y$12$sVuCdMYBz9hggvLKnqPFEOfquOvE5yYcnwSe4ebMTM07OA3.Krs7a', NULL, '2026-10-18 15:34:27', '2026-10-18 15:34:27', 'advisor', NULL, 'mazidah', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(69, 'Dr. NUR LIYANA BINTI SULAIMAN', 'nrliyana@uthm.edu.my', NULL, '$2y$12$ZrzIUq7ov2UygKy5cBo5Zuge5CjT0Xqz19zSAejHbHlxVrapPqBr2', NULL, '2026-10-18 15:34:27', '2026-10-18 15:34:27', 'advisor', NULL, 'nrliyana', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(70, 'Dr. SITI HAJAR BINTI ARBAIN', 'sitihajara@uthm.edu.my', NULL, '$2y$12$ai4BX4rOMkGVg9KFKARu1OxwE.zgbULlnbAGoFmHmUAqtAqjjhlmC', NULL, '2026-10-18 15:34:27', '2026-10-18 15:34:27', 'advisor', NULL, 'sitihajara', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(71, 'Dr. ROZITA BINTI ABDUL JALIL', 'rozita@uthm.edu.my', NULL, '$2y$12$mK4pau.YbP5Isu3K1u.pA.NZ2J.SPUvkodFfNd0ITHpVDVhZrVM6m', NULL, '2026-10-18 15:34:27', '2026-10-18 15:34:27', 'advisor', NULL, 'rozita', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(72, 'Dr. NASARUDIN BIN ISMAIL', 'nasar@uthm.edu.my', NULL, '$2y$12$mux6lLckHWi8YJqg9EfkXuPTwmsaRp.aW/Ps.oZ45QFS.2pgpkAJe', NULL, '2026-10-18 15:34:28', '2026-10-18 15:34:28', 'advisor', NULL, 'nasar', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(73, 'Ts. Dr. SHAMSUL BIN MOHAMAD', 'shamsulm@uthm.edu.my', NULL, '$2y$12$rALAMctOwEbkCAFiuhwge.zpgsi.oSpgWeajwBVIve..ySbNPXoYG', NULL, '2026-10-18 15:34:28', '2026-10-18 15:34:28', 'advisor', NULL, 'shamsulm', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(74, 'Dr. NOR AMALINA BINTI MOHD SABRI', 'noramalina@uthm.edu.my', NULL, '$2y$12$IdKSm7GhSQzkh4SHfa5SKO.FhKBQidcQpc8QBFDQu7NDb6WROcEPS', NULL, '2026-10-18 15:34:28', '2026-10-18 15:34:28', 'advisor', NULL, 'noramalina', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(75, 'Dr. SHAHDATUNNAIM BINTI AZMI', 'shahdatun@uthm.edu.my', NULL, '$2y$12$cjhtlxd38/UKbImLi8doUesBGmzBEyVBaPOMPbZLz8K7pD25TeF.y', NULL, '2026-10-18 15:34:28', '2026-10-18 15:34:28', 'advisor', NULL, 'shahdatun', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(76, 'Dr. MUHAMMAD NAZIM BIN RAZALI', 'mnazim@uthm.edu.my', NULL, '$2y$12$RiAsztyLumWrvq.rmaSCuOcPhhWg2/JR8Gg9M0Y40u9X7VOLOYor6', NULL, '2026-10-18 15:34:28', '2026-10-18 15:34:28', 'advisor', NULL, 'mnazim', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(77, 'Dr. ABDULLAH ABDURAHMAN MOHAMED AHMED', 'abdullahm@uthm.edu.my', NULL, '$2y$12$t4RlvzO4BWMhbXw1fht0oOqV03SM8fLc2dy4eKlUFkx0tXRijCIiC', NULL, '2026-10-18 15:34:28', '2026-10-18 15:34:28', 'advisor', NULL, 'abdullahm', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(78, 'PUAN RUHAYA BINTI AB.AZIZ', 'ruhaya@uthm.edu.my', NULL, '$2y$12$YOKi3s1UK4k/YeocaoLeoe5rjHHzLAbTnzxecQLbDn.7emGO6Qe6O', NULL, '2026-10-18 15:34:29', '2026-10-18 15:34:29', 'advisor', NULL, 'ruhaya', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(79, 'PUAN ROZLINI BINTI MOHAMED', 'rozlini@uthm.edu.my', NULL, '$2y$12$6CTrWJwXb7YyURZ./7ulzeVJffqCgl0eJznNZlqwYtQpGTjDTzshG', NULL, '2026-10-18 15:34:29', '2026-10-18 15:34:29', 'advisor', NULL, 'rozlini', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(80, 'PUAN NORHANIM BINTI SELAMAT', 'norhanim@uthm.edu.my', NULL, '$2y$12$Z1Vhqnyhc/BO2BQ/QNJd8e.3pQsoltZoR0BH2af7lBh8b5fCqphRa', NULL, '2026-10-18 15:34:29', '2026-10-18 15:34:29', 'advisor', NULL, 'norhanim', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(81, 'ENCIK MOHD ZAKI BIN MOHD SALIKON', 'mdzaki@uthm.edu.my', NULL, '$2y$12$W9DrG1HNOZK1jLT0gVPhOOoxArZXaLrUcj2oHOOowJnxWIAExYEYW', NULL, '2026-10-18 15:34:29', '2026-10-18 15:34:29', 'advisor', NULL, 'mdzaki', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(82, 'PUAN MUNIRAH BINTI MOHD YUSOF', 'munirah@uthm.edu.my', NULL, '$2y$12$aTaWcvXICC/xZwoDZZYlC.GV72C/cdVSA/9Jx19M0SPIS6Sedub.u', NULL, '2026-10-18 15:34:29', '2026-10-18 15:34:29', 'advisor', NULL, 'munirah', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(83, 'PUAN HANNANI BINTI AMAN', 'hanani@uthm.edu.my', NULL, '$2y$12$ZCDAoguMUDr0dqd1yp/0r.i4zvCTdpoWb59R8tgoyEMa8kKReZYr6', NULL, '2026-10-18 15:34:30', '2026-10-18 15:34:30', 'advisor', NULL, 'hanani', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(84, 'PUAN NORLIDA BINTI HASSAN', 'norlida@uthm.edu.my', NULL, '$2y$12$nBBKgxVJ5TFI8uONT1999.oPNwLU2QIokGAzWHmL5vvf6mLkTRMMa', NULL, '2026-10-18 15:34:30', '2026-10-18 15:34:30', 'advisor', NULL, 'norlida', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(85, 'Inv. MOHD HELMY BIN ABD WAHAB', 'helmy@uthm.edu.my', NULL, '$2y$12$HZBe3UPYbo8gRZS50Gurw.TA99p378y16WC1rYHdtZci.O3l4wki2', NULL, '2026-10-18 15:34:30', '2026-10-18 15:34:30', 'advisor', NULL, 'helmy', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(86, 'ENCIK MUHAMMAD SHUKRI BIN CHE LAH', 'mdshukri@uthm.edu.my', NULL, '$2y$12$LbOs2St74t/Inyj4ioFeKOD8R0NjuQJA29BwnAmy6E0orzhakivqC', NULL, '2026-10-18 15:34:30', '2026-10-18 15:34:30', 'advisor', NULL, 'mdshukri', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(87, 'ENCIK MOHD HAZIQ ASYRAFF BIN RAZALI', 'haziqr@uthm.edu.my', NULL, '$2y$12$03z0ZNu5osOrcglUnbom5uRIsNCphFTCs5SZGHNQTkErMWwwKCnyC', NULL, '2026-10-18 15:34:30', '2026-10-18 15:34:30', 'advisor', NULL, 'haziqr', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(88, 'CIK SITI HASLINDA BINTI MUKHTAR', 'sthaslinda@uthm.edu.my', NULL, '$2y$12$BwA5Np8mVf9yE9edmRSTaOlTyLdQSefLrKBLlShobXypM5TaI4Yem', NULL, '2026-10-18 15:34:30', '2026-10-18 15:34:30', 'advisor', NULL, 'sthaslinda', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(89, 'PROF. MADYA Dr. MUHAINI BINTI OTHMAN', 'muhaini@uthm.edu.my', NULL, '$2y$12$Jx0pVHBbiAhxKFqDppg9qexvOSzRavNDeoWzF1YzymLEmCXaag5DS', NULL, '2026-10-18 15:34:31', '2026-10-18 15:34:31', 'advisor', NULL, 'muhaini', 1, NULL, NULL, NULL, NULL, 0, 'active', 'draft', NULL, NULL, NULL),
	(119, 'KEW KAI XUAN', 'AI260232@student.uthm.edu.my', NULL, '$2y$12$yxyazM2Ek.MKMsnOAljdB.0WCGYmDIbuHATlvSIcqgZOp/oXH/5ei', NULL, '2026-10-18 15:38:30', '2026-10-18 15:38:30', 'student', 'AI260232', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(120, 'LIM CHEN KUN', 'AI260168@student.uthm.edu.my', NULL, '$2y$12$XtlT5FmM7Ado7MEPWWHe2eR2YpFE9c7XrUMmfs1B3NmX9ViTTxdMy', NULL, '2026-10-18 15:38:30', '2026-10-18 15:38:30', 'student', 'AI260168', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(121, 'LOO WEI LUN', 'AI260215@student.uthm.edu.my', NULL, '$2y$12$n2ELKnQZdVcH/AFjjH71t.PfAMWZnEjeklPyI2E4fcIHvhuoh0xQ6', NULL, '2026-10-18 15:38:30', '2026-10-18 15:38:30', 'student', 'AI260215', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(122, 'LOO ZHI DA', 'AI260189@student.uthm.edu.my', NULL, '$2y$12$ILdGiS7gVZFsMfAvlAi1kuo7j0s1lp1/Dl4cqDM9xn6erFZYIDJa6', NULL, '2026-10-18 15:38:30', '2026-10-18 15:38:30', 'student', 'AI260189', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(123, 'LUA SIN YEE', 'AI260154@student.uthm.edu.my', NULL, '$2y$12$PX5w1OAI1KzMXNzJjVQob.uIAl87lB4N7SkECCxBUbOjmxu5nFu.6', NULL, '2026-10-18 15:38:31', '2026-10-18 15:38:31', 'student', 'AI260154', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(124, 'MUHAMAD FARHAN BIN MAHZAMI', 'AI260143@student.uthm.edu.my', NULL, '$2y$12$NpoJVZA5XLOSq9bXrXIqFeaxEFpD3DYE5aQLsQNK62dvpO2YyY7PK', NULL, '2026-10-18 15:38:31', '2026-10-18 15:38:31', 'student', 'AI260143', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(125, 'MUHAMMAD AFIQ BIN ASMAWI', 'AI260240@student.uthm.edu.my', NULL, '$2y$12$kBB2ebXzpuIrBwlO5JLOxOw1RxkejWYVfnmd/aSlAxahamY.391Jy', NULL, '2026-10-18 15:38:31', '2026-10-18 15:38:31', 'student', 'AI260240', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(126, 'MUHAMMAD ARIF IMRAN BIN OSMAN', 'AI260229@student.uthm.edu.my', NULL, '$2y$12$LmFPsgfKi7aKyse4lpUAI.kRBtDE7gsV7wyvk45vT.uA3WFDEEDg6', NULL, '2026-10-18 15:38:31', '2026-10-18 15:38:31', 'student', 'AI260229', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(127, 'MUHAMMAD DANIEL HAKIMI BIN MOHD ISMAWADI', 'AI260153@student.uthm.edu.my', NULL, '$2y$12$8QEexK8uWi2XsjTSytkjA.nJ7AAXKHTS7mdwoa/J.7IHLyNpGqBB.', NULL, '2026-10-18 15:38:31', '2026-10-18 15:38:31', 'student', 'AI260153', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(128, 'MUHAMMAD FAIZS BIN MOHD RIDZWAN', 'AI260272@student.uthm.edu.my', NULL, '$2y$12$MGOCbfmrbizobU8XVHdot.tGOhTASIv5pVqRnRCptXhUmj6lWeFay', NULL, '2026-10-18 15:38:31', '2026-10-18 15:38:31', 'student', 'AI260272', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(129, 'NADHIRAH BINTI ADNAN', 'AI260216@student.uthm.edu.my', NULL, '$2y$12$diIUrreJPdp5vbtQWXKIQuKAvQNCxWLSjhowDGS7zIKKnO8ufq.ai', NULL, '2026-10-18 15:38:32', '2026-10-18 15:38:32', 'student', 'AI260216', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(130, 'NIK BATRISYIA HUSNA BINTI NIK BUDIMAN', 'AI260246@student.uthm.edu.my', NULL, '$2y$12$vw.sZ.ljFcrPZKxGLcleEOZ4k3VQwiZmgTAd3/Jw3.imUI4CGnZZi', NULL, '2026-10-18 15:38:32', '2026-10-18 15:38:32', 'student', 'AI260246', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(131, 'NOR FATIN BINTI ASKALANI', 'AI260146@student.uthm.edu.my', NULL, '$2y$12$gH3UtBe6HdU4JMfLzWLIS.OZ.6XrAHnqPq9G3ETI.HyNi0xOzzLtW', NULL, '2026-10-18 15:38:32', '2026-10-18 15:38:32', 'student', 'AI260146', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(132, 'NUR AMIRAH BINTI MOHD RIZAL', 'AI260200@student.uthm.edu.my', NULL, '$2y$12$66pBp9uHwLYJk.Ljuq1IiOH5MBRYOaE.LvsaL7YlW8G8usGOPEpsK', NULL, '2026-10-18 15:38:32', '2026-10-18 15:38:32', 'student', 'AI260200', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(133, 'NUR IRDINA BINTI AMIROLNIZAM', 'AI260180@student.uthm.edu.my', NULL, '$2y$12$nmUstJwR9MkLC6fxtld7/.Q6WV6EO6JAj2x.LXks3o2p4SYevVVc6', NULL, '2026-10-18 15:38:32', '2026-10-18 15:38:32', 'student', 'AI260180', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(134, 'NUR IRDINA BINTI MUSTAFA', 'AI260218@student.uthm.edu.my', NULL, '$2y$12$ptRY5MtYMjhFHT0UKArEpOWiG9oG2MICdU4amJvTCHa1T04FPmet6', NULL, '2026-10-18 15:38:33', '2026-10-18 15:38:33', 'student', 'AI260218', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(135, 'NUR LAILA BATRISYA BINTI ANUAR', 'AI260199@student.uthm.edu.my', NULL, '$2y$12$MyQgnVGU8DgOK/YNa81dReBeBEJeap/ac/CHNhul/4UUiQz925nnm', NULL, '2026-10-18 15:38:33', '2026-10-18 15:38:33', 'student', 'AI260199', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(136, 'NURIN HANANIA BINTI ASMADI', 'AI260197@student.uthm.edu.my', NULL, '$2y$12$1oSeaC832wz1yJWjslPtJuEZo18AiP0.QNl8i7IK23pweJcYR7a4C', NULL, '2026-10-18 15:38:33', '2026-10-18 15:38:33', 'student', 'AI260197', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(137, 'NURSYAHIDAH NAZHIRAH BINTI MOHD RAZALI', 'AI260206@student.uthm.edu.my', NULL, '$2y$12$qV5KVcQFMcMmfNtrpyYn4O//1EkqoX/RLhZUtrYoz6vy56wHmhW5C', NULL, '2026-10-18 15:38:33', '2026-10-18 15:38:33', 'student', 'AI260206', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(138, 'NURUL FAEZA BINTI SYAHARUDDIN', 'AI260227@student.uthm.edu.my', NULL, '$2y$12$Qi0X4wheak8FG1.6ZETpwuKBBzMvfWUar50MaABJpnHeOf5k3ViKu', NULL, '2026-10-18 15:38:33', '2026-10-18 15:38:33', 'student', 'AI260227', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(139, 'NURUL HIDAYAH BINTI MISKARI', 'AI260221@student.uthm.edu.my', NULL, '$2y$12$HYmmdxSMCRJmgyQD11p9BetroLuzIZ2Sq4ABt0.dG/UlyStotVJuG', NULL, '2026-10-18 15:38:34', '2026-10-18 15:38:34', 'student', 'AI260221', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(140, 'PEH ZHI QI', 'AI260147@student.uthm.edu.my', NULL, '$2y$12$fesC2mRjgy5P1U8TLnZ0veRchXdtmS5GV4Ca.gWkVgBVn2dDGksTO', NULL, '2026-10-18 15:38:34', '2026-10-18 15:38:34', 'student', 'AI260147', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(141, 'RAJA MUHAMMAD RAIS BIN RAJA ADAM', 'AI260155@student.uthm.edu.my', NULL, '$2y$12$BLPOeyBfBXVXRI53B9JVneykA.4uSF64AFbAwH1UBjlX1YWb.NU1G', NULL, '2026-10-18 15:38:34', '2026-10-18 15:38:34', 'student', 'AI260155', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(142, 'SITI NUR FATIMAH BINTI ABD RAHMAN', 'AI260183@student.uthm.edu.my', NULL, '$2y$12$u4/CFccF0svls6DBgn.T/uNGPP3K0O0G.R3lQaIIUvwYMgRh3i2dG', NULL, '2026-10-18 15:38:34', '2026-10-18 15:38:34', 'student', 'AI260183', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(143, 'SUBASHINI A/P SURESH', 'AI260159@student.uthm.edu.my', NULL, '$2y$12$lu0KPp48xLBXkz6OIbD6LemyklMeVWIPc.BxuyZrx6DJapnSaaCBC', NULL, '2026-10-18 15:38:34', '2026-10-18 15:38:34', 'student', 'AI260159', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(144, 'SYAMILAH BINTI CLARENCE JERRY @ IBRAHIM', 'AI260171@student.uthm.edu.my', NULL, '$2y$12$qEyH.ldLqtr2t4nE2Vhlj.dR51cx0jtrZ1/J1wRAzMlWJPL8UWb7a', NULL, '2026-10-18 15:38:34', '2026-10-18 15:38:34', 'student', 'AI260171', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(145, 'TAY JIA QIAN', 'AI260187@student.uthm.edu.my', NULL, '$2y$12$EujvzaYKg3ZZgg4LSRefUOOBR4q1zRLoFH.IWw3wiN69/N8xC1F1q', NULL, '2026-10-18 15:38:35', '2026-10-18 15:38:35', 'student', 'AI260187', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(146, 'WAN ISMATUL SYUHADA BINTI ISWADY', 'AI260213@student.uthm.edu.my', NULL, '$2y$12$Oq88wUQxv9AuPcU.rovCS.3P10NKF7Ra1eqyV30i1Ez7b0Ts2uHPy', NULL, '2026-10-18 15:38:35', '2026-10-18 15:38:35', 'student', 'AI260213', NULL, 3, 6, 1, 9, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(147, 'ALLEYA BINTI NAZARUDIN', 'AI260060@student.uthm.edu.my', NULL, '$2y$12$aWdmFsZafZun.WkyHL.2Mu7l4KaFohq9VBFMEQdVt780v1Kipqeb6', NULL, '2026-10-18 15:39:21', '2026-10-18 15:39:21', 'student', 'AI260060', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(148, 'ASFA HAZIRAH BINTI MOHD HAIRI', 'AI260128@student.uthm.edu.my', NULL, '$2y$12$jMKtBFK3EXCauNDR64D2f.91LDzIQALkz6LJFe3aG0PJ95vWJe1uG', NULL, '2026-10-18 15:39:22', '2026-10-18 15:39:22', 'student', 'AI260128', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(149, 'DANIA HANI BINTI ABDUL AZIZ', 'AI260084@student.uthm.edu.my', NULL, '$2y$12$dT3jnvS8XcySj.If4fk8Bekpg8o4n.zlkK.fUybtss6wvSQA1iY2u', NULL, '2026-10-18 15:39:22', '2026-10-18 15:39:22', 'student', 'AI260084', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(150, 'FAIEZZATUL HANIS BINTI ABDUL FAIRUZ', 'AI260113@student.uthm.edu.my', NULL, '$2y$12$oB38kLU8Aum9Mp36x8crS.jT4i1KKIX53l7D3QPJrQOYQDItyeb7W', NULL, '2026-10-18 15:39:22', '2026-10-18 15:39:22', 'student', 'AI260113', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(151, 'HAZIQAH BINTI ISMAIL', 'AI260130@student.uthm.edu.my', NULL, '$2y$12$vHpsC4eIJlQf4Ek3oVN89OaM0Cq8wGlAxUPDc2yN2N2MKMBUQ5PzW', NULL, '2026-10-18 15:39:22', '2026-10-18 15:39:22', 'student', 'AI260130', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(152, 'IRFAN FARIS BIN MAS IZUDIN', 'AI260078@student.uthm.edu.my', NULL, '$2y$12$0SOQPXGBahRT7jSFfb2tVutlLZ7bfOMOjsQvavz6Y3cN5p6z.4ujW', NULL, '2026-10-18 15:39:22', '2026-10-18 15:39:22', 'student', 'AI260078', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(153, 'MUHAMMAD ADAM RAFLISH BIN MUHAMMAD EZWAN', 'AI260043@student.uthm.edu.my', NULL, '$2y$12$9Qs/5jwb3vxVTmQY/DAFN.gSbyJ6A.iBY2KGS.WfAzFDS1F5t00iK', NULL, '2026-10-18 15:39:22', '2026-10-18 15:39:22', 'student', 'AI260043', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(154, 'MUHAMMAD AKRAM HAKIM BIN MOHD HANIFF', 'AI260037@student.uthm.edu.my', NULL, '$2y$12$P4lp2IP4nrDfndvgod6Zg.Ms8XZh8XB3c7VLideU6V/H0yMWgEWse', NULL, '2026-10-18 15:39:23', '2026-10-18 15:39:23', 'student', 'AI260037', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(155, 'MUHAMMAD HAZIM SAFWAN BIN HANIF', 'AI260006@student.uthm.edu.my', NULL, '$2y$12$w5oyjJ4eMGjIy8dSNMIeauQhWIEV1EYWFqSZLk.BCjEBA82oJq7A6', NULL, '2026-10-18 15:39:23', '2026-10-18 15:39:23', 'student', 'AI260006', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(156, 'MUHAMMAD RAFIZIE BIN ROSMAN', 'AI260090@student.uthm.edu.my', NULL, '$2y$12$qBV7UrA0X0cz4iPPrBjMeOKA0AILNJvO/ZxBNjBEUVtowN0YXz1vq', NULL, '2026-10-18 15:39:23', '2026-10-18 15:39:23', 'student', 'AI260090', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(157, 'MUHAMMAD SYUKRI BIN ZAINUDDIN', 'AI260035@student.uthm.edu.my', NULL, '$2y$12$CjqVdwQMZuMpAvBeTnSHGeSEtrAybQVW5SOYGkOce3mCBdaw/OWza', NULL, '2026-10-18 15:39:23', '2026-10-18 15:39:23', 'student', 'AI260035', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(158, 'NUR BATRISYIA ZAHRA BINTI MOHD SUKRI', 'AI260085@student.uthm.edu.my', NULL, '$2y$12$U3z8VSMKDCu28Go9IXsLsOGj86m5uxLReeyUedPQy5MFtPSEp4RDa', NULL, '2026-10-18 15:39:23', '2026-10-18 15:39:23', 'student', 'AI260085', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(159, 'NUR INSYIRAH BINTI MOHD AMINI', 'AI260029@student.uthm.edu.my', NULL, '$2y$12$ASL/LBXa2Mk6SA0uc/vHQe8iAeXQ/nownme3cpq7c6sCj.8JBMte6', NULL, '2026-10-18 15:39:24', '2026-10-18 15:39:24', 'student', 'AI260029', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(160, 'NUR NADIRAH NAJWA BINTI MD AKHIR', 'AI260001@student.uthm.edu.my', NULL, '$2y$12$DSBzYoc1VwkYxxHuYbVwEOp6oDQvY8KYn4EoPkmmesP.lzIuyEdaG', NULL, '2026-10-18 15:39:24', '2026-10-18 15:39:24', 'student', 'AI260001', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(161, 'NURIN ADLINA BINTI ZAIRULLIZAM', 'AI260002@student.uthm.edu.my', NULL, '$2y$12$RyZkYQEEhCPRDo8pUfgdUelAK0myWenFjS.2/qAEEw6tQ.o5FZsv.', NULL, '2026-10-18 15:39:24', '2026-10-18 15:39:24', 'student', 'AI260002', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(162, 'NURUL ALIYANA BINTI MAT SAJALI', 'AI260091@student.uthm.edu.my', NULL, '$2y$12$DfbRil1BKehbg41Zb35ai.LS27c1z.I5Fn1DTsczwG7/6t5amzcv.', NULL, '2026-10-18 15:39:24', '2026-10-18 15:39:24', 'student', 'AI260091', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(163, 'NURUL BALQIS BINTI NASHARUDDIN', 'AI260026@student.uthm.edu.my', NULL, '$2y$12$V5UFGfxK9bQeaIf8MUYGIehVSG2apPVMK5AEEmXQsIlvUMYSZ.oUm', NULL, '2026-10-18 15:39:24', '2026-10-18 15:39:24', 'student', 'AI260026', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(164, 'NURUL QISTINA SYAHIRAH BINTI IQBALL HAKIM', 'AI260073@student.uthm.edu.my', NULL, '$2y$12$21tFiicwxtCB09npR6oiUeBAU6R/99.n5t7vFzEjTDAlvmySWLFnG', NULL, '2026-10-18 15:39:25', '2026-10-18 15:39:25', 'student', 'AI260073', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(165, 'TUAN NUR ALYA QISTINA BINTI TUAN AZEAN', 'AI260049@student.uthm.edu.my', NULL, '$2y$12$AE2sIJntraeL8D5JUcy.0OULFKG15jSXN70NihSnGMTGQEByKvsxC', NULL, '2026-10-18 15:39:25', '2026-10-18 15:39:25', 'student', 'AI260049', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(166, 'LOW HOCK YONG', 'AI260148@student.uthm.edu.my', NULL, '$2y$12$YUo9C09aOrwdBYa8v6RG8O7rAhueSSEtyuDotLz96O13TDoZQGote', NULL, '2026-10-18 15:39:25', '2026-10-18 15:39:25', 'student', 'AI260148', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(167, 'NUR FARISHA AFRINA BINTI MD FADZIL', 'AI260210@student.uthm.edu.my', NULL, '$2y$12$A9KeRdn6bNHcFKGzS7OxI.i5cymd8mRY4cdLCffUFfnPZ/dXTUZDC', NULL, '2026-10-18 15:39:25', '2026-10-18 15:39:25', 'student', 'AI260210', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL),
	(168, 'NUR MAISARAH BINTI A RAHMAN', 'AI260182@student.uthm.edu.my', NULL, '$2y$12$5y1U1wbIfgDniNq0HVmoae2.KqvKdlzt5YGBe1ZqLuorHPYPBGTVu', NULL, '2026-10-18 15:39:25', '2026-10-18 15:39:25', 'student', 'AI260182', NULL, 1, 3, 5, 62, '123', 0, 'active', 'draft', NULL, NULL, NULL);

-- Dumping structure for table fsktm_pace_db.vbe
CREATE TABLE IF NOT EXISTS `vbe` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vbe_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fsktm_pace_db.vbe: ~16 rows (approximately)
INSERT INTO `vbe` (`id`, `code`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'VBE1', 'Dignity', 'Maruah (Dignity)', '2026-10-18 15:09:15', '2026-10-18 15:09:15'),
	(2, 'EPT1', 'Niat', NULL, '2026-10-18 15:10:00', '2026-10-18 15:10:00'),
	(3, 'EPT2', 'Hubungan baik dengan Tuhan', NULL, '2026-10-18 15:10:28', '2026-10-18 15:10:28'),
	(4, 'EPT3', 'Ikhlas', NULL, '2026-10-18 15:10:48', '2026-10-18 15:10:48'),
	(5, 'EPT4', 'Integriti', NULL, '2026-10-18 15:11:18', '2026-10-18 15:11:42'),
	(6, 'EPT5', 'Kesabaran', NULL, '2026-10-18 15:12:06', '2026-10-18 15:12:06'),
	(7, 'VBE2', 'Justice', NULL, '2026-10-18 15:13:24', '2026-10-18 15:13:24'),
	(8, 'VBE3', 'Integrity', NULL, '2026-10-18 15:13:48', '2026-10-18 15:13:48'),
	(9, 'VBE4', 'Gratitude', NULL, '2026-10-18 15:14:10', '2026-10-18 15:14:10'),
	(10, 'VBE5', 'Resilience', NULL, '2026-10-18 15:14:28', '2026-10-18 15:14:28'),
	(11, 'VBE6', 'Altruism', NULL, '2026-10-18 15:14:51', '2026-10-18 15:14:51'),
	(12, 'EPT18', 'Kesederhanaan', NULL, '2026-10-18 15:15:32', '2026-10-18 15:15:32'),
	(13, 'EPT19', 'Menjaga batas pergaulan', NULL, '2026-10-18 15:15:55', '2026-10-18 15:15:55'),
	(14, 'EPT20', 'Kemaafan dan keampunan', NULL, '2026-10-18 15:16:12', '2026-10-18 15:16:12'),
	(15, 'EPT21', 'Kawalan sifat mazmumah', NULL, '2026-10-18 15:16:27', '2026-10-18 15:16:27'),
	(16, 'EPT22', 'Pengurusan tekanan', NULL, '2026-10-18 15:16:42', '2026-10-18 15:16:42');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
