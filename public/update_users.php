<?php
require_once '../config/db.php';
require_once '../models/User.php';

$pdo = getDB();

echo "<h1>Updating Admins...</h1><ul>";

try {
    // 1. Super Admin: Chris Meshack
    $super_username = 'Chris Meshack';
    $super_email = 'chrismeshackwork@gmail.com';
    $super_password = 'Loreen@2004';
    $super_role = 'super_admin';

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$super_email]);
    $user = $stmt->fetch();

    if ($user) {
        User::update($user['id'], $super_username, $super_email, $super_role, $super_password);
        echo "<li>Updated Super Admin (Chris Meshack).</li>";
    } else {
        User::add($super_username, $super_email, $super_password, $super_role);
        echo "<li>Created Super Admin (Chris Meshack).</li>";
    }

    // 2. Admin: Regina Mwangi
    $admin_username = 'Regina Mwangi';
    $admin_email = 'reginamwangi@gmail.com';
    $admin_password = 'Kitale@2026';
    $admin_role = 'admin';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    $user = $stmt->fetch();

    if ($user) {
        User::update($user['id'], $admin_username, $admin_email, $admin_role, $admin_password);
        echo "<li>Updated Admin (Regina Mwangi).</li>";
    } else {
        User::add($admin_username, $admin_email, $admin_password, $admin_role);
        echo "<li>Created Admin (Regina Mwangi).</li>";
    }

} catch (Exception $e) {
    echo "<li>Error: " . $e->getMessage() . "</li>";
}

echo "</ul><p>Admins updated successfully! <a href='login.php'>Go to Login</a></p>";
?>
