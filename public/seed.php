<?php
// public/seed.php
// Run once to populate the database with dummy products that use the
// local image assets in assets/img/.
// Visit: http://localhost/Nalda/public/seed.php

require_once '../config/db.php';

$pdo = getDB();

// ── All images available in assets/img ──────────────────────────────────
// Paths are relative to the public/ directory so the browser resolves them
// as http://localhost/Nalda/public/../assets/img/...  → correct.
$imgs = [
    '../assets/img/Image.png',
    '../assets/img/image 3.png',
    '../assets/img/images 2.jpg',
    '../assets/img/images 4.jpg',
    '../assets/img/1789584459_images 4.jpg',
    '../assets/img/1789584478_images 4.jpg',
    '../assets/img/1789584517_images 4.jpg',
    '../assets/img/1789585234_images 4.jpg',
    '../assets/img/1789585252_image 3.png',
    '../assets/img/1789587413_0_images 4.jpg',
    '../assets/img/1789587413_1_image 3.png',
    '../assets/img/1789587413_2_images 2.jpg',
    '../assets/img/1789587413_3_Image.png',
];

// Helper – cycles through images by index
function img(int $i, array $imgs): string {
    return $imgs[$i % count($imgs)];
}

// ── Product definitions ──────────────────────────────────────────────────
// [category_id, name, description, price, discount_pct, is_flash_sale, stock, serial, image_index]
$products = [
    // Groceries (1)
    [1, 'Premium Fresh Apples',        'Crisp and juicy apples from the farm.',                               250.00,  0,  0, 120, 'GRO-001',  0],
    [1, 'Ripe Bananas – 1 kg',         'Sweet, farm-fresh bananas. Great for smoothies.',                     120.00, 10,  0, 200, 'GRO-002',  3],
    [1, 'Long Grain White Rice – 2 kg','Fluffy, fragrant rice. Perfect for everyday meals.',                  380.00,  5,  0,  80, 'GRO-003',  6],
    [1, 'Fresh Tomatoes – 500 g',      'Vine-ripened tomatoes, ideal for sauces and salads.',                  90.00,  0,  0, 300, 'GRO-004',  9],
    [1, 'Rolled Oats – 1 kg',          'High-fibre oats for a healthy breakfast.',                            290.00, 15,  1,  60, 'GRO-005', 12],
    [1, 'Cooking Oil – 1 L',           'Pure sunflower oil, cholesterol free.',                               320.00,  0,  0, 150, 'GRO-006',  1],
    [1, 'Wheat Flour – 2 kg',          'Fine milled flour for baking and cooking.',                           210.00,  0,  0, 100, 'GRO-007',  4],
    [1, 'Sugar – 1 kg',                'Refined white sugar.',                                                140.00,  0,  0, 200, 'GRO-008',  7],

    // Beverages (2)
    [2, 'Organic Orange Juice – 1 L',  '100% pure organic orange juice, no added sugar.',                     350.00,  0,  0,  80, 'BEV-001',  1],
    [2, 'Mineral Water – 500 ml',      'Pure, refreshing mineral water.',                                      60.00,  0,  0, 500, 'BEV-002',  4],
    [2, 'Mango Nectar – 500 ml',       'Sweet mango nectar made from real fruit.',                            130.00, 20,  1,  90, 'BEV-003',  7],
    [2, 'Black Tea Bags – 50 pcs',     'Rich, aromatic Kenyan black tea.',                                    180.00,  0,  0, 120, 'BEV-004', 10],
    [2, 'Instant Coffee – 200 g',      'Bold, smooth instant coffee. Start your morning right.',              450.00, 10,  0,  60, 'BEV-005',  0],
    [2, 'Strawberry Yoghurt – 500 ml', 'Creamy yoghurt with real strawberry pieces.',                         220.00,  5,  0,  70, 'BEV-006',  3],

    // Household (3)
    [3, 'Laundry Detergent – 2 kg',    'Heavy-duty formula for stubborn stains.',                             650.00, 20,  1,  50, 'HOU-001',  2],
    [3, 'Dishwashing Liquid – 750 ml', 'Cuts through grease with ease. Lemon scent.',                        280.00,  0,  0, 100, 'HOU-002',  5],
    [3, 'Floor Cleaner – 1 L',         'Antibacterial floor cleaner, leaves floors shining.',                 320.00,  0,  0,  80, 'HOU-003',  8],
    [3, 'Toilet Paper – 12 Rolls',     'Soft, 3-ply toilet tissue.',                                         480.00, 10,  0, 200, 'HOU-004', 11],
    [3, 'Air Freshener Spray',         'Fresh ocean breeze scent. Eliminates odours instantly.',              230.00,  0,  0,  90, 'HOU-005',  1],
    [3, 'Sponge Scrubber – 3 Pack',    'Heavy-duty scrubbers for pots and pans.',                              90.00,  0,  0, 300, 'HOU-006',  4],

    // Electronics (4)
    [4, 'Wireless Earbuds',            'Crystal-clear sound, 6 h battery, sweat resistant.',                3500.00, 15,  1,  30, 'ELE-001',  3],
    [4, 'Phone Charging Cable – 2 m',  'Fast-charge Type-C cable, braided nylon.',                           350.00,  0,  0, 200, 'ELE-002',  6],
    [4, 'Portable Power Bank 10 000 mAh','Dual USB-A output. Lightweight and pocket-sized.',               2200.00, 10,  0,  40, 'ELE-003',  9],
    [4, 'LED Desk Lamp',               'Adjustable brightness, USB powered, eye-care mode.',               1800.00, 20,  1,  25, 'ELE-004', 12],
    [4, 'Smart Watch – Basic',         'Step counter, sleep tracker, notifications mirror.',               4500.00, 25,  1,  20, 'ELE-005',  0],
    [4, 'Bluetooth Speaker – Mini',    'Waterproof, 8 h playtime, rich bass.',                             2800.00,  5,  0,  35, 'ELE-006',  3],
    [4, 'USB Wall Adapter – 20 W',     'Fast-charge adapter, dual port, universal input.',                   650.00,  0,  0, 100, 'ELE-007',  6],

    // Fashion (5)
    [5, 'Classic White T-Shirt',       '100% cotton, pre-shrunk, unisex fit.',                               850.00, 30,  1,  80, 'FSH-001',  2],
    [5, 'Slim-Fit Chinos',             'Lightweight stretch chinos. Smart casual comfort.',                 1800.00,  0,  0,  50, 'FSH-002',  5],
    [5, 'Canvas Sneakers',             'Flexible, breathable sole. Available in 3 colours.',               2200.00, 10,  0,  40, 'FSH-003',  8],
    [5, 'Straw Beach Hat',             'Wide brim for UV protection. Foldable for travel.',                  650.00,  0,  0,  60, 'FSH-004', 11],
    [5, 'Leather Belt',                'Genuine leather with antique silver buckle.',                        950.00, 15,  0,  70, 'FSH-005',  1],
    [5, 'Hooded Sweatshirt',           'Fleece lining, kangaroo pocket, ribbed cuffs.',                    1650.00,  0,  0,  45, 'FSH-006',  4],

    // Health & Beauty (6)
    [6, 'Shea Butter Lotion – 400 ml', 'Deep-moisture lotion with pure shea butter.',                        480.00, 10,  0, 100, 'HBT-001',  7],
    [6, 'Charcoal Face Wash – 150 ml', 'Deep cleanses pores, removes excess oil.',                           380.00,  0,  0,  90, 'HBT-002', 10],
    [6, 'Vitamin C Serum – 30 ml',     'Brightens skin, reduces dark spots.',                               1200.00, 20,  1,  50, 'HBT-003',  0],
    [6, 'Herbal Toothpaste – 75 ml',   'Whitening formula with clove and mint.',                             220.00,  0,  0, 150, 'HBT-004',  3],
    [6, 'Hand Sanitizer – 250 ml',     '70% alcohol, kills 99.9% of germs.',                                 180.00,  0,  0, 300, 'HBT-005',  6],
    [6, 'Multi-Vitamin Tablets – 60 pcs','Daily multivitamins for adults.',                                  650.00, 10,  0,  70, 'HBT-006',  9],

    // Baby Products (7)
    [7, 'Baby Diapers – Pack of 40',   'Ultra-soft, leak-proof diapers. Size M.',                           1400.00, 15,  1,  60, 'BAB-001', 12],
    [7, 'Baby Wipes – 80 pcs',         'Alcohol-free, fragrance-free wet wipes.',                            280.00,  0,  0, 200, 'BAB-002',  2],
    [7, 'Baby Shampoo – 200 ml',       'Tear-free formula, gentle on newborns.',                             320.00,  0,  0, 100, 'BAB-003',  5],
    [7, 'Feeding Bottle – 260 ml',     'BPA-free with anti-colic slow-flow teat.',                           550.00,  5,  0,  80, 'BAB-004',  8],
    [7, 'Baby Lotion – 200 ml',        'Nourishing lotion with chamomile extract.',                          280.00,  0,  0, 120, 'BAB-005', 11],

    // Stationery (8)
    [8, 'Ballpoint Pens – 10 Pack',    'Smooth-writing blue ink pens.',                                      150.00,  0,  0, 500, 'STA-001',  1],
    [8, 'A4 Notebook – 200 Pages',     'Ruled, spiral-bound notebook.',                                      320.00, 10,  0, 150, 'STA-002',  4],
    [8, 'Permanent Markers – 6 Colours','Waterproof ink, fine tip.',                                         380.00,  0,  0, 100, 'STA-003',  7],
    [8, 'Sticky Notes – 5 Pads',       '100 sheets per pad, assorted pastel colours.',                       250.00,  0,  0, 200, 'STA-004', 10],
    [8, 'Stapler + 1000 Staples',      'Heavy duty desktop stapler, 25-sheet capacity.',                     650.00,  5,  0,  80, 'STA-005',  0],
    [8, 'Scientific Calculator',       '240 functions, solar + battery dual power.',                        1800.00, 20,  1,  40, 'STA-006',  3],
];

// ── Clear existing products and re-seed ────────────────────────────────
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Seed</title>
<style>body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:0 16px}
li.ok{color:#27ae60} li.dup{color:#e67e22} li.err{color:#e74c3c}</style></head><body>";
echo "<h2>&#127807; Nalda Investment — Database Seed</h2>";

// Truncate products (cascades to cart / order_items via FK)
try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE products");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<p>&#9989; Cleared existing products.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>&#9888; Could not truncate: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$stmt = $pdo->prepare(
    "INSERT INTO products
        (category_id, name, description, price, discount_percentage, is_flash_sale, stock, serial_number, image)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

$ok  = 0;
$dup = 0;
$err = 0;

echo "<ul>";
foreach ($products as $p) {
    [$catId, $name, $desc, $price, $disc, $flash, $stock, $serial, $imgIdx] = $p;
    $imagePath = img($imgIdx, $imgs);
    try {
        $stmt->execute([$catId, $name, $desc, $price, $disc, $flash, $stock, $serial, $imagePath]);
        echo "<li class='ok'>&#10003; " . htmlspecialchars($name) . " <small>(" . htmlspecialchars($imagePath) . ")</small></li>";
        $ok++;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "<li class='dup'>&#8635; Duplicate (skipped): " . htmlspecialchars($name) . "</li>";
            $dup++;
        } else {
            echo "<li class='err'>&#10005; Error — " . htmlspecialchars($name) . ": " . htmlspecialchars($e->getMessage()) . "</li>";
            $err++;
        }
    }
}
echo "</ul>";
echo "<p><strong>Done.</strong> Inserted: $ok &nbsp; Skipped (duplicate): $dup &nbsp; Errors: $err</p>";
echo "<p><a href='index.php'>&#8592; Back to Homepage</a></p>";
echo "</body></html>";
?>
