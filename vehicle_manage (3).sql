-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 31, 2026 at 11:51 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.5.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vehicle_manage`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'Admin Login', 'Administrator logged into the system', '127.0.0.1', '2026-05-18 07:10:23'),
(2, NULL, 'Agent Login', 'Agent speedyagency logged into the system', '127.0.0.1', '2026-05-18 07:10:23'),
(3, NULL, 'Add Customer', 'Customer Rohan Sharma added by Agent 1', '127.0.0.1', '2026-05-18 07:10:23'),
(4, NULL, 'Add Vehicle', 'Vehicle MH02AB1234 added for Rohan Sharma', '127.0.0.1', '2026-05-18 07:10:23'),
(5, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent1', '::1', '2026-05-18 07:16:40'),
(6, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent1', '::1', '2026-05-18 07:17:12'),
(7, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent1', '::1', '2026-05-18 07:17:39'),
(8, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent1', '::1', '2026-05-18 07:19:45'),
(9, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '::1', '2026-05-18 07:25:58'),
(10, NULL, 'WhatsApp Reminder Sent', 'Sent Insurance renewal notification to Priya Patel for vehicle MH01XY9876 (Status: sent)', '::1', '2026-05-18 07:26:19'),
(11, NULL, 'Update Customer', 'Updated customer details: Priya Patel', '::1', '2026-05-18 07:27:34'),
(12, NULL, 'WhatsApp Reminder Sent', 'Sent Insurance renewal notification to Priya Patel for vehicle MH01XY9876 (Status: sent)', '::1', '2026-05-18 07:28:00'),
(13, NULL, 'Update Customer', 'Updated customer details: Priya Patel', '::1', '2026-05-18 07:34:00'),
(14, NULL, 'WhatsApp Reminder Sent', 'Sent Insurance renewal notification to Priya Patel for vehicle MH01XY9876 (Status: sent)', '::1', '2026-05-18 07:34:13'),
(15, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: admin', '::1', '2026-05-18 08:19:49'),
(16, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-18 08:20:21'),
(17, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-18 09:06:25'),
(18, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent', '::1', '2026-05-18 09:06:42'),
(19, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '::1', '2026-05-18 09:06:56'),
(20, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-18 10:20:19'),
(21, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: admin', '::1', '2026-05-18 10:25:44'),
(22, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-18 10:26:09'),
(23, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-18 10:32:14'),
(24, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '::1', '2026-05-18 10:32:48'),
(25, NULL, 'WhatsApp Reminder Sent', 'Sent Insurance renewal notification to Priya Patel for vehicle MH01XY9876 (Status: sent)', '::1', '2026-05-18 10:33:23'),
(26, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-18 11:39:50'),
(27, 1, 'Admin Login', 'Super Admin logged in successfully.', '127.0.0.1', '2026-05-19 05:14:38'),
(28, 1, 'Update Agent', 'Updated agent details: agent1 (Shop: Speedy RTO Consultancy)', '127.0.0.1', '2026-05-19 05:15:38'),
(29, 1, 'Admin Logout', 'Super Admin logged out successfully.', '127.0.0.1', '2026-05-19 05:15:55'),
(30, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '127.0.0.1', '2026-05-19 05:16:16'),
(31, NULL, 'Create Health Policy', 'Created Health Policy: HLT-99998888', '127.0.0.1', '2026-05-19 05:18:30'),
(32, NULL, 'Update Health Policy', 'Updated Health Policy: HLT-99998888', '127.0.0.1', '2026-05-19 05:19:21'),
(33, NULL, 'Agent Logout', 'Agent logged out successfully.', '127.0.0.1', '2026-05-19 05:22:21'),
(34, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '127.0.0.1', '2026-05-19 05:22:46'),
(35, NULL, 'Agent Logout', 'Agent logged out successfully.', '127.0.0.1', '2026-05-19 05:23:37'),
(36, 1, 'Admin Login', 'Super Admin logged in successfully.', '127.0.0.1', '2026-05-19 05:24:09'),
(37, 1, 'Create Agent', 'Created new agent: testagent (Shop: Test Agency Shop)', '127.0.0.1', '2026-05-19 05:31:53'),
(38, 1, 'Update Agent', 'Updated agent details: agent1 (Shop: Speedy RTO Consultancy)', '127.0.0.1', '2026-05-19 05:36:28'),
(39, 1, 'Update Agent', 'Updated agent details: agent2 (Shop: Elite Auto Solutions)', '127.0.0.1', '2026-05-19 05:46:29'),
(40, 1, 'Update Agent', 'Updated agent details: agent2 (Shop: Elite Auto Solutions)', '127.0.0.1', '2026-05-19 05:48:21'),
(41, 1, 'Admin Logout', 'Super Admin logged out successfully.', '127.0.0.1', '2026-05-19 05:52:22'),
(42, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '127.0.0.1', '2026-05-19 05:52:25'),
(43, NULL, 'Agent Logout', 'Agent logged out successfully.', '127.0.0.1', '2026-05-19 06:08:45'),
(44, 1, 'Admin Login', 'Super Admin logged in successfully.', '127.0.0.1', '2026-05-19 06:08:57'),
(45, 1, 'Update Settings', 'Global system and WhatsApp API configurations updated.', '127.0.0.1', '2026-05-19 06:14:57'),
(46, 1, 'Recharge Agent Messages', 'Recharged agent2 with Rs 100 for 500 messages at Rs 0.2 each.', '127.0.0.1', '2026-05-19 06:33:03'),
(47, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-19 06:34:53'),
(48, 1, 'Update Agent', 'Updated agent details: agent2 (Shop: Elite Auto Solutions)', '127.0.0.1', '2026-05-19 06:36:02'),
(49, NULL, 'Update Vehicle', 'Updated vehicle: KA03MZ5555', '::1', '2026-05-19 06:36:29'),
(50, NULL, 'Save Pollution', 'Added/Updated pollution certificate: PUC-5544332', '::1', '2026-05-19 06:36:29'),
(51, NULL, 'Update Vehicle', 'Updated vehicle: KA03MZ5555', '::1', '2026-05-19 06:36:52'),
(52, NULL, 'Save Pollution', 'Added/Updated pollution certificate: PUC-5544332', '::1', '2026-05-19 06:36:52'),
(53, NULL, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Anil Kumar (Status: sent)', '::1', '2026-05-19 06:36:59'),
(54, NULL, 'Update Vehicle', 'Updated vehicle: KA03MZ5555', '::1', '2026-05-19 06:46:46'),
(55, NULL, 'Save Pollution', 'Added/Updated pollution certificate: PUC-5544332', '::1', '2026-05-19 06:46:46'),
(56, NULL, 'Update Shop Details', 'Agent updated shop profile: Elite Auto Solutions', '::1', '2026-05-19 06:54:11'),
(57, NULL, 'Update Shop Details', 'Agent updated shop profile: Elite Auto Solutions', '::1', '2026-05-19 06:58:13'),
(58, 1, 'Admin Logout', 'Super Admin logged out successfully.', '127.0.0.1', '2026-05-19 09:39:47'),
(59, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '127.0.0.1', '2026-05-19 09:39:52'),
(61, NULL, 'Create Customer', 'Created customer on-the-fly: \' (ID: 5)', '127.0.0.1', '2026-05-19 10:53:39'),
(62, NULL, 'Create Vehicle', 'Created vehicle record: KL17N1515', '127.0.0.1', '2026-05-19 10:53:39'),
(63, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-19 11:02:24'),
(64, 1, 'Update Agent', 'Updated agent details: King\'s Auto \"Garage\" (Shop: Test Agency Shop)', '::1', '2026-05-19 11:02:56'),
(65, 1, 'Update Agent', 'Updated agent details: King\'s Auto \"Garage\" (Shop: King\'s Auto \"Garage\")', '::1', '2026-05-19 11:03:18'),
(66, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-19 11:38:01'),
(67, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-19 11:38:15'),
(68, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-21 05:30:22'),
(69, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-21 06:54:33'),
(70, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-21 06:54:42'),
(71, 1, 'Update Settings', 'Global system and WhatsApp API configurations updated.', '::1', '2026-05-21 06:57:10'),
(72, 1, 'Recharge Agent Messages', 'Recharged King\'s Auto \"Garage\" with Rs 1 for 4 messages at Rs 0.25 each.', '::1', '2026-05-21 06:57:36'),
(73, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-21 07:00:53'),
(74, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-21 07:01:08'),
(75, NULL, 'Update Vehicle', 'Updated vehicle: KL01CD5678', '::1', '2026-05-21 07:04:05'),
(76, NULL, 'Save Pollution', 'Added/Updated pollution certificate: INS20260002', '::1', '2026-05-21 07:04:05'),
(77, NULL, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Rahul Nair (Status: sent)', '::1', '2026-05-21 07:04:21'),
(78, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-21 07:13:57'),
(79, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-21 07:14:04'),
(80, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-21 07:40:24'),
(81, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-21 07:40:40'),
(82, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-21 08:17:42'),
(83, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-21 08:45:25'),
(84, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-21 11:52:08'),
(85, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-21 11:58:52'),
(86, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-21 11:59:23'),
(87, 1, 'Update Settings', 'Global system and WhatsApp API configurations updated.', '::1', '2026-05-21 12:00:37'),
(88, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-21 12:00:45'),
(89, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-21 12:00:52'),
(90, NULL, 'Update Customer', 'Updated customer details: Rahul Nair', '::1', '2026-05-21 12:03:04'),
(91, NULL, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Rahul Nair (Status: failed)', '::1', '2026-05-21 12:04:22'),
(92, NULL, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Rahul Nair (Status: failed)', '::1', '2026-05-21 12:04:33'),
(93, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-21 12:05:48'),
(94, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-21 12:05:55'),
(95, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-21 12:12:17'),
(96, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-21 12:12:26'),
(97, NULL, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Rahul Nair (Status: sent)', '::1', '2026-05-21 12:12:44'),
(98, NULL, 'Update Vehicle', 'Updated vehicle: KL03BB3344', '::1', '2026-05-21 12:14:21'),
(99, NULL, 'Save Pollution', 'Added/Updated pollution certificate: INS20260014', '::1', '2026-05-21 12:14:21'),
(100, NULL, 'Update Customer', 'Updated customer details: Athira Babu', '::1', '2026-05-21 12:15:15'),
(101, NULL, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Athira Babu (Status: sent)', '::1', '2026-05-21 12:15:28'),
(102, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-22 04:49:41'),
(103, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-22 08:33:53'),
(104, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-22 08:40:31'),
(105, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-22 08:40:38'),
(106, 1, 'Delete Agent', 'Deleted Agent: agent1 (Shop: Speedy RTO Consultancy)', '::1', '2026-05-22 08:41:18'),
(107, 1, 'Delete Agent', 'Deleted Agent: King\'s Auto \"Garage\" (Shop: King\'s Auto \"Garage\")', '::1', '2026-05-22 08:41:29'),
(108, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-22 08:41:40'),
(109, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-22 08:41:47'),
(110, NULL, 'Update Customer', 'Updated customer details: Akhil George', '::1', '2026-05-22 08:43:30'),
(111, NULL, 'Update Vehicle', 'Updated vehicle: KL08GH3456', '::1', '2026-05-22 08:44:19'),
(112, NULL, 'Update Vehicle', 'Updated vehicle: KL08GH3456', '::1', '2026-05-22 08:46:30'),
(113, NULL, 'Save Pollution', 'Added/Updated pollution certificate: puc-2758234723', '::1', '2026-05-22 08:46:30'),
(114, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-22 09:21:06'),
(115, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-22 09:29:19'),
(116, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-22 09:46:50'),
(117, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-07-10 10:38:19'),
(118, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-07-29 05:55:46'),
(119, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-07-30 05:14:02'),
(120, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-07-30 05:14:27'),
(121, NULL, 'Unified Login Failed', 'Failed login attempt for username: agent1', '::1', '2026-07-30 05:14:34'),
(122, NULL, 'Unified Login Failed', 'Failed login attempt for username: agent', '::1', '2026-07-30 05:14:50'),
(123, NULL, 'Unified Login Failed', 'Failed login attempt for username: agent1', '::1', '2026-07-30 05:15:07'),
(124, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-07-30 05:16:04'),
(125, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-07-30 05:16:58'),
(126, NULL, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-07-30 05:17:07'),
(127, NULL, 'Update Vehicle', 'Updated vehicle: KL01CD5678', '::1', '2026-07-30 05:41:09'),
(128, NULL, 'Save Pollution', 'Added/Updated pollution certificate: PUC2026002', '::1', '2026-07-30 05:41:09'),
(129, NULL, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Akhil George (Status: sent)', '::1', '2026-07-30 05:41:23'),
(130, NULL, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Akhil George (Status: sent)', '::1', '2026-07-30 05:42:41'),
(131, NULL, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Akhil George (Status: sent)', '::1', '2026-07-30 05:44:54'),
(132, NULL, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-07-30 05:52:18'),
(133, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-07-30 05:53:11'),
(134, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-07-30 07:27:52'),
(135, NULL, 'Shop Center Login', 'Shop Center logged in: Finez store (Parent Agent ID: 3)', '::1', '2026-07-30 07:28:02'),
(136, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-07-30 09:33:48'),
(137, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-07-30 09:33:54'),
(138, NULL, 'Recharge Messages', 'Recharged Finez store with ₹500 (2000 messages)', 'Unknown', '2026-07-30 09:44:05'),
(139, NULL, 'Recharge Messages', 'Recharged Finez store with 1500 messages (₹375)', 'Unknown', '2026-07-30 10:32:17'),
(140, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-07-30 11:27:26'),
(141, NULL, 'Shop Center Login', 'Shop Center logged in: Finez store (Parent Agent ID: 3)', '::1', '2026-07-30 11:27:39'),
(142, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-07-31 04:46:41'),
(143, 1, 'Create Agent', 'Created new agent: agent (Shop: Finez store)', '::1', '2026-07-31 05:04:18'),
(144, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-07-31 05:11:57'),
(145, 10, 'Shop Center Login', 'Shop Center logged in: Finez pollution Angamaly (Parent Agent ID: 9)', '::1', '2026-07-31 05:12:14'),
(146, 10, 'Create Vehicle', 'Created vehicle record: KL17N1515', '::1', '2026-07-31 05:20:55'),
(147, 10, 'Delete Vehicle', 'Deleted vehicle record: TEST5343 (ID: 6)', '::1', '2026-07-31 05:21:07'),
(148, 10, 'Update Vehicle', 'Updated existing agency vehicle record: KL17N1515 (ID: 7)', '::1', '2026-07-31 05:29:05'),
(149, 10, 'Delete Vehicle', 'Deleted vehicle record: TEST3386 (ID: 9)', '::1', '2026-07-31 05:29:17'),
(150, 10, 'Delete Vehicle', 'Deleted vehicle record: TEST7307 (ID: 8)', '::1', '2026-07-31 05:29:25'),
(151, 10, 'Update Vehicle', 'Updated existing agency vehicle record: KL17N1515 (ID: 7)', '::1', '2026-07-31 05:30:06'),
(152, 10, 'Update Vehicle', 'Updated existing agency vehicle record: KL17N1515 (ID: 7)', '::1', '2026-07-31 05:34:23'),
(153, 10, 'Update Vehicle', 'Updated existing agency vehicle record: KL17N1515 (ID: 7)', '::1', '2026-07-31 05:34:42'),
(154, 10, 'Update Vehicle', 'Updated existing agency vehicle record: KL17N1515 (ID: 7)', '::1', '2026-07-31 05:37:14'),
(155, 10, 'Save Pollution', 'Added/Updated pollution certificate: PUC-KL17N1515', '::1', '2026-07-31 05:37:14'),
(156, 10, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-07-31 05:39:09'),
(157, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-07-31 05:39:15'),
(158, 1, 'Recharge Messages', 'Recharged Finez pollution Angamaly with 100 messages (₹25)', '::1', '2026-07-31 05:42:28'),
(159, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-07-31 05:42:40'),
(160, 10, 'Shop Center Login', 'Shop Center logged in: Finez pollution Angamaly (Parent Agent ID: 9)', '::1', '2026-07-31 05:43:04'),
(161, 1, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Customer (Status: sent)', '::1', '2026-07-31 06:54:15'),
(162, 1, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Customer (Status: sent)', '::1', '2026-07-31 06:54:38'),
(163, 1, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Customer (Status: sent)', '::1', '2026-07-31 06:58:40'),
(164, 1, 'Update Vehicle', 'Updated existing agency vehicle record: KL17N1515 (ID: 7)', '::1', '2026-07-31 07:03:02'),
(165, 1, 'Save Pollution', 'Added/Updated pollution certificate: PUC-KL17N1515', '::1', '2026-07-31 07:03:02'),
(166, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-07-31 07:10:12'),
(167, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-07-31 07:10:18'),
(168, 1, 'Update Settings', 'Global system and WhatsApp API configurations updated.', '::1', '2026-07-31 07:10:36'),
(169, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-07-31 07:10:52'),
(170, 10, 'Shop Center Login', 'Shop Center logged in: Finez pollution Angamaly (Parent Agent ID: 9)', '::1', '2026-07-31 07:11:07'),
(171, 10, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Customer (Status: sent)', '::1', '2026-07-31 07:11:16'),
(172, 10, 'Update Vehicle', 'Updated existing agency vehicle record: KL17N1515 (ID: 7)', '::1', '2026-07-31 07:12:34'),
(173, 10, 'Save Pollution', 'Added/Updated pollution certificate: PUC-KL17N1515', '::1', '2026-07-31 07:12:34'),
(174, 1, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Customer (Status: sent)', '::1', '2026-07-31 07:14:54'),
(175, 1, 'Update Vehicle', 'Updated existing agency vehicle record: KL17N1515 (ID: 7)', '::1', '2026-07-31 08:40:53'),
(176, 1, 'Save Pollution', 'Added/Updated pollution certificate: PUC-KL17N1515', '::1', '2026-07-31 08:40:53'),
(177, 1, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Customer (Status: failed)', '::1', '2026-07-31 08:41:11'),
(178, 10, 'Shop Center Login', 'Shop Center logged in: Finez pollution Angamaly (Parent Agent ID: 9)', '::1', '2026-07-31 08:49:10'),
(179, 10, 'Update Vehicle', 'Updated existing agency vehicle record: KL17N1515 (ID: 7)', '::1', '2026-07-31 08:50:30'),
(180, 10, 'Save Pollution', 'Added/Updated pollution certificate: PUC-KL17N1515', '::1', '2026-07-31 08:50:30'),
(181, 10, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Customer (Status: sent)', '::1', '2026-07-31 08:50:46');

-- --------------------------------------------------------

--
-- Table structure for table `agent_message_recharges`
--

CREATE TABLE `agent_message_recharges` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `recharge_amount` decimal(10,2) NOT NULL,
  `message_unit_price` decimal(10,2) NOT NULL,
  `messages_credited` int(11) NOT NULL DEFAULT 0,
  `recharged_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `agent_message_recharges`
--

INSERT INTO `agent_message_recharges` (`id`, `agent_id`, `recharge_amount`, `message_unit_price`, `messages_credited`, `recharged_by`, `created_at`) VALUES
(5, 10, '25.00', '0.25', 100, 1, '2026-07-31 05:42:28');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `created_by_shop_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `whatsapp_number` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `agent_id`, `created_by_shop_id`, `name`, `mobile_number`, `whatsapp_number`, `created_at`, `updated_at`) VALUES
(6, 9, 10, 'Test Customer', '9999940322', '9999973848', '2026-07-31 05:19:40', '2026-07-31 05:19:40'),
(7, 9, 10, 'Customer', '6238426929', '6238426929', '2026-07-31 05:20:55', '2026-07-31 05:20:55'),
(8, 9, 10, 'Test Customer', '9999946378', '9999960290', '2026-07-31 05:22:32', '2026-07-31 05:22:32'),
(9, 9, 10, 'Test Customer', '9999910624', '9999996714', '2026-07-31 05:28:33', '2026-07-31 05:28:33'),
(10, 9, 10, 'Test Customer', '9999947849', '9999927954', '2026-07-31 05:32:35', '2026-07-31 05:32:35'),
(11, 9, 10, 'Test Customer', '9999967001', '9999994605', '2026-07-31 05:34:00', '2026-07-31 05:34:00'),
(12, 9, 10, 'Test Customer', '9999953278', '9999997030', '2026-07-31 05:35:52', '2026-07-31 05:35:52'),
(13, 9, 1, 'Customer', '9745170101', '9745170101', '2026-07-31 07:03:02', '2026-07-31 07:03:02');

-- --------------------------------------------------------

--
-- Table structure for table `health_insurances`
--

CREATE TABLE `health_insurances` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `created_by_shop_id` int(11) DEFAULT NULL,
  `insurance_company_id` int(11) NOT NULL,
  `policy_number` varchar(100) NOT NULL,
  `policy_name` varchar(255) NOT NULL,
  `insured_persons` text NOT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `premium_amount` decimal(10,2) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurances`
--

CREATE TABLE `insurances` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `created_by_shop_id` int(11) DEFAULT NULL,
  `insurance_company_id` int(11) NOT NULL,
  `policy_number` varchar(100) NOT NULL,
  `insurance_type` enum('Comprehensive','Third Party','Own Damage','Zero Dep') NOT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `premium_amount` decimal(10,2) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `insurances`
--

INSERT INTO `insurances` (`id`, `vehicle_id`, `agent_id`, `created_by_shop_id`, `insurance_company_id`, `policy_number`, `insurance_type`, `start_date`, `expiry_date`, `premium_amount`, `document_path`, `created_at`, `updated_at`) VALUES
(6, 10, 9, 10, 1, 'POL7434', 'Comprehensive', '2026-07-31', '2027-07-31', '1500.00', NULL, '2026-07-31 05:32:35', '2026-07-31 05:32:35'),
(7, 11, 9, 10, 1, 'POL8279', 'Comprehensive', '2026-07-31', '2027-07-31', '1500.00', NULL, '2026-07-31 05:34:00', '2026-07-31 05:34:00'),
(8, 12, 9, 10, 1, 'POL2319', 'Comprehensive', '2026-07-31', '2027-07-31', '1500.00', NULL, '2026-07-31 05:35:52', '2026-07-31 05:35:52');

-- --------------------------------------------------------

--
-- Table structure for table `insurance_companies`
--

CREATE TABLE `insurance_companies` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `insurance_companies`
--

INSERT INTO `insurance_companies` (`id`, `name`, `status`, `created_at`) VALUES
(1, 'HDFC ERGO General Insurance', 'active', '2026-05-18 07:10:23'),
(2, 'ICICI Lombard General Insurance', 'active', '2026-05-18 07:10:23'),
(3, 'Tata AIG General Insurance', 'active', '2026-05-18 07:10:23'),
(4, 'Bajaj Allianz General Insurance', 'active', '2026-05-18 07:10:23'),
(5, 'New India Assurance', 'active', '2026-05-18 07:10:23'),
(6, 'Star Health & Allied Insurance', 'active', '2026-05-18 07:10:23'),
(7, 'United India Insurance', 'active', '2026-05-18 07:10:23');

-- --------------------------------------------------------

--
-- Table structure for table `pollution_certificates`
--

CREATE TABLE `pollution_certificates` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `created_by_shop_id` int(11) DEFAULT NULL,
  `certificate_number` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pollution_certificates`
--

INSERT INTO `pollution_certificates` (`id`, `vehicle_id`, `agent_id`, `created_by_shop_id`, `certificate_number`, `start_date`, `expiry_date`, `document_path`, `created_at`, `updated_at`) VALUES
(1, 7, 9, 10, 'PUC-KL17N1515', '2026-02-01', '2026-08-01', NULL, '2026-07-31 05:37:14', '2026-07-31 05:37:14');

-- --------------------------------------------------------

--
-- Table structure for table `reminder_history`
--

CREATE TABLE `reminder_history` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `reminder_type` enum('Insurance','Pollution','RC','Health') NOT NULL,
  `reminder_period` varchar(20) NOT NULL,
  `sent_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_by_user_id` int(11) NOT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `message` text NOT NULL,
  `api_response` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reminder_history`
--

INSERT INTO `reminder_history` (`id`, `customer_id`, `vehicle_id`, `reminder_type`, `reminder_period`, `sent_date`, `sent_by_user_id`, `status`, `message`, `api_response`) VALUES
(7, 7, 7, 'Pollution', '1 Day', '2026-07-31 08:50:46', 9, 'sent', 'Dear Customer,\n\nThis is to remind you that the Pollution Certificate (PUC) for your vehicle KL17N1515 is expiring on 2026-08-01.\n\nPlease visit our testing center at our testing center (Phone: 6238426929) to renew it and avoid penalties.\n\nView Testing Centers & Directions:\nhttp://localhost/vehicle_manage/centers.php?agent_id=9\n\nThank You.', '{\"messaging_product\":\"whatsapp\",\"contacts\":[{\"input\":\"916238426929\",\"wa_id\":\"916238426929\"}],\"messages\":[{\"id\":\"wamid.HBgMOTE2MjM4NDI2OTI5FQIAERgSRkI1RDgwQzJEQTBBRjYxODhFAA==\",\"message_status\":\"accepted\"}]}');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `system_name` varchar(255) NOT NULL DEFAULT 'Vehicle Details & Insurance Renewal Management System',
  `contact_email` varchar(255) DEFAULT 'support@vehicledetails.com',
  `contact_phone` varchar(20) DEFAULT '+1234567890',
  `currency` varchar(10) DEFAULT 'INR',
  `single_message_price` decimal(10,2) NOT NULL DEFAULT 1.00,
  `whatsapp_api_url` varchar(255) DEFAULT 'https://graph.facebook.com/v17.0',
  `whatsapp_phone_number_id` varchar(100) DEFAULT '',
  `whatsapp_access_token` text DEFAULT NULL,
  `whatsapp_template_name` varchar(100) DEFAULT 'insurance_renewal_alert',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `system_name`, `contact_email`, `contact_phone`, `currency`, `single_message_price`, `whatsapp_api_url`, `whatsapp_phone_number_id`, `whatsapp_access_token`, `whatsapp_template_name`, `created_at`, `updated_at`) VALUES
(1, 'Vehicle Details & Insurance Renewal Management System', 'admin@vehicleinsurance.com', '+919876543210', 'INR', '0.25', 'https://graph.facebook.com/v17.0', '1201793359690573', 'EAAVXZCA0n0WIBSGTzawG7n3jIXIBZCDjz37zXNVhdhN3AuTujUnWweyZBVocz1O0NKIf4wvbygZCIXgoMO1vE1T516aMynUhYm6mEZBZCTb56CmsCu4S2eHORfqtifGGBvEmgxyc52E19k9SdjP1iOQh8WYsN4CVKfcZBIVMP4n5jaqTkAccnZAef5RVdDa97Eg0gQZDZD', 'alert_pollution', '2026-05-18 07:10:23', '2026-07-31 08:45:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','agent','shop') NOT NULL DEFAULT 'agent',
  `parent_agent_id` int(11) DEFAULT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `shop_name` varchar(255) DEFAULT NULL,
  `shop_owner_name` varchar(255) DEFAULT NULL,
  `shop_logo` varchar(255) DEFAULT NULL,
  `shop_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `access_health_insurance` tinyint(1) NOT NULL DEFAULT 1,
  `access_vehicle_insurance` tinyint(1) NOT NULL DEFAULT 1,
  `access_pollution` tinyint(1) NOT NULL DEFAULT 1,
  `message_balance` int(11) NOT NULL DEFAULT 0,
  `notify_days_before` int(11) DEFAULT 7,
  `notify_before_enabled` tinyint(1) DEFAULT 1,
  `notify_1day_before_enabled` tinyint(1) DEFAULT 1,
  `notify_after_expiry_enabled` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `parent_agent_id`, `status`, `shop_name`, `shop_owner_name`, `shop_logo`, `shop_address`, `city`, `state`, `pincode`, `mobile_number`, `whatsapp_number`, `created_at`, `updated_at`, `expiry_date`, `access_health_insurance`, `access_vehicle_insurance`, `access_pollution`, `message_balance`, `notify_days_before`, `notify_before_enabled`, `notify_1day_before_enabled`, `notify_after_expiry_enabled`) VALUES
(1, 'admin', '$2y$12$XfH1qrJD6wrSbnZiT3F0gu59TJEbfwH0AP3LFLatB.Gy6EAyY1cgW', 'admin@vehicleinsurance.com', 'admin', NULL, 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-18 07:10:23', '2026-05-18 07:24:58', NULL, 1, 1, 1, 0, 7, 1, 1, 1),
(9, 'agent', '$2y$12$yQ.IIHHCHzzcxlK/7HEclu/aouS6iEaYwNZonSfVf2IThSlMzZ8Xm', 'harikrishnamanoj79@gmail.com', 'agent', NULL, 'active', 'Finez store', 'freddy', 'logo_default.png', '', '', '', '', '6238426929', '', '2026-07-31 05:04:18', '2026-07-31 08:47:05', '2099-12-31', 0, 0, 1, 496, 7, 1, 1, 1),
(10, 'shop@angamaly', '$2y$12$3zJdsAJkYV7xSq8i7vU17OLHkYxBFtmT/P5fFeGkK0MFAtjLlki1O', 'harikrishnamanoj80@gmail.com', 'shop', 9, 'active', 'Finez pollution Angamaly', 'Freddy', NULL, '', 'kochi', 'Kerala', '686662', '6238426929', '', '2026-07-31 05:11:45', '2026-07-31 08:50:46', '2027-07-31', 1, 1, 1, 498, 7, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `created_by_shop_id` int(11) DEFAULT NULL,
  `vehicle_number` varchar(30) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `customer_id`, `agent_id`, `created_by_shop_id`, `vehicle_number`, `created_at`, `updated_at`) VALUES
(7, 7, 9, 10, 'KL17N1515', '2026-07-31 05:20:55', '2026-07-31 08:50:30'),
(10, 10, 9, 10, 'TEST1972', '2026-07-31 05:32:35', '2026-07-31 05:32:35'),
(11, 11, 9, 10, 'TEST2668', '2026-07-31 05:34:00', '2026-07-31 05:34:00'),
(12, 12, 9, 10, 'TEST2554', '2026-07-31 05:35:52', '2026-07-31 05:35:52');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_types`
--

CREATE TABLE `vehicle_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicle_types`
--

INSERT INTO `vehicle_types` (`id`, `name`, `status`) VALUES
(1, 'Two Wheeler (Motorcycle / Scooter)', 'active'),
(2, 'Four Wheeler (Car / SUV / Sedan)', 'active'),
(3, 'Commercial Vehicle (Auto / Taxi / Bus)', 'active'),
(4, 'Heavy Goods Vehicle (Truck / Lorry)', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `agent_message_recharges`
--
ALTER TABLE `agent_message_recharges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `recharged_by` (`recharged_by`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `fk_customers_shop` (`created_by_shop_id`);

--
-- Indexes for table `health_insurances`
--
ALTER TABLE `health_insurances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `policy_number` (`policy_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `insurance_company_id` (`insurance_company_id`);

--
-- Indexes for table `insurances`
--
ALTER TABLE `insurances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `policy_number` (`policy_number`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `insurance_company_id` (`insurance_company_id`),
  ADD KEY `fk_insurances_shop` (`created_by_shop_id`);

--
-- Indexes for table `insurance_companies`
--
ALTER TABLE `insurance_companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `pollution_certificates`
--
ALTER TABLE `pollution_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `fk_pollution_shop` (`created_by_shop_id`);

--
-- Indexes for table `reminder_history`
--
ALTER TABLE `reminder_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `sent_by_user_id` (`sent_by_user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_parent_agent` (`parent_agent_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_number` (`vehicle_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `fk_vehicles_shop` (`created_by_shop_id`);

--
-- Indexes for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=182;

--
-- AUTO_INCREMENT for table `agent_message_recharges`
--
ALTER TABLE `agent_message_recharges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `health_insurances`
--
ALTER TABLE `health_insurances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `insurances`
--
ALTER TABLE `insurances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `insurance_companies`
--
ALTER TABLE `insurance_companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pollution_certificates`
--
ALTER TABLE `pollution_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reminder_history`
--
ALTER TABLE `reminder_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `agent_message_recharges`
--
ALTER TABLE `agent_message_recharges`
  ADD CONSTRAINT `agent_message_recharges_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agent_message_recharges_ibfk_2` FOREIGN KEY (`recharged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_customers_shop` FOREIGN KEY (`created_by_shop_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `health_insurances`
--
ALTER TABLE `health_insurances`
  ADD CONSTRAINT `health_insurances_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `health_insurances_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `health_insurances_ibfk_3` FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies` (`id`);

--
-- Constraints for table `insurances`
--
ALTER TABLE `insurances`
  ADD CONSTRAINT `fk_insurances_shop` FOREIGN KEY (`created_by_shop_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `insurances_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `insurances_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `insurances_ibfk_3` FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies` (`id`);

--
-- Constraints for table `pollution_certificates`
--
ALTER TABLE `pollution_certificates`
  ADD CONSTRAINT `fk_pollution_shop` FOREIGN KEY (`created_by_shop_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pollution_certificates_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pollution_certificates_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminder_history`
--
ALTER TABLE `reminder_history`
  ADD CONSTRAINT `reminder_history_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminder_history_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminder_history_ibfk_3` FOREIGN KEY (`sent_by_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_parent_agent` FOREIGN KEY (`parent_agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicles_shop` FOREIGN KEY (`created_by_shop_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicles_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
