CREATE DATABASE IF NOT EXISTS nalda_investments;
USE nalda_investments;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    discount_percentage INT DEFAULT 0,
    is_flash_sale BOOLEAN DEFAULT 0,
    stock INT DEFAULT 0,
    serial_number VARCHAR(100) UNIQUE,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    receiver_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE manual_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    reference_number VARCHAR(100) NOT NULL,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Insert default admin and some categories
INSERT INTO users (username, email, password, role) VALUES ('admin', 'admin@nalda.com', '$2y$10$e.w2T.X.6U9f7G.q.wP3I.nN4M7f2q2O5g.q.wP3I.nN4M7f2q2O', 'admin'); -- Password is 'admin123'
INSERT INTO categories (name, description) VALUES 
('Groceries', 'Daily grocery items'), 
('Beverages', 'Drinks and juices'), 
('Household', 'Household cleaning and maintenance'), 
('Electronics', 'Gadgets and appliances'), 
('Fashion', 'Clothing and accessories'), 
('Health & Beauty', 'Cosmetics and healthcare'), 
('Baby Products', 'Items for babies'), 
('Stationery', 'Office and school supplies');

-- Example products
INSERT INTO products (category_id, name, description, price, stock, serial_number, image) VALUES 
(1, 'Premium Fresh Apples', 'Crisp and juicy apples directly from the farm.', 4.99, 100, 'APP1001', '../assets/img/Image.png'),
(2, 'Organic Orange Juice', '100% pure organic orange juice, no added sugar.', 5.49, 50, 'BEV2001', '../assets/img/image 3.png'),
(3, 'Heavy-Duty Laundry Detergent', 'Removes tough stains and leaves clothes smelling fresh.', 12.99, 30, 'HOU3001', '../assets/img/images 2.jpg'),
(4, 'Wireless Noise-Canceling Earbuds', 'High-quality sound with long battery life.', 89.99, 20, 'ELE4001', '../assets/img/images 4.jpg');
