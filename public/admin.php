<?php
// public/admin.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Product.php';

// Allow both admin and super_admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    die("Access denied. Admin only.");
}

$pdo = getDB();
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Handle Multiple File Uploads
    $uploaded_images = [];
    if (isset($_FILES['image_file']) && is_array($_FILES['image_file']['name'])) {
        $upload_dir = __DIR__ . '/../assets/img/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        foreach ($_FILES['image_file']['name'] as $key => $name) {
            if ($_FILES['image_file']['error'][$key] === UPLOAD_ERR_OK) {
                $filename = time() . '_' . $key . '_' . basename($name);
                $target_file = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'][$key], $target_file)) {
                    $uploaded_images[] = '../assets/img/' . $filename;
                }
            }
        }
    } elseif (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/img/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = time() . '_' . basename($_FILES['image_file']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
            $uploaded_images[] = '../assets/img/' . $filename;
        }
    }

    if ($_POST['action'] === 'add_product') {
        // Merge uploaded images with URL image
        if (!empty($_POST['image'])) {
            $uploaded_images[] = $_POST['image'];
        }
        $final_image = !empty($uploaded_images) ? implode(',', $uploaded_images) : '';
        Product::add([
            'category_id' => $_POST['category_id'],
            'name' => $_POST['name'],
            'brand' => $_POST['brand'] ?? null,
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'discount_percentage' => $_POST['discount_percentage'],
            'is_flash_sale' => isset($_POST['is_flash_sale']) ? 1 : 0,
            'stock' => $_POST['stock'],
            'serial_number' => $_POST['serial_number'],
            'image' => $final_image
        ]);
        $msg = "Product added successfully!";
    } elseif ($_POST['action'] === 'edit_product') {
        // If they uploaded new images or provided a URL, override the existing images.
        // Otherwise, keep the old ones.
        if (!empty($uploaded_images) || !empty($_POST['image'])) {
            if (!empty($_POST['image'])) {
                $uploaded_images[] = $_POST['image'];
            }
            $final_image = implode(',', $uploaded_images);
        } else {
            // Keep existing image if no new ones are provided
            $existing_product = Product::getById($_POST['product_id']);
            $final_image = $existing_product['image'];
        }
        
        Product::update($_POST['product_id'], [
            'category_id' => $_POST['category_id'],
            'name' => $_POST['name'],
            'brand' => $_POST['brand'] ?? null,
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'discount_percentage' => $_POST['discount_percentage'],
            'is_flash_sale' => isset($_POST['is_flash_sale']) ? 1 : 0,
            'stock' => $_POST['stock'],
            'serial_number' => $_POST['serial_number'],
            'image' => $final_image
        ]);
        $msg = "Product updated successfully!";
    } elseif ($_POST['action'] === 'delete_product') {
        Product::delete($_POST['product_id']);
        $msg = "Product deleted successfully!";
    } elseif ($_POST['action'] === 'update_order_status') {
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['order_id']]);
        $stmt = $pdo->prepare("UPDATE manual_payments SET status = ? WHERE order_id = ?");
        $stmt->execute([$_POST['status'], $_POST['order_id']]);
        $msg = "Order status updated successfully!";
    } elseif ($_POST['action'] === 'add_category') {
        Category::add($_POST['name']);
        $msg = "Category added successfully!";
        $categories = Category::getAll(); // Refresh before rendering
    } elseif ($_POST['action'] === 'delete_category') {
        Category::delete($_POST['category_id']);
        $msg = "Category deleted successfully!";
        $categories = Category::getAll(); // Refresh before rendering
    }
}

$ordersStmt = $pdo->query("SELECT o.*, u.username, mp.reference_number FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN manual_payments mp ON o.id = mp.order_id ORDER BY o.created_at DESC");
$orders = $ordersStmt->fetchAll();

$categories = Category::getAll();
$products = Product::getAll();

include __DIR__ . '/../includes/header.php';
?>

<h2>Admin Dashboard</h2>
<?php if ($msg): ?>
    <p style="color: green;"><?php echo $msg; ?></p>
<?php endif; ?>

<div class="admin-panel" style="margin-bottom: 20px;">
    <a href="chat_admin.php" class="btn" style="background-color: #3498db;">Open Live Chat Hub</a>
</div>

<!-- CATEGORY MANAGEMENT -->
<div class="admin-panel" style="margin-bottom: 20px;">
    <h3>Manage Categories</h3>
    
    <!-- Add Category -->
    <form method="POST" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-end;">
        <input type="hidden" name="action" value="add_category">
        <div class="form-group" style="margin-bottom: 0; flex: 1;">
            <label>New Category Name</label>
            <input type="text" name="name" required placeholder="e.g. Laptops">
        </div>
        <button type="submit" class="btn">Add Category</button>
    </form>

    <!-- List Categories -->
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th style="width: 100px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php echo $cat['id']; ?></td>
                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                <td>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete category? Products in this category might be affected.');">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="admin-panel">
    <h3>Add New Product</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_product">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Brand</label>
            <input type="text" name="brand" placeholder="e.g. Nike, Apple, etc.">
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Price</label>
            <input type="number" step="0.01" name="price" required>
        </div>
        <div class="form-group">
            <label>Discount Percentage (%)</label>
            <input type="number" name="discount_percentage" value="0" min="0" max="100">
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_flash_sale"> Flash Sale Event</label>
        </div>
        <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" required>
        </div>
        <div class="form-group">
            <label>Serial Number</label>
            <input type="text" name="serial_number" required>
        </div>
        <div class="form-group">
            <label>Upload Image from Phone/PC</label>
            <input type="file" name="image_file[]" accept="image/*" multiple>
        </div>
        <div class="form-group">
            <label>OR Image URL</label>
            <input type="text" name="image" placeholder="https://via.placeholder.com/200">
        </div>
        <button type="submit" class="btn">Add Product</button>
    </form>
</div>

<div class="admin-panel" style="margin-top: 30px;">
    <h3>Manage Products</h3>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Discount</th>
                <th>Flash Sale</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td>Ksh <?php echo number_format($p['price'], 2); ?></td>
                    <td><?php echo $p['discount_percentage']; ?>%</td>
                    <td><?php echo $p['is_flash_sale'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <button class="btn btn-sm" onclick='editProduct(<?php echo json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete product?');">
                            <input type="hidden" name="action" value="delete_product">
                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Edit Product Form (Hidden by default) -->
<div class="admin-panel" id="edit-product-form" style="margin-top: 30px; display: none; background-color: #fff9c4;">
    <h3>Edit Product</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_product">
        <input type="hidden" name="product_id" id="edit_product_id">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" id="edit_name" required>
        </div>
        <div class="form-group">
            <label>Brand</label>
            <input type="text" name="brand" id="edit_brand">
        </div>
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" id="edit_category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" id="edit_description" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Price</label>
            <input type="number" step="0.01" name="price" id="edit_price" required>
        </div>
        <div class="form-group">
            <label>Discount Percentage (%)</label>
            <input type="number" name="discount_percentage" id="edit_discount_percentage" min="0" max="100">
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_flash_sale" id="edit_is_flash_sale"> Flash Sale Event</label>
        </div>
        <div class="form-group">
            <label>Stock</label>
            <input type="number" name="stock" id="edit_stock" required>
        </div>
        <div class="form-group">
            <label>Serial Number</label>
            <input type="text" name="serial_number" id="edit_serial_number" required>
        </div>
        <div class="form-group">
            <label>Upload New Image (Overrides URL)</label>
            <input type="file" name="image_file[]" accept="image/*" multiple>
        </div>
        <div class="form-group">
            <label>OR Image URL</label>
            <input type="text" name="image" id="edit_image">
        </div>
        <button type="submit" class="btn">Update Product</button>
        <button type="button" class="btn" onclick="document.getElementById('edit-product-form').style.display='none'" style="background-color: #7f8c8d;">Cancel</button>
    </form>
</div>

<div class="admin-panel" style="margin-top: 30px;">
    <h3>Recent Orders</h3>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Ref No.</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                    <td>Ksh <?php echo number_format($order['total_amount'], 2); ?></td>
                    <td><?php echo ucfirst($order['payment_status']); ?></td>
                    <td><?php echo htmlspecialchars($order['reference_number']); ?></td>
                    <td>
                        <?php if ($order['payment_status'] === 'pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="status" value="verified">
                            <button type="submit" class="btn btn-sm btn-primary">Verify</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                        </form>
                        <?php else: ?>
                            <em>Completed</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
function editProduct(p) {
    document.getElementById('edit-product-form').style.display = 'block';
    document.getElementById('edit_product_id').value = p.id;
    document.getElementById('edit_name').value = p.name;
    document.getElementById('edit_brand').value = p.brand || '';
    document.getElementById('edit_category_id').value = p.category_id;
    document.getElementById('edit_description').value = p.description;
    document.getElementById('edit_price').value = p.price;
    document.getElementById('edit_discount_percentage').value = p.discount_percentage;
    document.getElementById('edit_is_flash_sale').checked = (p.is_flash_sale == 1);
    document.getElementById('edit_stock').value = p.stock;
    document.getElementById('edit_serial_number').value = p.serial_number;
    document.getElementById('edit_image').value = p.image;
    window.scrollTo(0, document.body.scrollHeight);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
