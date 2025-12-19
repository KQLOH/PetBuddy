<!-- Chat Widget Styling -->
<style>
    /* Floating Button */
    #chat-widget-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background-color: #FF9F1C; /* Primary Color */
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 9999;
        transition: transform 0.2s, background-color 0.2s;
    }
    #chat-widget-btn:hover { transform: scale(1.05); background-color: #E68E3F; }
    #chat-widget-btn img { width: 30px; height: 30px; filter: invert(1); } /* White Icon */

    /* Notification Badge */
    #chat-notification {
        position: absolute;
        top: 0;
        right: 0;
        width: 14px;
        height: 14px;
        background-color: #ef4444; /* Red */
        border-radius: 50%;
        border: 2px solid white;
        display: none; /* Hidden by default */
    }

    /* Chat Modal Window */
    #chat-widget-modal {
        position: fixed;
        bottom: 90px;
        right: 20px;
        width: 350px;
        height: 500px;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        display: none; /* Hidden by default */
        flex-direction: column;
        z-index: 9999;
        overflow: hidden;
        border: 1px solid #e0e0e0;
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Header */
    .widget-header {
        background-color: #FF9F1C;
        padding: 15px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: system-ui, -apple-system, sans-serif;
    }
    .widget-title h4 { margin: 0; font-size: 1rem; font-weight: 700; }
    .widget-title span { font-size: 0.75rem; opacity: 0.9; }
    .widget-close { cursor: pointer; font-size: 1.2rem; background: none; border: none; color: white; }

    /* Body */
    .widget-body {
        flex: 1;
        padding: 15px;
        background-color: #F9FAFB;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .widget-body::-webkit-scrollbar { width: 5px; }
    .widget-body::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

    /* Messages */
    .w-msg {
        max-width: 80%;
        padding: 8px 12px;
        border-radius: 12px;
        font-size: 0.9rem;
        line-height: 1.4;
        position: relative;
        font-family: sans-serif;
    }
    .w-msg-admin {
        align-self: flex-start;
        background-color: white;
        color: #333;
        border: 1px solid #eee;
        border-bottom-left-radius: 2px;
    }
    .w-msg-user {
        align-self: flex-end;
        background-color: #FF9F1C;
        color: white;
        border-bottom-right-radius: 2px;
    }
    .w-time { font-size: 0.65rem; display: block; text-align: right; margin-top: 4px; opacity: 0.7; }

    /* Footer (Input) */
    .widget-footer {
        padding: 10px;
        background-color: white;
        border-top: 1px solid #eee;
        display: flex;
        gap: 8px;
    }
    .widget-input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 20px;
        outline: none;
        font-size: 0.9rem;
    }
    .widget-send {
        background-color: #FF9F1C;
        color: white;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .widget-send:hover { background-color: #E68E3F; }
    .widget-send svg { width: 16px; height: 16px; fill: white; }

    /* Mobile Responsiveness */
    @media (max-width: 480px) {
        #chat-widget-modal {
            bottom: 0; right: 0; left: 0; width: 100%; height: 100%; border-radius: 0;
        }
        #chat-widget-btn { bottom: 15px; right: 15px; }
    }
</style>

<!-- Floating Button -->
<div id="chat-widget-btn" onclick="toggleChatWidget()">
    <!-- Uses your local pawprint icon, colored white via CSS filter -->
    <img src="../images/bubble-chat.png" alt="Chat">
    <span id="chat-notification"></span>
</div>

<!-- Chat Modal -->
<div id="chat-widget-modal">
    <div class="widget-header">
        <div class="widget-title">
            <h4>PetBuddy Support</h4>
            <span>We reply usually within minutes</span>
        </div>
        <button class="widget-close" onclick="toggleChatWidget()">&times;</button>
    </div>
    
    <div class="widget-body" id="widget-chat-box">
        <div style="text-align:center; color:#999; margin-top:50px; font-size:0.9rem;">Loading conversation...</div>
    </div>

    <form class="widget-footer" id="widget-form">
        <input type="text" class="widget-input" id="widget-input" placeholder="Type a message..." autocomplete="off">
        <button type="submit" class="widget-send">
            <!-- Simple SVG Arrow -->
            <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path></svg>
        </button>
    </form>
</div>

<!-- Chat Logic -->
<script>
    let widgetOpen = false;
    let widgetLastCount = 0;
    let pollingInterval = null;

    // 1. Toggle Visibility
    function toggleChatWidget() {
        const modal = document.getElementById('chat-widget-modal');
        const btn = document.getElementById('chat-widget-btn');
        
        if (!widgetOpen) {
            modal.style.display = 'flex';
            btn.style.display = 'none'; // Hide button when open (optional, or keep it)
            widgetOpen = true;
            loadWidgetMessages();
            scrollToBottomWidget();
        } else {
            modal.style.display = 'none';
            btn.style.display = 'flex';
            widgetOpen = false;
        }
    }

    // 2. Load Messages
    function loadWidgetMessages() {
        // Assumes chat_api.php is in the same folder (user/)
        fetch('chat_api.php?action=fetch')
            .then(res => res.json())
            .then(data => {
                if(data.error) return;

                const box = document.getElementById('widget-chat-box');
                
                // Only re-render if new messages exist
                if (data.length !== widgetLastCount) {
                    if(data.length === 0) {
                        box.innerHTML = '<div style="text-align:center; color:#888; margin-top:50px;">Hi there! 👋<br>How can we help you?</div>';
                    } else {
                        box.innerHTML = data.map(msg => {
                            const type = msg.sender === 'member' ? 'w-msg-user' : 'w-msg-admin';
                            // Escape HTML to prevent XSS
                            const safeTxt = msg.message.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                            return `<div class="w-msg ${type}">
                                        ${safeTxt}
                                        <span class="w-time">${msg.time}</span>
                                    </div>`;
                        }).join('');
                    }
                    scrollToBottomWidget();
                    widgetLastCount = data.length;
                }
            })
            .catch(e => console.error("Chat Error:", e));
    }

    // 3. Send Message
    document.getElementById('widget-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('widget-input');
        const text = input.value.trim();
        if(!text) return;

        const formData = new FormData();
        formData.append('action', 'send');
        formData.append('message', text);

        // Optimistic UI (Add immediately for better feel)
        const box = document.getElementById('widget-chat-box');
        const tempDiv = document.createElement('div');
        tempDiv.className = 'w-msg w-msg-user';
        tempDiv.style.opacity = '0.5'; // Sending state
        tempDiv.innerHTML = `${text.replace(/</g, "&lt;")}`;
        box.appendChild(tempDiv);
        scrollToBottomWidget();
        input.value = '';

        fetch('chat_api.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    loadWidgetMessages(); // Refresh real data
                }
            });
    });

    function scrollToBottomWidget() {
        const box = document.getElementById('widget-chat-box');
        box.scrollTop = box.scrollHeight;
    }

    // Poll for messages every 3 seconds
    setInterval(loadWidgetMessages, 3000);
</script>