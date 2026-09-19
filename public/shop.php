<?php
// public/shop.php
session_start();
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Product.php';

$filters = [
    'category_id' => $_GET['category'] ?? null,
    'search'      => $_GET['search']   ?? null,
    'brand'       => $_GET['brand']    ?? null,
    'min_price'   => $_GET['min_price'] ?? null,
    'max_price'   => $_GET['max_price'] ?? null,
];

$categories = Category::getAll();
$brands     = Product::getBrands();
$products   = Product::getAll($filters);

// Active category label for the page title
$activeCatName = 'All Products';
if (!empty($filters['category_id'])) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $filters['category_id']) {
            $activeCatName = htmlspecialchars($cat['name']);
            break;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!-- ── Filter bar ────────────────────────────────────────── -->
<form method="GET" class="filter-bar">
    <?php if (!empty($_GET['category'])): ?>
        <input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category']); ?>">
    <?php endif; ?>

    <input type="text"   name="search"    placeholder="&#128269; Search products…"
           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">

    <select name="brand">
        <option value="">All Brands</option>
        <?php foreach ($brands as $b): ?>
            <option value="<?php echo htmlspecialchars($b); ?>"
                <?php echo (isset($_GET['brand']) && $_GET['brand'] === $b) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($b); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="number" name="min_price" placeholder="Min (Ksh)" step="1"
           value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
    <input type="number" name="max_price" placeholder="Max (Ksh)" step="1"
           value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">

    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <a href="shop.php<?php echo !empty($_GET['category']) ? '?category=' . htmlspecialchars($_GET['category']) : ''; ?>"
       class="btn btn-sm" style="background:#95a5a6;color:#fff;">Reset</a>
</form>

<!-- ── Category pills ───────────────────────────────────── -->
<div class="categories-filter">
    <a href="shop.php" class="<?php echo empty($filters['category_id']) ? 'active' : ''; ?>">All</a>
    <?php foreach ($categories as $cat): ?>
        <a href="shop.php?category=<?php echo $cat['id']; ?>"
           class="<?php echo $filters['category_id'] == $cat['id'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($cat['name']); ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- ── Page title + count ───────────────────────────────── -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
    <h2 class="section-title" style="margin:0;"><?php echo $activeCatName; ?></h2>
    <span style="color:var(--text-muted);font-size:.9rem;">
        <?php echo count($products); ?> product<?php echo count($products) !== 1 ? 's' : ''; ?> found
    </span>
</div>

<!-- ── Product grid ─────────────────────────────────────── -->
<?php if (count($products) > 0): ?>
<div class="product-grid">
    <?php foreach ($products as $product):
        $images     = explode(',', $product['image']);
        $firstImage = trim($images[0]) ?: 'https://via.placeholder.com/300x225?text=No+Image';
        $hasDiscount = $product['discount_percentage'] > 0;
        $salePrice   = $hasDiscount
            ? $product['price'] * (1 - $product['discount_percentage'] / 100)
            : $product['price'];
    ?>
    <div class="product-card">
        <div class="product-card-img-wrap">
            <img src="<?php echo htmlspecialchars($firstImage); ?>"
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 loading="lazy"
                 onerror="this.src='https://via.placeholder.com/300x225?text=No+Image'">

            <?php if ($product['is_flash_sale'] || $hasDiscount): ?>
            <div class="ribbon<?php echo $product['is_flash_sale'] ? ' flash' : ''; ?>">
                <?php
                if ($product['is_flash_sale'])  echo '&#9889; FLASH';
                elseif ($hasDiscount)            echo $product['discount_percentage'] . '% OFF';
                ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="product-card-body">
            <h3><?php echo htmlspecialchars($product['name']); ?></h3>

            <p class="price">
                <?php if ($hasDiscount): ?>
                    <span class="original-price">Ksh <?php echo number_format($product['price'], 2); ?></span>
                <?php endif; ?>
                Ksh <?php echo number_format($salePrice, 2); ?>
            </p>

            <form class="add-to-cart-form card-actions">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="quantity"   value="1">
                <a href="product.php?id=<?php echo $product['id']; ?>"
                   class="btn btn-primary btn-sm">View</a>
                <button type="submit" class="btn btn-sm">Add</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<div class="empty-state">
    <div style="font-size:3rem;">&#128270;</div>
    <p>No products found. Try adjusting your filters.</p>
    <a href="shop.php" class="btn btn-primary mt-16">Clear Filters</a>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
