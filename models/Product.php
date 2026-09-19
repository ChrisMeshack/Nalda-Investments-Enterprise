<?php
// models/Product.php
require_once __DIR__ . '/../config/db.php';

class Product {
    public static function getAll($filters = []) {
        $pdo = getDB();
        $query = "SELECT * FROM products WHERE 1=1";
        $params = [];

        if (!empty($filters['category_id'])) {
            $query .= " AND category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['search'])) {
            $query .= " AND name LIKE ?";
            $params[] = "%" . $filters['search'] . "%";
        }
        if (!empty($filters['brand'])) {
            $query .= " AND brand = ?";
            $params[] = $filters['brand'];
        }
        if (!empty($filters['min_price'])) {
            $query .= " AND price >= ?";
            $params[] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $query .= " AND price <= ?";
            $params[] = $filters['max_price'];
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getBrands() {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function getById($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function add($data) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO products (category_id, name, description, price, discount_percentage, is_flash_sale, stock, serial_number, image, brand) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['description'],
            $data['price'],
            $data['discount_percentage'] ?? 0,
            $data['is_flash_sale'] ?? 0,
            $data['stock'],
            $data['serial_number'],
            $data['image'],
            $data['brand'] ?? null
        ]);
    }

    public static function update($id, $data) {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, discount_percentage = ?, is_flash_sale = ?, stock = ?, serial_number = ?, image = ?, brand = ? WHERE id = ?");
        return $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['description'],
            $data['price'],
            $data['discount_percentage'] ?? 0,
            $data['is_flash_sale'] ?? 0,
            $data['stock'],
            $data['serial_number'],
            $data['image'],
            $data['brand'] ?? null,
            $id
        ]);
    }

    public static function delete($id) {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>
