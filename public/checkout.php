<?php
// public/checkout.php
session_start();
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: shop.php');
    exit;
}

// Calculate total
$total = 0;
foreach ($cart as $item) {
    $product = Product::getById($item['product_id']);
    if ($product) {
        $actual = $product['price'] * (1 - ($product['discount_percentage'] ?? 0) / 100);
        $total += $actual * $item['quantity'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = trim($_POST['reference'] ?? '');

    if ($reference === '') {
        $error = 'Please enter the M-Pesa transaction reference number.';
    } else {
        $pdo = getDB();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total]);
            $order_id = $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
            );
            foreach ($cart as $item) {
                $p      = Product::getById($item['product_id']);
                $price  = $p['price'] * (1 - ($p['discount_percentage'] ?? 0) / 100);
                $itemStmt->execute([$order_id, $item['product_id'], $item['quantity'], $price]);
            }

            $pdo->prepare("INSERT INTO manual_payments (order_id, reference_number) VALUES (?, ?)")
                ->execute([$order_id, $reference]);

            $pdo->commit();
            $_SESSION['cart'] = [];
            $success = 'Order placed! Your payment is under review. We\'ll confirm it shortly.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Order could not be placed. Please try again.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">&#128176; Checkout</h1>

<div class="checkout-panel">

    <?php if (isset($success)): ?>
        <div class="alert alert-success">&#10003; <?php echo htmlspecialchars($success); ?></div>
        <a href="shop.php" class="btn btn-primary btn-full">Continue Shopping</a>

    <?php else: ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">&#9888; <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Order total -->
        <div class="checkout-total">
            <span>Amount Due</span>
            <strong>Ksh <?php echo number_format($total, 2); ?></strong>
        </div>

        <!-- M-Pesa instructions -->
        <div class="mpesa-instructions">
            <h3>&#128247; Pay via M-Pesa Pochi La Biashara</h3>
            <ul>
                <li>Paybill / Pochi Number: <strong>0708072721</strong></li>
                <li>Account Name: <strong>Regina Mwangi</strong></li>
            </ul>
        </div>

        <!-- Reference form -->
        <form method="POST">
            <div class="form-group">
                <label for="reference">M-Pesa Transaction ID / Reference Number</label>
                <input type="text" id="reference" name="reference" required
                       placeholder="e.g. QHJ4K8A9LM"
                       value="<?php echo htmlspecialchars($_POST['reference'] ?? ''); ?>">
                <small class="form-hint">Enter the confirmation code from your M-Pesa message.</small>
            </div>
            <button type="submit" class="btn btn-primary btn-full">&#10003; Submit Order</button>
        </form>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
