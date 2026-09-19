<?php
// public/chat_admin.php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    die("Access denied.");
}

$pdo = getDB();
$admin_id = $_SESSION['user_id'];

// Get distinct users who have sent or received messages
$usersStmt = $pdo->query("
    SELECT u.id, u.username, 
    (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id AND m.is_read = 0) as unread
    FROM users u 
    WHERE u.role = 'customer' 
    AND u.id IN (SELECT sender_id FROM messages UNION SELECT receiver_id FROM messages)
");
$customers = $usersStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<style>
    .chat-container { display: flex; gap: 20px; height: 60vh; }
    .chat-sidebar { width: 30%; background: #fff; padding: 15px; overflow-y: auto; border: 1px solid #ddd; }
    .chat-box { width: 70%; background: #fff; padding: 15px; border: 1px solid #ddd; display: flex; flex-direction: column; }
    .chat-messages { flex-grow: 1; overflow-y: auto; margin-bottom: 15px; padding: 10px; border: 1px solid #eee; background: #fafafa; }
    .message { margin-bottom: 10px; padding: 8px 12px; border-radius: 15px; max-width: 70%; }
    .message.sent { background: #dcf8c6; margin-left: auto; }
    .message.received { background: #fff; border: 1px solid #ddd; margin-right: auto; }
    .customer-item { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
    .customer-item:hover { background: #f0f0f0; }
    .unread-badge { background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.8rem; }
</style>

<h2>Live Chat Hub</h2>
<div class="chat-container">
    <div class="chat-sidebar">
        <h3>Customers</h3>
        <?php foreach ($customers as $c): ?>
            <div class="customer-item" onclick="loadChat(<?php echo $c['id']; ?>, <?php echo json_encode($c['username']); ?>)">
                <?php echo htmlspecialchars($c['username']); ?>
                <?php if ($c['unread'] > 0): ?>
                    <span class="unread-badge"><?php echo $c['unread']; ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($customers)): ?>
            <p style="color:#888;font-size:.9rem;padding:10px 0">No customer chats yet.</p>
        <?php endif; ?>
    </div>
    
    <div class="chat-box" id="chat-box" style="display: none;">
        <h3 id="chat-title">Chat</h3>
        <div class="chat-messages" id="chat-messages"></div>
        <form id="chat-form" style="display: flex; gap: 10px;">
            <input type="hidden" id="active_customer_id">
            <input type="text" id="chat_input" style="flex-grow: 1; padding: 10px;" placeholder="Type a message..." required>
            <button type="submit" class="btn">Send</button>
        </form>
    </div>
</div>

<script>
let activeCustomerId = null;
let chatInterval = null;
const API_URL = '/Nalda/public/chat_api.php';

function loadChat(customerId, customerName) {
    activeCustomerId = customerId;
    document.getElementById('chat-box').style.display = 'flex';
    document.getElementById('chat-title').textContent = '💬 ' + customerName;
    document.getElementById('active_customer_id').value = customerId;
    fetchMessages();
    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(fetchMessages, 3000);
}

function fetchMessages() {
    if (!activeCustomerId) return;
    fetch(API_URL + '?action=get_admin_chat&customer_id=' + activeCustomerId)
        .then(response => response.json())
        .then(data => {
            if (!Array.isArray(data)) return;
            const container = document.getElementById('chat-messages');
            container.innerHTML = '';
            data.forEach(msg => {
                const isMine = msg.sender_id != activeCustomerId;
                const div = document.createElement('div');
                div.className = 'message ' + (isMine ? 'sent' : 'received');
                div.textContent = msg.message;
                container.appendChild(div);
            });
            container.scrollTop = container.scrollHeight;
        })
        .catch(() => {});
}

document.getElementById('chat-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const message = document.getElementById('chat_input').value.trim();
    if (!message) return;
    const btn = this.querySelector('button');
    btn.disabled = true;

    fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=send_message&receiver_id=' + activeCustomerId + '&message=' + encodeURIComponent(message)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('chat_input').value = '';
            fetchMessages();
        } else {
            alert(data.error || 'Could not send.');
        }
    })
    .catch(() => alert('Network error.'))
    .finally(() => { btn.disabled = false; });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
