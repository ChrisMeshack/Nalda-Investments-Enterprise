<?php
// public/login.php
session_start();
require_once __DIR__ . '/../models/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    $user = User::login($email, $password);
    if ($user) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['username']  = $user['username'];
        if ($user['role'] === 'super_admin') {
            header('Location: super_admin.php');
        } elseif ($user['role'] === 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = 'Invalid email or password. Please try again.';
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
    <div class="auth-card">

        <h2>Welcome Back</h2>
        <p class="auth-subtitle">Sign in to your Nalda Investment account</p>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">&#9888; <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label for="email">Email or Username</label>
                <input type="text" id="email" name="email" required
                       placeholder="Email or Username"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary btn-full">Sign In</button>
        </form>

        <p class="auth-footer-link">
            Don't have an account? <a href="register.php">Create one free</a>
        </p>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
