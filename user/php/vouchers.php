<?php
session_start();
// 调整这里以匹配你的数据库连接文件路径
require_once "../include/db.php"; 

// 1. 获取当前日期
$today = date('Y-m-d');

// 2. 查询有效的 Voucher
// 条件：开始时间 <= 今天 且 结束时间 >= 今天
$sql = "SELECT * FROM vouchers 
        WHERE start_date <= ? AND end_date >= ? 
        ORDER BY end_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$today, $today]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy | Exclusive Vouchers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === 页面基础样式 === */
        body {
            background-color: #f9f9f9;
            font-family: "Inter", sans-serif;
        }

        .voucher-page-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .page-header h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        .page-header p {
            color: #666;
        }

        /* === 优惠券网格布局 === */
        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        /* === 优惠券卡片设计 === */
        .coupon-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            display: flex;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease;
            height: 140px;
        }

        .coupon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        /* 左侧：金额区域 */
        .coupon-left {
            background: linear-gradient(135deg, #FFB774 0%, #ff8e26 100%);
            width: 35%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
            position: relative;
            border-right: 2px dashed rgba(255,255,255,0.4);
        }

        .coupon-amount {
            font-size: 32px;
            font-weight: 800;
        }
        .coupon-currency {
            font-size: 14px;
            font-weight: 500;
        }
        .coupon-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
            opacity: 0.9;
        }

        /* 右侧：详情区域 */
        .coupon-right {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .coupon-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .coupon-condition {
            font-size: 13px;
            color: #777;
        }

        .coupon-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .coupon-date {
            font-size: 11px;
            color: #999;
        }

        /* 复制按钮样式 */
        .btn-copy {
            background: #fff;
            border: 1px solid #FFB774;
            color: #FFB774;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-copy:hover {
            background: #FFB774;
            color: #fff;
        }
        .btn-copy.copied {
            background: #28a745;
            border-color: #28a745;
            color: #fff;
        }

        /* === 装饰用的小圆圈（做成撕票的效果） === */
        .circle-top, .circle-bottom {
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: #f9f9f9; /* 跟背景色一样 */
            border-radius: 50%;
            left: 35%; /* 必须跟左侧宽度的百分比一致 */
            transform: translateX(-50%);
            z-index: 2;
        }
        .circle-top { top: -10px; }
        .circle-bottom { bottom: -10px; }

        /* 空状态 */
        .no-vouchers {
            text-align: center;
            grid-column: 1 / -1;
            padding: 50px;
            color: #888;
        }
        .no-vouchers i {
            font-size: 40px;
            margin-bottom: 15px;
            color: #ddd;
        }
    </style>
</head>
<body>

<?php include "../include/header.php"; // 引入你的 Header ?>

<div class="voucher-page-container">
    
    <div class="page-header">
        <h2>Exclusive Vouchers</h2>
        <p>Grab the best deals for your furry friends! Copy the code and apply at checkout.</p>
    </div>

    <div class="voucher-grid">
        
        <?php if (empty($vouchers)): ?>
            <div class="no-vouchers">
                <i class="fas fa-ticket-alt"></i>
                <p>No active vouchers available at the moment.<br>Please check back later!</p>
            </div>
        <?php else: ?>
            
            <?php foreach ($vouchers as $v): ?>
                <div class="coupon-card">
                    <div class="circle-top"></div>
                    <div class="circle-bottom"></div>

                    <div class="coupon-left">
                        <div class="coupon-amount">
                            <span class="coupon-currency">RM</span>
                            <?= number_format($v['discount_amount'], 0) ?>
                        </div>
                        <div class="coupon-label">OFF</div>
                    </div>

                    <div class="coupon-right">
                        <div>
                            <div class="coupon-title">Code: <span style="font-family:monospace; font-size:1.1em;"><?= htmlspecialchars($v['code']) ?></span></div>
                            <div class="coupon-condition">
                                <?php if ($v['min_amount'] > 0): ?>
                                    Min. Spend RM <?= number_format($v['min_amount'], 2) ?>
                                <?php else: ?>
                                    No Minimum Spend
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="coupon-footer">
                            <div class="coupon-date">
                                Exp: <?= date('d M Y', strtotime($v['end_date'])) ?>
                            </div>
                            <button class="btn-copy" onclick="copyToClipboard('<?= $v['code'] ?>', this)">
                                <i class="far fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

<?php include "../include/footer.php"; // 引入你的 Footer ?>

<script>
    // === 复制功能 ===
    function copyToClipboard(code, btn) {
        // 使用现代 API 复制
        navigator.clipboard.writeText(code).then(function() {
            // 复制成功后的视觉反馈
            let originalContent = btn.innerHTML;
            
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            
            // 2秒后恢复原状
            setTimeout(function() {
                btn.classList.remove('copied');
                btn.innerHTML = originalContent;
            }, 2000);
        }, function(err) {
            console.error('Could not copy text: ', err);
            alert("Failed to copy code. Please copy manually: " + code);
        });
    }
</script>

</body>
</html>