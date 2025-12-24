<?php
session_start();
require_once '../../user/include/db.php';

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    header('Location: admin_login.php');
    exit;
}

$adminName = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Support - PetBuddy Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin_product.css">
    
    <style>
        /* --- LAYOUT --- */
        .content {
            height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
            padding: 0;
            overflow: hidden;
            background-color: #f3f4f6;
        }
        .chat-container {
            display: flex;
            height: 100%;
            background: #fff;
            overflow: hidden;
        }

        /* --- ICONS (PNG) --- */
        .icon-sm { width: 16px; height: 16px; object-fit: contain; }
        .icon-md { width: 20px; height: 20px; object-fit: contain; }
        .icon-lg { width: 24px; height: 24px; object-fit: contain; }
        .icon-xl { width: 40px; height: 40px; object-fit: contain; opacity: 0.3; }
        
        /* White icon filter for colored buttons */
        .icon-white { filter: brightness(0) invert(1); }
        
        /* Spinning animation for loading.png */
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* --- SIDEBAR --- */
        .chat-sidebar {
            width: 340px;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            background: #fff;
            z-index: 2;
        }
        .sidebar-header {
            padding: 16px 20px;
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
        }
        .search-wrapper { position: relative; display: flex; align-items: center;}
        
        /* Search Icon Positioning */
        .search-icon-img {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            width: 14px; height: 14px; opacity: 0.5;
        }
        
        .search-box {
            width: 100%;
            padding: 10px 10px 10px 36px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            background: #f9fafb;
            outline: none;
            transition: all 0.2s;
        }
        .search-box:focus { background: #fff; border-color: #FF9F1C; }

        .contact-list { flex: 1; overflow-y: auto; }
        .contact-item {
            display: flex; align-items: center; padding: 12px 20px;
            border-bottom: 1px solid #f8f8f8; cursor: pointer; transition: background 0.2s;
        }
        .contact-item:hover { background-color: #f9fafb; }
        .contact-item.active { background-color: #FFF7ED; border-right: 3px solid #FF9F1C; }

        .avatar {
            width: 44px; height: 44px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 16px; margin-right: 14px; flex-shrink: 0;
            overflow: hidden; border: 1px solid #eee; background-color: #FFF4E5; color: #FF9F1C;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Avatar Placeholder Icon */
        .avatar-placeholder { width: 20px; height: 20px; opacity: 0.5; }

        .contact-info { flex: 1; min-width: 0; }
        .contact-top { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px; }
        .contact-name { font-weight: 600; font-size: 14px; color: #111827; }
        .contact-time { font-size: 11px; color: #9ca3af; }
        .contact-preview { font-size: 12px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .unread-badge {
            background-color: #ef4444; color: white;
            font-size: 10px; padding: 2px 6px; border-radius: 10px; font-weight: bold;
        }

        /* --- MAIN AREA --- */
        .chat-main {
            flex: 1; display: flex; flex-direction: column; background-color: #fff; position: relative;
        }
        .chat-header {
            padding: 0 24px; height: 68px; border-bottom: 1px solid #e5e7eb;
            background: #fff; display: flex; align-items: center; justify-content: space-between;
        }
        .header-user { display: flex; align-items: center; }
        .header-info h3 { margin: 0; font-size: 16px; color: #111827; font-weight: 700; }
        .header-info p { margin: 2px 0 0; font-size: 12px; color: #6b7280; }

        /* Delete Button with PNG */
        .btn-delete-chat {
            background: #fee2e2; border: none;
            width: 36px; height: 36px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-delete-chat:hover { background: #fecaca; transform: scale(1.05); }
        .btn-delete-chat img { width: 16px; height: 16px; }

        .messages-area {
            flex: 1; padding: 20px 24px; overflow-y: auto;
            background-color: #F4F6F8;
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 20px 20px;
            display: flex; flex-direction: column; gap: 8px;
        }

        .date-divider { text-align: center; margin: 20px 0; }
        .date-divider span {
            background: rgba(229, 231, 235, 0.8); color: #6b7280; font-size: 11px;
            padding: 4px 12px; border-radius: 12px; font-weight: 500;
        }

        .msg-bubble {
            max-width: 65%; padding: 12px 16px; border-radius: 16px;
            font-size: 14px; line-height: 1.5; position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .msg-member { align-self: flex-start; background: #fff; color: #1f2937; border-bottom-left-radius: 4px; }
        .msg-admin { align-self: flex-end; background: #FF9F1C; color: white; border-bottom-right-radius: 4px; }
        .msg-time { font-size: 10px; margin-top: 4px; text-align: right; opacity: 0.7; display: block; }

        .input-area {
            padding: 20px 24px; border-top: 1px solid #e5e7eb; background: #fff;
            display: flex; gap: 12px; align-items: center;
        }
        .chat-input {
            flex: 1; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 12px;
            outline: none; font-size: 14px; background: #f9fafb;
        }
        .chat-input:focus { border-color: #FF9F1C; background: #fff; }
        
        .btn-send {
            background: #FF9F1C; border: none;
            width: 46px; height: 46px; border-radius: 12px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s; flex-shrink: 0;
        }
        .btn-send:hover { background: #e08b15; }
        /* Send icon needs to be white */
        .btn-send img { width: 18px; filter: brightness(0) invert(1); }

        .empty-state {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            height: 100%; color: #9ca3af;
        }
        .empty-state img { width: 60px; height: 60px; opacity: 0.2; margin-bottom: 15px; }

        /* Custom Alert */
        .custom-alert-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: none; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
        .custom-alert-overlay.show { opacity: 1; }
        .custom-alert-box { background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); transform: scale(0.8); transition: transform 0.3s ease; }
        .custom-alert-overlay.show .custom-alert-box { transform: scale(1); }
        .custom-alert-icon { width: 60px; height: 60px; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; background: #FFF4E5; border: 2px solid #FF9F1C; }
        .custom-alert-icon img { width: 30px; }
        .custom-alert-title { margin: 0 0 10px; font-size: 1.25rem; color: #111827; font-weight: 700; }
        .custom-alert-text { color: #6b7280; margin-bottom: 24px; font-size: 14px; }
        .custom-alert-buttons { display: flex; justify-content: center; gap: 12px; }
        .btn-alert { padding: 10px 20px; border-radius: 8px; cursor: pointer; border: none; font-weight: 600; font-size: 14px; }
        .btn-alert-confirm { background: #D92D20; color: white; }
        .btn-alert-cancel { background: #F3F4F6; color: #374151; }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebarToggle" class="sidebar-toggle">☰</button>
                <div class="topbar-title">Customer Support</div>
            </div>
            <span class="tag-pill" style="margin-right: 20px;">Admin: <?= htmlspecialchars($adminName) ?></span>
        </header>

        <main class="content">
            <div class="chat-container">
                
                <div class="chat-sidebar">
                    <div class="sidebar-header">
                        <div class="search-wrapper">
                            <img src="../images/search.png" class="search-icon-img">
                            <input type="text" id="searchContact" class="search-box" placeholder="Search user..." onkeyup="filterContacts()">
                        </div>
                    </div>
                    <div id="contactList" class="contact-list">
                        <div style="padding:40px 20px; text-align:center; color:#ccc; display:flex; flex-direction:column; align-items:center;">
                            <img src="../images/loading.png" class="spin icon-md" style="margin-bottom:10px;">
                            <span>Loading...</span>
                        </div>
                    </div>
                </div>

                <div class="chat-main">
                    
                    <div class="chat-header" id="chatHeader" style="display:none;">
                        <div class="header-user">
                            <div class="avatar" id="headerAvatarContainer" style="margin-right:12px; width:40px; height:40px;"></div>
                            <div class="header-info">
                                <h3 id="chatUserName">User Name</h3>
                                <p>Member ID: <span id="chatUserId"></span></p>
                            </div>
                        </div>
                        
                        <button class="btn-delete-chat" title="Delete Conversation" onclick="confirmDeleteChat()">
                            <img src="../images/dusbin.png" alt="Delete">
                        </button>
                    </div>

                    <div id="messageArea" class="messages-area">
                        <div class="empty-state">
                            <img src="../images/comments.png" alt="Comments">
                            <p>Select a conversation to start chatting</p>
                        </div>
                    </div>

                    <div class="input-area" id="inputArea" style="display:none;">
                        <input type="text" id="adminInput" class="chat-input" placeholder="Type your reply..." autocomplete="off">
                        <button type="button" id="sendBtn" class="btn-send">
                            <img src="../images/send.png" alt="Send">
                        </button>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <div id="customAlert" class="custom-alert-overlay">
        <div class="custom-alert-box">
            <div class="custom-alert-icon">
                <img src="../images/warning.png" alt="Warning">
            </div>
            <h3 class="custom-alert-title">Delete Conversation?</h3>
            <p class="custom-alert-text">This will permanently delete all messages with this user. This action cannot be undone.</p>
            <div class="custom-alert-buttons">
                <button class="btn-alert btn-alert-cancel" onclick="closeCustomAlert()">Cancel</button>
                <button class="btn-alert btn-alert-confirm" id="btnConfirmDelete">Yes, Delete</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.body.classList.toggle('sidebar-collapsed');
        });

        let currentMemberId = null;
        let lastMsgCount = 0;
        let allContacts = [];

        function loadContacts() {
            fetch('chat_api.php?action=get_contacts')
                .then(res => res.json())
                .then(data => {
                    allContacts = data;
                    if(document.getElementById('searchContact').value === '') {
                        renderContacts(data);
                    }
                })
                .catch(err => console.error("Error loading contacts:", err));
        }

        function filterContacts() {
            const query = document.getElementById('searchContact').value.toLowerCase();
            const filtered = allContacts.filter(user => user.full_name.toLowerCase().includes(query));
            renderContacts(filtered);
        }

        function renderContacts(data) {
            const list = document.getElementById('contactList');
            list.innerHTML = '';
            
            if(data.length === 0) {
                list.innerHTML = '<div style="padding:20px; text-align:center; color:#999; font-size:13px;">No conversations found.</div>';
                return;
            }

            data.forEach(user => {
                const isActive = currentMemberId == user.member_id ? 'active' : '';
                let badgeHtml = user.unread_count > 0 ? `<span class="unread-badge">${user.unread_count}</span>` : '';

                // Avatar Logic (Image or Fallback)
                let avatarContent = '';
                if (user.image && user.image.trim() !== '') {
                    let imgPath = user.image;
                    if(!imgPath.startsWith('http') && !imgPath.startsWith('../')) {
                        imgPath = '../../user/' + imgPath.replace(/^\//, '');
                    }
                    avatarContent = `<img src="${imgPath}" alt="User">`;
                } else {
                    // Fallback to user.png
                    avatarContent = `<img src="../../images/user.png" class="avatar-placeholder">`;
                }

                const msgDate = new Date(user.last_msg_time);
                const today = new Date();
                let timeStr = msgDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                if(msgDate.getDate() !== today.getDate()) {
                    timeStr = msgDate.toLocaleDateString([], {month:'short', day:'numeric'});
                }

                const safeImage = user.image ? user.image.replace(/'/g, "\\'") : ''; 

                const item = document.createElement('div');
                item.className = `contact-item ${isActive}`;
                item.onclick = () => openChat(user.member_id, user.full_name, safeImage);
                item.innerHTML = `
                    <div class="avatar" ${!user.image ? 'style="background:#FFF4E5;"' : 'style="border:none;"'}>
                        ${avatarContent}
                    </div>
                    <div class="contact-info">
                        <div class="contact-top">
                            <span class="contact-name">${user.full_name}</span>
                            <span class="contact-time">${timeStr}</span>
                        </div>
                        <div class="contact-top">
                            <span class="contact-preview">Click to view chat...</span>
                            ${badgeHtml}
                        </div>
                    </div>
                `;
                list.appendChild(item);
            });
        }

        function openChat(memberId, name, image) {
            currentMemberId = memberId;
            document.getElementById('chatUserName').textContent = name;
            document.getElementById('chatUserId').textContent = memberId;
            
            // Header Avatar
            const headerAvatar = document.getElementById('headerAvatarContainer');
            if (image && image.trim() !== '') {
                let imgPath = image;
                if(!imgPath.startsWith('http') && !imgPath.startsWith('../')) {
                    imgPath = '../../user/' + imgPath.replace(/^\//, '');
                }
                headerAvatar.innerHTML = `<img src="${imgPath}" alt="User">`;
                headerAvatar.style.background = 'transparent';
                headerAvatar.style.border = 'none';
            } else {
                headerAvatar.innerHTML = `<img src="../../images/user.png" class="avatar-placeholder">`;
                headerAvatar.style.background = '#f0f0f0';
            }
            
            document.getElementById('chatHeader').style.display = 'flex';
            document.getElementById('inputArea').style.display = 'flex';
            document.getElementById('messageArea').innerHTML = ''; 
            lastMsgCount = 0; 

            loadMessages();
            loadContacts(); 
            
            setTimeout(() => document.getElementById('adminInput').focus(), 100);
        }

        function confirmDeleteChat() {
            if(!currentMemberId) return;
            const overlay = document.getElementById('customAlert');
            const btnConfirm = document.getElementById('btnConfirmDelete');
            btnConfirm.onclick = () => { deleteChat(); closeCustomAlert(); };
            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);
        }

        function closeCustomAlert() {
            const overlay = document.getElementById('customAlert');
            overlay.classList.remove('show');
            setTimeout(() => overlay.style.display = 'none', 300);
        }

        function deleteChat() {
            const formData = new FormData();
            formData.append('action', 'delete_conversation');
            formData.append('member_id', currentMemberId);

            fetch('chat_api.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        currentMemberId = null;
                        document.getElementById('chatHeader').style.display = 'none';
                        document.getElementById('inputArea').style.display = 'none';
                        document.getElementById('messageArea').innerHTML = `
                            <div class="empty-state">
                                <img src="../images/comments.png">
                                <p>Conversation Deleted</p>
                            </div>
                        `;
                        loadContacts();
                    } else {
                        alert('Error deleting chat');
                    }
                });
        }

        function loadMessages() {
            if (!currentMemberId) return;

            fetch(`chat_api.php?action=fetch_messages&member_id=${currentMemberId}`)
                .then(res => res.json())
                .then(data => {
                    if(data.length !== lastMsgCount) {
                        const area = document.getElementById('messageArea');
                        const isAtBottom = area.scrollHeight - area.scrollTop <= area.clientHeight + 100;

                        area.innerHTML = '';
                        let lastDate = '';

                        data.forEach(msg => {
                            const msgDate = new Date(msg.created_at);
                            const dateStr = formatDate(msgDate);
                            
                            if(dateStr !== lastDate) {
                                const div = document.createElement('div');
                                div.className = 'date-divider';
                                div.innerHTML = `<span>${dateStr}</span>`;
                                area.appendChild(div);
                                lastDate = dateStr;
                            }

                            const isMe = msg.sender === 'admin';
                            const div = document.createElement('div');
                            div.className = `msg-bubble ${isMe ? 'msg-admin' : 'msg-member'}`;
                            div.innerHTML = `${escapeHtml(msg.message)}<span class="msg-time">${msg.time}</span>`;
                            area.appendChild(div);
                        });

                        if (lastMsgCount === 0 || isAtBottom) {
                            area.scrollTop = area.scrollHeight;
                        }
                        lastMsgCount = data.length;
                    }
                });
        }

        function formatDate(date) {
            const today = new Date();
            const yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            if(date.toDateString() === today.toDateString()) return 'Today';
            if(date.toDateString() === yesterday.toDateString()) return 'Yesterday';
            return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
        }

        const inputField = document.getElementById('adminInput');
        const sendBtn = document.getElementById('sendBtn');

        function sendMessage() {
            const text = inputField.value.trim();
            if(!text || !currentMemberId) return;

            const formData = new FormData();
            formData.append('action', 'send_reply');
            formData.append('member_id', currentMemberId);
            formData.append('message', text);

            fetch('chat_api.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        inputField.value = '';
                        loadMessages();
                    }
                });
        }

        sendBtn.addEventListener('click', sendMessage);
        inputField.addEventListener('keydown', (e) => { if (e.key === 'Enter') sendMessage(); });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        setInterval(() => { if(currentMemberId) loadMessages(); loadContacts(); }, 3000);
        loadContacts();
    </script>
</body>
</html>