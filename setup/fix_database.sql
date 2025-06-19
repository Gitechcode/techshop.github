-- Fix database schema issues
USE tech_shop;

-- Add missing slug field to categories table
ALTER TABLE categories ADD COLUMN slug VARCHAR(100) UNIQUE AFTER name;

-- Add missing slug field to products table  
ALTER TABLE products ADD COLUMN slug VARCHAR(200) UNIQUE AFTER name;

-- Update existing categories with slugs
UPDATE categories SET slug = LOWER(REPLACE(REPLACE(name, ' ', '-'), '&', 'and')) WHERE slug IS NULL;

-- Update existing products with slugs
UPDATE products SET slug = LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), '&', 'and'), '/', '-')) WHERE slug IS NULL;

-- Make sure we have some basic categories
INSERT IGNORE INTO categories (name, slug, description, status) VALUES
('Laptops', 'laptops', 'Portable computers and notebooks', 'active'),
('Smartphones', 'smartphones', 'Mobile phones and accessories', 'active'),
('Tablets', 'tablets', 'Tablet computers and e-readers', 'active'),
('Accessories', 'accessories', 'Computer and phone accessories', 'active'),
('Gaming', 'gaming', 'Gaming laptops, consoles and accessories', 'active');

-- Add some sample products if none exist
INSERT IGNORE INTO products (name, slug, description, price, category_id, stock, status, featured) VALUES
('MacBook Pro 16"', 'macbook-pro-16', 'Powerful laptop for professionals', 2499.00, 1, 10, 'active', 1),
('iPhone 15 Pro', 'iphone-15-pro', 'Latest iPhone with advanced features', 999.00, 2, 25, 'active', 1),
('iPad Air', 'ipad-air', 'Lightweight tablet for work and play', 599.00, 3, 15, 'active', 1),
('Wireless Mouse', 'wireless-mouse', 'Ergonomic wireless mouse', 29.99, 4, 50, 'active', 0),
('Gaming Headset', 'gaming-headset', 'High-quality gaming headset', 79.99, 5, 30, 'active', 0);
