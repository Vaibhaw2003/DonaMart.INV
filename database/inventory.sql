-- ============================================================
-- Smart Inventory & Billing Management System - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS `smart_inventory` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `smart_inventory`;

-- -------------------------------------------------------
-- Table: users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','manager','staff') NOT NULL DEFAULT 'staff',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: categories
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: products
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `barcode` VARCHAR(100) DEFAULT NULL,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `purchase_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `minimum_stock` INT NOT NULL DEFAULT 5,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'pcs',
  `image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: suppliers
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `company_name` VARCHAR(200) NOT NULL,
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `gst_no` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: customers
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: purchases
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchases` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `supplier_id` INT UNSIGNED DEFAULT NULL,
  `purchase_number` VARCHAR(50) NOT NULL UNIQUE,
  `purchase_date` DATE NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: purchase_items
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchase_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `purchase_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) GENERATED ALWAYS AS (`quantity` * `price`) STORED,
  CONSTRAINT `fk_pi_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: sales
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sales` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `sale_date` DATE NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `gst` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash','card','upi','bank','credit') NOT NULL DEFAULT 'cash',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sale_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: sale_items
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `sale_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_si_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_si_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: settings
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `company_name` VARCHAR(200) NOT NULL DEFAULT 'Smart Inventory Co.',
  `logo` VARCHAR(255) DEFAULT NULL,
  `gst_number` VARCHAR(20) DEFAULT NULL,
  `invoice_prefix` VARCHAR(10) NOT NULL DEFAULT 'INV',
  `currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
  `currency_symbol` VARCHAR(5) NOT NULL DEFAULT '₹',
  `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  `address` TEXT DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `theme` ENUM('light','dark') NOT NULL DEFAULT 'light',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Table: activity_logs
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL,
  `module` VARCHAR(100) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin user (password: Admin@123)
INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Super Admin', 'admin@smartinv.com', '$2y$10$F4xgNPpyshQtS6Lg6KpPtOGmWnSiu/CUXzIdjOW1LNYAincbP4haG', 'admin'),
('Store Manager', 'manager@smartinv.com', '$2y$10$F4xgNPpyshQtS6Lg6KpPtOGmWnSiu/CUXzIdjOW1LNYAincbP4haG', 'manager'),
('Sales Staff', 'staff@smartinv.com', '$2y$10$F4xgNPpyshQtS6Lg6KpPtOGmWnSiu/CUXzIdjOW1LNYAincbP4haG', 'staff');

-- Default settings
INSERT INTO `settings` (`company_name`, `gst_number`, `invoice_prefix`, `currency`, `currency_symbol`, `gst_rate`, `address`, `phone`, `email`) VALUES
('Smart Inventory Co.', '27AAPFU0939F1ZV', 'INV', 'INR', '₹', 18.00, '123 Business Park, Mumbai, Maharashtra - 400001', '+91 98765 43210', 'info@smartinv.com');

-- Categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Electronics', 'Electronic gadgets and components'),
('Groceries', 'Food and daily essentials'),
('Office Supplies', 'Stationery and office equipment'),
('Packaging Materials', 'Boxes, tapes, and packaging supplies'),
('Clothing', 'Apparel and garments'),
('Furniture', 'Home and office furniture');

-- Suppliers
INSERT INTO `suppliers` (`company_name`, `contact_person`, `phone`, `email`, `gst_no`, `address`) VALUES
('TechWorld Distributors', 'Raj Kumar', '+91 98001 11111', 'raj@techworld.com', '27AAPFU0939F1Z1', 'Dharavi, Mumbai'),
('FreshMart Wholesale', 'Priya Singh', '+91 98001 22222', 'priya@freshmart.com', '27AAPFU0939F1Z2', 'Crawford Market, Mumbai'),
('OfficeHub Pvt Ltd', 'Ankit Mehta', '+91 98001 33333', 'ankit@officehub.com', '27AAPFU0939F1Z3', 'BKC, Mumbai');

-- Customers
INSERT INTO `customers` (`name`, `phone`, `email`, `address`) VALUES
('Ramesh Gupta', '+91 99001 11111', 'ramesh@email.com', 'Andheri West, Mumbai'),
('Sunita Patel', '+91 99001 22222', 'sunita@email.com', 'Borivali East, Mumbai'),
('Amit Sharma', '+91 99001 33333', 'amit@email.com', 'Thane West, Mumbai'),
('Kavya Nair', '+91 99001 44444', 'kavya@email.com', 'Powai, Mumbai'),
('Mohammed Iqbal', '+91 99001 55555', 'iqbal@email.com', 'Kurla, Mumbai');

-- Products
INSERT INTO `products` (`name`, `sku`, `barcode`, `category_id`, `purchase_price`, `selling_price`, `stock`, `minimum_stock`, `unit`, `description`) VALUES
('Samsung Galaxy M14', 'SKU-ELEC-001', '8901212193100', 1, 10000.00, 13499.00, 25, 5, 'pcs', 'Samsung Galaxy M14 5G Smartphone'),
('HP Wireless Keyboard', 'SKU-ELEC-002', '8901212193101', 1, 800.00, 1299.00, 50, 10, 'pcs', 'HP CS10 Wireless Keyboard & Mouse Combo'),
('A4 Copy Paper (500 sheets)', 'SKU-OFFC-001', '8901212193102', 3, 150.00, 250.00, 100, 20, 'ream', 'JK Copier A4 Size Paper 75 GSM'),
('Ballpoint Pens (Box of 12)', 'SKU-OFFC-002', '8901212193103', 3, 50.00, 90.00, 200, 30, 'box', 'Reynolds Ballpoint Pen Blue Ink'),
('Basmati Rice 5kg', 'SKU-GROC-001', '8901212193104', 2, 280.00, 420.00, 80, 15, 'bag', 'India Gate Basmati Rice Premium 5kg'),
('Cardboard Box Large', 'SKU-PACK-001', '8901212193105', 4, 25.00, 45.00, 500, 100, 'pcs', 'Large corrugated cardboard shipping box'),
('USB-C Hub 7-in-1', 'SKU-ELEC-003', '8901212193106', 1, 900.00, 1599.00, 30, 8, 'pcs', 'Ugreen 7-in-1 USB Type-C Hub'),
('Sticky Notes 3x3 (Pack)', 'SKU-OFFC-003', '8901212193107', 3, 30.00, 55.00, 150, 25, 'pack', 'Post-it Sticky Notes assorted colors');

-- Sample sales (last 6 months for charts)
INSERT INTO `sales` (`customer_id`, `invoice_number`, `sale_date`, `subtotal`, `discount`, `gst`, `grand_total`, `payment_method`) VALUES
(1, 'INV-2026-0001', DATE_SUB(CURDATE(), INTERVAL 5 MONTH), 13499.00, 500.00, 2339.82, 15338.82, 'card'),
(2, 'INV-2026-0002', DATE_SUB(CURDATE(), INTERVAL 4 MONTH), 1299.00, 0.00, 233.82, 1532.82, 'cash'),
(3, 'INV-2026-0003', DATE_SUB(CURDATE(), INTERVAL 3 MONTH), 840.00, 50.00, 142.20, 932.20, 'upi'),
(4, 'INV-2026-0004', DATE_SUB(CURDATE(), INTERVAL 2 MONTH), 4797.00, 200.00, 829.26, 5426.26, 'cash'),
(5, 'INV-2026-0005', DATE_SUB(CURDATE(), INTERVAL 1 MONTH), 1599.00, 0.00, 287.82, 1886.82, 'card'),
(1, 'INV-2026-0006', CURDATE(), 3198.00, 100.00, 558.44, 3656.44, 'upi');

INSERT INTO `sale_items` (`sale_id`, `product_id`, `quantity`, `price`, `discount`, `total`) VALUES
(1, 1, 1, 13499.00, 500.00, 12999.00),
(2, 2, 1, 1299.00, 0.00, 1299.00),
(3, 5, 2, 420.00, 50.00, 790.00),
(4, 3, 3, 250.00, 0.00, 750.00),
(4, 8, 2, 55.00, 0.00, 110.00),
(4, 7, 1, 1599.00, 200.00, 1399.00),
(5, 7, 1, 1599.00, 0.00, 1599.00),
(6, 2, 2, 1299.00, 0.00, 2598.00),
(6, 8, 3, 55.00, 0.00, 165.00);

-- Sample purchases
INSERT INTO `purchases` (`supplier_id`, `purchase_number`, `purchase_date`, `total_amount`) VALUES
(1, 'PO-2026-0001', DATE_SUB(CURDATE(), INTERVAL 60 DAY), 52000.00),
(2, 'PO-2026-0002', DATE_SUB(CURDATE(), INTERVAL 45 DAY), 8400.00),
(3, 'PO-2026-0003', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 7500.00);

INSERT INTO `purchase_items` (`purchase_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 5, 10000.00), (1, 2, 10, 800.00), (1, 7, 5, 900.00),
(2, 5, 30, 280.00),
(3, 3, 50, 150.00);
