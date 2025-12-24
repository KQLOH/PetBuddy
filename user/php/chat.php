<?php
session_start();
// Ensure user is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

// Visual Status Logic
date_default_timezone_set('Asia/Kuala_Lumpur');
$d = date('N'); $h = date('G');
$isOnline = ($d >= 1 && $d <= 5 && $h >= 9 && $h < 18);
$statusText = $isOnline ? "We are Online" : "Currently Offline";
$statusColor = $isOnline ? "#22c55e" : "#9ca3af"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Chat - PetBuddy</title>
    
    <link rel="stylesheet" href="../css/style.css"> 

    <style>
        /* Manual CSS - No Frameworks */
        body { background-color: #f9fafb; font-family: sans-serif; }
        
        .chat-wrapper {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            height: 600px; /* Fixed height */
            overflow: hidden;
        }

        /* Header */
        .chat-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .header-avatar {
            width: 40px; height: 40px;
            background: #FF9F1C; /* Brand Color */
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
        .header-avatar img { width: 20px; height: 20px; filter: brightness(0) invert(1); }
        .header-info h2 { margin: 0; font-size: 16px; color: #333; }
        .header-status { font-size: 12px; color: #666; display: flex; align-items: center; gap: 5px; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: block; }
        .header-hours { font-size: 11px; color: #888; background: #f3f4f6; padding: 4px 8px; border-radius: 12px; }

        /* Messages */
        .chat-box {
            flex: 1;
            padding: 20px;
            background-color: #f4f6f8;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .message {
            max-width: 70%;
            padding: 10px 14px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.4;
            position: relative;
            word-wrap: break-word;
        }
        .message-time {
            display: block;
            font-size: 10px;
            margin-top: 4px;
            text-align: right;
            opacity: 0.8;
        }

        /* User Message (Right) */
        .msg-member {
            align-self: flex-end;
            background-color: #FF9F1C;
            color: white;
            border-bottom-right-radius: 2px;
        }
        .msg-member .message-time { color: rgba(255,255,255,0.9); }

        /* Admin Message (Left) */
        .msg-admin {
            align-self: flex-start;
            background-color: #fff;
            color: #333;
            border: 1px solid #e5e7eb;
            border-bottom-left-radius: 2px;
        }
        .msg-admin .message-time { color: #888; }

        /* Input */
        .chat-input-area {
            padding: 15px;
            background: #fff;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        .chat-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
        }
        .chat-input:focus { border-color: #FF9F1C; }
        .btn-send {
            width: 42px; height: 42px;
            background: #FF9F1C;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .btn-send:hover { background: #e08b15; }
        .btn-send img { width: 18px; filter: invert(1); }

        .empty-state { text-align: center; color: #aaa; margin-top: 50px; font-size: 14px; }
    </style>
</head>
<body>

    <?php include '../include/header.php'; ?>

    <div class="chat-wrapper">
        <div class="chat-header">
            <div class="header-left">
                <div class="header-avatar">
                    <img src="../images/pawprint.png" alt="icon">
                </div>
                <div class="header-info">
                    <h2>PetBuddy Support</h2>
                    <div class="header-status">
                        <span class="status-dot" style="background-color: <?php echo $statusColor; ?>;"></span>
                        <?php echo $statusText; ?>
                    </div>
                </div>
            </div>
            <div class="header-hours">Mon-Fri: 9AM - 6PM</div>
        </div>

        <div class="chat-box" id="chatBox">
            <div class="empty-state">Loading conversation...</div>
        </div>

        <form class="chat-input-area" id="chatForm">
            <input type="text" id="messageInput" class="chat-input" placeholder="Type a message..." autocomplete="off">
            <button type="submit" class="btn-send">
                <img src="../images/send.png" alt="Send">
            </button>
        </form>
    </div>

    <?php include '../include/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chatBox');
            const chatForm = document.getElementById('chatForm');
            const messageInput = document.getElementById('messageInput');
            let lastMsgCount = 0;

            // 1. Fetch Messages from API
            function fetchMessages() {
                // Ensure this points to the correct file we created in Step 1
                fetch('chat_api.php?action=fetch')
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(data.error);
                            return;
                        }
                        // Update UI only if new messages arrive
                        if (data.length !== lastMsgCount) {
                            renderMessages(data);
                            lastMsgCount = data.length;
                            scrollToBottom();
                        }
                    })
                    .catch(err => console.error('Fetch Error:', err));
            }

            // 2. Render HTML
            function renderMessages(messages) {
                if (messages.length === 0) {
                    chatBox.innerHTML = '<div class="empty-state">Start a conversation with us!</div>';
                    return;
                }

                chatBox.innerHTML = messages.map(msg => {
                    const typeClass = msg.sender === 'member' ? 'msg-member' : 'msg-admin';
                    // Sanitize text
                    const safeMsg = msg.message.replace(/</g, "&lt;").replace(/>/g, "&gt;");
                    
                    return `
                        <div class="message ${typeClass}">
                            ${safeMsg}
                            <span class="message-time">${msg.time}</span>
                        </div>
                    `;
                }).join('');
            }

            // 3. Send Message
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const text = messageInput.value.trim();
                if (text === "") return;

                const formData = new FormData();
                formData.append('action', 'send');
                formData.append('message', text);

                fetch('chat_api.php', { // Sending to API
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        messageInput.value = '';
                        fetchMessages(); // Instant refresh
                    } else {
                        alert('Error sending message');
                    }
                })
                .catch(err => console.error('Send Error:', err));
            });

            function scrollToBottom() {
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            // Initial Load & Polling
            fetchMessages();
            setInterval(fetchMessages, 3000); // Check every 3 seconds
        });
    </script>

</body>
</html>