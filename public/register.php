<?php
// public/register.php
session_start();
require_once __DIR__ . '/../models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username']         ?? '');
    $email            = trim($_POST['email']            ?? '');
    $password         = $_POST['password']         ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $error = 'Password must contain at least one special character (e.g. @, #, !).';
    } else {
        try {
            if (User::register($username, $email, $password)) {
                header('Location: login.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration failed. That username or email may already be in use.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">

        <h2>Create Account</h2>
        <p class="auth-subtitle">Join Nalda Investment — it's free</p>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">&#9888; <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       placeholder="Choose a username"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required
                       placeholder="you@example.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Min 8 chars, 1 uppercase, 1 special char">
                <small class="form-hint">Use at least 8 characters including uppercase and a special character.</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       placeholder="Repeat your password">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Create Account</button>
        </form>

        <p class="auth-footer-link">
            Already have an account? <a href="login.php">Sign in</a>
        </p>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
