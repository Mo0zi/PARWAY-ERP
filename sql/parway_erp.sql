-- Parway Industry - ERP System Database Schema
-- Database: parway_erp

-- 1. Users Table (Admin Authentication)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) DEFAULT 'admin',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default admin (Email: parway2002@gmail.com, Password: SHAHBAZ@000)
INSERT INTO `users` (`username`, `password`, `role`) 
SELECT 'parway2002@gmail.com', '$2y$10$fVdJm33h7C8Z2k9mN0Z0y.B8gM3O2p3Q4R5S6T7U8V9W0X1Y2Z3a4', 'admin'
FROM DUAL WHERE NOT EXISTS (SELECT * FROM `users` WHERE `username`='parway2002@gmail.com');

-- 2. System Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_name` VARCHAR(150) NOT NULL DEFAULT 'Parway Industry',
  `logo` VARCHAR(255) DEFAULT '',
  `theme_primary_color` VARCHAR(20) DEFAULT '#2b5876',
  `theme_secondary_color` VARCHAR(20) DEFAULT '#4e4376',
  `address` TEXT,
  `phone` VARCHAR(50) DEFAULT '+92 300 1234567'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default settings
INSERT INTO `settings` (`company_name`, `theme_primary_color`, `theme_secondary_color`, `address`, `phone`)
SELECT 'Parway Industry - Clothing Manufacturing', '#1e3c72', '#2a5298', 'Industrial Area, Faisalabad, Pakistan', '+92 300 0000000'
FROM DUAL WHERE NOT EXISTS (SELECT * FROM `settings` WHERE `id`=1);

-- 3. Customers Table
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) DEFAULT '',
  `address` TEXT,
  `opening_balance` DECIMAL(12,2) DEFAULT 0.00,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Customer Ledger Table
CREATE TABLE IF NOT EXISTS `customer_ledger` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `type` ENUM('debit', 'credit') NOT NULL,
  `description` TEXT NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `invoice_id` INT DEFAULT NULL,
  `balance` DECIMAL(12,2) DEFAULT 0.00,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Personal Ledger Table
CREATE TABLE IF NOT EXISTS `personal_ledger` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `date` DATE NOT NULL,
  `type` ENUM('debit', 'credit') NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `description` TEXT NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `balance` DECIMAL(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Items Table (Catalog for Invoices)
CREATE TABLE IF NOT EXISTS `items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_name` VARCHAR(120) NOT NULL,
  `default_price` DECIMAL(10,2) DEFAULT 0.00,
  `unit` VARCHAR(20) DEFAULT 'Pcs'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Invoices Table
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_no` VARCHAR(50) NOT NULL UNIQUE,
  `customer_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(10,2) DEFAULT 0.00,
  `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
  `balance` DECIMAL(12,2) DEFAULT 0.00,
  `saved_copy_path` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Invoice Items Table
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT NOT NULL,
  `item_id` INT DEFAULT NULL,
  `item_name` VARCHAR(120) NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Workers Table
CREATE TABLE IF NOT EXISTS `workers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) DEFAULT '',
  `cnic` VARCHAR(30) DEFAULT '',
  `joining_date` DATE DEFAULT NULL,
  `default_rate` DECIMAL(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Worker Ledger Table
CREATE TABLE IF NOT EXISTS `worker_ledger` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `worker_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `type` ENUM('debit', 'credit') NOT NULL,
  `category` ENUM('salary', 'advance', 'extra', 'lot_payment') NOT NULL,
  `description` TEXT NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `balance` DECIMAL(12,2) DEFAULT 0.00,
  FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Lots Table (Working Sheet Header)
CREATE TABLE IF NOT EXISTS `lots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lot_no` VARCHAR(50) NOT NULL UNIQUE,
  `date` DATE NOT NULL,
  `description` TEXT,
  `target_qty` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Lot Work Table (Working Sheet Detail)
CREATE TABLE IF NOT EXISTS `lot_work` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lot_id` INT NOT NULL,
  `worker_id` INT NOT NULL,
  `work_type` VARCHAR(50) NOT NULL DEFAULT 'Stitching',
  `rate_per_piece` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `qty_done` INT NOT NULL DEFAULT 0,
  `qty_target` INT NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `date` DATE NOT NULL,
  FOREIGN KEY (`lot_id`) REFERENCES `lots`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Seed Data for Instant Demonstration
INSERT INTO `items` (`item_name`, `default_price`, `unit`) VALUES 
('Men Polo T-Shirt', 850.00, 'Pcs'),
('Cotton Trouser', 1200.00, 'Pcs'),
('Denim Jacket', 2400.00, 'Pcs'),
('Hoodie Premium', 1850.00, 'Pcs');

INSERT INTO `customers` (`name`, `phone`, `address`, `opening_balance`) VALUES
('Al-Madina Traders', '03001112233', 'Anarkali Market, Lahore', 15000.00),
('Tariq Clothing Store', '03214445566', 'Karkhano Market, Peshawar', 0.00);

INSERT INTO `workers` (`name`, `phone`, `cnic`, `joining_date`, `default_rate`) VALUES
('Muhammad Aslam', '03129998877', '35202-1234567-1', '2025-01-10', 45.00),
('Rashid Ali', '03337776655', '35202-7654321-2', '2025-02-01', 50.00);
