<?php
// public/checkout.php
session_start();
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: shop.php");
    exit;
}

$total = 0;
foreach ($cart as $item) {
    $product = Product::getById($item['product_id']);
    if ($product) {
        $actual_price = $product['price'] * (1 - ($product['discount_percentage'] ?? 0) / 100);
        $total += $actual_price * $item['quantity'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference = $_POST['reference'] ?? '';
    
    if ($reference) {
        $pdo = getDB();
        $pdo->beginTransaction();
        try {
            // Create Order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total]);
            $order_id = $pdo->lastInsertId();
            
            // Create Order Items
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart as $item) {
                $product = Product::getById($item['product_id']);
                $actual_price = $product['price'] * (1 - ($product['discount_percentage'] ?? 0) / 100);
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $actual_price]);
            }
            
            // Create Manual Payment
            $stmt = $pdo->prepare("INSERT INTO manual_payments (order_id, reference_number) VALUES (?, ?)");
            $stmt->execute([$order_id, $reference]);
            
            $pdo->commit();
            $_SESSION['cart'] = []; // clear cart
            $success = "Order placed successfully! Admin will verify your payment.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Order placement failed: " . $e->getMessage();
        }
    } else {
        $error = "Please enter your payment reference number.";
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2>Checkout</h2>

<?php if (isset($success)): ?>
    <p style="color: green;"><?php echo $success; ?></p>
    <a href="shop.php" class="btn">Continue Shopping</a>
<?php else: ?>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <p>Total Amount Due: <strong>Ksh <?php echo number_format($total, 2); ?></strong></p>
    
    <div style="background: var(--white); padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3>Manual Payment Instructions</h3>
        <p>Please pay the total amount via Lipa na M-Pesa Pochi La Biashara:</p>
        <ul>
            <li>Number: <strong>0708072721</strong></li>
            <li>Name: <strong>Regina Mwangi</strong></li>
            <li>Location: <strong>Kitale town, Kenya</strong></li>
        </ul>
        <br>
        <form method="POST">
            <div class="form-group">
                <label for="reference">Payment Reference Number / Receipt ID</label>
                <input type="text" id="reference" name="reference" required>
            </div>
            <button type="submit" class="btn">Submit Order</button>
        </form>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
