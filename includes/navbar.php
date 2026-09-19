<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current   = basename($_SERVER['PHP_SELF']);
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<div class="site-header">
  <nav class="navbar">

    <!-- Brand -->
    <a href="index.php" class="navbar-brand">Nalda <span>Investment</span></a>

    <!-- Persistent cart icon — always visible on mobile, yellow -->
    <a href="cart.php" class="navbar-cart" aria-label="Cart">
      &#128722;
      <span class="cart-bubble" id="cart-count"><?php echo $cartCount; ?></span>
    </a>

    <!-- Hamburger — yellow bars, mobile only -->
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <!-- Nav links -->
    <div class="nav-menu">
      <a href="index.php" class="<?php echo $current === 'index.php' ? 'active' : ''; ?>">
        <span class="nav-icon">&#127968;</span> Home
      </a>
      <a href="shop.php" class="<?php echo $current === 'shop.php' ? 'active' : ''; ?>">
        <span class="nav-icon">&#128717;</span> Shop
      </a>

      <!-- Cart link — desktop nav only (mobile uses the icon above) -->
      <a href="cart.php" class="nav-cart-link <?php echo $current === 'cart.php' ? 'active' : ''; ?>">
        <span class="cart-badge">
          <span class="nav-icon">&#128722;</span> Cart
          <span class="cart-bubble" id="cart-count-desktop"><?php echo $cartCount; ?></span>
        </span>
      </a>

      <?php if (isset($_SESSION['user_id'])): ?>
        <?php if ($_SESSION['user_role'] === 'super_admin'): ?>
          <a href="super_admin.php" class="<?php echo $current === 'super_admin.php' ? 'active' : ''; ?>">
            <span class="nav-icon">&#9881;</span> Super Admin
          </a>
          <a href="admin.php" class="<?php echo $current === 'admin.php' ? 'active' : ''; ?>">
            <span class="nav-icon">&#128188;</span> Admin
          </a>
        <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
          <a href="admin.php" class="<?php echo $current === 'admin.php' ? 'active' : ''; ?>">
            <span class="nav-icon">&#128188;</span> Admin
          </a>
        <?php endif; ?>
        <a href="logout.php">
          <span class="nav-icon">&#128682;</span> Logout
        </a>
      <?php else: ?>
        <a href="login.php" class="<?php echo $current === 'login.php' ? 'active' : ''; ?>">
          <span class="nav-icon">&#128100;</span> Login
        </a>
        <a href="register.php" class="<?php echo $current === 'register.php' ? 'active' : ''; ?>">
          <span class="nav-icon">&#128221;</span> Register
        </a>
      <?php endif; ?>
    </div><!-- /.nav-menu -->

  </nav>
</div><!-- /.site-header -->
