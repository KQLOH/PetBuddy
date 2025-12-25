<?php
session_start();

require_once "../include/db.php";
require_once "../include/product_utils.php";

$today = date('Y-m-d');


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
   

    <style>
        
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

        
        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        
        .coupon-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease;
            height: 140px;
        }

        .coupon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

       
        .coupon-left {
            background: linear-gradient(135deg, #FFB774 0%, #ff8e26 100%);
            width: 35%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
            position: relative;
            border-right: 2px dashed rgba(255, 255, 255, 0.4);
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

        
        .circle-top,
        .circle-bottom {
            position: absolute;
            width: 20px;
            height: 20px;
            background-color: #f9f9f9;
            
            border-radius: 50%;
            left: 35%;
            
            transform: translateX(-50%);
            z-index: 2;
        }

        .circle-top {
            top: -10px;
        }

        .circle-bottom {
            bottom: -10px;
        }

        
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

    <?php include "../include/header.php"; 
    ?>

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

    <?php include "../include/footer.php"; 
    ?>

    <script>
        
        function copyToClipboard(code, btn) {
            
            navigator.clipboard.writeText(code).then(function() {
                
                let originalContent = btn.innerHTML;

                btn.classList.add('copied');
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';

                
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