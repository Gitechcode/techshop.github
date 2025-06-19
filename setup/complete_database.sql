-- Create database
CREATE DATABASE IF NOT EXISTS tech_shop;
USE tech_shop;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    description TEXT,
    parent_id INT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE,
    description TEXT,
    short_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    sku VARCHAR(100) UNIQUE,
    stock INT DEFAULT 0,
    category_id INT,
    brand VARCHAR(100),
    image VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_method VARCHAR(50),
    shipping_address TEXT,
    billing_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(200) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(255),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT IGNORE INTO users (name, email, password, role, status) VALUES 
('Admin User', 'admin@techshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active');

-- Insert sample categories
INSERT IGNORE INTO categories (id, name, slug, description, status) VALUES 
(1, 'Laptops', 'laptops', 'High-performance laptops for work and gaming', 'active'),
(2, 'Smartphones', 'smartphones', 'Latest smartphones with cutting-edge technology', 'active'),
(3, 'Tablets', 'tablets', 'Portable tablets for productivity and entertainment', 'active'),
(4, 'Accessories', 'accessories', 'Tech accessories and peripherals', 'active');

-- Insert sample products
INSERT IGNORE INTO products (id, name, slug, description, short_description, price, sku, stock, category_id, brand, featured, status) VALUES 
(1, 'MacBook Pro 16"', 'macbook-pro-16', 'Powerful laptop with M2 chip, perfect for professionals and creatives.', 'High-performance laptop with M2 chip', 2499.00, 'MBP-16-001', 10, 1, 'Apple', TRUE, 'active'),
(2, 'iPhone 15 Pro', 'iphone-15-pro', 'Latest iPhone with advanced camera system and A17 Pro chip.', 'Latest iPhone with A17 Pro chip', 999.00, 'IP15P-001', 25, 2, 'Apple', TRUE, 'active'),
(3, 'iPad Air', 'ipad-air', 'Versatile tablet with M1 chip for work and creativity.', 'Powerful tablet with M1 chip', 599.00, 'IPA-001', 15, 3, 'Apple', TRUE, 'active'),
(4, 'Dell XPS 13', 'dell-xps-13', 'Ultra-portable laptop with stunning display and performance.', 'Ultra-portable premium laptop', 1299.00, 'DXS13-001', 8, 1, 'Dell', FALSE, 'active'),
(5, 'Samsung Galaxy S24', 'samsung-galaxy-s24', 'Flagship Android phone with AI-powered features.', 'AI-powered flagship smartphone', 899.00, 'SGS24-001', 20, 2, 'Samsung', TRUE, 'active'),
(6, 'AirPods Pro', 'airpods-pro', 'Premium wireless earbuds with active noise cancellation.', 'Wireless earbuds with ANC', 249.00, 'APP-001', 30, 4, 'Apple', FALSE, 'active');

-- Insert sample orders
INSERT IGNORE INTO orders (id, order_number, user_id, total_amount, status, payment_status, payment_method) VALUES 
(1, 'ORD-2024-0001', 2, 999.00, 'delivered', 'paid', 'credit_card'),
(2, 'ORD-2024-0002', 2, 2499.00, 'processing', 'paid', 'paypal'),
(3, 'ORD-2024-0003', 2, 249.00, 'pending', 'pending', 'credit_card');

-- Insert sample order items
INSERT IGNORE INTO order_items (order_id, product_id, product_name, product_price, quantity, total) VALUES 
(1, 2, 'iPhone 15 Pro', 999.00, 1, 999.00),
(2, 1, 'MacBook Pro 16"', 2499.00, 1, 2499.00),
(3, 6, 'AirPods Pro', 249.00, 1, 249.00);

-- Insert default settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('site_name', 'TechShop'),
('site_email', 'info@techshop.com'),
('currency', 'USD'),
('tax_rate', '8.5');
