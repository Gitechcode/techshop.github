-- Sample data for TechShop

USE tech_shop;

-- Insert admin user (password: admin123)
INSERT INTO users (name, email, password, role, status, email_verified) VALUES
('Admin User', 'admin@techshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', TRUE),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', TRUE),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', TRUE);

-- Insert categories
INSERT INTO categories (name, slug, description, status) VALUES
('Smartphones', 'smartphones', 'Latest smartphones and mobile devices', 'active'),
('Laptops', 'laptops', 'Laptops and notebooks for work and gaming', 'active'),
('Tablets', 'tablets', 'Tablets and e-readers', 'active'),
('Gaming', 'gaming', 'Gaming consoles, accessories and peripherals', 'active'),
('Audio', 'audio', 'Headphones, speakers and audio equipment', 'active'),
('Accessories', 'accessories', 'Phone cases, chargers and other accessories', 'active'),
('Smart Home', 'smart-home', 'Smart home devices and IoT products', 'active'),
('Cameras', 'cameras', 'Digital cameras and photography equipment', 'active');

-- Insert sample products
INSERT INTO products (name, slug, description, short_description, price, sale_price, sku, stock, category_id, brand, featured, status) VALUES
('iPhone 15 Pro Max', 'iphone-15-pro-max', 'The most advanced iPhone ever with titanium design, A17 Pro chip, and professional camera system.', 'Latest iPhone with titanium design and A17 Pro chip', 1199.99, 1099.99, 'IP15PM-256', 25, 1, 'Apple', TRUE, 'active'),
('Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Premium Android smartphone with S Pen, advanced AI features, and exceptional camera capabilities.', 'Premium Samsung flagship with S Pen and AI features', 1299.99, NULL, 'SGS24U-512', 18, 1, 'Samsung', TRUE, 'active'),
('MacBook Pro 16-inch M3', 'macbook-pro-16-m3', 'Powerful laptop for professionals with M3 chip, stunning Liquid Retina XDR display, and all-day battery life.', 'Professional laptop with M3 chip and XDR display', 2499.99, 2299.99, 'MBP16-M3-512', 12, 2, 'Apple', TRUE, 'active'),
('Dell XPS 13 Plus', 'dell-xps-13-plus', 'Ultra-portable laptop with InfinityEdge display, premium materials, and exceptional performance.', 'Ultra-portable premium laptop with InfinityEdge display', 1399.99, NULL, 'XPS13P-512', 15, 2, 'Dell', FALSE, 'active'),
('iPad Pro 12.9-inch', 'ipad-pro-12-9', 'Most advanced iPad with M2 chip, Liquid Retina XDR display, and support for Apple Pencil.', 'Professional tablet with M2 chip and XDR display', 1099.99, 999.99, 'IPADPRO129-256', 20, 3, 'Apple', TRUE, 'active'),
('PlayStation 5', 'playstation-5', 'Next-generation gaming console with ultra-high speed SSD, ray tracing, and 3D audio.', 'Next-gen gaming console with ray tracing and SSD', 499.99, NULL, 'PS5-STD', 8, 4, 'Sony', TRUE, 'active'),
('AirPods Pro 2nd Gen', 'airpods-pro-2nd-gen', 'Premium wireless earbuds with active noise cancellation, spatial audio, and MagSafe charging.', 'Premium wireless earbuds with noise cancellation', 249.99, 199.99, 'APP2-USBC', 35, 5, 'Apple', FALSE, 'active'),
('Sony WH-1000XM5', 'sony-wh-1000xm5', 'Industry-leading noise canceling headphones with exceptional sound quality and comfort.', 'Premium noise-canceling over-ear headphones', 399.99, 349.99, 'WH1000XM5-B', 22, 5, 'Sony', FALSE, 'active');

-- Insert product attributes
INSERT INTO product_attributes (product_id, attribute_name, attribute_value) VALUES
(1, 'Storage', '256GB'),
(1, 'Color', 'Natural Titanium'),
(1, 'Display', '6.7-inch Super Retina XDR'),
(1, 'Camera', '48MP Main + 12MP Ultra Wide + 12MP Telephoto'),
(2, 'Storage', '512GB'),
(2, 'Color', 'Titanium Gray'),
(2, 'Display', '6.8-inch Dynamic AMOLED 2X'),
(2, 'Camera', '200MP Main + 50MP Periscope + 10MP Telephoto + 12MP Ultra Wide'),
(3, 'Storage', '512GB SSD'),
(3, 'Memory', '18GB Unified Memory'),
(3, 'Display', '16.2-inch Liquid Retina XDR'),
(3, 'Processor', 'Apple M3 Pro chip');

-- Insert sample reviews
INSERT INTO reviews (user_id, product_id, rating, title, comment, status) VALUES
(2, 1, 5, 'Amazing phone!', 'The iPhone 15 Pro Max is incredible. The camera quality is outstanding and the titanium build feels premium.', 'approved'),
(3, 1, 4, 'Great but expensive', 'Love the features but the price is quite high. Overall satisfied with the purchase.', 'approved'),
(2, 3, 5, 'Perfect for work', 'This MacBook Pro handles everything I throw at it. The M3 chip is blazing fast!', 'approved'),
(3, 6, 5, 'Gaming beast', 'PS5 is amazing! The graphics and loading times are incredible. Highly recommended for gamers.', 'approved'),
(2, 7, 4, 'Good sound quality', 'AirPods Pro sound great and the noise cancellation works well. Battery life could be better.', 'approved');

-- Insert settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'TechShop'),
('site_description', 'Your one-stop shop for the latest technology and electronics'),
('contact_email', 'support@techshop.com'),
('contact_phone', '+1 (555) 123-4567'),
('contact_address', '123 Tech Street, Digital City, DC 12345'),
('shipping_free_threshold', '100'),
('tax_rate', '0.08'),
('currency_symbol', '$'),
('items_per_page', '12'),
('featured_products_count', '6');

-- Insert sample coupons
INSERT INTO coupons (code, type, value, minimum_amount, usage_limit, expires_at, status) VALUES
('WELCOME10', 'percentage', 10.00, 50.00, 100, DATE_ADD(NOW(), INTERVAL 30 DAY), 'active'),
('SAVE50', 'fixed', 50.00, 200.00, 50, DATE_ADD(NOW(), INTERVAL 60 DAY), 'active'),
('NEWUSER', 'percentage', 15.00, 100.00, 200, DATE_ADD(NOW(), INTERVAL 90 DAY), 'active');
