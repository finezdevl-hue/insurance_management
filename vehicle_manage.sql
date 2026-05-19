-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2026 at 11:56 AM
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
(2, 2, 'Agent Login', 'Agent speedyagency logged into the system', '127.0.0.1', '2026-05-18 07:10:23'),
(3, 2, 'Add Customer', 'Customer Rohan Sharma added by Agent 1', '127.0.0.1', '2026-05-18 07:10:23'),
(4, 2, 'Add Vehicle', 'Vehicle MH02AB1234 added for Rohan Sharma', '127.0.0.1', '2026-05-18 07:10:23'),
(5, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent1', '::1', '2026-05-18 07:16:40'),
(6, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent1', '::1', '2026-05-18 07:17:12'),
(7, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent1', '::1', '2026-05-18 07:17:39'),
(8, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent1', '::1', '2026-05-18 07:19:45'),
(9, 2, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '::1', '2026-05-18 07:25:58'),
(10, 2, 'WhatsApp Reminder Sent', 'Sent Insurance renewal notification to Priya Patel for vehicle MH01XY9876 (Status: sent)', '::1', '2026-05-18 07:26:19'),
(11, 2, 'Update Customer', 'Updated customer details: Priya Patel', '::1', '2026-05-18 07:27:34'),
(12, 2, 'WhatsApp Reminder Sent', 'Sent Insurance renewal notification to Priya Patel for vehicle MH01XY9876 (Status: sent)', '::1', '2026-05-18 07:28:00'),
(13, 2, 'Update Customer', 'Updated customer details: Priya Patel', '::1', '2026-05-18 07:34:00'),
(14, 2, 'WhatsApp Reminder Sent', 'Sent Insurance renewal notification to Priya Patel for vehicle MH01XY9876 (Status: sent)', '::1', '2026-05-18 07:34:13'),
(15, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: admin', '::1', '2026-05-18 08:19:49'),
(16, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-18 08:20:21'),
(17, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-18 09:06:25'),
(18, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: agent', '::1', '2026-05-18 09:06:42'),
(19, 2, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '::1', '2026-05-18 09:06:56'),
(20, 2, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-18 10:20:19'),
(21, NULL, 'Agent Login Failed', 'Failed login attempt for agent username: admin', '::1', '2026-05-18 10:25:44'),
(22, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-18 10:26:09'),
(23, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-18 10:32:14'),
(24, 2, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '::1', '2026-05-18 10:32:48'),
(25, 2, 'WhatsApp Reminder Sent', 'Sent Insurance renewal notification to Priya Patel for vehicle MH01XY9876 (Status: sent)', '::1', '2026-05-18 10:33:23'),
(26, 2, 'Agent Logout', 'Agent logged out successfully.', '::1', '2026-05-18 11:39:50'),
(27, 1, 'Admin Login', 'Super Admin logged in successfully.', '127.0.0.1', '2026-05-19 05:14:38'),
(28, 1, 'Update Agent', 'Updated agent details: agent1 (Shop: Speedy RTO Consultancy)', '127.0.0.1', '2026-05-19 05:15:38'),
(29, 1, 'Admin Logout', 'Super Admin logged out successfully.', '127.0.0.1', '2026-05-19 05:15:55'),
(30, 2, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '127.0.0.1', '2026-05-19 05:16:16'),
(31, 2, 'Create Health Policy', 'Created Health Policy: HLT-99998888', '127.0.0.1', '2026-05-19 05:18:30'),
(32, 2, 'Update Health Policy', 'Updated Health Policy: HLT-99998888', '127.0.0.1', '2026-05-19 05:19:21'),
(33, 2, 'Agent Logout', 'Agent logged out successfully.', '127.0.0.1', '2026-05-19 05:22:21'),
(34, 2, 'Agent Login', 'Agent logged in successfully. Shop: Speedy RTO Consultancy', '127.0.0.1', '2026-05-19 05:22:46'),
(35, 2, 'Agent Logout', 'Agent logged out successfully.', '127.0.0.1', '2026-05-19 05:23:37'),
(36, 1, 'Admin Login', 'Super Admin logged in successfully.', '127.0.0.1', '2026-05-19 05:24:09'),
(37, 1, 'Create Agent', 'Created new agent: testagent (Shop: Test Agency Shop)', '127.0.0.1', '2026-05-19 05:31:53'),
(38, 1, 'Update Agent', 'Updated agent details: agent1 (Shop: Speedy RTO Consultancy)', '127.0.0.1', '2026-05-19 05:36:28'),
(39, 1, 'Update Agent', 'Updated agent details: agent2 (Shop: Elite Auto Solutions)', '127.0.0.1', '2026-05-19 05:46:29'),
(40, 1, 'Update Agent', 'Updated agent details: agent2 (Shop: Elite Auto Solutions)', '127.0.0.1', '2026-05-19 05:48:21'),
(41, 1, 'Admin Logout', 'Super Admin logged out successfully.', '127.0.0.1', '2026-05-19 05:52:22'),
(42, 3, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '127.0.0.1', '2026-05-19 05:52:25'),
(43, 3, 'Agent Logout', 'Agent logged out successfully.', '127.0.0.1', '2026-05-19 06:08:45'),
(44, 1, 'Admin Login', 'Super Admin logged in successfully.', '127.0.0.1', '2026-05-19 06:08:57'),
(45, 1, 'Update Settings', 'Global system and WhatsApp API configurations updated.', '127.0.0.1', '2026-05-19 06:14:57'),
(46, 1, 'Recharge Agent Messages', 'Recharged agent2 with Rs 100 for 500 messages at Rs 0.2 each.', '127.0.0.1', '2026-05-19 06:33:03'),
(47, 3, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-19 06:34:53'),
(48, 1, 'Update Agent', 'Updated agent details: agent2 (Shop: Elite Auto Solutions)', '127.0.0.1', '2026-05-19 06:36:02'),
(49, 3, 'Update Vehicle', 'Updated vehicle: KA03MZ5555', '::1', '2026-05-19 06:36:29'),
(50, 3, 'Save Pollution', 'Added/Updated pollution certificate: PUC-5544332', '::1', '2026-05-19 06:36:29'),
(51, 3, 'Update Vehicle', 'Updated vehicle: KA03MZ5555', '::1', '2026-05-19 06:36:52'),
(52, 3, 'Save Pollution', 'Added/Updated pollution certificate: PUC-5544332', '::1', '2026-05-19 06:36:52'),
(53, 3, 'WhatsApp Reminder Sent', 'Sent Pollution renewal notification to Anil Kumar (Status: sent)', '::1', '2026-05-19 06:36:59'),
(54, 3, 'Update Vehicle', 'Updated vehicle: KA03MZ5555', '::1', '2026-05-19 06:46:46'),
(55, 3, 'Save Pollution', 'Added/Updated pollution certificate: PUC-5544332', '::1', '2026-05-19 06:46:46'),
(56, 3, 'Update Shop Details', 'Agent updated shop profile: Elite Auto Solutions', '::1', '2026-05-19 06:54:11'),
(57, 3, 'Update Shop Details', 'Agent updated shop profile: Elite Auto Solutions', '::1', '2026-05-19 06:58:13'),
(58, 1, 'Admin Logout', 'Super Admin logged out successfully.', '127.0.0.1', '2026-05-19 09:39:47'),
(59, 3, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '127.0.0.1', '2026-05-19 09:39:52'),
(61, 3, 'Create Customer', 'Created customer on-the-fly: \' (ID: 5)', '127.0.0.1', '2026-05-19 10:53:39'),
(62, 3, 'Create Vehicle', 'Created vehicle record: KL17N1515', '127.0.0.1', '2026-05-19 10:53:39'),
(63, 1, 'Admin Login', 'Super Admin logged in successfully.', '::1', '2026-05-19 11:02:24'),
(64, 1, 'Update Agent', 'Updated agent details: King\'s Auto \"Garage\" (Shop: Test Agency Shop)', '::1', '2026-05-19 11:02:56'),
(65, 1, 'Update Agent', 'Updated agent details: King\'s Auto \"Garage\" (Shop: King\'s Auto \"Garage\")', '::1', '2026-05-19 11:03:18'),
(66, 1, 'Admin Logout', 'Super Admin logged out successfully.', '::1', '2026-05-19 11:38:01'),
(67, 3, 'Agent Login', 'Agent logged in successfully. Shop: Elite Auto Solutions', '::1', '2026-05-19 11:38:15');

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
(1, 3, '100.00', '0.20', 500, 1, '2026-05-19 06:33:03');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mobile_number` varchar(20) NOT NULL,
  `whatsapp_number` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `id_proof_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `agent_id`, `name`, `mobile_number`, `whatsapp_number`, `email`, `address`, `id_proof_path`, `created_at`, `updated_at`) VALUES
(1, 2, 'Rohan Sharma', '9123456789', '9123456789', 'rohan.sharma@gmail.com', 'Flat 401, Sun Palace, Andheri West, Mumbai', 'proof_rohan.jpg', '2026-05-18 07:10:23', '2026-05-18 07:10:23'),
(2, 2, 'Priya Patel', '6238426929', '6238426929', 'priya.patel@yahoo.com', 'B-12, Green Meadow, Bandra East, Mumbai', 'proof_priya.jpg', '2026-05-18 07:10:23', '2026-05-18 07:34:00'),
(3, 3, 'Anil Kumar', '7654321098', '7654321098', 'anil.k@outlook.com', 'No 24, 5th Cross, Indiranagar, Bangalore', 'proof_anil.jpg', '2026-05-18 07:10:23', '2026-05-18 07:10:23'),
(5, 3, '\'', '6238426929', '6238426929', '', '', NULL, '2026-05-19 10:53:39', '2026-05-19 10:53:39');

-- --------------------------------------------------------

--
-- Table structure for table `health_insurances`
--

CREATE TABLE `health_insurances` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
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

--
-- Dumping data for table `health_insurances`
--

INSERT INTO `health_insurances` (`id`, `customer_id`, `agent_id`, `insurance_company_id`, `policy_number`, `policy_name`, `insured_persons`, `start_date`, `expiry_date`, `premium_amount`, `document_path`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 6, 'HLT-10293847', 'Star Family Delux', 'Rohan Sharma, Anita Sharma', '2025-06-10', '2026-06-09', '18500.00', NULL, '2026-05-19 05:10:16', '2026-05-19 05:10:16'),
(2, 2, 2, 6, 'HLT-55667788', 'Star Health Gain', 'Priya Patel', '2025-05-25', '2026-05-24', '8500.00', NULL, '2026-05-19 05:10:16', '2026-05-19 05:10:16'),
(3, 2, 2, 6, 'HLT-99998888', 'Optima Secure', 'Priya Patel, Rahul Patel', '2026-05-19', '2027-05-19', '16500.00', NULL, '2026-05-19 05:18:30', '2026-05-19 05:19:21');

-- --------------------------------------------------------

--
-- Table structure for table `insurances`
--

CREATE TABLE `insurances` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
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

INSERT INTO `insurances` (`id`, `vehicle_id`, `agent_id`, `insurance_company_id`, `policy_number`, `insurance_type`, `start_date`, `expiry_date`, `premium_amount`, `document_path`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 'POL-10928374', 'Comprehensive', '2025-06-03', '2026-06-02', '12500.00', 'doc_creta_ins.pdf', '2026-05-18 07:10:23', '2026-05-18 07:10:23'),
(2, 2, 2, 2, 'POL-90812734', 'Third Party', '2025-05-24', '2026-05-23', '2500.00', 'doc_re_ins.pdf', '2026-05-18 07:10:23', '2026-05-18 07:10:23'),
(3, 3, 3, 3, 'POL-33445566', 'Comprehensive', '2024-05-09', '2025-05-08', '9800.00', 'doc_swift_ins.pdf', '2026-05-18 07:10:23', '2026-05-18 07:10:23');

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

INSERT INTO `pollution_certificates` (`id`, `vehicle_id`, `agent_id`, `certificate_number`, `start_date`, `expiry_date`, `document_path`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'PUC-9018237', '2025-11-25', '2026-05-24', 'doc_creta_puc.pdf', '2026-05-18 07:10:23', '2026-05-18 07:10:23'),
(2, 2, 2, 'PUC-1098234', '2025-12-28', '2026-06-27', 'doc_re_puc.pdf', '2026-05-18 07:10:23', '2026-05-18 07:10:23'),
(3, 3, 3, 'PUC-5544332', '2024-11-12', '2026-05-21', 'doc_swift_puc.pdf', '2026-05-18 07:10:23', '2026-05-19 06:36:52');

-- --------------------------------------------------------

--
-- Table structure for table `reminder_history`
--

CREATE TABLE `reminder_history` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
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
(1, 1, 1, 'Insurance', '30 Days', '2026-05-03 05:00:00', 2, 'sent', 'Hello Rohan Sharma, your vehicle MH02AB1234 insurance will expire on 2026-06-02. Please renew it soon.', '{\"messaging_product\":\"whatsapp\",\"messages\":[{\"id\":\"wamid.HBgLOTEyMzQ1Njc4OQYVAg8EHwAd\"}]}'),
(2, 2, 2, 'Insurance', '15 Days', '2026-05-08 05:45:00', 2, 'sent', 'Hello Priya Patel, your vehicle MH01XY9876 insurance will expire on 2026-05-23. Please renew it soon.', '{\"messaging_product\":\"whatsapp\",\"messages\":[{\"id\":\"wamid.HBgLOTgxMjM0NTY3MEYVAg8EHwAd\"}]}'),
(3, 2, 2, 'Insurance', '7 Days', '2026-05-18 07:26:19', 2, 'sent', 'Hello Priya Patel, your vehicle MH01XY9876 Insurance will expire on 2026-05-23. Please renew it soon.', '{\"messaging_product\":\"whatsapp\",\"contacts\":[{\"input\":\"919812345670\",\"wa_id\":\"919812345670\"}],\"messages\":[{\"id\":\"wamid.HBgL919812345670YVAg8EHwAd\",\"message_status\":\"accepted\"}]}'),
(4, 2, 2, 'Insurance', '7 Days', '2026-05-18 07:28:00', 2, 'sent', 'Hello Priya Patel, your vehicle MH01XY9876 Insurance will expire on 2026-05-23. Please renew it soon.', '{\"messaging_product\":\"whatsapp\",\"contacts\":[{\"input\":\"916238426929\",\"wa_id\":\"916238426929\"}],\"messages\":[{\"id\":\"wamid.HBgL916238426929YVAg8EHwAd\",\"message_status\":\"accepted\"}]}'),
(5, 2, 2, 'Insurance', '7 Days', '2026-05-18 07:34:13', 2, 'sent', 'Hello Priya Patel, your vehicle MH01XY9876 Insurance will expire on 2026-05-23. Please renew it soon.', '{\"messaging_product\":\"whatsapp\",\"contacts\":[{\"input\":\"916238426929\",\"wa_id\":\"916238426929\"}],\"messages\":[{\"id\":\"wamid.HBgL916238426929YVAg8EHwAd\",\"message_status\":\"accepted\"}]}'),
(6, 2, 2, 'Insurance', '7 Days', '2026-05-18 10:33:23', 2, 'sent', 'Hello Priya Patel, your vehicle MH01XY9876 Insurance will expire on 2026-05-23. Please renew it soon.', '{\"messaging_product\":\"whatsapp\",\"contacts\":[{\"input\":\"916238426929\",\"wa_id\":\"916238426929\"}],\"messages\":[{\"id\":\"wamid.HBgL916238426929YVAg8EHwAd\",\"message_status\":\"accepted\"}]}'),
(7, 3, 3, 'Pollution', '7 Days', '2026-05-19 06:36:59', 3, 'sent', 'Hello Anil Kumar, your vehicle KA03MZ5555 Pollution will expire on 2026-05-21. Please renew it soon.', '{\"messaging_product\":\"whatsapp\",\"contacts\":[{\"input\":\"917654321098\",\"wa_id\":\"917654321098\"}],\"messages\":[{\"id\":\"wamid.HBgL917654321098YVAg8EHwAd\",\"message_status\":\"accepted\"}]}');

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
(1, 'Vehicle Details & Insurance Renewal Management System', 'admin@vehicleinsurance.com', '+919876543210', 'INR', '0.20', 'https://graph.facebook.com/v17.0', '109382746193847', 'EAAZB...MOCK_ACCESS_TOKEN', 'insurance_renewal_alert', '2026-05-18 07:10:23', '2026-05-19 06:14:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','agent') NOT NULL DEFAULT 'agent',
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `shop_name` varchar(255) DEFAULT NULL,
  `shop_owner_name` varchar(255) DEFAULT NULL,
  `shop_logo` varchar(255) DEFAULT NULL,
  `shop_banner` varchar(255) DEFAULT NULL,
  `shop_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `whatsapp_number` varchar(20) DEFAULT NULL,
  `gst_number` varchar(25) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `pan_number` varchar(15) DEFAULT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `access_health_insurance` tinyint(1) NOT NULL DEFAULT 1,
  `access_vehicle_insurance` tinyint(1) NOT NULL DEFAULT 1,
  `access_pollution` tinyint(1) NOT NULL DEFAULT 1,
  `message_balance` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `status`, `shop_name`, `shop_owner_name`, `shop_logo`, `shop_banner`, `shop_address`, `city`, `state`, `pincode`, `mobile_number`, `whatsapp_number`, `gst_number`, `license_number`, `pan_number`, `business_type`, `created_at`, `updated_at`, `expiry_date`, `access_health_insurance`, `access_vehicle_insurance`, `access_pollution`, `message_balance`) VALUES
(1, 'admin', '$2y$12$XfH1qrJD6wrSbnZiT3F0gu59TJEbfwH0AP3LFLatB.Gy6EAyY1cgW', 'admin@vehicleinsurance.com', 'admin', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-18 07:10:23', '2026-05-18 07:24:58', NULL, 1, 1, 1, 0),
(2, 'agent1', '$2y$12$mhAsIPmjPpxLcEDC7nZQVe/fxfPwVJjRDJRbtWKxKjwtPZlFw5sjS', 'agent1@speedyagency.com', 'agent', 'active', 'Speedy RTO Consultancy', 'John Updated Doe', 'logo_default.png', 'banner_default.png', '102 Main Street, High Street Complex', 'Mumbai', 'Maharashtra', '400001', '+91 99999 88888', '9876543210', '27AAAAA1111A1Z1', 'LIC-9087-A1', 'ABCDE1234F', 'Insurance & RTO Consultancy', '2026-05-18 07:10:23', '2026-05-19 05:36:28', '2027-05-18', 1, 0, 1, 0),
(3, 'agent2', '$2y$12$IxYH2SNfdRWJjE1BfeYP9eYVnHeXFP4T2aOyNMm7T9Un8PwofBOAu', 'agent2@eliteconsult.com', 'agent', 'active', 'Elite Auto Solutions', 'Shaji John', '6ed4ad223ea077ab5939ab9dec9c5dc8.png', 'fd970c3897a66badef6d3b525cfc0556.png', 'Near Metro Station, MG Road\r\nErnakulam, Kochi – 682035\r\nKerala, India', 'Ernakulam', 'Kerala', '682035', '6238426929', '6238426929', '29BBBBB2222B2Z2', 'LIC-4321-B2', 'FGHIJ5678K', 'Vehicle Sales & RTO Agent', '2026-05-18 07:10:23', '2026-05-19 06:58:13', '2027-05-18', 0, 0, 1, 499),
(4, 'King\'s Auto \"Garage\"', '$2y$12$G.j6zpmdjYL.KTxO2pQCGOhrVDA12ZI39tYwN5BJAOYzckE8yBKnW', 'testagent@agency.com', 'agent', 'active', 'King\'s Auto \"Garage\"', 'Tom Test', 'logo_default.png', 'banner_default.png', '', '', '', '', '6238426929', '916238426929', '', '', '', '', '2026-05-19 05:31:53', '2026-05-19 11:03:18', '2027-05-19', 1, 1, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `vehicle_number` varchar(30) NOT NULL,
  `vehicle_type_id` int(11) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `fuel_type` enum('Petrol','Diesel','CNG','LPG','Electric','Hybrid') NOT NULL,
  `registration_date` date NOT NULL,
  `chassis_number` varchar(50) NOT NULL,
  `engine_number` varchar(50) NOT NULL,
  `rc_expiry_date` date NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `customer_id`, `agent_id`, `vehicle_number`, `vehicle_type_id`, `brand`, `model`, `fuel_type`, `registration_date`, `chassis_number`, `engine_number`, `rc_expiry_date`, `image_path`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'MH02AB1234', 2, 'Hyundai', 'Creta', 'Diesel', '2020-03-15', 'MALC451AGH1298374', 'ENG5612345', '2035-03-14', 'vehicle_creta.jpg', '2026-05-18 07:10:23', '2026-05-18 07:10:23'),
(2, 2, 2, 'MH01XY9876', 1, 'Royal Enfield', 'Classic 350', 'Petrol', '2021-08-20', 'MEG1298471203948', 'ENG9087123', '2036-08-19', 'vehicle_re.jpg', '2026-05-18 07:10:23', '2026-05-18 07:10:23'),
(3, 3, 3, 'KA03MZ5555', 2, 'Maruti Suzuki', 'Swift', 'Petrol', '2019-11-10', 'MALS568AKJ8765432', 'ENG3456789', '2034-11-09', '51cc2f56cc8d147acb48bead5e1eb7a4.png', '2026-05-18 07:10:23', '2026-05-19 06:46:46'),
(4, 5, 3, 'KL17N1515', 1, 're', 'bullet', 'Petrol', '0000-00-00', '', '', '2026-05-20', NULL, '2026-05-19 10:53:39', '2026-05-19 10:53:39');

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_types`
--

CREATE TABLE `vehicle_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicle_types`
--

INSERT INTO `vehicle_types` (`id`, `name`, `status`, `created_at`) VALUES
(1, 'Two Wheeler (Motorcycle/Scooter)', 'active', '2026-05-18 07:10:23'),
(2, 'Four Wheeler (Sedan/SUV/Hatchback)', 'active', '2026-05-18 07:10:23'),
(3, 'Commercial Goods Vehicle (Truck/Tempo)', 'active', '2026-05-18 07:10:23'),
(4, 'Commercial Passenger Vehicle (Bus/Cab)', 'active', '2026-05-18 07:10:23'),
(5, 'Auto Rickshaw (Three Wheeler)', 'active', '2026-05-18 07:10:23');

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
  ADD KEY `agent_id` (`agent_id`);

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
  ADD KEY `insurance_company_id` (`insurance_company_id`);

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
  ADD KEY `agent_id` (`agent_id`);

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
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_number` (`vehicle_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `vehicle_type_id` (`vehicle_type_id`);

--
-- Indexes for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `agent_message_recharges`
--
ALTER TABLE `agent_message_recharges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `health_insurances`
--
ALTER TABLE `health_insurances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `insurances`
--
ALTER TABLE `insurances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `insurance_companies`
--
ALTER TABLE `insurance_companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pollution_certificates`
--
ALTER TABLE `pollution_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicle_types`
--
ALTER TABLE `vehicle_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `insurances_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `insurances_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `insurances_ibfk_3` FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies` (`id`);

--
-- Constraints for table `pollution_certificates`
--
ALTER TABLE `pollution_certificates`
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
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicles_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicles_ibfk_3` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
