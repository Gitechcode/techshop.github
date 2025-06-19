-- TechShop Complete Database Setup: Schema + Sample Data
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Drop existing tables if they exist (ensures a clean slate)
DROP TABLE IF EXISTS `admin_logs`;
DROP TABLE IF EXISTS `newsletter_subscribers`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `product_attributes`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

-- Schema Definition --

-- Users table
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products table
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `category_id` int(11) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gallery` text DEFAULT NULL, -- JSON array of image filenames
  `featured` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  UNIQUE KEY `sku` (`sku`),
  KEY `category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`featured`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product attributes table
CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart table
CREATE TABLE `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `shipping_name` varchar(100) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(50) NOT NULL,
  `shipping_postal` varchar(20) NOT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `billing_name` varchar(100) NOT NULL,
  `billing_address` text NOT NULL,
  `billing_city` varchar(50) NOT NULL,
  `billing_postal` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL, -- Price at the time of order
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT -- Prevent product deletion if in an order
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews table
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL, -- Optional link to order
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `order_id` (`order_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wishlist table
CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coupons table
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT 0.00,
  `usage_limit_per_coupon` int(11) DEFAULT NULL, -- Total times this coupon can be used
  `usage_limit_per_user` int(11) DEFAULT 1, -- Times a single user can use this coupon
  `used_count` int(11) DEFAULT 0,
  `expires_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact messages table
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `admin_reply` text DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL, -- Admin user ID
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `replied_by` (`replied_by`),
  CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter subscribers table
CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `status` enum('active','unsubscribed','pending') DEFAULT 'pending', -- Added pending for double opt-in
  `token` varchar(100) DEFAULT NULL, -- For verification
  `subscribed_at` timestamp NULL DEFAULT current_timestamp(),
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin logs table
CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL, -- Increased length for more descriptive actions
  `target_type` varchar(50) DEFAULT NULL, -- e.g., 'product', 'user', 'category'
  `target_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL, -- Can store JSON or descriptive text
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Sample Data Insertion --

-- Insert users (Passwords: admin123, user123)
-- IMPORTANT: These are weak passwords for demonstration. Use a strong password hashing method (like password_hash() in PHP)
-- and ensure users change them. The hash below is for 'admin123' and 'user123' using default PHP password_hash.
-- For 'admin123': $2y$10$E0A.qVl3YgKmK.D9jZpZ9uY0b.PzL.XzK.YgKmK.D9jZpZ9uY0b.P (Example, generate your own)
-- For 'user123':  $2y$10$anotherHashForUser123... (Example, generate your own)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `email_verified`, `created_at`) VALUES
('Admin TechShop', 'admin@techshop.com', '$2y$10$E0A.qVl3YgKmK.D9jZpZ9uY0b.PzL.XzK.YgKmK.D9jZpZ9uY0b.P', 'admin', 'active', 1, NOW()),
('John Customer', 'john@example.com', '$2y$10$anotherHashForUser123example', 'user', 'active', 1, NOW()),
('Jane Shopper', 'jane@example.com', '$2y$10$yetAnotherHashForJaneShopper', 'user', 'active', 1, NOW());

-- Insert categories
INSERT INTO `categories` (`name`, `slug`, `description`, `image`, `status`, `created_at`) VALUES
('Laptops & Ultrabooks', 'laptops-ultrabooks', 'High-performance laptops for work, gaming, and everyday use.', 'categories/laptops.jpg', 'active', NOW()),
('Smartphones & Devices', 'smartphones-devices', 'Latest smartphones with cutting-edge technology and features.', 'categories/smartphones.jpg', 'active', NOW()),
('Gaming Consoles & Gear', 'gaming-consoles-gear', 'Next-generation gaming consoles and accessories.', 'categories/gaming.jpg', 'active', NOW()),
('Audio & Sound Systems', 'audio-sound-systems', 'Headphones, earbuds, speakers, and sound systems.', 'categories/audio.jpg', 'active', NOW()),
('PC Components & Upgrades', 'pc-components-upgrades', 'CPUs, GPUs, motherboards, RAM, and storage solutions.', NULL, 'active', NOW()),
('Peripherals & Accessories', 'peripherals-accessories', 'Keyboards, mice, monitors, and other tech peripherals.', NULL, 'active', NOW());

-- Insert products
INSERT INTO `products` (`name`, `slug`, `description`, `short_description`, `price`, `sale_price`, `sku`, `stock`, `category_id`, `brand`, `image`, `gallery`, `featured`, `status`, `created_at`) VALUES
('ProBook X16 Laptop (2025 Model)', 'probook-x16-laptop-2025', 'A powerful and sleek laptop designed for professionals. Features a 16-inch QHD+ display, Intel Core Ultra 7 processor, 32GB RAM, and 1TB Gen4 SSD. Perfect for multitasking and demanding applications.', '16" QHD+, Core Ultra 7, 32GB RAM, 1TB SSD', 1599.99, 1499.99, 'LP-PBX16-2025', 25, 1, 'ProBrand', 'products/probook-x16.jpg', '["products/probook-x16-gallery1.jpg", "products/probook-x16-gallery2.jpg"]', 1, 'active', NOW()),
('NovaPhone Z Pro Max', 'novaphone-z-pro-max', 'Experience the future with NovaPhone Z Pro Max. Stunning 6.8-inch Dynamic AMOLED 2X display, advanced quad-camera system with 200MP sensor, and lightning-fast 5G connectivity. Comes with 512GB storage.', '6.8" AMOLED, Quad Camera 200MP, 512GB, 5G', 1199.00, NULL, 'SP-NPZPM-002', 40, 2, 'NovaTech', 'products/novaphone-z-pro.jpg', NULL, 1, 'active', NOW()),
('GameSphere 5 Pro Console', 'gamesphere-5-pro-console', 'Immerse yourself in next-gen gaming with the GameSphere 5 Pro. Ultra-fast custom SSD, breathtaking 8K graphics support, and innovative haptic feedback controller with adaptive triggers.', 'Next-Gen 8K Capable Gaming Console', 599.99, NULL, 'GC-GS5P-003', 15, 3, 'GameSys', 'products/gamesphere-5.jpg', '["products/gamesphere-5-controller.jpg"]', 1, 'active', NOW()),
('AuraSound Elite NC Headphones', 'aurasound-elite-nc-headphones', 'Premium wireless over-ear headphones with industry-leading active noise cancellation, crystal-clear Hi-Res audio, and 40-hour battery life. Foldable design for portability.', 'ANC Wireless Hi-Res Over-Ear Headphones', 349.99, 299.99, 'AU-ASENCH-004', 30, 4, 'AuraSound', 'products/aurasound-elite.jpg', NULL, 0, 'active', NOW()),
('CorePower X9 Desktop CPU', 'corepower-x9-desktop-cpu', 'Unlock extreme performance with the CorePower X9 desktop processor. 24 cores, 32 threads, and boost speeds up to 6.0GHz. Ideal for professional gaming and content creation.', 'X9, 24 Cores, 6.0GHz Boost', 799.00, NULL, 'CPU-CPWPX9-005', 20, 5, 'CorePower', 'products/corepower-cpu.jpg', NULL, 0, 'active', NOW()),
('MechType Pro Wireless Keyboard', 'mechtype-pro-wireless-keyboard', 'Wireless mechanical keyboard with customizable RGB backlighting, hot-swappable switches, durable PBT keycaps, and responsive tactile switches. Full-size layout with dedicated media controls and volume knob.', 'RGB Wireless Mechanical Keyboard, Hot-Swappable', 159.99, NULL, 'ACC-MTPWK-006', 50, 6, 'TypeMaster', 'products/mechtype-pro.jpg', NULL, 0, 'active', NOW());

-- Insert product attributes for ProBook X16 Laptop (product_id 1)
INSERT INTO `product_attributes` (`product_id`, `attribute_name`, `attribute_value`, `created_at`) VALUES
(1, 'Display Size', '16-inch QHD+', NOW()),
(1, 'Processor', 'Intel Core Ultra 7', NOW()),
(1, 'RAM', '32GB LPDDR5X', NOW()),
(1, 'Storage', '1TB NVMe Gen4 SSD', NOW()),
(1, 'Graphics', 'Integrated Intel Arc Graphics', NOW()),
(1, 'Color', 'Lunar Gray', NOW());

-- Insert product attributes for NovaPhone Z Pro Max (product_id 2)
INSERT INTO `product_attributes` (`product_id`, `attribute_name`, `attribute_value`, `created_at`) VALUES
(2, 'Display Type', 'Dynamic AMOLED 2X, 120Hz', NOW()),
(2, 'Screen Size', '6.8-inch', NOW()),
(2, 'Main Camera', '200MP Wide + 12MP Ultrawide + 10MP Telephoto (3x) + 10MP Telephoto (10x)', NOW()),
(2, 'Storage', '512GB UFS 4.0', NOW()),
(2, 'Battery', '5000 mAh', NOW()),
(2, 'Color', 'Phantom Black', NOW());


-- Insert sample settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `created_at`) VALUES
('site_name', 'TechShop', NOW()),
('site_tagline', 'Your Ultimate Destination for Tech Gadgets!', NOW()),
('admin_email', 'admin@techshop.com', NOW()),
('contact_email', 'support@techshop.com', NOW()),
('contact_phone', '+1-800-555-TECH', NOW()),
('address', '123 Innovation Drive, Tech City, TX 75001', NOW()),
('currency_symbol', '$', NOW()),
('currency_code', 'USD', NOW()),
('products_per_page', '12', NOW()),
('default_sort_order', 'newest', NOW()),
('enable_reviews', '1', NOW()),
('auto_approve_reviews', '0', NOW()),
('shipping_flat_rate', '9.99', NOW()),
('free_shipping_threshold', '99.00', NOW()),
('tax_rate_percentage', '7.5', NOW()); -- Example: 7.5%

-- Insert sample coupon
INSERT INTO `coupons` (`code`, `type`, `value`, `minimum_amount`, `usage_limit_per_coupon`, `expires_at`, `status`, `created_at`) VALUES
('WELCOME15', 'percentage', 15.00, 50.00, 100, DATE_ADD(NOW(), INTERVAL 30 DAY), 'active', NOW()),
('SAVE20NOW', 'fixed', 20.00, 100.00, 50, DATE_ADD(NOW(), INTERVAL 60 DAY), 'active', NOW());

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
