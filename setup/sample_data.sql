-- Sample data for TechShop
START TRANSACTION;

-- Insert users (Passwords: admin123, user123)
-- Use a strong password hashing method in your actual application.
-- This hash is for '$2y$10$E0A.qVl3YgKmK.D9jZpZ9uY0b.PzL.XzK.YgKmK.D9jZpZ9uY0b.P' (example only)
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `email_verified`, `created_at`) VALUES
('Admin TechShop', 'admin@techshop.com', '$2y$10$E0A.qVl3YgKmK.D9jZpZ9uY0b.PzL.XzK.YgKmK.D9jZpZ9uY0b.P', 'admin', 'active', 1, NOW()),
('John Customer', 'john@example.com', '$2y$10$E0A.qVl3YgKmK.D9jZpZ9uY0b.PzL.XzK.YgKmK.D9jZpZ9uY0b.P', 'user', 'active', 1, NOW()),
('Jane Shopper', 'jane@example.com', '$2y$10$E0A.qVl3YgKmK.D9jZpZ9uY0b.PzL.XzK.YgKmK.D9jZpZ9uY0b.P', 'user', 'active', 1, NOW());

-- Insert categories
INSERT INTO `categories` (`name`, `slug`, `description`, `image`, `status`, `created_at`) VALUES
('Laptops', 'laptops', 'High-performance laptops for work, gaming, and everyday use.', 'categories/laptops.jpg', 'active', NOW()),
('Smartphones', 'smartphones', 'Latest smartphones with cutting-edge technology and features.', 'categories/smartphones.jpg', 'active', NOW()),
('Gaming Consoles', 'gaming-consoles', 'Next-generation gaming consoles and accessories.', 'categories/gaming.jpg', 'active', NOW()),
('Audio Devices', 'audio-devices', 'Headphones, earbuds, speakers, and sound systems.', 'categories/audio.jpg', 'active', NOW()),
('PC Components', 'pc-components', 'CPUs, GPUs, motherboards, RAM, and storage solutions.', NULL, 'active', NOW()),
('Accessories', 'accessories', 'Keyboards, mice, monitors, and other tech peripherals.', NULL, 'active', NOW());

-- Insert products
INSERT INTO `products` (`name`, `slug`, `description`, `short_description`, `price`, `sale_price`, `sku`, `stock`, `category_id`, `brand`, `image`, `gallery`, `featured`, `status`, `created_at`) VALUES
('ProBook X16 Laptop', 'probook-x16-laptop', 'A powerful and sleek laptop designed for professionals. Features a 16-inch QHD display, Intel Core i7 processor, 16GB RAM, and 512GB SSD. Perfect for multitasking and demanding applications.', '16" QHD, Core i7, 16GB RAM, 512GB SSD', 1299.99, 1199.99, 'LP-PBX16-001', 25, 1, 'ProBrand', 'products/probook-x16.jpg', '["products/probook-x16-gallery1.jpg", "products/probook-x16-gallery2.jpg"]', 1, 'active', NOW()),
('NovaPhone Z Pro', 'novaphone-z-pro', 'Experience the future with NovaPhone Z Pro. Stunning 6.7-inch AMOLED display, advanced triple-camera system, and lightning-fast 5G connectivity. Comes with 256GB storage.', '6.7" AMOLED, Triple Camera, 256GB, 5G', 899.00, NULL, 'SP-NPZP-002', 40, 2, 'NovaTech', 'products/novaphone-z-pro.jpg', NULL, 1, 'active', NOW()),
('GameSphere 5 Console', 'gamesphere-5-console', 'Immerse yourself in next-gen gaming with the GameSphere 5. Ultra-fast SSD, breathtaking 4K graphics at 120fps, and innovative haptic feedback controller.', 'Next-Gen 4K Gaming Console', 499.99, NULL, 'GC-GS5-003', 15, 3, 'GameSys', 'products/gamesphere-5.jpg', '["products/gamesphere-5-controller.jpg"]', 1, 'active', NOW()),
('AuraSound Elite Headphones', 'aurasound-elite-headphones', 'Premium wireless over-ear headphones with active noise cancellation, crystal-clear audio, and 30-hour battery life. Foldable design for portability.', 'ANC Wireless Over-Ear Headphones', 249.99, 199.99, 'AU-ASEH-004', 30, 4, 'AuraSound', 'products/aurasound-elite.jpg', NULL, 0, 'active', NOW()),
('CorePower Desktop CPU', 'corepower-desktop-cpu', 'Unlock extreme performance with the CorePower i9 desktop processor. 16 cores, 24 threads, and boost speeds up to 5.8GHz. Ideal for gaming and content creation.', 'i9, 16 Cores, 5.8GHz Boost', 599.00, NULL, 'CPU-CPWP-005', 20, 5, 'CorePower', 'products/corepower-cpu.jpg', NULL, 0, 'active', NOW()),
('MechType Pro Keyboard', 'mechtype-pro-keyboard', 'Mechanical keyboard with customizable RGB backlighting, durable PBT keycaps, and responsive tactile switches. Full-size layout with dedicated media controls.', 'RGB Mechanical Keyboard, Tactile Switches', 129.99, NULL, 'ACC-MTPK-006', 50, 6, 'TypeMaster', 'products/mechtype-pro.jpg', NULL, 0, 'active', NOW());

-- Insert product attributes for ProBook X16 Laptop (product_id 1)
INSERT INTO `product_attributes` (`product_id`, `attribute_name`, `attribute_value`, `created_at`) VALUES
(1, 'Display Size', '16-inch', NOW()),
(1, 'Resolution', 'QHD (2560 x 1600)', NOW()),
(1, 'Processor', 'Intel Core i7 (13th Gen)', NOW()),
(1, 'RAM', '16GB DDR5', NOW()),
(1, 'Storage', '512GB NVMe SSD', NOW()),
(1, 'Color', 'Space Gray', NOW());

-- Insert product attributes for NovaPhone Z Pro (product_id 2)
INSERT INTO `product_attributes` (`product_id`, `attribute_name`, `attribute_value`, `created_at`) VALUES
(2, 'Display Type', 'AMOLED', NOW()),
(2, 'Screen Size', '6.7-inch', NOW()),
(2, 'Main Camera', '108MP Wide', NOW()),
(2, 'Storage', '256GB UFS 4.0', NOW()),
(2, 'Color', 'Obsidian Black', NOW());


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

COMMIT;
