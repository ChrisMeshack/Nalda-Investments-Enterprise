<?php
// public/product.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

$product_id = (int) ($_GET['id'] ?? 0);
if (!$product_id) {
    header('Location: shop.php');
    exit;
}

$product = Product::getById($product_id);
if (!$product) {
    header('Location: shop.php');
    exit;
}

$pdo = getDB();
$msg_type = 'info';
$msg = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
    if (!isset($_SESSION['user_id'])) {
        $msg = 'You must be logged in to leave a review.';
        $msg_type = 'error';
    } else {
        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if ($rating >= 1 && $rating <= 5) {
            $checkStmt = $pdo->prepare(
                "SELECT DATEDIFF(NOW(), o.created_at) AS days_since
                 FROM orders o
                 JOIN order_items oi ON o.id = oi.order_id
                 WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'verified'
                 ORDER BY o.created_at ASC LIMIT 1"
            );
            $checkStmt->execute([$_SESSION['user_id'], $product_id]);
            $purchase = $checkStmt->fetch();

            if (!$purchase) {
                $msg = 'You can only review products you have purchased.';
                $msg_type = 'error';
            } elseif ($purchase['days_since'] < 7) {
                $msg = 'You can leave a review ' . (7 - $purchase['days_since']) . ' day(s) after your purchase.';
                $msg_type = 'warning';
            } else {
                try {
                    $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)")
                        ->execute([$product_id, $_SESSION['user_id'], $rating, $comment]);
                    $msg = 'Thank you! Your review has been submitted.';
                    $msg_type = 'success';
                    // Reload to prevent re-submit on refresh
                    header("Location: product.php?id=$product_id&reviewed=1");
                    exit;
                } catch (Exception $e) {
                    $msg = 'You may have already reviewed this product.';
                    $msg_type = 'error';
                }
            }
        } else {
            $msg = 'Please select a rating between 1 and 5.';
            $msg_type = 'error';
        }
    }
}

if (isset($_GET['reviewed'])) {
    $msg = 'Thank you! Your review has been submitted.';
    $msg_type = 'success';
}

// Fetch reviews
$stmt = $pdo->prepare(
    "SELECT r.*, u.username FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.product_id = ? ORDER BY r.created_at DESC"
);
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

// Average rating
$avg_rating = 0;
if (!empty($reviews)) {
    $avg_rating = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
}

// Images
$images     = array_filter(array_map('trim', explode(',', $product['image'])));
$main_image = $images[0] ?? 'https://via.placeholder.com/600x600?text=No+Image';

// Prices
$hasDiscount = $product['discount_percentage'] > 0;
$salePrice   = $hasDiscount
    ? $product['price'] * (1 - $product['discount_percentage'] / 100)
    : $product['price'];

include __DIR__ . '/../includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="index.php">Home</a> ›
    <a href="shop.php">Shop</a> ›
    <span><?php echo htmlspecialchars($product['name']); ?></span>
</nav>

<?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?>">
        <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<!-- ── Product detail panel ── -->
<div class="product-detail-panel">

    <!-- Left: images -->
    <div class="product-detail-img-col">

        <?php if ($product['is_flash_sale'] || $hasDiscount): ?>
        <div class="ribbon<?php echo $product['is_flash_sale'] ? ' flash' : ''; ?>">
            <?php echo $product['is_flash_sale'] ? '⚡ FLASH SALE' : $product['discount_percentage'] . '% OFF'; ?>
        </div>
        <?php endif; ?>

        <img id="main-product-image"
             class="main-img"
             src="<?php echo htmlspecialchars($main_image); ?>"
             alt="<?php echo htmlspecialchars($product['name']); ?>"
             onerror="this.src='https://via.placeholder.com/600x600?text=No+Image'">

        <?php if (count($images) > 1): ?>
        <div class="product-thumbnails">
            <?php foreach ($images as $i => $img): ?>
            <img src="<?php echo htmlspecialchars($img); ?>"
                 class="<?php echo $i === 0 ? 'active-thumb' : ''; ?>"
                 alt="Product image <?php echo $i + 1; ?>"
                 onclick="switchImage(this)"
                 onerror="this.style.display='none'">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right: info -->
    <div class="product-detail-info-col">

        <h1><?php echo htmlspecialchars($product['name']); ?></h1>

        <?php if (!empty($product['brand'])): ?>
        <p class="product-brand"><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?></p>
        <?php endif; ?>

        <!-- Star rating -->
        <div class="star-rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <?php echo $i <= round($avg_rating) ? '★' : '☆'; ?>
            <?php endfor; ?>
            <span class="rating-text"><?php echo $avg_rating; ?> / 5 &nbsp;(<?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?>)</span>
        </div>

        <!-- Price -->
        <div class="product-price-block">
            <div class="sale-price">Ksh <?php echo number_format($salePrice, 2); ?></div>
            <?php if ($hasDiscount): ?>
            <div class="was-price">Was: Ksh <?php echo number_format($product['price'], 2); ?></div>
            <?php endif; ?>
        </div>

        <!-- Description -->
        <div class="product-description">
            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
        </div>

        <!-- Stock -->
        <p class="product-stock">
            <?php if ($product['stock'] > 0): ?>
                &#9989; <strong><?php echo $product['stock']; ?> in stock</strong>
            <?php else: ?>
                &#10060; <strong>Out of stock</strong>
            <?php endif; ?>
        </p>

        <!-- Add to cart -->
        <?php if ($product['stock'] > 0): ?>
        <form class="add-to-cart-form product-atc-form">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="number"
                   name="quantity"
                   value="1"
                   min="1"
                   max="<?php echo $product['stock']; ?>"
                   class="product-qty-input"
                   aria-label="Quantity">
            <button type="submit" class="btn btn-primary">&#128722; Add to Cart</button>
        </form>
        <?php endif; ?>

    </div>
</div>

<!-- ── Reviews ── -->
<div class="reviews-panel">
    <h2>&#11088; Customer Reviews (<?php echo count($reviews); ?>)</h2>

    <?php if (empty($reviews)): ?>
        <p class="text-center" style="color:var(--text-muted);padding:16px 0;">
            No reviews yet. Be the first to share your experience!
        </p>
    <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
        <div class="review-item">
            <div class="review-header">
                <span class="review-author"><?php echo htmlspecialchars($rev['username']); ?></span>
                <span class="review-stars">
                    <?php for ($i = 1; $i <= 5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
                </span>
            </div>
            <div class="review-date"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></div>
            <div class="review-body"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Review form -->
    <div class="review-form-wrap">
        <h3>Leave a Review</h3>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="alert alert-info">
                &#128274; Please <a href="login.php"><strong>sign in</strong></a> to leave a review.
            </div>
        <?php else:
            $can_review = false;
            $review_msg = '';
            $review_msg_type = 'info';
            $checkStmt = $pdo->prepare(
                "SELECT DATEDIFF(NOW(), o.created_at) AS days_since
                 FROM orders o JOIN order_items oi ON o.id = oi.order_id
                 WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'verified'
                 ORDER BY o.created_at ASC LIMIT 1"
            );
            $checkStmt->execute([$_SESSION['user_id'], $product_id]);
            $purchase = $checkStmt->fetch();

            if (!$purchase) {
                $review_msg = 'You can only review products you have purchased from us.';
            } elseif ($purchase['days_since'] < 7) {
                $review_msg = 'You can leave a review ' . (7 - $purchase['days_since']) . ' day(s) after purchase.';
                $review_msg_type = 'warning';
            } else {
                $can_review = true;
            }
        ?>
            <?php if (!$can_review): ?>
                <div class="alert alert-<?php echo $review_msg_type; ?>">
                    <?php echo htmlspecialchars($review_msg); ?>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="add_review">
                    <div class="form-group">
                        <label for="rating">Your Rating</label>
                        <select name="rating" id="rating" required>
                            <option value="">Select rating…</option>
                            <option value="5">5 ★ — Excellent</option>
                            <option value="4">4 ★ — Good</option>
                            <option value="3">3 ★ — Average</option>
                            <option value="2">2 ★ — Poor</option>
                            <option value="1">1 ★ — Terrible</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="comment">Your Comment</label>
                        <textarea name="comment" id="comment" rows="4"
                                  placeholder="What did you like or dislike about this product?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Thumbnail switcher -->
<script>
function switchImage(thumb) {
    document.getElementById('main-product-image').src = thumb.src;
    document.querySelectorAll('.product-thumbnails img').forEach(t => t.classList.remove('active-thumb'));
    thumb.classList.add('active-thumb');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
