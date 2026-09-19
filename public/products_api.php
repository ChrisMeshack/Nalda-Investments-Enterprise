<?php
// public/products_api.php
// Returns a paginated, category-filtered JSON list of products.
// Query params:
//   category_id  int|"all"   default: all
//   page         int         default: 1  (1-based)
//   limit        int         default: 20 (max 40)
//   search       string      optional full-text filter

header('Content-Type: application/json');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config/db.php';

$categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== 'all'
    ? (int) $_GET['category_id']
    : null;

$page  = max(1, (int) ($_GET['page']  ?? 1));
$limit = min(40, max(1, (int) ($_GET['limit'] ?? 20)));
$search = trim($_GET['search'] ?? '');
$offset = ($page - 1) * $limit;

try {
    $pdo = getDB();

    // Build WHERE clause
    $where  = ['1=1'];
    $params = [];

    if ($categoryId !== null) {
        $where[]  = 'category_id = ?';
        $params[] = $categoryId;
    }

    if ($search !== '') {
        $where[]  = 'name LIKE ?';
        $params[] = '%' . $search . '%';
    }

    $whereSQL = implode(' AND ', $where);

    // Total count for "has more" flag
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Paginated fetch
    $dataStmt = $pdo->prepare(
        "SELECT id, name, price, discount_percentage, is_flash_sale, image, stock
         FROM products
         WHERE $whereSQL
         ORDER BY id DESC
         LIMIT ? OFFSET ?"
    );
    // PDO bindValue needed because LIMIT/OFFSET must be integers
    $i = 1;
    foreach ($params as $v) {
        $dataStmt->bindValue($i++, $v);
    }
    $dataStmt->bindValue($i++, $limit, PDO::PARAM_INT);
    $dataStmt->bindValue($i,   $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalise each row
    $products = array_map(function ($p) {
        $images = array_map('trim', explode(',', $p['image']));
        $img    = $images[0] ?: '';

        $hasDiscount = $p['discount_percentage'] > 0;
        $salePrice   = $hasDiscount
            ? round($p['price'] * (1 - $p['discount_percentage'] / 100), 2)
            : (float) $p['price'];

        return [
            'id'                  => (int)   $p['id'],
            'name'                => $p['name'],
            'price'               => (float) $p['price'],
            'sale_price'          => $salePrice,
            'discount_percentage' => (int)   $p['discount_percentage'],
            'is_flash_sale'       => (int)   $p['is_flash_sale'] === 1,
            'image'               => $img,
            'in_stock'            => (int)   $p['stock'] > 0,
        ];
    }, $rows);

    echo json_encode([
        'success'  => true,
        'page'     => $page,
        'limit'    => $limit,
        'total'    => $total,
        'has_more' => ($offset + count($products)) < $total,
        'products' => $products,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
