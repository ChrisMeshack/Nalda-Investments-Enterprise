<?php
// config/db.php

define('DB_HOST', 'sql106.infinityfree.com');
define('DB_USER', 'if0_42939269');
define('DB_PASS', 'DarajaDaraja11');
define('DB_NAME', 'if0_42939269_nalda_investments');

function getDB() {
    static $pdo;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Auto-migrate tables (fails silently if already done)
            try { $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'customer') DEFAULT 'customer'"); } catch(Exception $e) {}
            try { $pdo->exec("ALTER TABLE products ADD COLUMN discount_percentage INT DEFAULT 0"); } catch(Exception $e) {}
            try { $pdo->exec("ALTER TABLE products ADD COLUMN is_flash_sale BOOLEAN DEFAULT 0"); } catch(Exception $e) {}
            try { $pdo->exec("ALTER TABLE products ADD COLUMN brand VARCHAR(255) DEFAULT NULL"); } catch(Exception $e) {}
            try { $pdo->exec("ALTER TABLE products MODIFY COLUMN image TEXT"); } catch(Exception $e) {}
            try { $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY, sender_id INT, receiver_id INT, message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
            )"); } catch(Exception $e) {}
            try { $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, user_id INT, rating INT NOT NULL CHECK(rating >= 1 AND rating <= 5),
                comment TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )"); } catch(Exception $e) {}

            // Auto-create Admins if missing
            try {
                $hash1 = password_hash('Loreen@2004', PASSWORD_BCRYPT);
                $pdo->exec("INSERT IGNORE INTO users (username, email, password, role) VALUES ('Chris Meshack', 'chrismeshackwork@gmail.com', '$hash1', 'super_admin')");
                
                $hash2 = password_hash('Kitale@2026', PASSWORD_BCRYPT);
                $pdo->exec("INSERT IGNORE INTO users (username, email, password, role) VALUES ('Regina Mwangi', 'reginamwangi@gmail.com', '$hash2', 'admin')");
                
                // If they exist but role or password is wrong, update them
                $pdo->exec("UPDATE users SET role='super_admin', password='$hash1' WHERE email='chrismeshackwork@gmail.com'");
                $pdo->exec("UPDATE users SET role='admin', password='$hash2' WHERE email='reginamwangi@gmail.com'");
            } catch(Exception $e) {}
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}
?>
