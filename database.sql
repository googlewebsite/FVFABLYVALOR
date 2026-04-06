-- Jemimah Fashion E-commerce Database
-- Database: dada Structure
-- MySQL Database Schema

CREATE DATABASE IF NOT EXISTS dada;
USE dada;

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    sizes JSON,
    images JSON,
    video VARCHAR(255),
    mrp_price DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    product_details JSON NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
    utr_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    customer_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    utr_number VARCHAR(50) UNIQUE,
    status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    size VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Banners/Posters table
CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    position ENUM('home', 'product', 'all') DEFAULT 'home',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Insert default admin user
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.YE4yKoUa6aeyRcADsab.H8d2y3fVj9GQ9vB4l3a1yG7'); -- Password: 987654321

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('business_name', 'Jemimah Fashion'),
('upi_id', 'jesuslifemylife@okaxis'),
('contact_phone', '+91 98765 43210'),
('contact_email', 'info@jemimahfashion.com'),
('contact_address', '123 Fashion Street, Mumbai, Maharashtra 400001'),
('shipping_charge', '50'),
('free_shipping_above', '999');

-- Insert sample data
INSERT INTO products (title, description, short_description, sizes, images, mrp_price, selling_price, stock_quantity) VALUES
('Classic T-Shirt', 'Premium quality cotton t-shirt with comfortable fit', 'Comfortable and stylish t-shirt', '["S", "M", "L", "XL"]', '["https://via.placeholder.com/300x300"]', 999.00, 699.00, 50),
('Denim Jeans', 'Classic fit denim jeans with modern styling', 'Stylish denim jeans for all occasions', '["28", "30", "32", "34", "36"]', '["https://via.placeholder.com/300x300"]', 1999.00, 1499.00, 30),
('Casual Shirt', 'Perfect casual shirt for everyday wear', 'Comfortable casual shirt', '["S", "M", "L", "XL"]', '["https://via.placeholder.com/300x300"]', 1299.00, 999.00, 25);
