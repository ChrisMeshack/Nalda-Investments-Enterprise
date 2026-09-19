<?php
// public/register.php
session_start();
require_once __DIR__ . '/../models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $error = "Password must contain at least one special character.";
    } else {
        try {
            if (User::register($username, $email, $password)) {
                header("Location: login.php");
                exit;
            }
        } catch (Exception $e) {
            $error = "Registration failed. Username or email might already exist.";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<h2>Register</h2>
<?php if (isset($error)): ?>
    <p style="color: red; text-align: center;"><?php echo $error; ?></p>
<?php endif; ?>
<div style="max-width: 400px; margin: 0 auto; background: var(--neutral-color); padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
            <small style="color: #666;">Min 8 chars, 1 uppercase, 1 special char.</small>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn" style="width: 100%;">Register</button>
    </form>
    <p style="text-align: center; margin-top: 15px;">Already have an account? <a href="login.php">Login</a></p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
