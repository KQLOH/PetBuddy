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
    <link rel="stylesheet" href="../css/admin_btn.css">
    <link rel="stylesheet" href="../css/admin_chat.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebarToggle" class="sidebar-toggle"><img src="../images/menu.png"></button>
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
        document.getElementById('sidebarToggle').addEventListener('click', function() {
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
                    if (document.getElementById('searchContact').value === '') {
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

            if (data.length === 0) {
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
                    if (!imgPath.startsWith('http') && !imgPath.startsWith('../')) {
                        imgPath = '../../user/' + imgPath.replace(/^\//, '');
                    }
                    avatarContent = `<img src="${imgPath}" alt="User">`;
                } else {
                    // Fallback to user.png
                    avatarContent = `<img src="../../images/user.png" class="avatar-placeholder">`;
                }

                const msgDate = new Date(user.last_msg_time);
                const today = new Date();
                let timeStr = msgDate.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                if (msgDate.getDate() !== today.getDate()) {
                    timeStr = msgDate.toLocaleDateString([], {
                        month: 'short',
                        day: 'numeric'
                    });
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
                if (!imgPath.startsWith('http') && !imgPath.startsWith('../')) {
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
            if (!currentMemberId) return;
            const overlay = document.getElementById('customAlert');
            const btnConfirm = document.getElementById('btnConfirmDelete');
            btnConfirm.onclick = () => {
                deleteChat();
                closeCustomAlert();
            };
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

            fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
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
                    if (data.length !== lastMsgCount) {
                        const area = document.getElementById('messageArea');
                        const isAtBottom = area.scrollHeight - area.scrollTop <= area.clientHeight + 100;

                        area.innerHTML = '';
                        let lastDate = '';

                        data.forEach(msg => {
                            const msgDate = new Date(msg.created_at);
                            const dateStr = formatDate(msgDate);

                            if (dateStr !== lastDate) {
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
            if (date.toDateString() === today.toDateString()) return 'Today';
            if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
            return date.toLocaleDateString('en-GB', {
                day: 'numeric',
                month: 'short'
            });
        }

        const inputField = document.getElementById('adminInput');
        const sendBtn = document.getElementById('sendBtn');

        function sendMessage() {
            const text = inputField.value.trim();
            if (!text || !currentMemberId) return;

            const formData = new FormData();
            formData.append('action', 'send_reply');
            formData.append('member_id', currentMemberId);
            formData.append('message', text);

            fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        inputField.value = '';
                        loadMessages();
                    }
                });
        }

        sendBtn.addEventListener('click', sendMessage);
        inputField.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') sendMessage();
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        setInterval(() => {
            if (currentMemberId) loadMessages();
            loadContacts();
        }, 3000);
        loadContacts();
    </script>
</body>

</html>