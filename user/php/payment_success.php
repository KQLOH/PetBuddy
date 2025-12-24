<?php
session_start();
require "../include/db.php";

// 1. 检查是否有 order_id
if (!isset($_GET['order_id'])) {
    header("Location: home.php");
    exit;
}

$order_id = intval($_GET['order_id']);

// 可选：你也可以在这里查数据库获取订单金额，显示给用户看
// 但为了保持简单，我们直接显示订单号即可
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful - PetBuddy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #FFB774;
            --primary-dark: #E89C55;
            --text-dark: #2F2F2F;
            --bg-color: #fff9f2;
        }

        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            background: var(--bg-color); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px;
        }

        .success-card { 
            background: white; 
            padding: 50px 40px; 
            border-radius: 16px; 
            text-align: center; 
            box-shadow: 0 10px 30px rgba(255, 183, 116, 0.15); 
            max-width: 450px; 
            width: 100%; 
            animation: fadeIn 0.6s ease-out;
        }

        /* 动画图标 */
        .icon-circle { 
            width: 80px; 
            height: 80px; 
            background: #4CAF50; /* 成功通常用绿色，或者你可以改成橙色 var(--primary-color) */
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 auto 25px; 
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .icon-circle i { 
            color: white; 
            font-size: 40px; 
        }

        h1 { 
            color: var(--text-dark); 
            margin: 0 0 15px; 
            font-size: 26px; 
            font-weight: 700;
        }

        p { 
            color: #666; 
            margin-bottom: 30px; 
            line-height: 1.6; 
            font-size: 15px;
        }

        .order-id {
            font-weight: 700;
            color: var(--text-dark);
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
            text-align: center;
            box-sizing: border-box;
        }
        
        .btn i {
            margin-right: 8px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(255, 183, 116, 0.4);
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: #888;
            border: 1px solid #eee;
        }
        .btn-secondary:hover {
            color: var(--text-dark);
            border-color: #ccc;
            background: #f9f9f9;
        }

        /* 动画定义 */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes popIn {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }

    </style>
</head>
<body>

<div class="success-card">
    <div class="icon-circle">
        <i class="fas fa-check"></i>
    </div>
    
    <h1>Payment Successful!</h1>
    
    <p>
        Thank you for your purchase.<br>
        Your order <span class="order-id">#<?= $order_id ?></span> has been confirmed.
        We will send you an email with the details shortly.
    </p>

    <div class="btn-group">
        <button id="sendReceiptBtn" class="btn btn-primary" onclick="sendReceipt()">
            <img src="../images/mail.png" alt="Email" style="width: 18px; height: 18px; margin-right: 8px; vertical-align: middle;"> Send E-Receipt (Email)
        </button>
        <a href="download_receipt.php?order_id=<?= $order_id ?>" class="btn btn-primary" style="background: #2196F3; display: flex; align-items: center; justify-content: center;">
            <img src="../images/pdf.png" alt="PDF" style="width: 18px; height: 18px; margin-right: 8px; vertical-align: middle;"> Download E-Receipt (PDF)
        </a>
        <a href="home.php" class="btn btn-secondary" style="display: flex; align-items: center; justify-content: center;">
            <img src="../images/shopping-bag.png" alt="Shopping" style="width: 18px; height: 18px; margin-right: 8px; vertical-align: middle;"> Continue Shopping
        </a>
    </div>
    
    <div id="receiptMessage" style="margin-top: 15px; font-size: 14px; display: none;"></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function sendReceipt() {
    const btn = document.getElementById('sendReceiptBtn');
    const messageDiv = document.getElementById('receiptMessage');
    const originalText = btn.innerHTML;
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<img src="../images/mail.png" alt="Email" style="width: 18px; height: 18px; margin-right: 8px; vertical-align: middle; opacity: 0.6;"> Sending...';
    messageDiv.style.display = 'none';
    
    $.ajax({
        url: 'send_receipt.php',
        type: 'POST',
        data: { order_id: <?= $order_id ?> },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                btn.innerHTML = '<img src="../images/mail.png" alt="Email" style="width: 18px; height: 18px; margin-right: 8px; vertical-align: middle;"> E-Receipt Sent!';
                btn.style.background = '#4CAF50';
                messageDiv.style.display = 'block';
                messageDiv.style.color = '#4CAF50';
                messageDiv.innerHTML = '✓ ' + response.message;
            } else {
                btn.innerHTML = originalText;
                btn.disabled = false;
                messageDiv.style.display = 'block';
                messageDiv.style.color = '#D92D20';
                messageDiv.innerHTML = '✗ ' + response.message;
            }
        },
        error: function() {
            btn.innerHTML = originalText;
            btn.disabled = false;
            messageDiv.style.display = 'block';
            messageDiv.style.color = '#D92D20';
            messageDiv.innerHTML = '✗ Error sending receipt. Please try again.';
        }
    });
}
</script>

</body>
</html>