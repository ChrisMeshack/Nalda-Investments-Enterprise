<?php
// public/cart.php
session_start();
require_once __DIR__ . '/../models/Product.php';

// ── Handle POST (quantity update) before any output ───────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product_id'])) {
    $update_id = $_POST['update_product_id'];
    $new_qty   = max(1, (int) $_POST['quantity']);
    foreach ($_SESSION['cart'] as &$cItem) {
        if ($cItem['product_id'] == $update_id) {
            $cItem['quantity'] = $new_qty;
            break;
        }
    }
    unset($cItem);
    header('Location: cart.php');
    exit;
}

// ── Handle GET remove ─────────────────────────────────────────
if (isset($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    $_SESSION['cart'] = array_values(array_filter(
        $_SESSION['cart'] ?? [],
        fn($item) => $item['product_id'] != $remove_id
    ));
    header('Location: cart.php');
    exit;
}

// ── Build cart items ──────────────────────────────────────────
$cart      = $_SESSION['cart'] ?? [];
$cartItems = [];
$total     = 0;

foreach ($cart as $item) {
    $product = Product::getById($item['product_id']);
    if ($product) {
        $actual_price          = $product['price'] * (1 - ($product['discount_percentage'] ?? 0) / 100);
        $product['actual_price'] = $actual_price;
        $product['quantity']     = $item['quantity'];
        $product['subtotal']     = $actual_price * $item['quantity'];
        $total                  += $product['subtotal'];
        $cartItems[]             = $product;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2 class="page-title">🛒 Shopping Cart</h2>

<?php if (empty($cartItems)): ?>
    <div class="empty-state">
        <p style="font-size:2.5rem;">🛒</p>
        <p>Your cart is empty.</p>
        <a href="shop.php" class="btn btn-primary" style="margin-top:16px;">Start Shopping</a>
    </div>
<?php else: ?>

    <!-- ── Cart items list ── -->
    <div class="cart-list">
        <?php foreach ($cartItems as $item):
            $images      = explode(',', $item['image']);
            $first_image = trim($images[0]) ?: 'https://via.placeholder.com/100?text=No+Image';
        ?>
        <div class="cart-row">

            <!-- Image + Name -->
            <a href="product.php?id=<?php echo $item['id']; ?>" class="cart-item-info">
                <img
                    src="<?php echo htmlspecialchars($first_image); ?>"
                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                    onerror="this.src='https://via.placeholder.com/100?text=No+Image'">
                <span class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></span>
            </a>

            <!-- Price -->
            <div class="cart-cell" data-label="Price">
                <span class="cart-price">Ksh <?php echo number_format($item['actual_price'], 2); ?></span>
            </div>

            <!-- Quantity update form -->
            <div class="cart-cell" data-label="Qty">
                <form method="POST" action="cart.php" class="cart-qty-form">
                    <input type="hidden" name="update_product_id" value="<?php echo $item['id']; ?>">
                    <input type="number"
                           name="quantity"
                           value="<?php echo $item['quantity']; ?>"
                           min="1"
                           max="<?php echo $item['stock']; ?>"
                           class="cart-qty-input"
                           aria-label="Quantity">
                    <button type="submit" class="btn btn-sm">Update</button>
                </form>
            </div>

            <!-- Subtotal -->
            <div class="cart-cell" data-label="Subtotal">
                <span class="cart-subtotal">Ksh <?php echo number_format($item['subtotal'], 2); ?></span>
            </div>

            <!-- Remove -->
            <div class="cart-cell">
                <a href="?remove=<?php echo $item['id']; ?>"
                   class="btn btn-danger btn-sm cart-remove"
                   aria-label="Remove <?php echo htmlspecialchars($item['name']); ?>">✕ Remove</a>
            </div>

        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Totals + checkout ── -->
    <div class="cart-summary">
        <div class="cart-total-row">
            <span>Total</span>
            <strong class="cart-total-amount">Ksh <?php echo number_format($total, 2); ?></strong>
        </div>
        <a href="checkout.php" class="btn btn-primary cart-checkout-btn">Proceed to Checkout →</a>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
