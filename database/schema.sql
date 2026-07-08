-- Arsy Delivery Database Schema
CREATE DATABASE IF NOT EXISTS arsy_delivery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE arsy_delivery;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('client', 'admin', 'driver') DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fa-utensils',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT 'default-food.jpg',
    available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    driver_id INT NULL,
    delivery_address TEXT NOT NULL,
    delivery_lat DECIMAL(10,8) NULL,
    delivery_lng DECIMAL(11,8) NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'picked_up', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE product_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_default TINYINT(1) DEFAULT 1,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    customization TEXT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE driver_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    order_id INT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Default admin (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin Arsy', 'admin@arsy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Default driver (password: driver123)
INSERT INTO users (name, email, password, phone, role) VALUES
('Mohamed Driver', 'driver@arsy.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0612345678', 'driver');

-- Categories
INSERT INTO categories (name, icon) VALUES
('Food', 'fa-utensils'),
('Drinks', 'fa-glass-water'),
('Groceries', 'fa-basket-shopping'),
('Pharmacy', 'fa-pills'),
('Other', 'fa-box');

-- Sample products
INSERT INTO products (category_id, name, description, price, image) VALUES
(1, 'Burger Classic', 'Juicy beef burger with fries', 45.00, 'burger.jpg'),
(1, 'Pizza Margherita', 'Fresh mozzarella and basil', 55.00, 'pizza.jpg'),
(1, 'Tacos Mix', '3 tacos with guacamole', 40.00, 'tacos.jpg'),
(1, 'Pasta Carbonara', 'Creamy Italian pasta', 50.00, 'pasta.jpg'),
(2, 'Fresh Orange Juice', '100% natural orange juice', 15.00, 'juice.jpg'),
(2, 'Smoothie Bowl', 'Mixed berries smoothie', 25.00, 'smoothie.jpg'),
(3, 'Grocery Pack', 'Essential groceries bundle', 80.00, 'groceries.jpg'),
(4, 'Pain Relief Kit', 'Basic pharmacy essentials', 35.00, 'pharmacy.jpg'),
(5, 'Express Package', 'Small package delivery', 20.00, 'package.jpg');
