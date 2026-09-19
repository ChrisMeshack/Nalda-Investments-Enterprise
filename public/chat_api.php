<?php
// public/chat_api.php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$pdo     = getDB();
$user_id = (int) $_SESSION['user_id'];
$role    = $_SESSION['user_role'];

try {

    /* ── GET: customer fetches their own thread ── */
    if ($_SERVER['REQUEST_METHOD'] === 'GET'
        && ($_GET['action'] ?? '') === 'get_client_chat'
        && $role === 'customer'
    ) {
        // Mark all admin→customer messages as read
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ?")
            ->execute([$user_id]);

        $stmt = $pdo->prepare(
            "SELECT m.*, u.username AS sender_name
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.sender_id = ? OR m.receiver_id = ?
             ORDER BY m.created_at ASC"
        );
        $stmt->execute([$user_id, $user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /* ── GET: admin fetches a specific customer thread ── */
    if ($_SERVER['REQUEST_METHOD'] === 'GET'
        && ($_GET['action'] ?? '') === 'get_admin_chat'
        && in_array($role, ['admin', 'super_admin'])
    ) {
        $customer_id = (int) ($_GET['customer_id'] ?? 0);
        if (!$customer_id) {
            echo json_encode(['error' => 'Missing customer_id']);
            exit;
        }

        // Mark customer messages as read
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?")
            ->execute([$customer_id, $user_id]);

        $stmt = $pdo->prepare(
            "SELECT m.*, u.username AS sender_name
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE (m.sender_id = ? AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id = ?)
             ORDER BY m.created_at ASC"
        );
        $stmt->execute([$user_id, $customer_id, $customer_id, $user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    /* ── GET: admin polls for unread count across all customers ── */
    if ($_SERVER['REQUEST_METHOD'] === 'GET'
        && ($_GET['action'] ?? '') === 'get_unread_count'
        && in_array($role, ['admin', 'super_admin'])
    ) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0"
        );
        $stmt->execute([$user_id]);
        echo json_encode(['count' => (int) $stmt->fetchColumn()]);
        exit;
    }

    /* ── POST: send a message ── */
    if ($_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['action'] ?? '') === 'send_message'
    ) {
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            echo json_encode(['error' => 'Empty message']);
            exit;
        }

        $receiver_id = (int) ($_POST['receiver_id'] ?? 0);

        // Customer auto-routes to first available admin
        if ($role === 'customer' && !$receiver_id) {
            $admin = $pdo->query(
                "SELECT id FROM users WHERE role IN ('admin','super_admin') ORDER BY id ASC LIMIT 1"
            )->fetch(PDO::FETCH_ASSOC);
            $receiver_id = $admin ? (int) $admin['id'] : 0;
        }

        if (!$receiver_id) {
            echo json_encode(['error' => 'No admin available right now']);
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)"
        );
        $stmt->execute([$user_id, $receiver_id, $message]);
        echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
        exit;
    }

    echo json_encode(['error' => 'Invalid request']);

} catch (Throwable $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
