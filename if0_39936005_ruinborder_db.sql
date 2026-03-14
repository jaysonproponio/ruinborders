-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql311.infinityfree.com
-- Generation Time: Mar 14, 2026 at 06:43 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_39936005_ruinborder_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `seen_receipts_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `fullname`, `created_at`, `seen_receipts_at`) VALUES
(5, 'admin', '$2y$10$d15a5sy7iViEV/E7qMFgkeiQqHNH4oN3daOeNpGEr1msHrHkdIv0C', 'SYSTEM ADMIN', '2026-03-13 01:55:11', NULL),
(2, 'adminJpro', '$2y$10$RUmEtrKp5NflF8nRSQZW1ODUiyEvnlfcGG.xSuspUVsU9GKD/ARci', 'Jayson Proponio', '2025-09-15 07:44:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `details`, `created_at`) VALUES
(18, 2, 'logout', 'Admin logged out', '2025-11-06 15:47:43'),
(19, 4, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":\"4\",\"username\":\"ruinboardersadmin\",\"fullname\":\"System Admin\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"time\":\"2025-11-06 23:47:54\"}', '2025-11-06 15:47:54'),
(20, 4, 'logout', 'Admin logged out', '2025-11-06 15:48:02'),
(21, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":\"2\",\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"time\":\"2025-11-06 23:48:12\"}', '2025-11-06 15:48:12'),
(22, 4, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":4,\"username\":\"ruinboardersadmin\",\"fullname\":\"System Admin\"},\"ip\":\"112.198.164.43\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"time\":\"2025-11-07 00:06:37\"}', '2025-11-06 16:06:37'),
(23, 2, 'logout', 'Admin logged out', '2025-11-06 16:11:33'),
(24, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.164.43\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-07 00:12:04\"}', '2025-11-06 16:12:04'),
(25, 4, 'logout', 'Admin logged out', '2025-11-06 16:27:56'),
(26, 4, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":4,\"username\":\"ruinboardersadmin\",\"fullname\":\"System Admin\"},\"ip\":\"112.198.164.43\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"time\":\"2025-11-07 00:28:03\"}', '2025-11-06 16:28:03'),
(27, 4, 'announcement_create', '{\"target\":\"announcement\",\"operation\":\"create\",\"title\":\"NEW ANNOUNCEMENT\",\"content\":\"Sa wala pa naka bayad sa month of August. Please e settle na dw ang payments\"}', '2025-11-06 16:38:59'),
(28, 4, 'announcement_edit', '{\"target\":\"announcement\",\"operation\":\"edit\",\"announcement_id\":\"7\",\"before\":{\"title\":\"NEW ANNOUNCEMENT\",\"content\":\"Sa wala pa naka bayad sa month of August. Please e settle na dw ang payments\"},\"after\":{\"title\":\"PAYMENT REMINDER\",\"content\":\"Sa wala pa naka bayad sa month of August. Please e settle na dw ang payments\"}}', '2025-11-06 16:39:20'),
(29, 4, 'announcement_edit', '{\"target\":\"announcement\",\"operation\":\"edit\",\"announcement_id\":\"7\",\"before\":{\"title\":\"PAYMENT REMINDER\",\"content\":\"Sa wala pa naka bayad sa month of August. Please e settle na dw ang payments\"},\"after\":{\"title\":\"PAYMENT REMINDER\",\"content\":\"Sa wala pa naka bayad sa month of August. Please e settle na dw ang payments\"}}', '2025-11-06 16:39:23'),
(30, 4, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"0.00\",\"method\":\"Gcash\"}', '2025-11-06 16:40:36'),
(31, 4, 'delete_payment_history', '{\"history_id\":\"39\"}', '2025-11-06 16:48:01'),
(32, 4, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"0.00\",\"method\":\"Cash\"}', '2025-11-06 16:48:21'),
(33, 4, 'delete_payment_history', '{\"history_id\":\"40\"}', '2025-11-06 16:48:50'),
(34, 4, 'announcement_edit', '{\"target\":\"announcement\",\"operation\":\"edit\",\"announcement_id\":\"7\",\"before\":{\"title\":\"PAYMENT REMINDER\",\"content\":\"Sa wala pa naka bayad sa month of August. Please e settle na dw ang payments\"},\"after\":{\"title\":\"PAYMENT REMINDER\",\"content\":\"Sa wala pa naka bayad sa month of August. Please e settle na dw ang inyung payments\"}}', '2025-11-06 16:49:24'),
(35, 4, 'logout', 'Admin logged out', '2025-11-06 16:52:24'),
(36, 4, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":4,\"username\":\"ruinboardersadmin\",\"fullname\":\"System Admin\"},\"ip\":\"175.176.93.50\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"time\":\"2025-11-07 12:24:50\"}', '2025-11-07 04:24:50'),
(37, 4, 'logout', 'Admin logged out', '2025-11-07 04:29:22'),
(38, 2, 'update_payment', '{\"user_id\":\"9\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-07 10:33:52'),
(39, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.165.120\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36 Edg\\/142.0.0.0\",\"time\":\"2025-11-08 18:19:25\"}', '2025-11-08 10:19:25'),
(40, 2, 'logout', 'Admin logged out', '2025-11-08 10:23:53'),
(41, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.90.124\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-10 11:53:30\"}', '2025-11-10 03:53:30'),
(42, 4, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":4,\"username\":\"ruinboardersadmin\",\"fullname\":\"System Admin\"},\"ip\":\"120.72.25.186\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"time\":\"2025-11-10 16:31:27\"}', '2025-11-10 08:31:27'),
(43, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"27.110.182.242\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-11 13:13:56\"}', '2025-11-11 05:13:56'),
(44, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.165.158\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-13 13:04:48\"}', '2025-11-13 05:04:49'),
(45, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.91.189\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-14 18:42:44\"}', '2025-11-14 10:42:44'),
(46, 2, 'update_payment', '{\"user_id\":\"31\",\"year\":\"2025\",\"month\":\"september\",\"amount\":\"800\",\"method\":\"Cash\"}', '2025-11-14 10:43:03'),
(47, 2, 'update_payment', '{\"user_id\":\"31\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Cash\"}', '2025-11-14 10:43:13'),
(48, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.165.27\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-17 09:46:03\"}', '2025-11-17 01:46:03'),
(49, 2, 'update_payment', '{\"user_id\":\"12\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"880\",\"method\":\"Cash\"}', '2025-11-17 01:46:21'),
(50, 2, 'update_payment', '{\"user_id\":\"17\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-17 01:58:49'),
(51, 2, 'update_payment', '{\"user_id\":\"14\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-17 02:04:02'),
(52, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"131.226.112.181\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-19 06:54:40\"}', '2025-11-18 22:54:40'),
(53, 2, 'update_payment', '{\"user_id\":\"27\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 22:55:13'),
(54, 2, 'update_payment', '{\"user_id\":\"26\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 22:55:28'),
(55, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"36\",\"before\":{\"fullname\":\"roselynsaygad\",\"gender\":\"Other\",\"room_number\":\"N\\/A\"},\"after\":{\"fullname\":\"roselynsaygad\",\"gender\":\"Other\",\"room_number\":\"3\",\"password_changed\":false}}', '2025-11-18 23:36:54'),
(56, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"36\",\"before\":{\"fullname\":\"roselynsaygad\",\"gender\":\"Other\",\"room_number\":\"3\"},\"after\":{\"fullname\":\"roselynsaygad\",\"gender\":\"Other\",\"room_number\":\"5\",\"password_changed\":false}}', '2025-11-18 23:38:50'),
(57, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:39:12'),
(58, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:39:18'),
(59, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"march\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:39:25'),
(60, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"april\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:39:31'),
(61, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"may\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:39:38'),
(62, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"june\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:39:45'),
(63, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"july\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:39:51'),
(64, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"august\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:39:58'),
(65, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"september\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:40:05'),
(66, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:40:11'),
(67, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"35\",\"before\":{\"fullname\":\"Randy D. Mandabon\",\"gender\":\"Other\",\"room_number\":\"N\\/A\"},\"after\":{\"fullname\":\"Randy D. Mandabon\",\"gender\":\"Other\",\"room_number\":\"7\",\"password_changed\":false}}', '2025-11-18 23:40:57'),
(68, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:42:31'),
(69, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:42:37'),
(70, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"march\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:42:44'),
(71, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"february\",\"amount\":\"800.00\",\"method\":\"Gcash\"}', '2025-11-18 23:42:48'),
(72, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"april\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:42:54'),
(73, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"may\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:43:00'),
(74, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"june\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:43:10'),
(75, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"july\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:43:15'),
(76, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"august\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:43:22'),
(77, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"september\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:43:28'),
(78, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:43:34'),
(79, 2, 'delete_payment_history', '{\"history_id\":\"69\"}', '2025-11-18 23:43:54'),
(80, 2, 'delete_payment_history', '{\"history_id\":\"68\"}', '2025-11-18 23:43:56'),
(81, 2, 'delete_payment_history', '{\"history_id\":\"67\"}', '2025-11-18 23:43:58'),
(82, 2, 'delete_payment_history', '{\"history_id\":\"66\"}', '2025-11-18 23:44:00'),
(83, 2, 'delete_payment_history', '{\"history_id\":\"65\"}', '2025-11-18 23:44:03'),
(84, 2, 'delete_payment_history', '{\"history_id\":\"64\"}', '2025-11-18 23:44:05'),
(85, 2, 'delete_payment_history', '{\"history_id\":\"63\"}', '2025-11-18 23:44:09'),
(86, 2, 'delete_payment_history', '{\"history_id\":\"62\"}', '2025-11-18 23:44:13'),
(87, 2, 'delete_payment_history', '{\"history_id\":\"61\"}', '2025-11-18 23:44:15'),
(88, 2, 'delete_payment_history', '{\"history_id\":\"60\"}', '2025-11-18 23:44:17'),
(89, 2, 'delete_payment_history', '{\"history_id\":\"59\"}', '2025-11-18 23:44:19'),
(90, 2, 'delete_payment_history', '{\"history_id\":\"58\"}', '2025-11-18 23:44:21'),
(91, 2, 'delete_payment_history', '{\"history_id\":\"57\"}', '2025-11-18 23:44:23'),
(92, 2, 'delete_payment_history', '{\"history_id\":\"56\"}', '2025-11-18 23:44:25'),
(93, 2, 'delete_payment_history', '{\"history_id\":\"55\"}', '2025-11-18 23:44:27'),
(94, 2, 'delete_payment_history', '{\"history_id\":\"54\"}', '2025-11-18 23:44:29'),
(95, 2, 'delete_payment_history', '{\"history_id\":\"53\"}', '2025-11-18 23:44:32'),
(96, 2, 'delete_payment_history', '{\"history_id\":\"52\"}', '2025-11-18 23:44:33'),
(97, 2, 'delete_payment_history', '{\"history_id\":\"51\"}', '2025-11-18 23:44:35'),
(98, 2, 'delete_payment_history', '{\"history_id\":\"50\"}', '2025-11-18 23:44:37'),
(99, 2, 'delete_payment_history', '{\"history_id\":\"49\"}', '2025-11-18 23:44:38'),
(100, 2, 'delete_payment_history', '{\"history_id\":\"48\"}', '2025-11-18 23:44:40'),
(101, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"0\",\"method\":\"Cash\"}', '2025-11-18 23:45:02'),
(102, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-18 23:45:09'),
(103, 2, 'delete_payment_history', '{\"history_id\":\"70\"}', '2025-11-18 23:45:19'),
(104, 2, 'user_delete', '{\"target\":\"user\",\"operation\":\"delete\",\"user_id\":\"32\",\"user\":{\"fullname\":\"Randy Mandabon\",\"gender\":\"Male\",\"room_number\":\"7\"}}', '2025-11-18 23:45:38'),
(105, 2, 'user_delete', '{\"target\":\"user\",\"operation\":\"delete\",\"user_id\":\"26\",\"user\":{\"fullname\":\"Roselyn\",\"gender\":\"Female\",\"room_number\":\"5\"}}', '2025-11-18 23:46:21'),
(106, 2, 'logout', 'Admin logged out', '2025-11-18 23:47:13'),
(107, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"131.226.112.181\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-19 07:47:17\"}', '2025-11-18 23:47:17'),
(108, 2, 'receipt_rejected', '{\"receipt_id\":\"9\"}', '2025-11-18 23:48:40'),
(109, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"131.226.112.181\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-20 23:03:56\"}', '2025-11-20 15:03:56'),
(110, 2, 'update_payment', '{\"user_id\":\"28\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-20 15:04:30'),
(111, 4, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":4,\"username\":\"ruinboardersadmin\",\"fullname\":\"System Admin\"},\"ip\":\"136.158.24.122\",\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/605.1.15 (KHTML, like Gecko) Version\\/26.0.1 Safari\\/605.1.15\",\"time\":\"2025-11-20 23:22:27\"}', '2025-11-20 15:22:27'),
(112, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-20 23:42:26'),
(113, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"143.44.185.5\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"time\":\"2025-11-22 18:24:47\"}', '2025-11-22 10:24:47'),
(114, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.91.32\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-11-26 11:50:54\"}', '2025-11-26 03:50:54'),
(115, 2, 'delete_payment_history', '{\"history_id\":\"72\"}', '2025-11-26 10:56:41'),
(116, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-11-26 10:57:56'),
(117, 2, 'logout', 'Admin logged out', '2025-11-26 14:41:40'),
(118, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.167.176\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-02 11:42:26\"}', '2025-12-02 03:42:26'),
(119, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.90.246\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"time\":\"2025-12-02 23:23:59\"}', '2025-12-02 15:23:59'),
(120, 2, 'update_payment', '{\"user_id\":\"21\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-02 15:24:31'),
(121, 2, 'update_payment', '{\"user_id\":\"21\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-02 15:54:14'),
(122, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.164.162\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-04 19:47:09\"}', '2025-12-04 11:47:09'),
(123, 2, 'update_payment', '{\"user_id\":\"9\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 11:48:10'),
(124, 2, 'update_payment', '{\"user_id\":\"25\",\"year\":\"2025\",\"month\":\"august\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 12:57:09'),
(125, 2, 'update_payment', '{\"user_id\":\"25\",\"year\":\"2025\",\"month\":\"september\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 12:57:16'),
(126, 2, 'update_payment', '{\"user_id\":\"25\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 12:57:23'),
(127, 2, 'update_payment', '{\"user_id\":\"25\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 12:57:29'),
(128, 2, 'update_payment', '{\"user_id\":\"19\",\"year\":\"2025\",\"month\":\"august\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 12:58:15'),
(129, 2, 'update_payment', '{\"user_id\":\"19\",\"year\":\"2025\",\"month\":\"september\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 12:58:20'),
(130, 2, 'update_payment', '{\"user_id\":\"19\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 12:58:25'),
(131, 2, 'update_payment', '{\"user_id\":\"19\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 12:58:31'),
(132, 2, 'update_payment', '{\"user_id\":\"29\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-04 13:00:48'),
(133, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.93.242\",\"user_agent\":\"Mozilla\\/5.0 (X11; Linux x86_64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Safari\\/537.36\",\"time\":\"2025-12-06 10:50:35\"}', '2025-12-06 02:50:35'),
(134, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.164.62\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-11 17:38:17\"}', '2025-12-11 09:38:17'),
(135, 2, 'update_payment', '{\"user_id\":\"24\",\"year\":\"2025\",\"month\":\"september\",\"amount\":\"600\",\"method\":\"Cash\"}', '2025-12-11 09:49:24'),
(136, 2, 'update_payment', '{\"user_id\":\"24\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-11 09:49:31'),
(137, 2, 'update_payment', '{\"user_id\":\"24\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800.00\",\"method\":\"Cash\"}', '2025-12-11 09:49:37'),
(138, 2, 'delete_payment_history', '{\"history_id\":\"89\"}', '2025-12-11 09:49:44'),
(139, 2, 'update_payment', '{\"user_id\":\"24\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Cash\"}', '2025-12-11 09:49:54'),
(140, 2, 'update_payment', '{\"user_id\":\"24\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Cash\"}', '2025-12-11 09:50:01'),
(141, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"131.226.113.5\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/142.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-12 16:11:59\"}', '2025-12-12 08:11:59'),
(142, 2, 'update_payment', '{\"user_id\":\"30\",\"year\":\"2025\",\"month\":\"october\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-12 08:56:13'),
(143, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.88.31\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-13 09:36:25\"}', '2025-12-13 01:36:25'),
(144, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-13 01:36:53'),
(145, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.164.62\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-15 12:57:16\"}', '2025-12-15 04:57:16'),
(146, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.164.62\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-15 22:04:32\"}', '2025-12-15 14:04:32'),
(147, 2, 'update_payment', '{\"user_id\":\"10\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-16 05:02:53'),
(148, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"131.226.112.236\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-17 12:54:01\"}', '2025-12-17 04:54:01'),
(149, 2, 'update_payment', '{\"user_id\":\"12\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-18 04:39:49'),
(150, 2, 'delete_payment_history', '{\"history_id\":\"96\"}', '2025-12-18 04:39:54'),
(151, 2, 'update_payment', '{\"user_id\":\"12\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800.00\",\"method\":\"Cash\"}', '2025-12-18 04:39:59'),
(152, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.166.8\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-18 14:23:14\"}', '2025-12-18 06:23:14'),
(153, 2, 'update_payment', '{\"user_id\":\"19\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Cash\"}', '2025-12-19 00:10:11'),
(154, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"49.146.3.170\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-22 13:05:57\"}', '2025-12-22 05:05:57'),
(155, 2, 'update_payment', '{\"user_id\":\"17\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Cash\"}', '2025-12-22 05:07:29'),
(156, 2, 'delete_payment_history', '{\"history_id\":\"99\"}', '2025-12-22 05:07:43'),
(157, 2, 'update_payment', '{\"user_id\":\"17\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800.00\",\"method\":\"Gcash\"}', '2025-12-22 05:07:54'),
(158, 2, 'update_payment', '{\"user_id\":\"14\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-22 05:08:03'),
(159, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.91.22\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-24 12:07:00\"}', '2025-12-24 04:07:00'),
(160, 2, 'update_payment', '{\"user_id\":\"23\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-24 04:07:17'),
(161, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.88.116\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-25 20:12:27\"}', '2025-12-25 12:12:27'),
(162, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.88.235\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2025-12-30 11:54:43\"}', '2025-12-30 03:54:43'),
(163, 2, 'update_payment', '{\"user_id\":\"29\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-30 09:18:00'),
(164, 2, 'update_payment', '{\"user_id\":\"29\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"1000\",\"method\":\"Gcash\"}', '2025-12-30 09:18:15'),
(165, 2, 'delete_payment_history', '{\"history_id\":\"103\"}', '2025-12-30 09:18:19'),
(166, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2025-12-30 09:25:03'),
(167, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.88.144\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-01-02 19:43:13\"}', '2026-01-02 11:43:13'),
(168, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"49.146.7.93\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-02 20:22:54\"}', '2026-01-02 12:22:54'),
(169, 2, 'update_payment', '{\"user_id\":\"25\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-02 12:43:46'),
(170, 2, 'update_payment', '{\"user_id\":\"21\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-02 12:43:56'),
(171, 2, 'update_payment', '{\"user_id\":\"25\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-02 12:44:10'),
(172, 2, 'logout', 'Admin logged out', '2026-01-03 01:59:00'),
(173, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"131.226.112.136\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-01-03 17:23:47\"}', '2026-01-03 09:23:47'),
(174, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.91.229\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-01-04 19:28:00\"}', '2026-01-04 11:28:00'),
(175, 2, 'update_payment', '{\"user_id\":\"10\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-04 11:28:30'),
(176, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.166.13\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-01-07 08:44:20\"}', '2026-01-07 00:44:20'),
(177, 2, 'update_payment', '{\"user_id\":\"27\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-07 09:55:22'),
(178, 2, 'update_payment', '{\"user_id\":\"27\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-07 09:55:29'),
(179, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-07 09:55:37'),
(180, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-07 09:55:44'),
(181, 2, 'update_payment', '{\"user_id\":\"28\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-07 09:55:56'),
(182, 2, 'update_payment', '{\"user_id\":\"28\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-07 09:56:04'),
(183, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.166.13\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-01-10 23:43:34\"}', '2026-01-10 15:43:34'),
(184, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.166.13\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-11 18:15:09\"}', '2026-01-11 10:15:09'),
(185, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"37\",\"before\":{\"fullname\":\"Joseph cloyd Denosta\",\"gender\":\"Other\",\"room_number\":\"N\\/A\"},\"after\":{\"fullname\":\"Joseph cloyd Denosta\",\"gender\":\"Other\",\"room_number\":\"1\",\"password_changed\":false}}', '2026-01-11 10:16:17'),
(186, 2, 'update_payment', '{\"user_id\":\"37\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-01-11 10:16:37'),
(187, 2, 'update_payment', '{\"user_id\":\"37\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-01-11 10:16:49'),
(188, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.165.132\",\"user_agent\":\"Mozilla\\/5.0 (X11; Linux x86_64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-16 08:06:29\"}', '2026-01-16 00:06:29'),
(189, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-16 00:19:33'),
(190, 2, 'update_payment', '{\"user_id\":\"37\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"0\",\"method\":\"Cash\"}', '2026-01-16 00:22:05'),
(191, 2, 'delete_payment_history', '{\"history_id\":\"119\"}', '2026-01-16 00:22:13'),
(192, 2, 'delete_payment_history', '{\"history_id\":\"117\"}', '2026-01-16 00:22:16'),
(193, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.167.46\",\"user_agent\":\"Mozilla\\/5.0 (X11; Linux x86_64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-20 19:39:20\"}', '2026-01-20 11:39:20'),
(194, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-22 02:02:33\"}', '2026-01-21 18:02:33'),
(195, 2, 'logout', 'Admin logged out', '2026-01-21 18:06:58'),
(196, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-22 02:07:17\"}', '2026-01-21 18:07:17'),
(197, 2, 'receipt_approved', '{\"receipt_id\":\"10\"}', '2026-01-21 18:09:04'),
(198, 2, 'delete_payment_history', '{\"history_id\":\"118\"}', '2026-01-21 18:09:30'),
(199, 2, 'logout', 'Admin logged out', '2026-01-21 18:11:13'),
(200, 4, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":4,\"username\":\"ruinboardersadmin\",\"fullname\":\"System Admin\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-22 02:15:24\"}', '2026-01-21 18:15:24'),
(201, 4, 'user_status_toggle', '{\"target\":\"user\",\"operation\":\"toggle_status\",\"user_id\":\"9\",\"before_status\":\"active\",\"after_status\":\"deactivated\"}', '2026-01-21 18:18:49'),
(202, 4, 'user_status_toggle', '{\"target\":\"user\",\"operation\":\"toggle_status\",\"user_id\":\"10\",\"before_status\":\"active\",\"after_status\":\"deactivated\"}', '2026-01-21 18:21:42'),
(203, 4, 'update_payment', '{\"user_id\":\"17\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-21 18:27:20'),
(204, 4, 'update_payment', '{\"user_id\":\"14\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-01-21 18:27:33'),
(205, 4, 'logout', 'Admin logged out', '2026-01-21 19:04:14'),
(206, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-22 03:18:58\"}', '2026-01-21 19:18:58'),
(207, 2, 'logout', 'Admin logged out', '2026-01-21 19:19:55'),
(208, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-22 03:22:31\"}', '2026-01-21 19:22:31'),
(209, 2, 'Revert Receipt', 'Reverted receipt #10 to pending.', '2026-01-21 20:21:21'),
(210, 2, 'Approve Receipt', 'Approved receipt #10 for user #35 (january 2026)', '2026-01-21 20:21:24'),
(211, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"1\",\"method\":\"Cash\"}', '2026-01-21 20:21:51'),
(212, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"0\",\"method\":\"Cash\"}', '2026-01-21 20:22:12'),
(213, 2, 'delete_payment_history', '{\"history_id\":\"123\"}', '2026-01-21 20:22:19'),
(214, 2, 'delete_payment_history', '{\"history_id\":\"124\"}', '2026-01-21 20:22:22'),
(215, 2, 'logout', 'Admin logged out', '2026-01-21 20:24:23'),
(216, 2, 'logout', 'Admin logged out', '2026-01-21 20:29:19'),
(217, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.167.46\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/143.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-22 04:29:24\"}', '2026-01-21 20:29:24'),
(218, 2, 'logout', 'Admin logged out', '2026-01-21 20:29:50'),
(219, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.167.46\",\"user_agent\":\"Mozilla\\/5.0 (X11; Linux x86_64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-22 08:08:32\"}', '2026-01-22 00:08:32'),
(220, 2, 'update_payment', '{\"user_id\":\"12\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-01-22 00:09:06'),
(221, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.89.44\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-01-26 10:59:42\"}', '2026-01-26 02:59:42'),
(222, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"120.72.25.186\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36\",\"time\":\"2026-01-26 14:23:48\"}', '2026-01-26 06:23:48'),
(223, 2, 'update_payment', '{\"user_id\":\"31\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-01-26 10:13:11'),
(224, 2, 'update_payment', '{\"user_id\":\"31\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-01-26 10:13:17'),
(225, 2, 'update_payment', '{\"user_id\":\"30\",\"year\":\"2025\",\"month\":\"november\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-01-26 10:13:28'),
(226, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.165.82\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-01-30 02:27:29\"}', '2026-01-29 18:27:29'),
(227, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.88.178\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-02-01 18:23:50\"}', '2026-02-01 10:23:50'),
(228, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"112.198.164.141\",\"user_agent\":\"Mozilla\\/5.0 (X11; Linux x86_64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36\",\"time\":\"2026-02-06 11:01:45\"}', '2026-02-06 03:01:45'),
(229, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-02-06 03:08:48'),
(230, 2, 'update_payment', '{\"user_id\":\"27\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-06 03:09:09'),
(231, 2, 'update_payment', '{\"user_id\":\"36\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-06 03:09:18'),
(232, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-06 14:41:44'),
(233, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"120.72.25.186\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-02-11 14:04:32\"}', '2026-02-11 06:04:32'),
(234, 2, 'update_payment', '{\"user_id\":\"23\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-02-11 06:06:31'),
(235, 2, 'update_payment', '{\"user_id\":\"28\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-11 07:48:47'),
(236, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"139.135.83.30\",\"user_agent\":\"Mozilla\\/5.0 (X11; Linux x86_64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Safari\\/537.36\",\"time\":\"2026-02-15 21:55:01\"}', '2026-02-15 13:55:01'),
(237, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.89.192\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-02-15 21:55:16\"}', '2026-02-15 13:55:16'),
(238, 2, 'update_payment', '{\"user_id\":\"17\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-15 13:55:38'),
(239, 2, 'update_payment', '{\"user_id\":\"14\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-15 13:55:52'),
(240, 2, 'update_payment', '{\"user_id\":\"24\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-16 01:58:34'),
(241, 2, 'update_payment', '{\"user_id\":\"24\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"400\",\"method\":\"Gcash\"}', '2026-02-16 01:58:40'),
(242, 2, 'update_payment', '{\"user_id\":\"30\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-16 02:02:53'),
(243, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.78.243\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-02-18 08:43:47\"}', '2026-02-18 00:43:47'),
(244, 2, 'update_payment', '{\"user_id\":\"12\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-18 00:44:28'),
(245, 2, 'update_payment', '{\"user_id\":\"19\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-18 00:51:39'),
(246, 2, 'update_payment', '{\"user_id\":\"21\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-18 00:51:54'),
(247, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.78\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Safari\\/537.36\",\"time\":\"2026-02-26 17:54:02\"}', '2026-02-26 09:54:02'),
(248, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.91.4\",\"user_agent\":\"Mozilla\\/5.0 (X11; Linux x86_64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36\",\"time\":\"2026-02-27 19:41:29\"}', '2026-02-27 11:41:29'),
(249, 2, 'update_payment', '{\"user_id\":\"29\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-27 11:42:28'),
(250, 2, 'delete_payment_history', '{\"history_id\":\"143\"}', '2026-02-27 11:42:37'),
(251, 2, 'update_payment', '{\"user_id\":\"29\",\"year\":\"2025\",\"month\":\"december\",\"amount\":\"600.00\",\"method\":\"Gcash\"}', '2026-02-27 11:42:49'),
(252, 2, 'update_payment', '{\"user_id\":\"29\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-02-27 11:42:59'),
(253, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.89.21\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Safari\\/537.36\",\"time\":\"2026-02-28 18:34:45\"}', '2026-02-28 10:34:44'),
(254, 2, 'update_payment', '{\"user_id\":\"30\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-02-28 10:46:08'),
(255, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.91.112\",\"user_agent\":\"Mozilla\\/5.0 (X11; Linux x86_64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/144.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-02 18:07:37\"}', '2026-03-02 10:07:37'),
(256, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.89.177\",\"user_agent\":\"Mozilla\\/5.0 (X11; Linux x86_64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-06 15:23:44\"}', '2026-03-06 07:23:44'),
(257, 2, 'update_payment', '{\"user_id\":\"31\",\"year\":\"2026\",\"month\":\"january\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-03-06 07:24:24'),
(258, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-03-07 02:19:50'),
(259, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-07 11:05:10\"}', '2026-03-07 03:05:10'),
(260, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-03-07 03:05:33'),
(261, 2, 'update_payment', '{\"user_id\":\"35\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-03-07 03:05:36'),
(262, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.91.238\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-09 12:47:01\"}', '2026-03-09 04:47:01'),
(263, 2, 'update_payment', '{\"user_id\":\"28\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-03-09 04:47:25'),
(264, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.91.221\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-10 15:41:05\"}', '2026-03-10 07:41:05'),
(265, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/128.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-11 17:40:58\"}', '2026-03-11 09:40:59'),
(266, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"175.176.90.9\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-11 18:52:53\"}', '2026-03-11 10:52:53'),
(267, 2, 'update_payment', '{\"user_id\":\"23\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"1000\",\"method\":\"Gcash\"}', '2026-03-11 10:53:40'),
(268, 2, 'update_payment', '{\"user_id\":\"24\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-03-12 03:43:01'),
(269, 2, 'update_payment', '{\"user_id\":\"37\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-03-12 05:51:13'),
(270, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-12 19:41:03\"}', '2026-03-12 11:41:03');
INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `details`, `created_at`) VALUES
(271, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-12 20:26:41\"}', '2026-03-12 12:26:41'),
(272, 2, 'logout', 'Admin logged out', '2026-03-12 13:11:28'),
(273, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-12 21:11:39\"}', '2026-03-12 13:11:39'),
(274, 2, 'logout', 'Admin logged out', '2026-03-12 13:56:18'),
(275, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-12 21:56:29\"}', '2026-03-12 13:56:29'),
(276, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"801\",\"method\":\"Cash\"}', '2026-03-12 14:05:02'),
(277, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"1.00\",\"method\":\"Cash\"}', '2026-03-12 14:05:27'),
(278, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"0.00\",\"method\":\"Cash\"}', '2026-03-12 14:05:48'),
(279, 2, 'delete_payment_history', '{\"history_id\":\"157\"}', '2026-03-12 14:06:01'),
(280, 2, 'delete_payment_history', '{\"history_id\":\"156\"}', '2026-03-12 14:06:04'),
(281, 2, 'delete_payment_history', '{\"history_id\":\"155\"}', '2026-03-12 14:06:07'),
(282, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800.00\",\"method\":\"Cash\"}', '2026-03-12 14:30:12'),
(283, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-03-12 14:32:09'),
(284, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"0\",\"method\":\"Cash\"}', '2026-03-12 14:32:40'),
(285, 2, 'delete_payment_history', '{\"history_id\":\"160\"}', '2026-03-12 14:32:44'),
(286, 2, 'delete_payment_history', '{\"history_id\":\"159\"}', '2026-03-12 14:32:47'),
(287, 2, 'delete_payment_history', '{\"history_id\":\"159\"}', '2026-03-12 14:33:06'),
(288, 2, 'Reject Receipt', 'Rejected receipt #12. Reason: HAHAHAA', '2026-03-12 14:35:48'),
(289, 2, 'Reject Receipt', 'Rejected receipt #11. Reason: ', '2026-03-12 14:36:01'),
(290, 2, 'Reject Receipt', 'Rejected receipt #13. Reason: ', '2026-03-12 14:46:32'),
(291, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"1\",\"method\":\"Cash\"}', '2026-03-12 14:47:02'),
(292, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"0\",\"method\":\"Cash\"}', '2026-03-12 14:47:37'),
(293, 2, 'delete_payment_history', '{\"history_id\":\"162\"}', '2026-03-12 14:47:45'),
(294, 2, 'delete_payment_history', '{\"history_id\":\"161\"}', '2026-03-12 14:47:49'),
(295, 2, 'update_payment', '{\"user_id\":\"31\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"1\",\"method\":\"Cash\"}', '2026-03-12 14:48:26'),
(296, 2, 'update_payment', '{\"user_id\":\"31\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"0\",\"method\":\"Cash\"}', '2026-03-12 14:48:36'),
(297, 2, 'delete_payment_history', '{\"history_id\":\"164\"}', '2026-03-12 14:48:40'),
(298, 2, 'delete_payment_history', '{\"history_id\":\"163\"}', '2026-03-12 14:48:43'),
(299, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"1\",\"method\":\"Cash\"}', '2026-03-12 14:52:29'),
(300, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"0\",\"method\":\"Cash\"}', '2026-03-12 14:52:45'),
(301, 2, 'delete_payment_history', '{\"history_id\":\"166\"}', '2026-03-12 14:52:57'),
(302, 2, 'delete_payment_history', '{\"history_id\":\"165\"}', '2026-03-12 14:52:59'),
(303, 2, 'logout', 'Admin logged out', '2026-03-12 15:07:05'),
(304, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-12 23:07:16\"}', '2026-03-12 15:07:16'),
(305, 2, 'Reject Receipt', 'Rejected receipt #14. Reason: HAHAHAHHA', '2026-03-12 15:08:34'),
(306, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"27\",\"before\":{\"fullname\":\"Ronalyn\",\"gender\":\"Female\",\"room_number\":\"5\"},\"after\":{\"fullname\":\"Ronalyn Saygad\",\"gender\":\"Female\",\"room_number\":\"5\",\"email\":\"\",\"password_changed\":false}}', '2026-03-12 15:24:50'),
(307, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"36\",\"before\":{\"fullname\":\"roselynsaygad\",\"gender\":\"Other\",\"room_number\":\"5\"},\"after\":{\"fullname\":\"Roselyn Saygad\",\"gender\":\"Other\",\"room_number\":\"5\",\"email\":\"saygadroselyn03@gmail.com\",\"password_changed\":false}}', '2026-03-12 15:25:14'),
(308, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"1\",\"method\":\"Cash\"}', '2026-03-12 15:44:23'),
(309, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"0\",\"method\":\"Cash\"}', '2026-03-12 15:44:49'),
(310, 2, 'logout', 'Admin logged out', '2026-03-12 15:57:30'),
(311, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-12 23:58:08\"}', '2026-03-12 15:58:08'),
(312, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"::1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-13 00:01:54\"}', '2026-03-12 16:01:54'),
(313, 2, 'logout', 'Admin logged out', '2026-03-12 16:02:00'),
(314, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"1\",\"method\":\"Cash\"}', '2026-03-13 00:18:10'),
(315, 2, 'update_payment', '{\"user_id\":\"6\",\"year\":\"2026\",\"month\":\"march\",\"amount\":\"0\",\"method\":\"Gcash\"}', '2026-03-13 00:18:34'),
(316, 2, 'delete_payment_history', '{\"history_id\":\"170\"}', '2026-03-13 00:18:45'),
(317, 2, 'delete_payment_history', '{\"history_id\":\"169\"}', '2026-03-13 00:18:48'),
(318, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-13 09:18:12\"}', '2026-03-13 01:18:12'),
(319, 2, 'logout', 'Admin logged out', '2026-03-13 01:18:56'),
(320, 2, 'logout', 'Admin logged out', '2026-03-13 01:19:39'),
(321, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-13 09:20:10\"}', '2026-03-13 01:20:10'),
(322, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-13 09:21:05\"}', '2026-03-13 01:21:04'),
(323, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"25\",\"before\":{\"fullname\":\"John\",\"gender\":\"Male\",\"room_number\":\"2\"},\"after\":{\"fullname\":\"John\",\"gender\":\"Male\",\"room_number\":\"2\",\"email\":\"john@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:22:00'),
(324, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"23\",\"before\":{\"fullname\":\"Cloy\",\"gender\":\"Male\",\"room_number\":\"2\"},\"after\":{\"fullname\":\"Cloy\",\"gender\":\"Male\",\"room_number\":\"2\",\"email\":\"cloy@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:24:03'),
(325, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"21\",\"before\":{\"fullname\":\"Jenie  Rose Madenancil\",\"gender\":\"Female\",\"room_number\":\"3\"},\"after\":{\"fullname\":\"Jenie  Rose Madenancil\",\"gender\":\"Female\",\"room_number\":\"3\",\"email\":\"jenierose@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:25:39'),
(326, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"17\",\"before\":{\"fullname\":\"Princess Barbarona\",\"gender\":\"Female\",\"room_number\":\"4\"},\"after\":{\"fullname\":\"Princess Barbarona\",\"gender\":\"Female\",\"room_number\":\"4\",\"email\":\"princess@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:27:52'),
(327, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"14\",\"before\":{\"fullname\":\"Quennie Barbarona\",\"gender\":\"Female\",\"room_number\":\"4\"},\"after\":{\"fullname\":\"Quennie Barbarona\",\"gender\":\"Female\",\"room_number\":\"4\",\"email\":\"quennie@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:28:35'),
(328, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"12\",\"before\":{\"fullname\":\"Maylanie Duyag\",\"gender\":\"Female\",\"room_number\":\"4\"},\"after\":{\"fullname\":\"Maylanie Duyag\",\"gender\":\"Female\",\"room_number\":\"4\",\"email\":\"maylanie@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:29:10'),
(329, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"36\",\"before\":{\"fullname\":\"Roselyn Saygad\",\"gender\":\"Other\",\"room_number\":\"5\"},\"after\":{\"fullname\":\"Roselyn Saygad\",\"gender\":\"Other\",\"room_number\":\"5\",\"email\":\"roselyn@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:31:59'),
(330, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"28\",\"before\":{\"fullname\":\"Angelica\",\"gender\":\"Female\",\"room_number\":\"5\"},\"after\":{\"fullname\":\"Angelica\",\"gender\":\"Female\",\"room_number\":\"5\",\"email\":\"angelica@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:32:42'),
(331, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"27\",\"before\":{\"fullname\":\"Ronalyn Saygad\",\"gender\":\"Female\",\"room_number\":\"5\"},\"after\":{\"fullname\":\"Ronalyn Saygad\",\"gender\":\"Female\",\"room_number\":\"5\",\"email\":\"ronalyn@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:33:40'),
(332, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"30\",\"before\":{\"fullname\":\"Melvin Liguez\",\"gender\":\"Male\",\"room_number\":\"6\"},\"after\":{\"fullname\":\"Melvin Liguez\",\"gender\":\"Male\",\"room_number\":\"6\",\"email\":\"melvin@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:34:23'),
(333, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"29\",\"before\":{\"fullname\":\"Daryl\",\"gender\":\"Male\",\"room_number\":\"6\"},\"after\":{\"fullname\":\"Daryl\",\"gender\":\"Male\",\"room_number\":\"6\",\"email\":\"daryl@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:35:14'),
(334, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"31\",\"before\":{\"fullname\":\"Albert\",\"gender\":\"Male\",\"room_number\":\"7\"},\"after\":{\"fullname\":\"Albert\",\"gender\":\"Male\",\"room_number\":\"7\",\"email\":\"albert@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:35:53'),
(335, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"37\",\"before\":{\"fullname\":\"Joseph cloyd Denosta\",\"gender\":\"Other\",\"room_number\":\"1\"},\"after\":{\"fullname\":\"Joseph cloyd Denosta\",\"gender\":\"Other\",\"room_number\":\"1\",\"email\":\"joseph@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:36:43'),
(336, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"35\",\"before\":{\"fullname\":\"Randy D. Mandabon\",\"gender\":\"Other\",\"room_number\":\"7\"},\"after\":{\"fullname\":\"Randy D. Mandabon\",\"gender\":\"Other\",\"room_number\":\"7\",\"email\":\"randy@ruin.borders\",\"password_changed\":true}}', '2026-03-13 01:37:35'),
(337, 2, 'user_edit', '{\"target\":\"user\",\"operation\":\"edit\",\"user_id\":\"6\",\"before\":{\"fullname\":\"Jayson Proponio\",\"gender\":\"Male\",\"room_number\":\"1\"},\"after\":{\"fullname\":\"Jayson Proponio\",\"gender\":\"Male\",\"room_number\":\"1\",\"email\":\"jayson@ruin.borders\",\"password_changed\":false}}', '2026-03-13 01:37:59'),
(338, 2, 'logout', 'Admin logged out', '2026-03-13 01:39:36'),
(339, 2, 'logout', 'Admin logged out', '2026-03-13 01:43:19'),
(340, 5, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":5,\"username\":\"admin\",\"fullname\":\"SYSTEM ADMIN\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-13 09:55:30\"}', '2026-03-13 01:55:29'),
(341, 5, 'change_admin_password', '', '2026-03-13 01:55:52'),
(342, 5, 'logout', 'Admin logged out', '2026-03-13 01:59:02'),
(343, 5, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":5,\"username\":\"admin\",\"fullname\":\"SYSTEM ADMIN\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/146.0.0.0 Safari\\/537.36\",\"time\":\"2026-03-13 10:10:25\"}', '2026-03-13 02:10:26'),
(344, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-13 10:11:00\"}', '2026-03-13 02:11:01'),
(345, 2, 'logout', 'Admin logged out', '2026-03-13 02:43:23'),
(346, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-13 10:45:28\"}', '2026-03-13 02:45:28'),
(347, 2, 'logout', 'Admin logged out', '2026-03-13 02:45:35'),
(348, 5, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":5,\"username\":\"admin\",\"fullname\":\"SYSTEM ADMIN\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-13 10:45:37\"}', '2026-03-13 02:45:37'),
(349, 5, 'logout', 'Admin logged out', '2026-03-13 03:02:23'),
(350, 5, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":5,\"username\":\"admin\",\"fullname\":\"SYSTEM ADMIN\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-13 11:38:31\"}', '2026-03-13 03:38:31'),
(351, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.79.209\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-13 22:42:18\"}', '2026-03-13 14:42:18'),
(352, 2, 'delete_payment_history', '{\"history_id\":\"168\"}', '2026-03-13 14:43:00'),
(353, 2, 'delete_payment_history', '{\"history_id\":\"167\"}', '2026-03-13 14:43:03'),
(354, 2, 'update_payment', '{\"user_id\":\"30\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Cash\"}', '2026-03-14 02:51:14'),
(355, 2, 'update_payment', '{\"user_id\":\"21\",\"year\":\"2026\",\"month\":\"february\",\"amount\":\"800\",\"method\":\"Gcash\"}', '2026-03-14 03:35:05'),
(356, 2, 'logout', 'Admin logged out', '2026-03-14 05:58:13'),
(357, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.78.48\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-14 13:58:20\"}', '2026-03-14 05:58:20'),
(358, 2, 'logout', 'Admin logged out', '2026-03-14 05:58:39'),
(359, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.78.48\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-14 13:59:38\"}', '2026-03-14 05:59:38'),
(360, 2, 'logout', 'Admin logged out', '2026-03-14 05:59:54'),
(361, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.78.48\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-14 14:01:05\"}', '2026-03-14 06:01:06'),
(362, 2, 'logout', 'Admin logged out', '2026-03-14 06:01:45'),
(363, 2, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":2,\"username\":\"adminJpro\",\"fullname\":\"Jayson Proponio\"},\"ip\":\"180.191.78.48\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-14 14:02:23\"}', '2026-03-14 06:02:23'),
(364, 2, 'logout', 'Admin logged out', '2026-03-14 06:06:36'),
(365, 5, 'admin_login', '{\"event\":\"login\",\"admin\":{\"id\":5,\"username\":\"admin\",\"fullname\":\"SYSTEM ADMIN\"},\"ip\":\"180.191.78.48\",\"user_agent\":\"Mozilla\\/5.0 (Linux; Android 10; K) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/145.0.0.0 Mobile Safari\\/537.36\",\"time\":\"2026-03-14 14:06:39\"}', '2026-03-14 06:06:40'),
(366, 5, 'logout', 'Admin logged out', '2026-03-14 06:09:37');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `admin_id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(16, 5, 'PAYMENT REMINDER', 'Kadtong wala pa nagbayad, please settle your payables. Thank you!', '2026-03-14 06:09:04', '2026-03-14 06:09:04'),
(2, 2, 'Announcement', 'Pamayad na daw mo guys ana si ante', '2025-09-15 09:08:58', '2026-03-12 14:29:16'),
(7, 4, 'PAYMENT REMINDER', 'Sa wala pa naka bayad sa month of August. Please e settle na dw ang inyung payments. SALAMAT :)', '2025-11-06 16:38:59', '2026-03-12 14:29:16'),
(8, 4, 'PAYMENT REMINDERS', 'REMINDER: Please bayad namo. Kung dli mo makabayad in full by December, pwede namo mangita ug lain boarding house and move out by January. Thank you!', '2025-11-20 15:24:25', '2026-03-12 14:29:16'),
(15, 5, 'HOUSE RULES', '1. Payment Obligation – All payments shall be made on or before the 16th day of each month.\r\n\r\n2. Curfew – 12 midnight. Any return beyond curfew hours must be communicated to the household in advance.\r\n\r\n3. Restriction on Overnight Guests – Non-residents and outsiders are STRICTLY prohibited from sleeping overnight within the premises, unless otherwise permitted.\r\n\r\n4. Maintenance of Cleanliness in Sink Area – To avoid clogging the sink, all food scraps, crumbs, and residue must be disposed of appropriately.\r\n\r\n5. Proper Disposal of Bathroom Waste – Please ensure that you dispose your waste in the plastic bag.\r\n\r\n6. Prohibition on Improper Disposal of Sanitary Products – Sanitary napkins and similar items must not be flushed or disposed of in the toilet bowl to avoid clogging.\r\n\r\n7.Clean-As-You-Go Policy (CLAYGO) – All residents and guests must observe cleanliness and orderliness in all common areas at all times.', '2026-03-14 06:08:07', '2026-03-14 06:08:07');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `january` decimal(10,2) DEFAULT 0.00,
  `february` decimal(10,2) DEFAULT 0.00,
  `march` decimal(10,2) DEFAULT 0.00,
  `april` decimal(10,2) DEFAULT 0.00,
  `may` decimal(10,2) DEFAULT 0.00,
  `june` decimal(10,2) DEFAULT 0.00,
  `july` decimal(10,2) DEFAULT 0.00,
  `august` decimal(10,2) DEFAULT 0.00,
  `september` decimal(10,2) DEFAULT 0.00,
  `october` decimal(10,2) DEFAULT 0.00,
  `november` decimal(10,2) DEFAULT 0.00,
  `december` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `year`, `january`, `february`, `march`, `april`, `may`, `june`, `july`, `august`, `september`, `october`, `november`, `december`, `created_at`, `updated_at`) VALUES
(1, 6, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-15 07:34:22', '2025-12-30 09:25:03'),
(2, 9, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '0.00', '2025-09-15 07:38:27', '2025-12-04 11:48:10'),
(3, 10, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-15 07:38:48', '2026-01-04 11:28:30'),
(4, 12, 2025, '1.00', '1.00', '1.00', '1.00', '1.00', '1.00', '100.00', '800.00', '800.00', '800.00', '880.00', '800.00', '2025-09-15 07:53:09', '2025-12-18 04:39:49'),
(5, 17, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-15 08:22:13', '2026-01-21 18:27:20'),
(6, 14, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-15 08:22:59', '2026-01-21 18:27:33'),
(7, 19, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-16 00:43:34', '2025-12-19 00:10:11'),
(8, 23, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '1000.00', '600.00', '1000.00', '600.00', '800.00', '2025-09-16 00:53:26', '2025-12-24 04:07:17'),
(9, 24, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '1000.00', '600.00', '800.00', '800.00', '800.00', '2025-09-16 00:54:10', '2025-12-11 09:50:01'),
(10, 25, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-16 00:54:41', '2026-01-02 12:43:46'),
(11, 26, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '0.00', '0.00', '2025-09-16 00:55:17', '2025-11-18 22:55:28'),
(12, 27, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-16 00:56:03', '2026-01-07 09:55:29'),
(13, 28, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-16 00:56:55', '2026-01-07 09:55:56'),
(14, 29, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '1000.00', '600.00', '2025-09-16 00:57:38', '2026-02-27 11:42:49'),
(15, 30, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-16 00:58:00', '2026-02-16 02:02:53'),
(16, 31, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-16 00:58:39', '2026-01-26 10:13:17'),
(17, 32, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '0.00', '0.00', '2025-09-16 00:59:39', '2025-10-27 04:42:23'),
(18, 6, 2024, '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '800.00', '2025-09-16 01:53:35', '2025-09-16 01:53:35'),
(19, 21, 2025, '1.00', '1.00', '1.00', '1.00', '1.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-09-16 08:03:36', '2026-01-02 12:43:56'),
(20, 6, 2023, '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2025-09-17 00:56:19', '2025-09-17 00:56:19'),
(21, 33, 2025, '800.00', '800.00', '800.00', '800.00', '1.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2025-11-01 08:46:13', '2025-11-01 09:15:27'),
(22, 36, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-11-18 23:39:12', '2026-01-07 09:55:44'),
(23, 35, 2025, '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '800.00', '2025-11-18 23:42:31', '2025-12-13 01:36:53'),
(24, 25, 2026, '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-01-02 12:44:10', '2026-01-02 12:44:10'),
(25, 28, 2026, '800.00', '800.00', '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-01-07 09:56:04', '2026-03-09 04:47:25'),
(26, 37, 2026, '800.00', '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-01-11 10:16:37', '2026-03-12 05:51:13'),
(27, 35, 2026, '800.00', '800.00', '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-01-16 00:19:33', '2026-03-07 03:05:33'),
(28, 6, 2026, '800.00', '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-01-21 20:21:51', '2026-03-13 00:18:34'),
(29, 12, 2026, '800.00', '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-01-22 00:09:06', '2026-02-18 00:44:28'),
(30, 27, 2026, '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-06 03:09:09', '2026-02-06 03:09:09'),
(31, 36, 2026, '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-06 03:09:18', '2026-02-06 03:09:18'),
(32, 23, 2026, '800.00', '1000.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-11 06:06:31', '2026-03-11 10:53:40'),
(33, 17, 2026, '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-15 13:55:38', '2026-02-15 13:55:38'),
(34, 14, 2026, '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-15 13:55:52', '2026-02-15 13:55:52'),
(35, 24, 2026, '800.00', '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-16 01:58:34', '2026-03-12 03:43:01'),
(36, 19, 2026, '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-18 00:51:39', '2026-02-18 00:51:39'),
(37, 21, 2026, '800.00', '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-18 00:51:54', '2026-03-14 03:35:05'),
(38, 29, 2026, '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-27 11:42:59', '2026-02-27 11:42:59'),
(39, 30, 2026, '800.00', '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-02-28 10:46:08', '2026-03-14 02:51:14'),
(40, 31, 2026, '800.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '2026-03-06 07:24:24', '2026-03-12 14:48:36');

-- --------------------------------------------------------

--
-- Table structure for table `payment_history`
--

CREATE TABLE `payment_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `year` year(4) NOT NULL,
  `month` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_history`
--

INSERT INTO `payment_history` (`id`, `user_id`, `fullname`, `year`, `month`, `amount`, `admin_id`, `created_at`, `payment_method`) VALUES
(15, 30, 'Melvin Liguez', 2025, 'september', '800.00', 2, '2025-11-01 09:48:54', NULL),
(16, 6, 'Jayson Agbon Proponio', 2025, 'october', '800.00', 2, '2025-11-01 10:37:38', NULL),
(28, 21, 'Jenie  Rose Madenancil', 2025, 'september', '800.00', 3, '2025-11-01 10:53:57', NULL),
(29, 21, 'Jenie  Rose Madenancil', 2025, 'october', '800.00', 3, '2025-11-01 10:54:01', NULL),
(31, 28, 'Angelica', 2025, 'september', '800.00', 3, '2025-11-02 06:25:34', NULL),
(32, 28, 'Angelica', 2025, 'october', '800.00', 3, '2025-11-02 06:25:42', NULL),
(33, 10, 'Mel John Empuesto', 2025, 'october', '800.00', 2, '2025-11-04 00:31:34', NULL),
(35, 23, 'Cloy', 2025, 'october', '1000.00', 2, '2025-11-05 09:15:50', NULL),
(36, 23, 'Cloy', 2025, 'november', '600.00', 2, '2025-11-06 12:30:16', NULL),
(41, 9, 'Christian Peralta', 2025, 'october', '800.00', 2, '2025-11-07 10:33:52', 'Gcash'),
(42, 31, 'Albert', 2025, 'september', '800.00', 2, '2025-11-14 10:43:03', 'Cash'),
(43, 31, 'Albert', 2025, 'october', '800.00', 2, '2025-11-14 10:43:13', 'Cash'),
(44, 12, 'Maylanie Duyag', 2025, 'november', '880.00', 2, '2025-11-17 01:46:21', 'Cash'),
(45, 17, 'Princess Barbarona', 2025, 'october', '800.00', 2, '2025-11-17 01:58:49', 'Gcash'),
(46, 14, 'Quennie Barbarona', 2025, 'october', '800.00', 2, '2025-11-17 02:04:02', 'Gcash'),
(47, 27, 'Ronalyn', 2025, 'october', '800.00', 2, '2025-11-18 22:55:13', 'Gcash'),
(71, 35, 'Randy D. Mandabon', 2025, 'october', '800.00', 2, '2025-11-18 23:45:09', 'Gcash'),
(73, 28, 'Angelica', 2025, 'november', '800.00', 2, '2025-11-20 15:04:30', 'Gcash'),
(74, 35, 'Randy D. Mandabon', 2025, 'november', '800.00', 2, '2025-11-20 23:42:26', 'Gcash'),
(75, 6, 'Jayson Proponio', 2025, 'november', '800.00', 2, '2025-11-26 10:57:56', 'Gcash'),
(76, 21, 'Jenie  Rose Madenancil', 2025, 'november', '800.00', 2, '2025-12-02 15:24:31', 'Gcash'),
(77, 21, 'Jenie  Rose Madenancil', 2025, 'november', '800.00', 2, '2025-12-02 15:54:14', 'Gcash'),
(78, 9, 'Christian Peralta', 2025, 'november', '800.00', 2, '2025-12-04 11:48:10', 'Gcash'),
(79, 25, 'John', 2025, 'august', '800.00', 2, '2025-12-04 12:57:09', 'Gcash'),
(80, 25, 'John', 2025, 'september', '800.00', 2, '2025-12-04 12:57:16', 'Gcash'),
(81, 25, 'John', 2025, 'october', '800.00', 2, '2025-12-04 12:57:23', 'Gcash'),
(82, 25, 'John', 2025, 'november', '800.00', 2, '2025-12-04 12:57:29', 'Gcash'),
(83, 19, 'Kristine Grace Pudang', 2025, 'august', '800.00', 2, '2025-12-04 12:58:15', 'Gcash'),
(84, 19, 'Kristine Grace Pudang', 2025, 'september', '800.00', 2, '2025-12-04 12:58:20', 'Gcash'),
(85, 19, 'Kristine Grace Pudang', 2025, 'october', '800.00', 2, '2025-12-04 12:58:25', 'Gcash'),
(86, 19, 'Kristine Grace Pudang', 2025, 'november', '800.00', 2, '2025-12-04 12:58:31', 'Gcash'),
(87, 29, 'Daryl', 2025, 'october', '800.00', 2, '2025-12-04 13:00:48', 'Gcash'),
(88, 24, 'Ken', 2025, 'september', '600.00', 2, '2025-12-11 09:49:24', 'Cash'),
(90, 24, 'Ken', 2025, 'october', '800.00', 2, '2025-12-11 09:49:37', 'Cash'),
(91, 24, 'Ken', 2025, 'november', '800.00', 2, '2025-12-11 09:49:54', 'Cash'),
(92, 24, 'Ken', 2025, 'december', '800.00', 2, '2025-12-11 09:50:01', 'Cash'),
(93, 30, 'Melvin Liguez', 2025, 'october', '800.00', 2, '2025-12-12 08:56:13', 'Gcash'),
(94, 35, 'Randy D. Mandabon', 2025, 'december', '800.00', 2, '2025-12-13 01:36:53', 'Gcash'),
(95, 10, 'Mel John Empuesto', 2025, 'november', '800.00', 2, '2025-12-16 05:02:53', 'Gcash'),
(97, 12, 'Maylanie Duyag', 2025, 'december', '800.00', 2, '2025-12-18 04:39:59', 'Cash'),
(98, 19, 'Kristine Grace Pudang', 2025, 'december', '800.00', 2, '2025-12-19 00:10:11', 'Cash'),
(100, 17, 'Princess Barbarona', 2025, 'november', '800.00', 2, '2025-12-22 05:07:54', 'Gcash'),
(101, 14, 'Quennie Barbarona', 2025, 'november', '800.00', 2, '2025-12-22 05:08:03', 'Gcash'),
(102, 23, 'Cloy', 2025, 'december', '800.00', 2, '2025-12-24 04:07:17', 'Gcash'),
(104, 29, 'Daryl', 2025, 'november', '1000.00', 2, '2025-12-30 09:18:15', 'Gcash'),
(105, 6, 'Jayson Proponio', 2025, 'december', '800.00', 2, '2025-12-30 09:25:03', 'Gcash'),
(106, 25, 'John', 2025, 'december', '800.00', 2, '2026-01-02 12:43:46', 'Gcash'),
(107, 21, 'Jenie  Rose Madenancil', 2025, 'december', '800.00', 2, '2026-01-02 12:43:56', 'Gcash'),
(108, 25, 'John', 2026, 'january', '800.00', 2, '2026-01-02 12:44:10', 'Gcash'),
(109, 10, 'Mel John Empuesto', 2025, 'december', '800.00', 2, '2026-01-04 11:28:30', 'Gcash'),
(110, 27, 'Ronalyn', 2025, 'november', '800.00', 2, '2026-01-07 09:55:22', 'Gcash'),
(111, 27, 'Ronalyn', 2025, 'december', '800.00', 2, '2026-01-07 09:55:29', 'Gcash'),
(112, 36, 'roselynsaygad', 2025, 'november', '800.00', 2, '2026-01-07 09:55:37', 'Gcash'),
(113, 36, 'roselynsaygad', 2025, 'december', '800.00', 2, '2026-01-07 09:55:44', 'Gcash'),
(114, 28, 'Angelica', 2025, 'december', '800.00', 2, '2026-01-07 09:55:56', 'Gcash'),
(115, 28, 'Angelica', 2026, 'january', '800.00', 2, '2026-01-07 09:56:04', 'Gcash'),
(116, 37, 'Joseph cloyd Denosta', 2026, 'january', '800.00', 2, '2026-01-11 10:16:37', 'Cash'),
(120, 35, 'Randy D. Mandabon', 2026, 'january', '800.00', 2, '2026-01-21 18:09:04', 'Receipt Approved'),
(121, 17, 'Princess Barbarona', 2025, 'december', '800.00', 4, '2026-01-21 18:27:20', 'Gcash'),
(122, 14, 'Quennie Barbarona', 2025, 'december', '800.00', 4, '2026-01-21 18:27:33', 'Gcash'),
(125, 12, 'Maylanie Duyag', 2026, 'january', '800.00', 2, '2026-01-22 00:09:06', 'Cash'),
(126, 31, 'Albert', 2025, 'november', '800.00', 2, '2026-01-26 10:13:11', 'Cash'),
(127, 31, 'Albert', 2025, 'december', '800.00', 2, '2026-01-26 10:13:17', 'Cash'),
(128, 30, 'Melvin Liguez', 2025, 'november', '800.00', 2, '2026-01-26 10:13:28', 'Cash'),
(129, 6, 'Jayson Proponio', 2026, 'january', '800.00', 2, '2026-02-06 03:08:48', 'Cash'),
(130, 27, 'Ronalyn', 2026, 'january', '800.00', 2, '2026-02-06 03:09:09', 'Gcash'),
(131, 36, 'roselynsaygad', 2026, 'january', '800.00', 2, '2026-02-06 03:09:18', 'Gcash'),
(132, 35, 'Randy D. Mandabon', 2026, 'february', '800.00', 2, '2026-02-06 14:41:44', 'Gcash'),
(133, 23, 'Cloy', 2026, 'january', '800.00', 2, '2026-02-11 06:06:31', 'Cash'),
(134, 28, 'Angelica', 2026, 'february', '800.00', 2, '2026-02-11 07:48:47', 'Gcash'),
(135, 17, 'Princess Barbarona', 2026, 'january', '800.00', 2, '2026-02-15 13:55:38', 'Gcash'),
(136, 14, 'Quennie Barbarona', 2026, 'january', '800.00', 2, '2026-02-15 13:55:52', 'Gcash'),
(137, 24, 'Ken', 2026, 'january', '800.00', 2, '2026-02-16 01:58:34', 'Gcash'),
(138, 24, 'Ken', 2026, 'february', '400.00', 2, '2026-02-16 01:58:40', 'Gcash'),
(139, 30, 'Melvin Liguez', 2025, 'december', '800.00', 2, '2026-02-16 02:02:53', 'Gcash'),
(140, 12, 'Maylanie Duyag', 2026, 'february', '800.00', 2, '2026-02-18 00:44:28', 'Gcash'),
(141, 19, 'Kristine Grace Pudang', 2026, 'january', '800.00', 2, '2026-02-18 00:51:39', 'Gcash'),
(142, 21, 'Jenie  Rose Madenancil', 2026, 'january', '800.00', 2, '2026-02-18 00:51:54', 'Gcash'),
(144, 29, 'Daryl', 2025, 'december', '600.00', 2, '2026-02-27 11:42:49', 'Gcash'),
(145, 29, 'Daryl', 2026, 'january', '800.00', 2, '2026-02-27 11:42:59', 'Gcash'),
(146, 30, 'Melvin Liguez', 2026, 'january', '800.00', 2, '2026-02-28 10:46:08', 'Cash'),
(147, 31, 'Albert', 2026, 'january', '800.00', 2, '2026-03-06 07:24:24', 'Cash'),
(148, 6, 'Jayson Proponio', 2026, 'february', '800.00', 2, '2026-03-07 02:19:50', 'Gcash'),
(149, 35, 'Randy D. Mandabon', 2026, 'march', '800.00', 2, '2026-03-07 03:05:33', 'Gcash'),
(150, 35, 'Randy D. Mandabon', 2026, 'march', '800.00', 2, '2026-03-07 03:05:36', 'Gcash'),
(151, 28, 'Angelica', 2026, 'march', '800.00', 2, '2026-03-09 04:47:25', 'Gcash'),
(152, 23, 'Cloy', 2026, 'february', '1000.00', 2, '2026-03-11 10:53:40', 'Gcash'),
(153, 24, 'Ken', 2026, 'february', '800.00', 2, '2026-03-12 03:43:01', 'Cash'),
(154, 37, 'Joseph cloyd Denosta', 2026, 'february', '800.00', 2, '2026-03-12 05:51:13', 'Cash'),
(158, 6, 'Jayson Proponio', 2026, 'february', '800.00', 2, '2026-03-12 14:30:12', 'Cash'),
(171, 30, 'Melvin Liguez', 2026, 'february', '800.00', 2, '2026-03-14 02:51:14', 'Cash'),
(172, 21, 'Jenie  Rose Madenancil', 2026, 'february', '800.00', 2, '2026-03-14 03:35:05', 'Gcash');

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `month` enum('january','february','march','april','may','june','july','august','september','october','november','december') NOT NULL,
  `year` year(4) NOT NULL,
  `receipt_image` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `user_comment` text DEFAULT NULL,
  `admin_comment` text DEFAULT NULL,
  `user_deleted_at` timestamp NULL DEFAULT NULL,
  `admin_deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payment_receipts`
--

INSERT INTO `payment_receipts` (`id`, `user_id`, `month`, `year`, `receipt_image`, `amount`, `status`, `admin_comment`, `created_at`, `updated_at`) VALUES
(10, 35, 'january', 2026, 'receipt_35_january_2026_1768184910.jpg', '800.00', 'approved', 'Salamat', '2026-01-12 02:28:30', '2026-03-12 14:29:16'),
(14, 6, 'january', 2024, 'receipt_6_january_2024_1773328099.jpg', '1.00', 'rejected', 'HAHAHAHHA', '2026-03-12 15:08:19', '2026-03-12 15:08:34');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(50) NOT NULL,
  `max_capacity` int(11) DEFAULT 4,
  `current_occupancy` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `max_capacity`, `current_occupancy`, `created_at`, `updated_at`) VALUES
(4, '1', 4, 2, '2025-09-15 07:27:42', '2026-01-21 18:21:42'),
(5, '4', 4, 3, '2025-09-15 07:52:11', '2025-09-15 08:17:44'),
(6, '3', 4, 2, '2025-09-15 08:29:33', '2025-11-18 23:38:50'),
(7, '2', 4, 3, '2025-09-16 00:50:25', '2025-09-16 00:50:58'),
(8, '5', 4, 3, '2025-09-16 00:51:20', '2025-11-18 23:46:21'),
(9, '6', 4, 2, '2025-09-16 00:52:13', '2025-09-16 00:52:32'),
(10, '7', 4, 2, '2025-09-16 00:52:53', '2025-11-18 23:45:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','deactivated') DEFAULT 'active',
  `room_number` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  `seen_payments_at` timestamp NULL DEFAULT NULL,
  `seen_receipts_at` timestamp NULL DEFAULT NULL,
  `seen_announcements_at` timestamp NULL DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `gender`, `password`, `status`, `room_number`, `profile_picture`, `created_at`, `updated_at`, `email`, `seen_payments_at`, `seen_receipts_at`, `seen_announcements_at`) VALUES
(10, 'Mel John Empuesto', 'Male', '$2y$10$jHzlAsUMe0dE8AOBR8a8/Ov2wggHzXNfVfHm3OsZasm7kg0zOeAhK', 'deactivated', '1', NULL, '2025-09-15 07:37:55', '2026-01-21 18:21:42', 'meljohnempuesto@gmail.com', NULL, NULL, NULL),
(23, 'Cloy', 'Male', '$2y$10$/yJ7ZFm6PkoeMa0ExEZoLOb.WZXOGuKKuk1lpmy0iWSPPAXN0WuXS', 'active', '2', NULL, '2025-09-16 00:50:25', '2026-03-13 01:24:03', 'cloy@ruin.borders', NULL, NULL, NULL),
(9, 'Christian Peralta', 'Male', '$2y$10$aNryY0idyBEQrWaXAa0sUOgSxr0BEJnmZP.PIGDBvr0PmNS8pLAwW', 'deactivated', '1', NULL, '2025-09-15 07:37:40', '2026-01-21 18:18:49', 'christianperalta@gmail.com', NULL, NULL, NULL),
(6, 'Jayson Proponio', 'Male', '$2y$10$1fG.2.dqB6LMZCkvOWunx.rSrle.ODvd2tKbkrsJLCXwRsY2E17ze', 'active', '1', 'profile_6_1758070512.jpg', '2025-09-15 07:27:42', '2026-03-14 06:10:50', 'jayson@ruin.borders', '2026-03-14 06:10:41', '2026-03-14 06:10:50', '2026-03-14 06:09:54'),
(12, 'Maylanie Duyag', 'Female', '$2y$10$NvT3TGmYUt72ghHEOl8LSeIng7Mitf4UdiqSXtAGB7EIaUdcUyheG', 'active', '4', 'profile_12_1758029935.jpg', '2025-09-15 07:52:11', '2026-03-14 06:00:57', 'maylanie@ruin.borders', NULL, NULL, '2026-03-14 06:00:57'),
(14, 'Quennie Barbarona', 'Female', '$2y$10$WccmT11VvRslXbj3yaLccu81XdLYgPBSZUvtIkcPA5JtDSzw6bUma', 'active', '4', NULL, '2025-09-15 08:16:17', '2026-03-13 01:28:35', 'quennie@ruin.borders', NULL, NULL, NULL),
(17, 'Princess Barbarona', 'Female', '$2y$10$zMzc7IU/7wK/yFwQmi.s8.3xz7GP3I7v4i9jrKMcJ0jvmpmSt5nua', 'active', '4', NULL, '2025-09-15 08:17:10', '2026-03-13 01:27:52', 'princess@ruin.borders', NULL, NULL, NULL),
(19, 'Kristine Grace Pudang', 'Female', '$2y$10$H8Bnkoa3SMEiuEpuuUD./uxy7iH0fLmMU1OeBdCguqsoJYxG2reHa', 'active', '3', NULL, '2025-09-15 08:29:33', '2025-11-01 10:17:54', 'kristinegracepudang@gmail.com', NULL, NULL, NULL),
(21, 'Jenie  Rose Madenancil', 'Female', '$2y$10$AcVdWM9FBiyliK3SkoORwu4ListyRG8MlpzDYnZZSCCIuRlPCJR2y', 'active', '3', NULL, '2025-09-15 08:30:52', '2026-03-13 01:25:39', 'jenierose@ruin.borders', NULL, NULL, NULL),
(24, 'Ken', 'Male', '$2y$10$K18rBiL1tzbLwr1q7QW8u.BPBn7CpCME4h2o7HfIPMDiahSajzlrC', 'active', '2', NULL, '2025-09-16 00:50:42', '2025-11-03 11:08:57', 'jericomadenancil@gmail.com', NULL, NULL, NULL),
(25, 'John', 'Male', '$2y$10$Je71/10nS7AzOb1K6T6T6uzvGA2x6yD3qem/VjDuoudL4DO1ojMlO', 'active', '2', NULL, '2025-09-16 00:50:58', '2026-03-13 01:22:00', 'john@ruin.borders', NULL, NULL, NULL),
(37, 'Joseph cloyd Denosta', 'Other', '$2y$10$eXlVCMg4kFH98ROIHnni.upMjAPrNfUIYoA/yzggvnotGhsErOOw2', 'active', '1', NULL, '2026-01-11 04:31:04', '2026-03-13 01:36:43', 'joseph@ruin.borders', NULL, NULL, NULL),
(27, 'Ronalyn Saygad', 'Female', '$2y$10$NcKMyc9pBScPu5owZ9xPPOwYpa9wG4h0knJbbuRTZYPYCnc0YPcpC', 'active', '5', NULL, '2025-09-16 00:51:37', '2026-03-13 01:33:40', 'ronalyn@ruin.borders', NULL, NULL, NULL),
(28, 'Angelica', 'Female', '$2y$10$0FMGFlOyK1IVEw47QLINcO5DRcfBXi1LYibulpYgXtwxe2AazoLxe', 'active', '5', NULL, '2025-09-16 00:51:58', '2026-03-13 01:32:42', 'angelica@ruin.borders', NULL, NULL, NULL),
(29, 'Daryl', 'Male', '$2y$10$ZtCYiniuNzU.yIDnAxlap.ElBzAvFN94TqX7954mSz/2PHMso4V3C', 'active', '6', NULL, '2025-09-16 00:52:13', '2026-03-13 01:35:14', 'daryl@ruin.borders', NULL, NULL, NULL),
(30, 'Melvin Liguez', 'Male', '$2y$10$v/0xpmJf7zt5ZjdAQ4rXgudtv8ERRomAh9QHlTqGi2mS62gjMKk1G', 'active', '6', NULL, '2025-09-16 00:52:32', '2026-03-13 01:34:23', 'melvin@ruin.borders', NULL, NULL, NULL),
(31, 'Albert', 'Male', '$2y$10$sffD9d.fcPfAabXUHOxXD.UYgrrqYI4tSXj3z1xdJpmeCRoMB2bEC', 'active', '7', NULL, '2025-09-16 00:52:53', '2026-03-13 01:35:53', 'albert@ruin.borders', NULL, NULL, NULL),
(35, 'Randy D. Mandabon', 'Other', '$2y$10$9Sl95pzyGdzn7NJz1OkYH.RjY6unEiBXSn1TUvXWk6gvnswxta2UW', 'active', '7', NULL, '2025-11-18 12:54:21', '2026-03-13 02:09:45', 'randy@ruin.borders', '2026-03-13 02:09:45', NULL, NULL),
(36, 'Roselyn Saygad', 'Other', '$2y$10$VDYlDvFRDnKR7zOaJbAaG.X9InmK6UlpQjkOYXSgoIIn6EhJ8.8LW', 'active', '5', NULL, '2025-11-18 23:24:16', '2026-03-13 01:31:59', 'roselyn@ruin.borders', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_announcements_admin` (`admin_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_year` (`user_id`,`year`),
  ADD KEY `idx_payments_user_year` (`user_id`,`year`);

--
-- Indexes for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_year_month` (`year`,`month`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_receipts_user` (`user_id`),
  ADD KEY `idx_payment_receipts_status` (`status`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_room_number` (`room_number`),
  ADD KEY `idx_users_room_number` (`room_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=367;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `payment_history`
--
ALTER TABLE `payment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
