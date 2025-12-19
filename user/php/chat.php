<?php
session_start();
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

// Determine Status for display (PHP Logic)
date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDay = date('N');
$currentHour = date('G');
$isOnline = ($currentDay >= 1 && $currentDay <= 5 && $currentHour >= 9 && $currentHour < 18);
$statusText = $isOnline ? "We are Online" : "Currently Offline";
$statusColor = $isOnline ? "#22c55e" : "#9ca3af"; // Green or Gray
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Chat - PetBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* --- Chat Specific Styles --- */
        body { background-color: #F9FAFB; }
        
        .chat-container {
            max-width: 800px; margin: 2rem auto; background: #fff; border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e0e0e0;
            display: flex; flex-direction: column; height: 80vh; overflow: hidden;
        }

        /* Professional Header */
        .chat-header {
            padding: 1.25rem 1.5rem;
            background: white;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-brand { display: flex; align-items: center; gap: 12px; }
        .chat-avatar { 
            width: 45px; height: 45px; border-radius: 50%; background: var(--primary-color); 
            display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;
        }
        .chat-info h2 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #333; }
        .chat-meta { font-size: 0.8rem; color: #666; display: flex; align-items: center; gap: 6px; margin-top: 2px;}
        
        /* Status Dot */
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

        .chat-hours {
            font-size: 0.75rem;
            color: #888;
            background: #f9f9f9;
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid #eee;
        }

        .chat-close { color: #999; text-decoration: none; font-size: 1.5rem; line-height: 1; }
        .chat-close:hover { color: #333; }

        /* Messages Area */
        .chat-box {
            flex: 1; padding: 1.5rem; overflow-y: auto; background-color: #F4F6F8;
            display: flex; flex-direction: column; gap: 1rem;
        }
        .chat-box::-webkit-scrollbar { width: 6px; }
        .chat-box::-webkit-scrollbar-track { background: transparent; }
        .chat-box::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

        /* Message Bubbles */
        .message {
            max-width: 75%; padding: 12px 16px; border-radius: 12px; font-size: 0.95rem; line-height: 1.5; position: relative;
        }
        .message-time { font-size: 0.7rem; margin-top: 4px; display: block; text-align: right; opacity: 0.7; }

        /* Admin (Left) */
        .msg-admin {
            align-self: flex-start; background-color: #ffffff; color: #333;
            border-bottom-left-radius: 2px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        
        /* User (Right) */
        .msg-member {
            align-self: flex-end; background-color: var(--primary-color); color: white;
            border-bottom-right-radius: 2px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .msg-member .message-time { color: white; }

        /* Input Area */
        .chat-input-area {
            padding: 1.25rem; border-top: 1px solid #eee; background: #fff; display: flex; gap: 10px; align-items: center;
        }
        .chat-input {
            flex: 1; padding: 12px 16px; border: 1px solid #ddd; border-radius: 24px;
            outline: none; transition: border-color 0.2s; font-size: 0.95rem; background: #f9f9f9;
        }
        .chat-input:focus { border-color: var(--primary-color); background: #fff; }

        .btn-send {
            background-color: var(--primary-color); color: white; border: none;
            width: 42px; height: 42px; border-radius: 50%; cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: background 0.2s;
        }
        .btn-send:hover { background-color: var(--primary-dark); }
        .btn-send img { width: 18px; height: 18px; filter: invert(1); } /* White icon */

        .empty-chat { text-align: center; color: #888; margin-top: 3rem; font-size: 0.9rem; }
    </style>
</head>
<body>

    <?php include '../include/header.php'; ?>

    <div class="dashboard-container" style="display: block;"> 
        <div class="breadcrumb">
            <a href="home.php">Home</a> / <span>Customer Support</span>
        </div>

        <div class="chat-container">
            <!-- Header -->
            <div class="chat-header">
                <div class="chat-brand">
                    <div class="chat-avatar">
                        <!-- Local generic icon or image -->
                        <img src="../images/pawprint.png" style="width:24px; height:24px; filter: brightness(0) invert(1);">
                    </div>
                    <div class="chat-info">
                        <h2>PetBuddy Support</h2>
                        <div class="chat-meta">
                            <span class="status-dot" style="background-color: <?php echo $statusColor; ?>;"></span>
                            <?php echo $statusText; ?>
                        </div>
                    </div>
                </div>
                
                <div style="text-align:right;">
                    <div class="chat-hours">Mon-Fri: 9AM - 6PM</div>
                </div>
            </div>

            <!-- Chat Box -->
            <div class="chat-box" id="chatBox">
                <div class="empty-chat">Loading messages...</div>
            </div>

            <!-- Input -->
            <form class="chat-input-area" id="chatForm">
                <input type="text" id="messageInput" class="chat-input" placeholder="Type your message here..." autocomplete="off">
                <button type="submit" class="btn-send">
                    <!-- Simple arrow icon -->
                    <img src="../images/send.png">
                </button>
            </form>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chatBox');
            const chatForm = document.getElementById('chatForm');
            const messageInput = document.getElementById('messageInput');
            let lastMsgCount = 0;

            // 1. Fetch Messages
            function fetchMessages() {
                fetch('chat_api.php?action=fetch')
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) return;
                        
                        // Only render if there's a change in message count
                        if (data.length !== lastMsgCount) {
                            renderMessages(data);
                            lastMsgCount = data.length;
                            scrollToBottom();
                        }
                    })
                    .catch(err => console.error('Error:', err));
            }

            // 2. Render
            function renderMessages(messages) {
                if (messages.length === 0) {
                    chatBox.innerHTML = '<div class="empty-chat">Welcome to PetBuddy Support!<br>How can we help you today?</div>';
                    return;
                }

                chatBox.innerHTML = messages.map(msg => {
                    const typeClass = msg.sender === 'member' ? 'msg-member' : 'msg-admin';
                    // Simple safety check for message content
                    const safeMsg = msg.message.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                    
                    return `
                        <div class="message ${typeClass}">
                            ${safeMsg}
                            <span class="message-time">${msg.time}</span>
                        </div>
                    `;
                }).join('');
            }

            // 3. Send
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const text = messageInput.value.trim();
                
                if (text === "") return;

                // Optimistic UI: Show message immediately before waiting for server
                // (Optional, but makes it feel faster)
                
                const formData = new FormData();
                formData.append('action', 'send');
                formData.append('message', text);

                fetch('chat_api.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        messageInput.value = '';
                        fetchMessages(); // Refresh to see any auto-reply
                    }
                });
            });

            function scrollToBottom() {
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            // Poll every 3 seconds
            fetchMessages();
            setInterval(fetchMessages, 3000);
        });
    </script>

</body>
</html>