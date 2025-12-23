<?php
// ✨ 1. Session Start 放在第一行
session_start();

require "../include/db.php";
require_once "../include/product_utils.php";

// 强制登录检查
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('Please login to view wishlist.'); window.location.href='login.php';</script>";
    exit;
}

$member_id = $_SESSION['member_id'];

// 获取收藏列表 (按添加时间倒序)
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* === 页面布局 === */
        .wishlist-container {
            max-width: 1300px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: 80vh;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* 头部样式 */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f8f8f8;
        }

        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-left i { font-size: 32px; color: #FFB774; animation: heartBeat 1.5s infinite; }
        .header-left h2 { font-size: 28px; font-weight: 700; color: #2F2F2F; margin: 0; }

        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            10%, 30% { transform: scale(1.1); }
            20%, 40% { transform: scale(1); }
        }

        .wishlist-count {
            background: linear-gradient(135deg, #FFB774, #FF9D5C);
            color: white; padding: 5px 15px; border-radius: 20px;
            font-size: 14px; font-weight: 600;
            box-shadow: 0 2px 8px rgba(255, 183, 116, 0.3);
        }

        /* === 网格布局 (一行5个) === */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }

        /* === 卡片样式 === */
        .w-card {
            background: #fff; border-radius: 16px; overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06); position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f5f5f5; display: flex; flex-direction: column;
        }

        .w-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12); border-color: #FFB774;
        }

        /* 图片区域 */
        .w-img-box {
            width: 100%; height: 200px; overflow: hidden; position: relative;
            background: linear-gradient(135deg, #f8f8f8, #fff); display: block;
        }

        .w-img-box::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, transparent 60%, rgba(0, 0, 0, 0.05));
            z-index: 1; pointer-events: none;
        }

        .w-img-box img {
            width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;
        }

        .w-card:hover .w-img-box img { transform: scale(1.08); }

        /* ✨✨✨ 移除按钮 (右上角) ✨✨✨ */
        .w-remove-btn {
            position: absolute; top: 10px; right: 10px;
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(4px);
            border-radius: 50%; width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            color: #ff4d4d; border: none; cursor: pointer;
            font-size: 16px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s; z-index: 2;
        }

        .w-remove-btn:hover {
            background: #ff4d4d; color: white; transform: scale(1.15) rotate(90deg);
            box-shadow: 0 6px 20px rgba(255, 77, 77, 0.4);
        }

        /* 信息区域 */
        .w-info { padding: 20px; flex: 1; display: flex; flex-direction: column; }

        .w-title {
            font-size: 15px; font-weight: 600; color: #2F2F2F; margin-bottom: 10px;
            line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2;
            -webkit-box-orient: vertical; overflow: hidden; min-height: 42px;
        }

        .w-price-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 15px; margin-top: auto;
        }

        .w-price { display: flex; flex-direction: column; }
        .currency { font-size: 12px; color: #999; font-weight: 500; }
        .amount { font-size: 20px; color: #FFB774; font-weight: 700; letter-spacing: -0.5px; }

        .w-date { font-size: 11px; color: #999; display: flex; align-items: center; gap: 4px; }

        /* 按钮组 */
        .w-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        /* View 按钮 */
        .btn-view {
            text-align: center; border: 2px solid #eee; padding: 10px;
            border-radius: 10px; text-decoration: none; color: #555;
            font-size: 14px; font-weight: 600; transition: all 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-view:hover {
            border-color: #FFB774; color: #FFB774; background: #fff9f4; transform: translateY(-2px);
        }

        /* Add 按钮 */
        .btn-add {
            background: linear-gradient(135deg, #2F2F2F, #1a1a1a);
            color: white; border: none; border-radius: 10px;
            cursor: pointer; font-size: 14px; font-weight: 600;
            padding: 10px; transition: all 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .btn-add:hover {
            background: linear-gradient(135deg, #000, #2F2F2F);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }
        .btn-add:active { transform: translateY(0); }

        /* 空状态 */
        .empty-wish { text-align: center; padding: 80px 20px; color: #999; background: #fafafa; border-radius: 20px; }
        .empty-wish-icon { font-size: 80px; opacity: 0.3; margin-bottom: 20px; animation: float 3s ease-in-out infinite; }
        .empty-wish a {
            display: inline-block; padding: 12px 30px; margin-top: 15px;
            background: linear-gradient(135deg, #FFB774, #FF9D5C); color: white;
            border-radius: 25px; text-decoration: none; font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 183, 116, 0.3); transition: all 0.3s;
        }
        .empty-wish a:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(255, 183, 116, 0.4); }

        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        /* 响应式 */
        @media (max-width: 1200px) { .wishlist-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (max-width: 992px) { .wishlist-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) {
            .wishlist-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
            .w-img-box { height: 160px; }
            .w-title { font-size: 13px; }
            .btn-view, .btn-add { font-size: 12px; padding: 8px; }
        }
    </style>
</head>

<body>

    <?php include "../include/header.php"; ?>

    <div class="wishlist-container">
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-heart"></i>
                <h2>My Wishlist</h2>
            </div>
            <?php if (!empty($wishlist_items)): ?>
                <div class="wishlist-count"><?= count($wishlist_items) ?> Items</div>
            <?php endif; ?>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <div class="empty-wish">
                <div class="empty-wish-icon"><i class="far fa-heart"></i></div>
                <p>Your wishlist is empty</p>
                <a href="product_listing.php"><i class="fas fa-shopping-bag"></i> Explore Products</a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="w-card" id="wish-row-<?= $item['product_id'] ?>">

                        <div class="w-img-box">
                            <button class="w-remove-btn" onclick="removeWish(<?= $item['product_id'] ?>)" title="Remove">
                                <i class="fas fa-times"></i>
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
                                    <i class="far fa-clock"></i> <?= date('M d', strtotime($item['saved_date'])) ?>
                                </div>
                            </div>

                            <div class="w-actions">
                                <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <button class="btn-add" onclick="addToCart(<?= $item['product_id'] ?>)">
                                    <i class="fas fa-cart-plus"></i> Add
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

    <script>
        // === 1. 实时加入购物车 ===
        function addToCart(pid) {
            let $btn = $("button[onclick='addToCart(" + pid + ")']");
            $btn.prop('disabled', true).css('opacity', '0.7');

            $.ajax({
                url: "add_to_cart.php",
                type: "POST",
                data: { product_id: pid, quantity: 1 },
                success: function(response) {
                    let res = response.trim();
                    $btn.prop('disabled', false).css('opacity', '1');

                    if (res === "login_required") {
                        Swal.fire({
                            title: 'Please Login', text: 'You need to login to shop.', icon: 'info',
                            showCancelButton: true, confirmButtonText: 'Login', confirmButtonColor: '#2F2F2F'
                        }).then((r) => { if (r.isConfirmed) window.location.href = 'login.php'; });
                    } else if (res === "added" || res === "quantity increased") {
                        const Toast = Swal.mixin({
                            toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true
                        });
                        Toast.fire({ icon: 'success', title: 'Added to Cart!' });

                        refreshCartSidebar(); // 刷新购物车
                        setTimeout(() => { if (typeof openCart === 'function') openCart(); }, 300); // 弹窗
                    } else {
                        Swal.fire('Error', 'Could not add item.', 'error');
                    }
                },
                error: function() { $btn.prop('disabled', false).css('opacity', '1'); }
            });
        }

        // === 2. 刷新侧边栏 ===
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

        // === ✨✨✨ 3. 删除功能 (Remove Wish) ✨✨✨ ===
        function removeWish(pid) {
            Swal.fire({
                title: 'Remove item?',
                text: "Are you sure you want to remove this from wishlist?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ff4d4d',
                cancelButtonColor: '#ccc',
                confirmButtonText: 'Yes, remove it',
                cancelButtonText: 'No'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'wishlist_action.php',
                        type: 'POST',
                        data: { product_id: pid },
                        dataType: 'json',
                        success: function(res) {
                            if(res.status === 'removed') {
                                // 动画移除
                                $("#wish-row-" + pid).fadeOut(300, function(){ 
                                    $(this).remove(); 
                                    
                                    // 更新顶部数量
                                    let count = $(".w-card:visible").length;
                                    
                                    // 如果删空了，刷新页面显示 empty state
                                    if(count === 0) {
                                        location.reload(); 
                                    } else {
                                        $(".wishlist-count").text(count + " Items");
                                    }
                                });
                                
                                // 成功提示
                                const Toast = Swal.mixin({
                                    toast: true, position: 'top-end', showConfirmButton: false, timer: 1500
                                });
                                Toast.fire({ icon: 'success', title: 'Removed' });
                            }
                        }
                    });
                }
            });
        }
    </script>

</body>
</html>