<?php
// models/Category.php
require_once __DIR__ . '/../config/db.php';

class Category {
    public static function getAll() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT * FROM categories");
        return $stmt->fetchAll();
    }
}
?>
