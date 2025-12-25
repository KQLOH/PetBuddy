<?php
session_start();

require "../include/db.php";
require_once "../include/product_utils.php";

if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

$member_id = $_SESSION['member_id'];

$sql = "SELECT p.product_id, p.name, p.price, p.image, p.stock_qty, w.created_at as saved_date 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.product_id 
        WHERE w.member_id = ? 
        ORDER BY w.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$member_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Wishlist - PetBuddy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        .custom-alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .custom-alert-overlay.show {
            opacity: 1;
        }

        .custom-alert-box {
            background: white;
            width: 90%;
            max-width: 400px;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .custom-alert-overlay.show .custom-alert-box {
            transform: scale(1);
        }

        .custom-alert-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .icon-success {
            background: #d1fae5;
            color: #059669;
        }

        .icon-error {
            background: #fee2e2;
            color: #dc2626;
        }

        .icon-confirm {
            background: #fef3c7;
            color: #d97706;
        }

        .custom-alert-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .custom-alert-text {
            font-size: 15px;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .custom-alert-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-alert {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-alert-cancel {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-alert-cancel:hover {
            background: #e5e7eb;
        }

        .btn-alert-confirm {
            background: #FFB774;
            color: white;
        }

        .btn-alert-confirm:hover {
            background: #E89C55;
        }

        .wishlist-container {
            max-width: 1300px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: 80vh;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f8f8f8;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-left img {
            width: 32px;
            animation: heartBeat 1.5s infinite;
        }

        .header-left h2 {
            font-size: 28px;
            font-weight: 700;
            color: #2F2F2F;
            margin: 0;
        }

        @keyframes heartBeat {

            0%,
            100% {
                transform: scale(1);
            }

            10%,
            30% {
                transform: scale(1.1);
            }

            20%,
            40% {
                transform: scale(1);
            }
        }

        .wishlist-count {
            background: linear-gradient(135deg, #FFB774, #FF9D5C);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(255, 183, 116, 0.3);
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }

        .w-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f5f5f5;
            display: flex;
            flex-direction: column;
        }

        .w-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            border-color: #FFB774;
        }

        .w-img-box {
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #f8f8f8, #fff);
            display: block;
        }

        .w-img-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 60%, rgba(0, 0, 0, 0.05));
            z-index: 1;
            pointer-events: none;
        }

        .w-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .w-remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 32px;
            height: 32px;
            background: #ffffff;
            border-radius: 50%;
            border: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            z-index: 2;
        }

        .w-remove-btn img {
            width: 30px;
            height: 30px;
            opacity: 0.8;
            transition: 0.2s;
        }

        .w-remove-btn:hover {
            background: #fff5f5;
            border-color: #ff4d4d;
            transform: scale(1.1);
        }

        .w-card:hover .w-img-box img {
            transform: scale(1.08);
        }

        .w-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .w-title {
            font-size: 15px;
            font-weight: 600;
            color: #2F2F2F;
            margin-bottom: 10px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 42px;
        }

        .w-price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            margin-top: auto;
        }

        .w-price {
            display: flex;
            flex-direction: column;
        }

        .currency {
            font-size: 12px;
            color: #999;
            font-weight: 500;
        }

        .amount {
            font-size: 20px;
            color: #FFB774;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .w-date {
            font-size: 11px;
            color: #999;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .w-date img {
            width: 12px;
            opacity: 0.5;
        }

        .w-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn-view {
            text-align: center;
            border: 2px solid #eee;
            padding: 10px;
            border-radius: 10px;
            text-decoration: none;
            color: #555;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-view img {
            width: 16px;
            opacity: 0.6;
        }

        .btn-view:hover {
            border-color: #FFB774;
            color: #FFB774;
            background: #fff9f4;
            transform: translateY(-2px);
        }

        .btn-view:hover img {
            opacity: 1;
            filter: sepia(1) saturate(5) hue-rotate(320deg);
        }

        .btn-add {
            background: linear-gradient(135deg, #2F2F2F, #1a1a1a);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            padding: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-add img {
            width: 16px;
            filter: brightness(0) invert(1);
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #000, #2F2F2F);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        .btn-add:active {
            transform: translateY(0);
        }

        .empty-wish {
            text-align: center;
            padding: 80px 20px;
            color: #999;
            background: #fafafa;
            border-radius: 20px;
        }

        .empty-wish img.main-icon {
            width: 80px;
            opacity: 0.3;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }

        .empty-wish a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 30px;
            margin-top: 15px;
            background: linear-gradient(135deg, #FFB774, #FF9D5C);
            color: white;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 183, 116, 0.3);
            transition: all 0.3s;
        }

        .empty-wish a img {
            width: 16px;
            filter: brightness(0) invert(1);
        }

        .empty-wish a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 183, 116, 0.4);
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        @media (max-width: 1200px) {
            .wishlist-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 992px) {
            .wishlist-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .w-img-box {
                height: 160px;
            }

            .w-title {
                font-size: 13px;
            }

            .btn-view,
            .btn-add {
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
</head>

<body>

    <?php include "../include/header.php"; ?>

    <div class="wishlist-container">
        <div class="page-header">
            <div class="header-left">
                <img src="../images/heart.png" alt="Wishlist">
                <h2>My Wishlist</h2>
            </div>
            <?php if (!empty($wishlist_items)): ?>
                <div class="wishlist-count"><?= count($wishlist_items) ?> Items</div>
            <?php endif; ?>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <div class="empty-wish">
                <img src="../images/heart.png" class="main-icon" alt="Empty">
                <p>Your wishlist is empty</p>
                <a href="product_listing.php">
                    <img src="../images/cart.png" alt="Shop"> Explore Products
                </a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="w-card" id="wish-row-<?= $item['product_id'] ?>">

                        <div class="w-img-box">
                            <button class="w-remove-btn" onclick="confirmRemove(<?= $item['product_id'] ?>)" title="Remove">
                                <img src="../images/error.png" alt="Remove">
                            </button>
                            <a href="product_detail.php?id=<?= $item['product_id'] ?>">
                                <img src="<?= productImageUrl($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                            </a>
                        </div>

                        <div class="w-info">
                            <div class="w-title"><?= htmlspecialchars($item['name']) ?></div>

                            <div class="w-price-row">
                                <div class="w-price">
                                    <span class="currency">RM</span>
                                    <span class="amount"><?= number_format($item['price'], 2) ?></span>
                                </div>
                                <div class="w-date">
                                    <img src="../images/clock.png" alt="Date"> <?= date('M d', strtotime($item['saved_date'])) ?>
                                </div>
                            </div>

                            <div class="w-actions">
                                <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="btn-view">
                                    <img src="../images/show.png" alt="View"> View
                                </a>
                                <button class="btn-add" onclick="addToCart(<?= $item['product_id'] ?>)">
                                    <img src="../images/cart.png" alt="Add"> Add
                                </button>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include "../include/footer.php"; ?>
    <?php include '../include/chat_widget.php'; ?>

    <div id="customAlert" class="custom-alert-overlay">
        <div class="custom-alert-box">
            <div id="customAlertIcon" class="custom-alert-icon"></div>
            <h3 id="customAlertTitle" class="custom-alert-title"></h3>
            <p id="customAlertText" class="custom-alert-text"></p>
            <div id="customAlertButtons" class="custom-alert-buttons">
                <button id="customAlertCancel" class="btn-alert btn-alert-cancel" style="display:none">Cancel</button>
                <button id="customAlertConfirm" class="btn-alert btn-alert-confirm">OK</button>
            </div>
        </div>
    </div>

    <script>
        function showCustomAlert(type, title, text, autoClose = false) {
            const overlay = document.getElementById('customAlert');
            const icon = document.getElementById('customAlertIcon');
            const btnCancel = document.getElementById('customAlertCancel');

            document.getElementById('customAlertTitle').innerText = title;
            document.getElementById('customAlertText').innerText = text;

            icon.className = 'custom-alert-icon';
            if (type === 'success') {
                icon.classList.add('icon-success');
                icon.innerHTML = '✓';
            } else if (type === 'error') {
                icon.classList.add('icon-error');
                icon.innerHTML = '✕';
            } else {
                icon.classList.add('icon-confirm');
                icon.innerHTML = '?';
            }

            btnCancel.style.display = 'none';
            document.getElementById('customAlertConfirm').onclick = closeCustomAlert;

            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);

            if (autoClose) setTimeout(closeCustomAlert, 2000);
        }

        function closeCustomAlert() {
            const overlay = document.getElementById('customAlert');
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300);
        }

        function confirmRemove(pid) {
            const overlay = document.getElementById('customAlert');
            const icon = document.getElementById('customAlertIcon');
            const btnCancel = document.getElementById('customAlertCancel');
            const btnConfirm = document.getElementById('customAlertConfirm');

            document.getElementById('customAlertTitle').innerText = 'Remove Item?';
            document.getElementById('customAlertText').innerText = "Are you sure you want to remove this item?";

            icon.className = 'custom-alert-icon icon-confirm';
            icon.innerHTML = '?';

            btnCancel.style.display = 'block';
            btnCancel.onclick = closeCustomAlert;

            btnConfirm.innerText = 'Yes, remove it';
            btnConfirm.onclick = function() {
                deleteWishItem(pid);
                closeCustomAlert();
            };

            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);
        }

        function deleteWishItem(pid) {
            $.ajax({
                url: 'wishlist_action.php',
                type: 'POST',
                data: {
                    product_id: pid
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'removed') {
                        $("#wish-row-" + pid).fadeOut(300, function() {
                            $(this).remove();
                            let count = $(".w-card:visible").length;
                            if (count === 0) {
                                location.reload();
                            } else {
                                $(".wishlist-count").text(count + " Items");
                            }
                        });
                        showCustomAlert('success', 'Removed', 'Item removed from wishlist', true);
                    }
                }
            });
        }

        function addToCart(pid) {
            let $btn = $("button[onclick='addToCart(" + pid + ")']");
            $btn.prop('disabled', true).css('opacity', '0.7');

            $.ajax({
                url: "add_to_cart.php",
                type: "POST",
                data: {
                    product_id: pid,
                    quantity: 1
                },
                success: function(response) {
                    let res = response.trim();
                    $btn.prop('disabled', false).css('opacity', '1');

                    if (res === "login_required") {
                        showCustomAlert('error', 'Login Required', 'Please login to add items to cart.');
                    } else if (res === "added" || res === "quantity increased") {
                        showCustomAlert('success', 'Success', 'Added to Cart!', true);
                        refreshCartSidebar();
                        setTimeout(() => {
                            if (typeof openCart === 'function') openCart();
                        }, 300);
                    } else {
                        showCustomAlert('error', 'Error', 'Could not add item.');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).css('opacity', '1');
                }
            });
        }

        function refreshCartSidebar() {
            $.ajax({
                url: 'fetch_cart.php',
                type: 'GET',
                success: function(htmlResponse) {
                    $('#cartBody').html(htmlResponse);
                    let newTotal = $('#ajax-new-total').val();
                    if (newTotal) {
                        $('#cartSidebarTotal').text(newTotal);
                        if (typeof updateFreeShipping === 'function') updateFreeShipping();
                        if (parseFloat(newTotal) > 0) $('#cartFooter').show();
                    }
                }
            });
        }
    </script>

</body>

</html>