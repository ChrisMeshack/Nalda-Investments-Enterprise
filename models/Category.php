<?php
// models/Category.php
require_once __DIR__ . '/../config/db.php';

class Category {
    public static function getAll() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT * FROM categories");
        return $stmt->fetchAll();
    }

    public static function add($name) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        return $stmt->execute([$name]);
    }

    public static function delete($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>
