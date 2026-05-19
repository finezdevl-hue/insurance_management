-- MySQL Database Schema for Vehicle Details & Insurance Renewal Management System
-- Database: vehicle_manage

CREATE DATABASE IF NOT EXISTS `vehicle_manage` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vehicle_manage`;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `system_name` VARCHAR(255) NOT NULL DEFAULT 'Vehicle Details & Insurance Renewal Management System',
  `contact_email` VARCHAR(255) DEFAULT 'support@vehicledetails.com',
  `contact_phone` VARCHAR(20) DEFAULT '+1234567890',
  `currency` VARCHAR(10) DEFAULT 'INR',
  `single_message_price` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `whatsapp_api_url` VARCHAR(255) DEFAULT 'https://graph.facebook.com/v17.0',
  `whatsapp_phone_number_id` VARCHAR(100) DEFAULT '',
  `whatsapp_access_token` TEXT DEFAULT NULL,
  `whatsapp_template_name` VARCHAR(100) DEFAULT 'insurance_renewal_alert',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `users` (Super Admins & Agents)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `role` ENUM('admin', 'agent') NOT NULL DEFAULT 'agent',
  `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  
  -- Agent Shop / Business Details (Null for Admin)
  `shop_name` VARCHAR(255) DEFAULT NULL,
  `shop_owner_name` VARCHAR(255) DEFAULT NULL,
  `shop_logo` VARCHAR(255) DEFAULT NULL,
  `shop_banner` VARCHAR(255) DEFAULT NULL,
  `shop_address` TEXT DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `state` VARCHAR(100) DEFAULT NULL,
  `pincode` VARCHAR(10) DEFAULT NULL,
  `mobile_number` VARCHAR(20) DEFAULT NULL,
  `whatsapp_number` VARCHAR(20) DEFAULT NULL,
  `gst_number` VARCHAR(25) DEFAULT NULL,
  `license_number` VARCHAR(50) DEFAULT NULL,
  `pan_number` VARCHAR(15) DEFAULT NULL,
  `business_type` VARCHAR(100) DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `access_health_insurance` TINYINT(1) NOT NULL DEFAULT 1,
  `access_vehicle_insurance` TINYINT(1) NOT NULL DEFAULT 1,
  `access_pollution` TINYINT(1) NOT NULL DEFAULT 1,
  `message_balance` INT NOT NULL DEFAULT 0,
  
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `agent_message_recharges`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `agent_message_recharges` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `agent_id` INT NOT NULL,
  `recharge_amount` DECIMAL(10,2) NOT NULL,
  `message_unit_price` DECIMAL(10,2) NOT NULL,
  `messages_credited` INT NOT NULL DEFAULT 0,
  `recharged_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`recharged_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `insurance_companies`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `insurance_companies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `vehicle_types`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicle_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `customers`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `agent_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `mobile_number` VARCHAR(20) NOT NULL,
  `whatsapp_number` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `id_proof_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `vehicles`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `agent_id` INT NOT NULL,
  `vehicle_number` VARCHAR(30) NOT NULL UNIQUE,
  `vehicle_type_id` INT NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `model` VARCHAR(100) NOT NULL,
  `fuel_type` ENUM('Petrol', 'Diesel', 'CNG', 'LPG', 'Electric', 'Hybrid') NOT NULL,
  `registration_date` DATE NOT NULL,
  `chassis_number` VARCHAR(50) NOT NULL,
  `engine_number` VARCHAR(50) NOT NULL,
  `rc_expiry_date` DATE NOT NULL,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `insurances`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `insurances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT NOT NULL,
  `agent_id` INT NOT NULL,
  `insurance_company_id` INT NOT NULL,
  `policy_number` VARCHAR(100) NOT NULL UNIQUE,
  `insurance_type` ENUM('Comprehensive', 'Third Party', 'Own Damage', 'Zero Dep') NOT NULL,
  `start_date` DATE NOT NULL,
  `expiry_date` DATE NOT NULL,
  `premium_amount` DECIMAL(10,2) NOT NULL,
  `document_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`insurance_company_id`) REFERENCES `insurance_companies`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `pollution_certificates`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pollution_certificates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT NOT NULL,
  `agent_id` INT NOT NULL,
  `certificate_number` VARCHAR(100) NOT NULL UNIQUE,
  `start_date` DATE NOT NULL,
  `expiry_date` DATE NOT NULL,
  `document_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `reminder_history`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reminder_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `vehicle_id` INT NOT NULL,
  `reminder_type` ENUM('Insurance', 'Pollution', 'RC') NOT NULL,
  `reminder_period` VARCHAR(20) NOT NULL, -- '30 Days', '15 Days', '7 Days', '1 Day'
  `sent_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `sent_by_user_id` INT NOT NULL,
  `status` ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
  `message` TEXT NOT NULL,
  `api_response` TEXT DEFAULT NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sent_by_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `activity_logs`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data / Sample Data
-- --------------------------------------------------------

-- Default Settings
INSERT INTO `settings` (`system_name`, `contact_email`, `contact_phone`, `currency`, `single_message_price`, `whatsapp_api_url`, `whatsapp_phone_number_id`, `whatsapp_access_token`) VALUES
('Vehicle Details & Insurance Renewal Management System', 'admin@vehicleinsurance.com', '+919876543210', 'INR', 1.00, 'https://graph.facebook.com/v17.0', '109382746193847', 'EAAZB...MOCK_ACCESS_TOKEN');

-- Default Users (Passwords are hashed values of 'admin123' and 'agent123')
-- admin123 hash: $2y$12$XfH1qrJD6wrSbnZiT3F0gu59TJEbfwH0AP3LFLatB.Gy6EAyY1cgW (password_hash('admin123', PASSWORD_DEFAULT))
-- agent123 hash: $2y$12$mhAsIPmjPpxLcEDC7nZQVe/fxfPwVJjRDJRbtWKxKjwtPZlFw5sjS (password_hash('agent123', PASSWORD_DEFAULT))
INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `status`, `shop_name`, `shop_owner_name`, `shop_logo`, `shop_banner`, `shop_address`, `city`, `state`, `pincode`, `mobile_number`, `whatsapp_number`, `gst_number`, `license_number`, `pan_number`, `business_type`, `expiry_date`, `access_health_insurance`, `access_vehicle_insurance`, `access_pollution`, `message_balance`) VALUES
(1, 'admin', '$2y$12$XfH1qrJD6wrSbnZiT3F0gu59TJEbfwH0AP3LFLatB.Gy6EAyY1cgW', 'admin@vehicleinsurance.com', 'admin', 'active', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 1, 0),
(2, 'agent1', '$2y$12$mhAsIPmjPpxLcEDC7nZQVe/fxfPwVJjRDJRbtWKxKjwtPZlFw5sjS', 'agent1@speedyagency.com', 'agent', 'active', 'Speedy RTO Consultancy', 'John Doe', 'logo_default.png', 'banner_default.png', '102 Main Street, High Street Complex', 'Mumbai', 'Maharashtra', '400001', '9876543210', '9876543210', '27AAAAA1111A1Z1', 'LIC-9087-A1', 'ABCDE1234F', 'Insurance & RTO Consultancy', '2027-05-18', 1, 1, 1, 0),
(3, 'agent2', '$2y$12$mhAsIPmjPpxLcEDC7nZQVe/fxfPwVJjRDJRbtWKxKjwtPZlFw5sjS', 'agent2@eliteconsult.com', 'agent', 'active', 'Elite Auto Solutions', 'Sarah Smith', 'logo_default.png', 'banner_default.png', '405 Galleria Chambers, Park Avenue', 'Bangalore', 'Karnataka', '560001', '8765432109', '8765432109', '29BBBBB2222B2Z2', 'LIC-4321-B2', 'FGHIJ5678K', 'Vehicle Sales & RTO Agent', '2027-05-18', 1, 1, 1, 0);

-- Default Vehicle Types
INSERT INTO `vehicle_types` (`id`, `name`, `status`) VALUES
(1, 'Two Wheeler (Motorcycle/Scooter)', 'active'),
(2, 'Four Wheeler (Sedan/SUV/Hatchback)', 'active'),
(3, 'Commercial Goods Vehicle (Truck/Tempo)', 'active'),
(4, 'Commercial Passenger Vehicle (Bus/Cab)', 'active'),
(5, 'Auto Rickshaw (Three Wheeler)', 'active');

-- Default Insurance Companies
INSERT INTO `insurance_companies` (`id`, `name`, `status`) VALUES
(1, 'HDFC ERGO General Insurance', 'active'),
(2, 'ICICI Lombard General Insurance', 'active'),
(3, 'Tata AIG General Insurance', 'active'),
(4, 'Bajaj Allianz General Insurance', 'active'),
(5, 'New India Assurance', 'active'),
(6, 'Star Health & Allied Insurance', 'active'),
(7, 'United India Insurance', 'active');

-- Sample Customers (For Agent 1)
INSERT INTO `customers` (`id`, `agent_id`, `name`, `mobile_number`, `whatsapp_number`, `email`, `address`, `id_proof_path`) VALUES
(1, 2, 'Rohan Sharma', '9123456789', '9123456789', 'rohan.sharma@gmail.com', 'Flat 401, Sun Palace, Andheri West, Mumbai', 'proof_rohan.jpg'),
(2, 2, 'Priya Patel', '9812345670', '9812345670', 'priya.patel@yahoo.com', 'B-12, Green Meadow, Bandra East, Mumbai', 'proof_priya.jpg'),
(3, 3, 'Anil Kumar', '7654321098', '7654321098', 'anil.k@outlook.com', 'No 24, 5th Cross, Indiranagar, Bangalore', 'proof_anil.jpg');

-- Sample Vehicles
-- Vehicle 1: Rohan Sharma (Agent 1)
-- Vehicle 2: Priya Patel (Agent 1)
-- Vehicle 3: Anil Kumar (Agent 2)
INSERT INTO `vehicles` (`id`, `customer_id`, `agent_id`, `vehicle_number`, `vehicle_type_id`, `brand`, `model`, `fuel_type`, `registration_date`, `chassis_number`, `engine_number`, `rc_expiry_date`, `image_path`) VALUES
(1, 1, 2, 'MH02AB1234', 2, 'Hyundai', 'Creta', 'Diesel', '2020-03-15', 'MALC451AGH1298374', 'ENG5612345', '2035-03-14', 'vehicle_creta.jpg'),
(2, 2, 2, 'MH01XY9876', 1, 'Royal Enfield', 'Classic 350', 'Petrol', '2021-08-20', 'MEG1298471203948', 'ENG9087123', '2036-08-19', 'vehicle_re.jpg'),
(3, 3, 3, 'KA03MZ5555', 2, 'Maruti Suzuki', 'Swift', 'Petrol', '2019-11-10', 'MALS568AKJ8765432', 'ENG3456789', '2034-11-09', 'vehicle_swift.jpg');

-- Sample Insurances
-- Rohan: Expiry in 15 days (For alert demonstration)
-- Priya: Expiry in 5 days (For alert demonstration)
-- Anil: Expired 10 days ago
-- We calculate dynamic dates relative to current date in PHP for realistic alerts,
-- but insert standard dates here for initial records.
-- Let's put dates that are close to current date of 2026-05-18
INSERT INTO `insurances` (`id`, `vehicle_id`, `agent_id`, `insurance_company_id`, `policy_number`, `insurance_type`, `start_date`, `expiry_date`, `premium_amount`, `document_path`) VALUES
(1, 1, 2, 1, 'POL-10928374', 'Comprehensive', '2025-06-03', '2026-06-02', 12500.00, 'doc_creta_ins.pdf'), -- Expires in ~15 days from May 18, 2026
(2, 2, 2, 2, 'POL-90812734', 'Third Party', '2025-05-24', '2026-05-23', 2500.00, 'doc_re_ins.pdf'),     -- Expires in ~5 days from May 18, 2026
(3, 3, 3, 3, 'POL-33445566', 'Comprehensive', '2024-05-09', '2025-05-08', 9800.00, 'doc_swift_ins.pdf');    -- Expired

-- Sample Pollution Certificates
-- Rohan: Expiry in 6 days
-- Priya: Active, expires in 40 days
-- Anil: Expired
INSERT INTO `pollution_certificates` (`id`, `vehicle_id`, `agent_id`, `certificate_number`, `start_date`, `expiry_date`, `document_path`) VALUES
(1, 1, 2, 'PUC-9018237', '2025-11-25', '2026-05-24', 'doc_creta_puc.pdf'), -- Expires in 6 days
(2, 2, 2, 'PUC-1098234', '2025-12-28', '2026-06-27', 'doc_re_puc.pdf'),    -- Active (expires in 40 days)
(3, 3, 3, 'PUC-5544332', '2024-11-12', '2025-05-11', 'doc_swift_puc.pdf');   -- Expired

-- Sample Reminder History
INSERT INTO `reminder_history` (`id`, `customer_id`, `vehicle_id`, `reminder_type`, `reminder_period`, `sent_date`, `sent_by_user_id`, `status`, `message`, `api_response`) VALUES
(1, 1, 1, 'Insurance', '30 Days', '2026-05-03 10:30:00', 2, 'sent', 'Hello Rohan Sharma, your vehicle MH02AB1234 insurance will expire on 2026-06-02. Please renew it soon.', '{"messaging_product":"whatsapp","messages":[{"id":"wamid.HBgLOTEyMzQ1Njc4OQYVAg8EHwAd"}]}'),
(2, 2, 2, 'Insurance', '15 Days', '2026-05-08 11:15:00', 2, 'sent', 'Hello Priya Patel, your vehicle MH01XY9876 insurance will expire on 2026-05-23. Please renew it soon.', '{"messaging_product":"whatsapp","messages":[{"id":"wamid.HBgLOTgxMjM0NTY3MEYVAg8EHwAd"}]}');

-- Sample Activity Logs
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`) VALUES
(1, 1, 'Admin Login', 'Administrator logged into the system', '127.0.0.1'),
(2, 2, 'Agent Login', 'Agent speedyagency logged into the system', '127.0.0.1'),
(3, 2, 'Add Customer', 'Customer Rohan Sharma added by Agent 1', '127.0.0.1'),
(4, 2, 'Add Vehicle', 'Vehicle MH02AB1234 added for Rohan Sharma', '127.0.0.1');
