<?php
// public/index.php
session_start();
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Product.php';

$categories = Category::getAll();

// Flash-sale strip: up to 10 items for the horizontal scroll strip
$allProducts   = Product::getAll();
$hotDeals      = array_values(array_filter($allProducts, fn($p) => $p['is_flash_sale'] || $p['discount_percentage'] > 0));
if (empty($hotDeals)) $hotDeals = array_slice($allProducts, 0, 8);
else                  $hotDeals = array_slice($hotDeals, 0, 10);

include __DIR__ . '/../includes/header.php';
?>

<!-- ── Offer Marquee ──────────────────────────────────────── -->
<div class="marquee-container">
    <div class="marquee-content">
        &#9889; FLASH SALE: Up to 50% OFF on Electronics! &nbsp;&nbsp;|&nbsp;&nbsp;
        &#128666; Fast Delivery within Kitale &nbsp;&nbsp;|&nbsp;&nbsp;
        &#11088; Top-Rated Supermarket in Town &nbsp;&nbsp;|&nbsp;&nbsp;
        &#128176; Huge Discounts on New Arrivals! &nbsp;&nbsp;|&nbsp;&nbsp;
        &#127381; Shop Smart. Save More. Only at Nalda Investment!
    </div>
</div>

<!-- ── Hero Carousel ─────────────────────────────────────── -->
<div class="hero-carousel">
    <div class="carousel-inner">

        <div class="carousel-item" style="background-image:
            linear-gradient(to top,rgba(0,0,0,.75) 0%,rgba(0,0,0,.2) 60%,transparent 100%),
            url('https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=80');">
            <h2>Welcome to Nalda Investment</h2>
            <p>Your one-stop online supermarket for everything you need.</p>
            <a href="shop.php" class="btn btn-primary">Shop Now &#8594;</a>
        </div>

        <div class="carousel-item" style="background-image:
            linear-gradient(to top,rgba(0,0,0,.75) 0%,rgba(0,0,0,.2) 60%,transparent 100%),
            url('https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=1400&q=80');">
            <h2>Mega Flash Sales</h2>
            <p>Grab the best deals before they run out!</p>
            <a href="shop.php" class="btn btn-danger">View Offers &#8594;</a>
        </div>

        <div class="carousel-item" style="background-image:
            linear-gradient(to top,rgba(0,0,0,.75) 0%,rgba(0,0,0,.2) 60%,transparent 100%),
            url('https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&w=1400&q=80');">
            <h2>New Arrivals</h2>
            <p>Fresh electronics, fashion, and household essentials — just landed.</p>
            <a href="shop.php" class="btn" style="background:#3498db;color:#fff;">Explore Now &#8594;</a>
        </div>

    </div>
    <div class="carousel-controls">
        <button class="carousel-btn" id="carousel-prev" aria-label="Previous slide">&#10094;</button>
        <button class="carousel-btn" id="carousel-next" aria-label="Next slide">&#10095;</button>
    </div>
    <div class="carousel-dots"></div>
</div>

<!-- ── Flash-sale horizontal strip ───────────────────────── -->
<?php if (!empty($hotDeals)): ?>
<section class="category-row-section">
    <div class="category-row-header">
        <h2 class="category-row-title">&#9889; Flash Deals</h2>
        <a href="shop.php" class="see-all-link">See all</a>
    </div>
    <div class="h-scroll-strip">
        <?php foreach ($hotDeals as $p):
            $imgs     = explode(',', $p['image']);
            $img      = trim($imgs[0]) ?: 'https://via.placeholder.com/300x300?text=No+Image';
            $hasDis   = $p['discount_percentage'] > 0;
            $salePrice = $hasDis ? $p['price'] * (1 - $p['discount_percentage'] / 100) : $p['price'];
        ?>
        <a href="product.php?id=<?php echo $p['id']; ?>" class="strip-card">
            <div class="strip-card-img">
                <img src="<?php echo htmlspecialchars($img); ?>"
                     alt="<?php echo htmlspecialchars($p['name']); ?>"
                     loading="lazy"
                     onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'">
                <?php if ($p['is_flash_sale'] || $hasDis): ?>
                <span class="ribbon<?php echo $p['is_flash_sale'] ? ' flash' : ''; ?>">
                    <?php echo $p['is_flash_sale'] ? '⚡ FLASH' : $p['discount_percentage'] . '% OFF'; ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="strip-card-body">
                <p class="strip-card-name"><?php echo htmlspecialchars($p['name']); ?></p>
                <p class="strip-card-price">Ksh <?php echo number_format($salePrice, 2); ?></p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ── Category chips ────────────────────────────────────── -->
<div class="categories-filter" id="category-chips" role="tablist" aria-label="Filter by category">
    <button class="chip active" data-cat="all" role="tab" aria-selected="true">All</button>
    <?php foreach ($categories as $cat): ?>
    <button class="chip" data-cat="<?php echo $cat['id']; ?>"
            role="tab" aria-selected="false">
        <?php echo htmlspecialchars($cat['name']); ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- ── Main 2-col product feed ───────────────────────────── -->
<div class="feed-grid" id="product-feed" aria-live="polite">
    <!-- Skeleton placeholders shown while first batch loads -->
    <?php for ($i = 0; $i < 10; $i++): ?>
    <div class="feed-card skeleton" aria-hidden="true">
        <div class="feed-card-img skeleton-img"></div>
        <div class="feed-card-body">
            <div class="skeleton-line wide"></div>
            <div class="skeleton-line short"></div>
            <div class="skeleton-line price"></div>
        </div>
    </div>
    <?php endfor; ?>
</div>

<!-- ── Infinite scroll sentinel ──────────────────────────── -->
<div id="feed-sentinel" aria-hidden="true"></div>

<!-- ── Loading spinner (shown between batches) ───────────── -->
<div id="feed-loader" class="feed-loader" hidden>
    <span class="spinner" aria-label="Loading more products"></span>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
