-- Complete Database Reset Commands
-- Run these commands in MySQL command line or phpMyAdmin

-- Step 1: Drop the existing database completely
DROP DATABASE IF EXISTS tech_shop;

-- Step 2: Create fresh database
CREATE DATABASE tech_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Step 3: Use the database
USE tech_shop;

-- Step 4: Create all tables with proper structure

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    postal_code VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    parent_id INT,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    short_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2),
    sku VARCHAR(100) UNIQUE,
    stock INT DEFAULT 0,
    category_id INT,
    brand VARCHAR(100),
    weight DECIMAL(8,2),
    dimensions VARCHAR(100),
    image VARCHAR(255),
    gallery TEXT,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    meta_title VARCHAR(200),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(100),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    shipping_name VARCHAR(100) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(50) NOT NULL,
    shipping_postal VARCHAR(20) NOT NULL,
    shipping_phone VARCHAR(20),
    billing_name VARCHAR(100) NOT NULL,
    billing_address TEXT NOT NULL,
    billing_city VARCHAR(50) NOT NULL,
    billing_postal VARCHAR(20) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    tracking_number VARCHAR(100),
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Admin logs table
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Step 5: Insert admin user
INSERT INTO users (name, email, password, role, status, created_at) VALUES 
('Admin User', 'admin@techshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', NOW());

-- Step 6: Insert test user
INSERT INTO users (name, email, password, role, status, created_at) VALUES 
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active', NOW());

-- Step 7: Insert categories
INSERT INTO categories (name, slug, description, status) VALUES
('Laptops', 'laptops', 'High-performance laptops for work and gaming', 'active'),
('Smartphones', 'smartphones', 'Latest smartphones with cutting-edge technology', 'active'),
('Accessories', 'accessories', 'Tech accessories and peripherals', 'active'),
('Gaming', 'gaming', 'Gaming laptops and accessories', 'active'),
('Audio', 'audio', 'Headphones, speakers, and audio equipment', 'active');

-- Step 8: Insert products
INSERT INTO products (name, slug, description, short_description, price, sale_price, sku, stock, category_id, brand, image, featured, status) VALUES
('MacBook Pro 16"', 'macbook-pro-16', 'The most powerful MacBook Pro ever with M2 chip', 'High-performance laptop for professionals', 2499.00, 2299.00, 'MBP16-001', 15, 1, 'Apple', 'macbook-pro.jpg', 1, 'active'),
('iPhone 15 Pro', 'iphone-15-pro', 'iPhone 15 Pro with titanium design and A17 Pro chip', 'Premium smartphone with titanium design', 999.00, NULL, 'IP15P-001', 25, 2, 'Apple', 'iphone-15-pro.jpg', 1, 'active'),
('AirPods Pro', 'airpods-pro-2nd-gen', 'AirPods Pro with H2 chip and noise cancellation', 'Premium wireless earbuds', 249.00, 199.00, 'APP2-001', 50, 3, 'Apple', 'airpods-pro.jpg', 1, 'active'),
('Dell XPS 13', 'dell-xps-13', 'Premium ultrabook with InfinityEdge display', 'Ultra-portable premium laptop', 1299.00, NULL, 'DXS13-001', 20, 1, 'Dell', 'dell-xps-13.jpg', 0, 'active'),
('Samsung Galaxy S24', 'samsung-galaxy-s24-ultra', 'Galaxy S24 Ultra with advanced camera system', 'Premium Android smartphone', 1199.00, 1099.00, 'SGS24U-001', 18, 2, 'Samsung', 'galaxy-s24-ultra.jpg', 1, 'active');

-- Step 9: Insert sample orders for dashboard data
INSERT INTO orders (user_id, order_number, total_amount, shipping_amount, tax_amount, shipping_name, shipping_address, shipping_city, shipping_postal, billing_name, billing_address, billing_city, billing_postal, payment_method, payment_status, status, created_at) VALUES
(2, 'ORD-001', 2299.00, 0, 183.92, 'John Doe', '123 Main St', 'New York', '10001', 'John Doe', '123 Main St', 'New York', '10001', 'credit_card', 'paid', 'delivered', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(2, 'ORD-002', 999.00, 15.00, 79.92, 'John Doe', '123 Main St', 'New York', '10001', 'John Doe', '123 Main St', 'New York', '10001', 'paypal', 'paid', 'shipped', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'ORD-003', 249.00, 0, 19.92, 'John Doe', '123 Main St', 'New York', '10001', 'John Doe', '123 Main St', 'New York', '10001', 'credit_card', 'paid', 'processing', NOW());

-- Step 10: Insert order items
INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES
(1, 1, 1, 2299.00, 2299.00),
(2, 2, 1, 999.00, 999.00),
(3, 3, 1, 199.00, 199.00);

-- Step 11: Insert settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'TechShop'),
('site_email', 'info@techshop.com'),
('site_phone', '+1 (555) 123-4567'),
('currency', 'USD'),
('tax_rate', '8.0'),
('shipping_rate', '15.00'),
('free_shipping_minimum', '100.00');
