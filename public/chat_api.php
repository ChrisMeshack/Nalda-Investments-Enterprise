<?php
// public/chat_api.php
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

try {


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'get_admin_chat' && in_array($role, ['admin', 'super_admin'])) {
        $customer_id = $_GET['customer_id'];
        // Mark as read
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")->execute([$customer_id, $user_id]);
        
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
        $stmt->execute([$user_id, $customer_id, $customer_id, $user_id]);
        echo json_encode($stmt->fetchAll());
        exit;
    }
    
    if (isset($_GET['action']) && $_GET['action'] === 'get_client_chat' && $role === 'customer') {
        // Mark as read from any admin
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ?")->execute([$user_id]);
        
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE sender_id = ? OR receiver_id = ? ORDER BY created_at ASC");
        $stmt->execute([$user_id, $user_id]);
        echo json_encode($stmt->fetchAll());
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $message = trim($_POST['message']);
    if (empty($message)) {
        echo json_encode(['error' => 'Empty message']);
        exit;
    }
    
    $receiver_id = $_POST['receiver_id'] ?? null;
    
    // If a customer sends a message and doesn't specify receiver, find an admin to send to (or just use 1 for default admin)
    if ($role === 'customer') {
        $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') LIMIT 1");
        $admin = $stmt->fetch();
        if ($admin) {
            $receiver_id = $admin['id'];
        }
    }
    
    if ($receiver_id) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'No receiver']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request']);
} catch (Exception $e) {
    echo json_encode(['error' => 'DB Error: ' . $e->getMessage()]);
}
?>
