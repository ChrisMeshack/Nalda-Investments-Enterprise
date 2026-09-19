<?php
require_once '../config/db.php';

$pdo = getDB();

echo "<h1>Applying Database Migrations...</h1><ul>";

try {
    // 1. Update users role enum
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'customer') DEFAULT 'customer'");
    echo "<li>Updated 'users' table role ENUM.</li>";

    // Create a default super admin if none exists
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'super_admin'");
    if ($stmt->rowCount() == 0) {
        $password = password_hash('superadmin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, email, password, role) VALUES ('superadmin', 'superadmin@nalda.com', '$password', 'super_admin')");
        echo "<li>Created default super_admin account (username: superadmin, password: superadmin123).</li>";
    }

} catch (PDOException $e) {
    echo "<li>Error updating users table: " . $e->getMessage() . "</li>";
}

try {
    // 2. Add columns to products table
    $pdo->exec("ALTER TABLE products ADD COLUMN discount_percentage INT DEFAULT 0");
    echo "<li>Added 'discount_percentage' to products.</li>";
} catch (PDOException $e) {
    echo "<li>discount_percentage may already exist: " . $e->getMessage() . "</li>";
}

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN is_flash_sale BOOLEAN DEFAULT 0");
    echo "<li>Added 'is_flash_sale' to products.</li>";
} catch (PDOException $e) {
    echo "<li>is_flash_sale may already exist: " . $e->getMessage() . "</li>";
}

try {
    // 3. Create messages table
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT,
        receiver_id INT,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "<li>Created 'messages' table.</li>";
} catch (PDOException $e) {
    echo "<li>Error creating messages table: " . $e->getMessage() . "</li>";
}

echo "</ul><p>Migration complete! <a href='index.php'>Return to Home</a></p>";
?>
