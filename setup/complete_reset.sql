-- Complete database reset and setup
DROP DATABASE IF EXISTS tech_shop;
CREATE DATABASE tech_shop;
USE tech_shop;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
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
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    short_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NULL,
    sku VARCHAR(100) UNIQUE,
    stock INT DEFAULT 0,
    category_id INT,
    image VARCHAR(255),
    gallery TEXT,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    session_id VARCHAR(255) NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
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

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT,
    billing_address TEXT,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
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
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin logs table
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert admin user
INSERT INTO users (name, email, password, role, status) VALUES 
('Admin User', 'admin@techshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert test user  
INSERT INTO users (name, email, password, role, status) VALUES 
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active');

-- Insert sample categories
INSERT INTO categories (name, slug, description, status) VALUES 
('Smartphones', 'smartphones', 'Latest smartphones and mobile devices', 'active'),
('Laptops', 'laptops', 'High-performance laptops and notebooks', 'active'),
('Tablets', 'tablets', 'Tablets and iPad devices', 'active'),
('Accessories', 'accessories', 'Phone and computer accessories', 'active'),
('Gaming', 'gaming', 'Gaming consoles and accessories', 'active'),
('Audio', 'audio', 'Headphones, speakers, and audio equipment', 'active');

-- Insert sample products
INSERT INTO products (name, slug, description, short_description, price, sale_price, sku, stock, category_id, featured, status) VALUES 
('iPhone 15 Pro', 'iphone-15-pro', 'Latest iPhone with advanced features and powerful performance', 'Premium smartphone with Pro camera system', 999.00, 899.00, 'IP15P001', 50, 1, TRUE, 'active'),
('MacBook Pro 16"', 'macbook-pro-16', 'Professional laptop with M3 chip and stunning display', 'High-performance laptop for professionals', 2499.00, NULL, 'MBP16001', 25, 2, TRUE, 'active'),
('iPad Air', 'ipad-air', 'Powerful and versatile tablet for work and creativity', 'Lightweight tablet with powerful performance', 599.00, 549.00, 'IPA001', 40, 3, TRUE, 'active'),
('AirPods Pro', 'airpods-pro', 'Premium wireless earbuds with noise cancellation', 'Wireless earbuds with spatial audio', 249.00, NULL, 'APP001', 100, 6, TRUE, 'active'),
('Gaming Mouse', 'gaming-mouse', 'High-precision gaming mouse with RGB lighting', 'Professional gaming mouse', 79.00, 59.00, 'GM001', 75, 5, FALSE, 'active'),
('Wireless Charger', 'wireless-charger', 'Fast wireless charging pad for smartphones', 'Convenient wireless charging solution', 39.00, 29.00, 'WC001', 200, 4, FALSE, 'active');

-- Insert sample orders
INSERT INTO orders (order_number, user_id, status, total_amount, shipping_address, payment_method, payment_status) VALUES 
('ORD-2024-0001', 2, 'delivered', 899.00, '123 Main St, City, State 12345', 'credit_card', 'paid'),
('ORD-2024-0002', 2, 'processing', 549.00, '123 Main St, City, State 12345', 'paypal', 'paid'),
('ORD-2024-0003', 2, 'pending', 249.00, '123 Main St, City, State 12345', 'credit_card', 'pending');

-- Insert order items
INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES 
(1, 1, 1, 899.00, 899.00),
(2, 3, 1, 549.00, 549.00),
(3, 4, 1, 249.00, 249.00);

-- Insert settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('site_name', 'TechShop'),
('site_description', 'Your one-stop shop for technology'),
('contact_email', 'info@techshop.com'),
('contact_phone', '+1 (555) 123-4567'),
('currency', 'USD'),
('tax_rate', '8.5');

-- Note: Default password for both users is 'password'
SELECT 'Database setup completed successfully!' as message;
SELECT 'Admin Login: admin@techshop.com / password' as admin_credentials;
SELECT 'User Login: john@example.com / password' as user_credentials;
