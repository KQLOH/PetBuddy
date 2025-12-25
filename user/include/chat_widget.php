<style>
    :root {
        --widget-primary: #FFB774;
        --widget-primary-dark: #E89C55;
        --widget-bg: #f9fafb;
        --widget-white: #ffffff;
        --widget-text: #333333;
        --widget-border: #e0e0e0;
    }

    #chat-widget-wrapper {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
    }

    #chat-widget-btn {
        width: 60px;
        height: 60px;
        background-color: var(--widget-primary);
        border-radius: 50%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: visible !important;
    }

    #chat-widget-btn:hover {
        transform: scale(1.1);
        background-color: var(--widget-primary-dark);
    }

    #chat-widget-btn img {
        width: 32px;
        height: 32px;
        filter: brightness(0) invert(1);
        object-fit: contain;
    }

    #chat-notification {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #ef4444;
        color: white;
        border-radius: 50%;
        min-width: 24px;
        height: 24px;
        font-size: 12px;
        font-weight: 800;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        font-family: sans-serif;
        z-index: 10001;
        animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes popIn {
        from {
            transform: scale(0);
        }

        to {
            transform: scale(1);
        }
    }

    #chat-widget-modal {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 360px;
        height: 520px;
        background-color: var(--widget-white);
        border-radius: 12px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
        display: none;
        flex-direction: column;
        z-index: 99999;
        overflow: hidden;
        border: 1px solid var(--widget-border);
        animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .widget-header {
        background-color: var(--widget-white);
        padding: 15px 20px;
        border-bottom: 1px solid var(--widget-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .widget-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .widget-avatar {
        width: 40px;
        height: 40px;
        background-color: var(--widget-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .widget-avatar img {
        width: 20px;
        height: 20px;
        filter: brightness(0) invert(1);
        object-fit: contain;
    }

    .widget-info h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: var(--widget-text);
        font-family: sans-serif;
    }

    .widget-info span {
        display: block;
        font-size: 11px;
        color: var(--widget-grey);
        margin-top: 2px;
        font-family: sans-serif;
    }

    .status-dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        background-color: #22c55e;
        border-radius: 50%;
        margin-right: 4px;
    }

    .widget-close {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .widget-close img {
        width: 20px;
        height: 20px;
        opacity: 0.5;
        transition: 0.2s;
    }

    .widget-close:hover img {
        opacity: 1;
        transform: scale(1.1);
    }

    .widget-body {
        flex: 1;
        padding: 20px;
        background-color: var(--widget-bg);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .widget-body::-webkit-scrollbar {
        width: 5px;
    }

    .widget-body::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 4px;
    }

    .date-divider {
        text-align: center;
        margin: 10px 0;
        display: flex;
        justify-content: center;
    }

    .date-divider span {
        background: rgba(229, 231, 235, 0.8);
        color: #6b7280;
        font-size: 10px;
        padding: 4px 12px;
        border-radius: 12px;
        font-weight: 500;
        font-family: sans-serif;
    }

    .w-msg {
        max-width: 75%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.4;
        position: relative;
        word-wrap: break-word;
        font-family: sans-serif;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .w-msg-admin {
        align-self: flex-start;
        background-color: var(--widget-white);
        color: var(--widget-text);
        border: 1px solid var(--widget-border);
        border-bottom-left-radius: 2px;
    }

    .w-msg-user {
        align-self: flex-end;
        background-color: var(--widget-primary);
        color: white;
        border-bottom-right-radius: 2px;
    }

    .w-time {
        font-size: 10px;
        display: block;
        text-align: right;
        margin-top: 4px;
        opacity: 0.8;
    }

    .w-msg-admin .w-time {
        color: #888;
    }

    .w-msg-user .w-time {
        color: rgba(255, 255, 255, 0.9);
    }

    .widget-footer {
        padding: 15px;
        background-color: var(--widget-white);
        border-top: 1px solid var(--widget-border);
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .widget-input {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 25px;
        outline: none;
        font-size: 14px;
        background: #fff;
        font-family: sans-serif;
        transition: border-color 0.2s;
    }

    .widget-input:focus {
        border-color: var(--widget-primary);
    }

    .widget-send {
        background-color: var(--widget-primary);
        color: white;
        border: none;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(255, 183, 116, 0.4);
    }

    .widget-send:hover {
        background-color: var(--widget-primary-dark);
        transform: scale(1.05);
    }

    .widget-send img {
        width: 18px;
        filter: brightness(0) invert(1);
    }

    @media (max-width: 480px) {
        #chat-widget-modal {
            bottom: 0;
            right: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 0;
        }

        #chat-widget-wrapper {
            bottom: 20px;
            right: 20px;
        }
    }

    /* Modal Prompt Styles */
    #chat-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 100000;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    }

    #chat-modal-overlay.show {
        display: flex;
    }

    #chat-modal {
        background: white;
        border-radius: 16px;
        padding: 40px 30px 30px;
        max-width: 400px;
        width: 90%;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        animation: slideUp 0.3s ease;
        position: relative;
    }

    .chat-modal-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #E8F5E9;
    }

    .chat-modal-icon img {
        width: 40px;
        height: 40px;
    }

    .chat-modal-icon.error {
        background: #FFEBEE;
    }

    .chat-modal-title {
        font-size: 24px;
        font-weight: 700;
        color: #2F2F2F;
        margin: 0 0 10px 0;
    }

    .chat-modal-message {
        font-size: 15px;
        color: #666;
        margin: 0 0 25px 0;
        line-height: 1.5;
    }

    .chat-modal-buttons {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .chat-modal-btn {
        padding: 12px 32px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        min-width: 100px;
    }

    .chat-modal-btn-primary {
        background: #FFB774;
        color: white;
    }

    .chat-modal-btn-primary:hover {
        background: #E89C55;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 183, 116, 0.4);
    }

    .chat-modal-btn-secondary {
        background: #f5f5f5;
        color: #666;
    }

    .chat-modal-btn-secondary:hover {
        background: #e8e8e8;
    }
</style>

<div id="chat-widget-wrapper">
    <div id="chat-widget-btn" onclick="toggleChatWidget()">
        <img src="../images/bubble-chat.png" alt="Chat">
        <span id="chat-notification">0</span>
    </div>
</div>

<div id="chat-widget-modal">
    <div class="widget-header">
        <div class="widget-title">
            <div class="widget-avatar">
                <img src="../images/pawprint.png" alt="Icon">
            </div>
            <div class="widget-info">
                <h4>PetBuddy Support</h4>
                <span><span class="status-dot"></span>We are Online</span>
            </div>
        </div>
        <button class="widget-close" onclick="toggleChatWidget()">
            <img src="../images/error.png" alt="Close">
        </button>
    </div>

    <div class="widget-body" id="widget-chat-box">
        <div style="text-align:center; color:#9ca3af; margin-top:50px; font-size:13px; display:flex; flex-direction:column; align-items:center; font-family:sans-serif;">
            <img src="../images/comments.png" style="width:50px; opacity:0.2; margin-bottom:10px;">
            Loading conversation...
        </div>
    </div>

    <form class="widget-footer" id="widget-form">
        <input type="text" class="widget-input" id="widget-input" placeholder="Type a message..." autocomplete="off">
        <button type="submit" class="widget-send">
            <img src="../images/send.png" alt="Send">
        </button>
    </form>
</div>

<!-- Chat Modal Prompt -->
<div id="chat-modal-overlay">
    <div id="chat-modal">
        <div class="chat-modal-icon" id="chat-modal-icon">
            <img src="../images/success.png" alt="">
        </div>
        <h3 class="chat-modal-title" id="chat-modal-title">Login Required</h3>
        <p class="chat-modal-message" id="chat-modal-message">Please login to use the chat feature.</p>
        <div class="chat-modal-buttons" id="chat-modal-buttons">
            <button class="chat-modal-btn chat-modal-btn-primary" id="chat-login-btn">Go to Login</button>
            <button class="chat-modal-btn chat-modal-btn-secondary" onclick="closeChatModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
    let widgetOpen = false;
    let widgetLastCount = 0;

    function getApiPath() {
        if (window.location.href.indexOf('user/php') > -1) {
            return 'chat_api.php';
        } else {
            return 'user/php/chat_api.php';
        }
    }
    const API_URL = getApiPath();

    function getLoginPath() {
        if (window.location.href.indexOf('user/php') > -1) {
            return 'login.php';
        } else {
            return 'user/php/login.php';
        }
    }

    // Set login button path
    document.addEventListener('DOMContentLoaded', function() {
        const loginBtn = document.getElementById('chat-login-btn');
        if (loginBtn) {
            loginBtn.onclick = function() {
                window.location.href = getLoginPath();
            };
        }
    });

    function showChatLoginModal() {
        const overlay = document.getElementById('chat-modal-overlay');
        if (overlay) {
            overlay.classList.add('show');
        }
    }

    function closeChatModal() {
        const overlay = document.getElementById('chat-modal-overlay');
        if (overlay) {
            overlay.classList.remove('show');
        }
    }

    // Close modal when clicking overlay
    document.getElementById('chat-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) {
            closeChatModal();
        }
    });

    function toggleChatWidget() {
        const modal = document.getElementById('chat-widget-modal');
        const btnWrapper = document.getElementById('chat-widget-wrapper');
        const input = document.getElementById('widget-input');

        if (!widgetOpen) {
            // Check if user is logged in first
            fetch(API_URL + '?action=get_unread')
                .then(res => res.json())
                .then(data => {
                    if (data.error === 'Unauthorized') {
                        showChatLoginModal();
                        return;
                    }
                    // User is logged in, open widget
                    modal.style.display = 'flex';
                    btnWrapper.style.display = 'none';
                    widgetOpen = true;

                    loadWidgetMessages();
                    markMessagesAsRead();
                    setTimeout(() => input.focus(), 300);
                })
                .catch(e => {
                    console.error("Error checking login:", e);
                    showChatLoginModal();
                });
        } else {
            modal.style.display = 'none';
            btnWrapper.style.display = 'block';
            widgetOpen = false;
        }
    }

    document.addEventListener('click', function(event) {
        if (widgetOpen) {
            const modal = document.getElementById('chat-widget-modal');
            const btnWrapper = document.getElementById('chat-widget-wrapper');

            if (!modal.contains(event.target) && !btnWrapper.contains(event.target)) {
                toggleChatWidget();
            }
        }
    });

    function updateUnreadCount() {
        if (widgetOpen) return;

        fetch(API_URL + '?action=get_unread')
            .then(res => res.json())
            .then(data => {
                if (data.error === 'Unauthorized') {
                    // User not logged in, hide badge
                    document.getElementById('chat-notification').style.display = 'none';
                    return;
                }

                console.log("Unread API Response:", data);

                const badge = document.getElementById('chat-notification');
                const count = parseInt(data.unread);

                if (count > 0) {
                    badge.style.display = 'flex';
                    badge.innerText = count > 9 ? '9+' : count;
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(e => {
                console.error("Badge Error (Path might be wrong):", e);
            });
    }

    function markMessagesAsRead() {
        fetch(API_URL + '?action=mark_read')
            .then(res => res.json())
            .then(() => {
                document.getElementById('chat-notification').style.display = 'none';
            });
    }

    function loadWidgetMessages() {
        if (!widgetOpen && widgetLastCount > 0) {
            updateUnreadCount();
            return;
        }

        fetch(API_URL + '?action=fetch')
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    if (data.error === 'Unauthorized') {
                        showChatLoginModal();
                        toggleChatWidget(); // Close widget if open
                    }
                    return;
                }

                const box = document.getElementById('widget-chat-box');

                if (data.length !== widgetLastCount) {
                    if (data.length === 0) {
                        box.innerHTML = `
                            <div style="text-align:center; color:#9ca3af; margin-top:50px; font-family:sans-serif;">
                                <img src="../images/comments.png" style="width:50px; opacity:0.2; margin-bottom:10px;">
                                <p>Hi there! 👋<br>How can we help you today?</p>
                            </div>`;
                    } else {
                        box.innerHTML = '';
                        let lastDate = '';

                        data.forEach(msg => {
                            const msgDate = new Date(msg.created_at);
                            const dateStr = formatDateWidget(msgDate);

                            if (dateStr !== lastDate) {
                                const div = document.createElement('div');
                                div.className = 'date-divider';
                                div.innerHTML = `<span>${dateStr}</span>`;
                                box.appendChild(div);
                                lastDate = dateStr;
                            }

                            const type = msg.sender === 'member' ? 'w-msg-user' : 'w-msg-admin';
                            const safeTxt = msg.message.replace(/</g, "&lt;").replace(/>/g, "&gt;");

                            const div = document.createElement('div');
                            div.className = `w-msg ${type}`;
                            div.innerHTML = `${safeTxt}<span class="w-time">${msg.time}</span>`;
                            box.appendChild(div);
                        });
                    }
                    scrollToBottomWidget();
                    widgetLastCount = data.length;

                    if (widgetOpen) markMessagesAsRead();
                }
            });
    }

    function formatDateWidget(date) {
        if (isNaN(date.getTime())) return '';
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);

        if (date.toDateString() === today.toDateString()) return 'Today';
        if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
        return date.toLocaleDateString('en-GB', {
            day: 'numeric',
            month: 'short'
        });
    }

    document.getElementById('widget-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('widget-input');
        const text = input.value.trim();
        if (!text) return;

        const formData = new FormData();
        formData.append('action', 'send');
        formData.append('message', text);

        const box = document.getElementById('widget-chat-box');
        if (widgetLastCount === 0) box.innerHTML = '';

        const tempDiv = document.createElement('div');
        tempDiv.className = 'w-msg w-msg-user';
        tempDiv.style.opacity = '0.7';
        tempDiv.innerHTML = `${text.replace(/</g, "&lt;")}<span class="w-time">Sending...</span>`;
        box.appendChild(tempDiv);
        scrollToBottomWidget();
        input.value = '';

        fetch(API_URL, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    loadWidgetMessages();
                } else if (data.error === 'Unauthorized') {
                    showChatLoginModal();
                    toggleChatWidget(); // Close widget if open
                }
            })
            .catch(e => {
                console.error("Send message error:", e);
            });
    });

    function scrollToBottomWidget() {
        const box = document.getElementById('widget-chat-box');
        box.scrollTop = box.scrollHeight;
    }

    setInterval(() => {
        if (widgetOpen) {
            loadWidgetMessages();
        } else {
            updateUnreadCount();
        }
    }, 3000);

    updateUnreadCount();
</script>