<?php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'add') {
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;

    if ($product_id) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'quantity' => $quantity
            ];
        }

        $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
        echo json_encode(['success' => true, 'cartCount' => $cartCount]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    }
    exit;
}
echo json_encode(['error' => 'Invalid request']);
?>
