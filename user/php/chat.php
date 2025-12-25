<?php
session_start();
require_once '../include/db.php';

if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['member_id'];
$memberName = $_SESSION['full_name'] ?? 'User';

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDay = date('N');
$currentHour = date('G');

$isOnline = ($currentDay >= 1 && $currentDay <= 5 && $currentHour >= 9 && $currentHour < 18);
$statusText = $isOnline ? "Support is Online" : "Currently Offline";
$statusColor = $isOnline ? "#22c55e" : "#9ca3af";
$statusSub = $isOnline ? "We typically reply in minutes." : "We'll reply when we're back!";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Support - PetBuddy</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/memberProfileStyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --chat-primary: #FFB774;
            --chat-bg: #f4f6f8;
            --chat-border: #e0e0e0;
        }

        .dashboard-container {
            display: flex;
            gap: 20px;
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            min-height: 80vh;
        }

        .chat-main-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--chat-border);
            overflow: hidden;
            height: 600px;
        }

        .chat-header {
            padding: 15px 25px;
            background: #fff;
            border-bottom: 1px solid var(--chat-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-icon {
            width: 45px;
            height: 45px;
            background: var(--chat-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-icon img {
            width: 24px;
            height: 24px;
            filter: brightness(0) invert(1);
            object-fit: contain;
        }

        .header-info h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .header-status {
            font-size: 13px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .chat-box {
            flex: 1;
            padding: 25px;
            background-color: var(--chat-bg);
            background-image: radial-gradient(#e5e7eb 1px, transparent 1px);
            background-size: 20px 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .msg {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 15px;
            line-height: 1.5;
            position: relative;
            word-wrap: break-word;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .msg-admin {
            align-self: flex-start;
            background: #fff;
            color: #333;
            border-bottom-left-radius: 4px;
            border: 1px solid #e5e7eb;
        }

        .msg-member {
            align-self: flex-end;
            background: var(--chat-primary);
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .msg-time {
            display: block;
            font-size: 11px;
            margin-top: 5px;
            text-align: right;
            opacity: 0.7;
        }

        .date-divider {
            text-align: center;
            margin: 15px 0;
            display: flex;
            justify-content: center;
        }

        .date-divider span {
            background: rgba(229, 231, 235, 0.8);
            color: #6b7280;
            font-size: 11px;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 500;
        }

        .chat-input-area {
            padding: 20px;
            background: #fff;
            border-top: 1px solid var(--chat-border);
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 14px 20px;
            border: 1px solid #ddd;
            border-radius: 30px;
            outline: none;
            font-size: 15px;
            transition: border 0.3s;
            background: #f9f9f9;
        }

        .chat-input:focus {
            border-color: var(--chat-primary);
            background: #fff;
        }

        .btn-send {
            width: 50px;
            height: 50px;
            background: var(--chat-primary);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            box-shadow: 0 4px 10px rgba(255, 183, 116, 0.3);
        }

        .btn-send:hover {
            background: #E89C55;
            transform: scale(1.05);
        }

        .btn-send img {
            width: 22px;
            filter: brightness(0) invert(1);
            margin-left: 3px;
        }

        .empty-state {
            text-align: center;
            color: #aaa;
            margin-top: 50px;
            font-size: 14px;
        }

        .empty-state img {
            width: 60px;
            opacity: 0.2;
            margin-bottom: 10px;
        }

        .sidebar-link img {
            width: 20px;
            margin-right: 10px;
            opacity: 0.6;
        }

        .sidebar-link.active img {
            opacity: 1;
            filter: brightness(0) invert(1);
        }
    </style>
</head>

<body>

    <?php include '../include/header.php'; ?>

    <div class="dashboard-container">

        <aside>
            <div class="card-box">
                <div class="user-brief">
                    <?php
                    $img = !empty($_SESSION['user_image']) ? $_SESSION['user_image'] : '../images/default-avatar.png';
                    ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="user-avatar" style="object-fit:cover;">
                    <div class="user-info">
                        <h4><?= htmlspecialchars($memberName) ?></h4>
                        <p>Member</p>
                    </div>
                </div>

                <nav class="sidebar-nav">
                    <a href="memberProfile.php" class="sidebar-link">
                        <img src="../images/dashboard.png"> Dashboard
                    </a>
                    <a href="memberProfile.php#orders" class="sidebar-link">
                        <img src="../images/order.png"> My Orders
                    </a>
                    <div class="sidebar-link active">
                        <img src="../images/bubble-chat.png"> Customer Support
                    </div>
                    <a href="logout.php" class="sidebar-link text-red">
                        <img src="../images/logout.png"> Logout
                    </a>
                </nav>
            </div>
        </aside>

        <div class="chat-main-area">

            <div class="chat-header">
                <div class="header-left">
                    <div class="header-icon">
                        <img src="../images/paw.png" alt="Support">
                    </div>
                    <div class="header-info">
                        <h2>PetBuddy Support</h2>
                        <div class="header-status">
                            <span class="status-dot" style="background-color: <?= $statusColor ?>;"></span>
                            <?= $statusText ?>
                        </div>
                    </div>
                </div>
                <div style="font-size: 12px; color: #888; text-align: right;">
                    <?= $statusSub ?>
                </div>
            </div>

            <div class="chat-box" id="chatBox">
                <div class="empty-state">
                    <img src="../images/comments.png" alt="Loading">
                    <p>Loading your conversation...</p>
                </div>
            </div>

            <form class="chat-input-area" id="chatForm">
                <input type="text" id="messageInput" class="chat-input" placeholder="Type your message here..." autocomplete="off">
                <button type="submit" class="btn-send">
                    <img src="../images/send.png" alt="Send">
                </button>
            </form>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chatBox');
            const chatForm = document.getElementById('chatForm');
            const messageInput = document.getElementById('messageInput');
            let lastMsgCount = 0;

            function loadMessages() {
                fetch('chat_api.php?action=fetch')
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) return;

                        if (data.length !== lastMsgCount) {
                            renderMessages(data);
                            lastMsgCount = data.length;
                            scrollToBottom();
                        }
                    })
                    .catch(err => console.error("Chat Error:", err));
            }

            function renderMessages(data) {
                if (data.length === 0) {
                    chatBox.innerHTML = `
                        <div class="empty-state">
                            <img src="../images/comments.png" style="opacity:0.3; width:60px;">
                            <p>Start a conversation with us! 👋</p>
                        </div>`;
                    return;
                }

                let html = '';
                let lastDate = '';

                data.forEach(msg => {
                    const msgDate = new Date(msg.created_at);
                    const dateStr = formatDate(msgDate);

                    if (dateStr !== lastDate) {
                        html += `<div class="date-divider"><span>${dateStr}</span></div>`;
                        lastDate = dateStr;
                    }

                    const type = msg.sender === 'member' ? 'msg-member' : 'msg-admin';
                    const safeTxt = msg.message.replace(/</g, "&lt;").replace(/>/g, "&gt;");

                    html += `
                        <div class="msg ${type}">
                            ${safeTxt}
                            <span class="msg-time">${msg.time}</span>
                        </div>
                    `;
                });

                chatBox.innerHTML = html;
            }

            function formatDate(date) {
                if (isNaN(date.getTime())) return '';
                const today = new Date();
                const yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);

                if (date.toDateString() === today.toDateString()) return 'Today';
                if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';
                return date.toLocaleDateString('en-GB', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            }

            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });

            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    sendMessage();
                }
            });

            function sendMessage() {
                const text = messageInput.value.trim();
                if (!text) return;

                const tempDiv = document.createElement('div');
                tempDiv.className = 'msg msg-member';
                tempDiv.style.opacity = '0.5';
                tempDiv.innerHTML = `${text.replace(/</g, "&lt;")}<span class="msg-time">Sending...</span>`;
                chatBox.appendChild(tempDiv);
                scrollToBottom();

                messageInput.value = '';

                const formData = new FormData();
                formData.append('action', 'send');
                formData.append('message', text);

                fetch('chat_api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            loadMessages();
                        }
                    });
            }

            function scrollToBottom() {
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            loadMessages();
            setInterval(loadMessages, 3000);
        });
    </script>

</body>

</html>