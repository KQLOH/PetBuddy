<?php
session_start();
// include '../include/admin_check.php'; // Ensure only admins can access
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Customer Support</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Simple Chat Styles reused */
        .msg-bubble {
            max-width: 75%; padding: 10px 15px; border-radius: 10px; margin-bottom: 8px; font-size: 0.95rem; position: relative;
        }
        .msg-member { background: #f1f1f1; color: #333; align-self: flex-start; border-bottom-left-radius: 2px; }
        .msg-admin { background: #FF9F1C; color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
        .contact-item.active { background-color: #FFF7ED; border-right: 4px solid #FF9F1C; }
        
        /* Animation for new message badge */
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .badge-pulse {
            animation: pulse-red 2s infinite;
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col">

    <!-- Simple Admin Header -->
    <header class="bg-white shadow p-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800"><i class="fas fa-headset text-orange-500 mr-2"></i> Customer Support</h1>
        <a href="dashboard.php" class="text-gray-500 hover:text-gray-700">Back to Dashboard</a>
    </header>

    <div class="flex flex-1 overflow-hidden max-w-7xl mx-auto w-full p-4 gap-4">
        
        <!-- LEFT: Contact List -->
        <div class="w-1/3 bg-white rounded-lg shadow overflow-hidden flex flex-col">
            <div class="p-4 border-b bg-gray-50">
                <h2 class="font-bold text-gray-700">Conversations</h2>
            </div>
            <div id="contactList" class="flex-1 overflow-y-auto p-2 space-y-1">
                <!-- Contacts loaded via JS -->
                <div class="text-center text-gray-400 mt-10">Loading...</div>
            </div>
        </div>

        <!-- RIGHT: Chat Area -->
        <div class="w-2/3 bg-white rounded-lg shadow flex flex-col">
            <!-- Chat Header -->
            <div class="p-4 border-b flex items-center gap-3 bg-gray-50" id="chatHeader" style="display:none;">
                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                    <i class="fas fa-user text-gray-500"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800" id="chatUserName">Select a user</h3>
                    <p class="text-xs text-gray-500">Member ID: <span id="chatUserId"></span></p>
                </div>
            </div>

            <!-- Messages -->
            <div id="messageArea" class="flex-1 overflow-y-auto p-4 flex flex-col bg-gray-50">
                <div class="text-center text-gray-400 mt-20">Select a conversation to start chatting</div>
            </div>

            <!-- Input Area -->
            <div class="p-4 border-t bg-white" id="inputArea" style="display:none;">
                <form id="adminChatForm" class="flex gap-2">
                    <input type="text" id="adminInput" class="flex-1 border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-orange-400" placeholder="Type your reply..." autocomplete="off">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-2 rounded-lg font-bold transition">Send</button>
                </form>
            </div>
        </div>

    </div>

    <script>
        let currentMemberId = null;
        let lastMsgCount = 0;

        // 1. Load Contact List
        function loadContacts() {
            fetch('chat_api.php?action=get_contacts')
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('contactList');
                    // We don't clear innerHTML here immediately to avoid flickering if we wanted to optimize, 
                    // but for simplicity, we rebuild the list.
                    list.innerHTML = '';
                    
                    if(data.length === 0) {
                        list.innerHTML = '<div class="p-4 text-center text-gray-400">No active chats</div>';
                        return;
                    }

                    data.forEach(user => {
                        const isActive = currentMemberId == user.member_id;
                        
                        // Create Badge HTML if unread count > 0
                        let badgeHtml = '';
                        if (user.unread_count > 0) {
                            badgeHtml = `<div class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full ml-auto badge-pulse">${user.unread_count}</div>`;
                        }

                        const div = document.createElement('div');
                        div.className = `contact-item p-3 rounded cursor-pointer hover:bg-gray-50 flex items-center gap-3 transition ${isActive ? 'active' : ''}`;
                        div.onclick = () => openChat(user.member_id, user.full_name);
                        
                        div.innerHTML = `
                            <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-bold shrink-0">
                                ${user.full_name.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-bold text-gray-800 text-sm truncate ${user.unread_count > 0 ? 'font-extrabold' : ''}">${user.full_name}</h4>
                                    ${badgeHtml} 
                                </div>
                                <p class="text-xs text-gray-500 truncate">Last active: ${new Date(user.last_msg_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</p>
                            </div>
                        `;
                        list.appendChild(div);
                    });
                });
        }

        // 2. Open Specific Chat
        function openChat(memberId, name) {
            currentMemberId = memberId;
            document.getElementById('chatUserName').textContent = name;
            document.getElementById('chatUserId').textContent = memberId;
            document.getElementById('chatHeader').style.display = 'flex';
            document.getElementById('inputArea').style.display = 'block';
            
            // Load messages immediately
            loadMessages();
            // Refresh contacts to clear the badge for this user
            loadContacts(); 
        }

        // 3. Load Messages
        function loadMessages() {
            if (!currentMemberId) return;

            fetch(`chat_api.php?action=fetch_messages&member_id=${currentMemberId}`)
                .then(res => res.json())
                .then(data => {
                    // Only scroll down if new messages arrived
                    if(data.length !== lastMsgCount) {
                        const area = document.getElementById('messageArea');
                        area.innerHTML = ''; // Clear current
                        
                        data.forEach(msg => {
                            const isMe = msg.sender === 'admin';
                            const div = document.createElement('div');
                            div.className = `msg-bubble ${isMe ? 'msg-admin' : 'msg-member'}`;
                            div.innerHTML = `
                                ${escapeHtml(msg.message)}
                                <div class="text-[10px] ${isMe ? 'text-orange-100' : 'text-gray-400'} text-right mt-1">${msg.time}</div>
                            `;
                            area.appendChild(div);
                        });
                        
                        area.scrollTop = area.scrollHeight; // Auto scroll down
                        lastMsgCount = data.length;
                    }
                });
        }

        // 4. Send Reply
        document.getElementById('adminChatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const input = document.getElementById('adminInput');
            const text = input.value.trim();
            if(!text || !currentMemberId) return;

            const formData = new FormData();
            formData.append('action', 'send_reply');
            formData.append('member_id', currentMemberId);
            formData.append('message', text);

            fetch('chat_api.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        input.value = '';
                        lastMsgCount = 0; // Force refresh
                        loadMessages();
                    }
                });
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Auto Refresh Loop
        setInterval(() => {
            if(currentMemberId) loadMessages(); // Check for new messages in current chat
            loadContacts(); // Check for new messages in the sidebar list
        }, 3000);

        // Initial load
        loadContacts();

    </script>
</body>
</html>