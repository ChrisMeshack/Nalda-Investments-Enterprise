    </main>

    <!-- ── Bottom offer marquee (full-width, outside main) ── -->
    <div class="marquee-container" style="margin:0;">
        <div class="marquee-content">
            &#127381; Free delivery on orders over Ksh 2,000 &nbsp;&nbsp;|&nbsp;&nbsp;
            &#128293; New deals added every day &nbsp;&nbsp;|&nbsp;&nbsp;
            &#128222; Call us: 0700 000 000 &nbsp;&nbsp;|&nbsp;&nbsp;
            &#11088; Trusted by thousands of shoppers in Kitale
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Nalda Investment Enterprise. All rights reserved.</p>
        <p class="footer-dev">
            Developer: Chris Meshack &nbsp;|&nbsp;
            Contacts: <a href="tel:+254757983900">0757 983 900</a> &nbsp;|&nbsp;
            Email: <a href="mailto:chrismeshackwork@gmail.com">chrismeshackwork@gmail.com</a>
        </p>
    </footer>
    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'customer'): ?>
    <div class="chat-toggle-btn" id="chat-toggle-btn" onclick="toggleChat()">
        💬
    </div>

    <div class="chat-widget" id="chat-widget">
        <div class="chat-widget-header" onclick="toggleChat()">
            <span>Live Chat Support</span>
            <span>▼</span>
        </div>
        <div class="chat-widget-messages" id="client-chat-messages">
            <!-- Messages load here -->
        </div>
        <form class="chat-widget-form" id="client-chat-form">
            <input type="text" id="client_chat_input" placeholder="Type a message..." required>
            <button type="submit" class="btn">Send</button>
        </form>
    </div>

    <script>
    let clientChatInterval = null;

    function toggleChat() {
        const widget = document.getElementById('chat-widget');
        const toggleBtn = document.getElementById('chat-toggle-btn');
        if (widget.style.display === 'flex') {
            widget.style.display = 'none';
            toggleBtn.style.display = 'flex';
            if (clientChatInterval) clearInterval(clientChatInterval);
        } else {
            widget.style.display = 'flex';
            toggleBtn.style.display = 'none';
            fetchClientMessages();
            clientChatInterval = setInterval(fetchClientMessages, 3000);
        }
    }

    function fetchClientMessages() {
        fetch('chat_api.php?action=get_client_chat')
            .then(response => response.json())
            .then(data => {
                if (data.error) return;
                const container = document.getElementById('client-chat-messages');
                container.innerHTML = '';
                const myId = <?php echo $_SESSION['user_id']; ?>;
                data.forEach(msg => {
                    const div = document.createElement('div');
                    div.className = 'message ' + (msg.sender_id == myId ? 'sent' : 'received');
                    div.style.marginBottom = '10px';
                    div.style.padding = '8px 12px';
                    div.style.borderRadius = '15px';
                    div.style.maxWidth = '80%';
                    div.style.clear = 'both';
                    if (msg.sender_id == myId) {
                        div.style.background = '#dcf8c6';
                        div.style.float = 'right';
                    } else {
                        div.style.background = '#fff';
                        div.style.border = '1px solid #ddd';
                        div.style.float = 'left';
                    }
                    div.textContent = msg.message;
                    container.appendChild(div);
                });
                container.scrollTop = container.scrollHeight;
            });
    }

    document.getElementById('client-chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const message = document.getElementById('client_chat_input').value;
        fetch('chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=send_message&message=' + encodeURIComponent(message)
        }).then(() => {
            document.getElementById('client_chat_input').value = '';
            fetchClientMessages();
        });
    });
    </script>
    <?php endif; ?>

</body>
</html>
