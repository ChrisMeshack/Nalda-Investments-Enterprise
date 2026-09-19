<?php
// public/product.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Product.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header("Location: shop.php");
    exit;
}

$product = Product::getById($product_id);
if (!$product) {
    echo "Product not found.";
    exit;
}

$pdo = getDB();

// Handle new review submission
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_review') {
    if (!isset($_SESSION['user_id'])) {
        $msg = "You must be logged in to leave a review.";
    } else {
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        if ($rating >= 1 && $rating <= 5) {
            // Check if user bought this product and if it's been 7 days
            $checkStmt = $pdo->prepare("SELECT DATEDIFF(NOW(), o.created_at) as days_since 
                                        FROM orders o 
                                        JOIN order_items oi ON o.id = oi.order_id 
                                        WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'verified' 
                                        ORDER BY o.created_at ASC LIMIT 1");
            $checkStmt->execute([$_SESSION['user_id'], $product_id]);
            $purchase = $checkStmt->fetch();

            if (!$purchase) {
                $msg = "You can only review products you have successfully purchased.";
            } elseif ($purchase['days_since'] < 7) {
                $msg = "You can only review a product 7 days after a successful purchase.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$product_id, $_SESSION['user_id'], $rating, $comment]);
                    $msg = "Review added successfully!";
                } catch (Exception $e) {
                    $msg = "Error adding review. You might have already reviewed this product.";
                }
            }
        } else {
            $msg = "Invalid rating.";
        }
    }
}

// Fetch Reviews
$stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll();

// Calculate Average Rating
$avg_rating = 0;
if (count($reviews) > 0) {
    $sum = array_sum(array_column($reviews, 'rating'));
    $avg_rating = round($sum / count($reviews), 1);
}

include __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 1000px; margin: 0 auto; padding: 20px;">
    <?php if ($msg): ?>
        <p style="background: #e1f5fe; padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <div style="display: flex; gap: 30px; flex-wrap: wrap; background: var(--neutral-color); padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="flex: 1; min-width: 300px; position: relative;">
            <?php if ($product['is_flash_sale'] || $product['discount_percentage'] > 0): ?>
                <div class="ribbon" style="top: 10px; right: 10px; padding: 10px; font-size: 1.1rem;">
                    <?php if ($product['is_flash_sale']) echo "⚡ FLASH SALE<br>"; ?>
                    <?php if ($product['discount_percentage'] > 0) echo $product['discount_percentage'] . "% OFF"; ?>
                </div>
            <?php endif; ?>
            <?php 
                $images = explode(',', $product['image']);
                $first_image = trim($images[0]);
            ?>
            <img id="main-product-image" src="<?php echo htmlspecialchars($first_image ?: 'https://via.placeholder.com/400'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.onerror=null; this.src='https://via.placeholder.com/400?text=No+Image';" style="width: 100%; border-radius: 8px; margin-bottom: 10px;">
            
            <?php if (count($images) > 1): ?>
                <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px;">
                    <?php foreach ($images as $img): $img = trim($img); if (!$img) continue; ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" onclick="document.getElementById('main-product-image').src=this.src" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px; cursor: pointer; border: 2px solid transparent;" onmouseover="this.style.border='2px solid var(--primary-color)'" onmouseout="this.style.border='2px solid transparent'">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="flex: 2; min-width: 300px;">
            <h2 style="margin-top: 0;"><?php echo htmlspecialchars($product['name']); ?></h2>
            <?php if ($product['brand']): ?>
                <p style="color: #666; font-size: 1.1rem;"><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?></p>
            <?php endif; ?>
            
            <div style="margin: 15px 0; color: #f39c12; font-size: 1.2rem;">
                <?php 
                for ($i=1; $i<=5; $i++) {
                    echo $i <= round($avg_rating) ? '★' : '☆';
                }
                ?>
                <span style="color: #666; font-size: 1rem;"> (<?php echo $avg_rating; ?> / 5 - <?php echo count($reviews); ?> Reviews)</span>
            </div>

            <p class="price" style="font-size: 1.8rem;">
                <?php if ($product['discount_percentage'] > 0): ?>
                    <span class="original-price" style="font-size: 1.2rem;">Ksh <?php echo number_format($product['price'], 2); ?></span>
                    Ksh <?php echo number_format($product['price'] * (1 - $product['discount_percentage'] / 100), 2); ?>
                <?php else: ?>
                    Ksh <?php echo number_format($product['price'], 2); ?>
                <?php endif; ?>
            </p>

            <p style="margin: 20px 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            
            <p><strong>Stock Available:</strong> <?php echo $product['stock']; ?></p>

            <form class="add-to-cart-form" style="margin-top: 30px; display: flex; gap: 10px;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 80px; padding: 10px; font-size: 1.2rem;">
                <button type="submit" class="btn" style="padding: 10px 30px; font-size: 1.2rem;">Add to Cart</button>
            </form>
        </div>
    </div>

    <div style="margin-top: 50px; background: var(--neutral-color); padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3>Customer Reviews</h3>
        
        <?php if (empty($reviews)): ?>
            <p>No reviews yet. Be the first to review this product!</p>
        <?php else: ?>
            <div style="margin-bottom: 40px;">
                <?php foreach ($reviews as $rev): ?>
                    <div style="border-bottom: 1px solid #eee; padding: 15px 0;">
                        <div style="display: flex; justify-content: space-between;">
                            <strong><?php echo htmlspecialchars($rev['username']); ?></strong>
                            <span style="color: #f39c12;">
                                <?php for($i=1; $i<=5; $i++) echo $i <= $rev['rating'] ? '★' : '☆'; ?>
                            </span>
                        </div>
                        <p style="color: #888; font-size: 0.9rem; margin: 5px 0;"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></p>
                        <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h4>Leave a Review</h4>
        <?php 
        if (isset($_SESSION['user_id'])): 
            // Check eligibility for UI
            $can_review = false;
            $ui_msg = "";
            $checkStmt = $pdo->prepare("SELECT DATEDIFF(NOW(), o.created_at) as days_since FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? AND o.payment_status = 'verified' ORDER BY o.created_at ASC LIMIT 1");
            $checkStmt->execute([$_SESSION['user_id'], $product_id]);
            $purchase = $checkStmt->fetch();
            if (!$purchase) {
                $ui_msg = "You can only leave a review if you have purchased this product.";
            } elseif ($purchase['days_since'] < 7) {
                $ui_msg = "You can review this product in " . (7 - $purchase['days_since']) . " days (7 days after purchase).";
            } else {
                $can_review = true;
            }
            
            if ($can_review):
        ?>
            <form method="POST">
                <input type="hidden" name="action" value="add_review">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Rating (1-5)</label>
                    <select name="rating" required style="padding: 10px; width: 100px;">
                        <option value="5">5 ★</option>
                        <option value="4">4 ★</option>
                        <option value="3">3 ★</option>
                        <option value="2">2 ★</option>
                        <option value="1">1 ★</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Comment</label>
                    <textarea name="comment" rows="4" style="width: 100%; padding: 10px;" placeholder="What did you like or dislike about this product?"></textarea>
                </div>
                <button type="submit" class="btn">Submit Review</button>
            </form>
        <?php else: ?>
            <p style="color: #888; font-style: italic;"><?php echo $ui_msg; ?></p>
        <?php endif; else: ?>
            <p>Please <a href="login.php">login</a> to leave a review.</p>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
