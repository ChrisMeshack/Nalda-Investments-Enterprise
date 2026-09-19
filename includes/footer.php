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
    <!-- ── Customer Live Chat Widget ── -->
    <button class="chat-toggle-btn" id="chat-toggle-btn" aria-label="Open live chat" aria-expanded="false">
        💬 <span class="chat-unread-dot" id="chat-unread-dot" hidden></span>
    </button>

    <div class="chat-widget" id="chat-widget" role="dialog" aria-label="Live Chat Support" aria-hidden="true">
        <div class="chat-widget-header" id="chat-widget-header">
            <span>💬 Live Chat Support</span>
            <button class="chat-close-btn" aria-label="Close chat">✕</button>
        </div>
        <div class="chat-widget-messages" id="client-chat-messages"></div>
        <form class="chat-widget-form" id="client-chat-form" autocomplete="off">
            <input type="text" id="client_chat_input" placeholder="Type a message…" required maxlength="500">
            <button type="submit" class="btn">Send</button>
        </form>
    </div>

    <script>
    (function () {
        'use strict';

        // Root-relative so it works from any page depth
        const API_URL  = '/Nalda/public/chat_api.php';
        const MY_ID    = <?php echo (int) $_SESSION['user_id']; ?>;
        const toggleBtn = document.getElementById('chat-toggle-btn');
        const widget    = document.getElementById('chat-widget');
        const closeBtn  = widget.querySelector('.chat-close-btn');
        const form      = document.getElementById('client-chat-form');
        const input     = document.getElementById('client_chat_input');
        const messages  = document.getElementById('client-chat-messages');
        const unreadDot = document.getElementById('chat-unread-dot');

        let isOpen    = false;
        let pollTimer = null;

        function openChat() {
            isOpen = true;
            widget.style.display      = 'flex';
            widget.setAttribute('aria-hidden', 'false');
            toggleBtn.setAttribute('aria-expanded', 'true');
            toggleBtn.style.display   = 'none';
            if (unreadDot) unreadDot.hidden = true;
            fetchMessages();
            pollTimer = setInterval(fetchMessages, 4000);
            input.focus();
        }

        function closeChat() {
            isOpen = false;
            widget.style.display      = 'none';
            widget.setAttribute('aria-hidden', 'true');
            toggleBtn.setAttribute('aria-expanded', 'false');
            toggleBtn.style.display   = 'flex';
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        }

        toggleBtn.addEventListener('click', openChat);
        closeBtn.addEventListener('click',  closeChat);

        function fetchMessages() {
            fetch(API_URL + '?action=get_client_chat')
                .then(r => r.json())
                .then(data => {
                    if (!Array.isArray(data)) return;
                    messages.innerHTML = '';
                    data.forEach(msg => {
                        const isMine = parseInt(msg.sender_id) === MY_ID;
                        const div = document.createElement('div');
                        div.className = 'chat-msg ' + (isMine ? 'chat-msg--sent' : 'chat-msg--recv');

                        const bubble = document.createElement('div');
                        bubble.className = 'chat-bubble';
                        bubble.textContent = msg.message;

                        const meta = document.createElement('span');
                        meta.className = 'chat-meta';
                        meta.textContent = isMine ? 'You' : (msg.sender_name || 'Support');

                        div.appendChild(bubble);
                        div.appendChild(meta);
                        messages.appendChild(div);
                    });
                    messages.scrollTop = messages.scrollHeight;

                    // Show unread dot if chat is closed and there are received messages
                    if (!isOpen && data.some(m => parseInt(m.sender_id) !== MY_ID && !parseInt(m.is_read))) {
                        if (unreadDot) unreadDot.hidden = false;
                    }
                })
                .catch(() => {}); // silent — don't break the page
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const msg = input.value.trim();
            if (!msg) return;

            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            fetch(API_URL, {
                method : 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body   : 'action=send_message&message=' + encodeURIComponent(msg)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    fetchMessages();
                } else {
                    alert(data.error || 'Could not send message.');
                }
            })
            .catch(() => alert('Network error. Please try again.'))
            .finally(() => { submitBtn.disabled = false; });
        });

        // Start a background poll for unread when chat is closed
        setInterval(() => {
            if (!isOpen) fetchMessages();
        }, 15000);

    })();
    </script>
    <?php endif; ?>

</body>
</html>
