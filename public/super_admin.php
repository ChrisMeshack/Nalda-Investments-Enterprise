<?php
// public/super_admin.php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'super_admin') {
    die("Access denied. Super Admin only.");
}

$msg = "";

// Handle User Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_user') {
        User::add($_POST['username'], $_POST['email'], $_POST['password'], $_POST['role']);
        $msg = "User added successfully!";
    } elseif ($_POST['action'] === 'edit_user') {
        User::update($_POST['user_id'], $_POST['username'], $_POST['email'], $_POST['role'], !empty($_POST['password']) ? $_POST['password'] : null);
        $msg = "User updated successfully!";
    } elseif ($_POST['action'] === 'delete_user') {
        User::delete($_POST['user_id']);
        $msg = "User deleted successfully!";
    }
}

$users = User::getAll();

include __DIR__ . '/../includes/header.php';
?>

<h2>Super Admin - User Management</h2>
<?php if ($msg): ?>
    <p style="color: green;"><?php echo $msg; ?></p>
<?php endif; ?>

<div class="admin-panel">
    <h3>Add New User</h3>
    <form method="POST">
        <input type="hidden" name="action" value="add_user">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="customer">Customer</option>
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
            </select>
        </div>
        <button type="submit" class="btn">Add User</button>
    </form>
</div>

<div class="admin-panel" style="margin-top: 30px;">
    <h3>Manage Users</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo ucfirst($u['role']); ?></td>
                    <td>
                        <button class="btn" style="padding: 5px 10px; font-size: 0.8rem;" onclick="editUser(<?php echo htmlspecialchars(json_encode($u)); ?>)">Edit</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" class="btn" style="background-color: #e74c3c; padding: 5px 10px; font-size: 0.8rem;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Edit User Modal/Form -->
<div class="admin-panel" id="edit-user-form" style="margin-top: 30px; display: none; background-color: #fff9c4;">
    <h3>Edit User</h3>
    <form method="POST">
        <input type="hidden" name="action" value="edit_user">
        <input type="hidden" name="user_id" id="edit_user_id">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" id="edit_username" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="edit_email" required>
        </div>
        <div class="form-group">
            <label>New Password (leave blank to keep current)</label>
            <input type="password" name="password">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" id="edit_role">
                <option value="customer">Customer</option>
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
            </select>
        </div>
        <button type="submit" class="btn">Update User</button>
        <button type="button" class="btn" onclick="document.getElementById('edit-user-form').style.display='none'" style="background-color: #7f8c8d;">Cancel</button>
    </form>
</div>

<script>
function editUser(user) {
    document.getElementById('edit-user-form').style.display = 'block';
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    window.scrollTo(0, document.body.scrollHeight);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
