<?php
session_start();

require_once '../include/db.php';
require_once '../include/product_utils.php';


$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: product_listing.php');
    exit;
}


function fetchProductDetail(PDO $pdo, int $productId): ?array
{
    $sql = "SELECT p.product_id, p.category_id, p.name, p.description, p.price, p.stock_qty, p.image, c.name AS category_name
            FROM products p
            LEFT JOIN product_categories c ON p.category_id = c.category_id
            WHERE p.product_id = :id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function fetchRelated(PDO $pdo, int $categoryId, int $productId, int $limit = 3): array
{
    $sql = "SELECT product_id, name, price, image FROM products
            WHERE category_id = :cat_id AND product_id <> :prod_id
            ORDER BY RAND() LIMIT :limit";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cat_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':prod_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function fetchProductReviews(PDO $pdo, int $productId): array
{
    $reviews = [];
    $sql = "SELECT r.rating, r.comment, r.review_date, m.full_name AS reviewer
            FROM product_reviews r
            JOIN members m ON r.member_id = m.member_id
            WHERE r.product_id = :pid
            ORDER BY r.review_date DESC";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['pid' => $productId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $row) {
            $reviews[] = [
                'rating'   => (int)$row['rating'],
                'comment'  => $row['comment'],
                'reviewer' => $row['reviewer'] ?: 'Anonymous',
                'date'     => $row['review_date']
            ];
        }
    } catch (PDOException $e) {
    }
    return $reviews;
}

function calculateReviewStats(array $reviews): array
{
    if (empty($reviews)) return ['average' => 0, 'total' => 0];
    $total = count($reviews);
    $sum = 0;
    foreach ($reviews as $review) {
        $sum += (int)$review['rating'];
    }
    $average = $total > 0 ? round($sum / $total, 1) : 0;
    return ['average' => $average, 'total' => $total];
}

function generateStars(int $rating): string
{
    $html = '';
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    for ($i = 0; $i < $fullStars; $i++) $html .= '<i class="fas fa-star" style="color: #FFB774;"></i> ';
    if ($hasHalfStar) {
        $html .= '<i class="fas fa-star-half-alt" style="color: #FFB774;"></i> ';
        $fullStars++;
    }
    for ($i = 0; $i < 5 - $fullStars; $i++) $html .= '<i class="far fa-star" style="color: #ddd;"></i> ';
    return $html;
}


$product = fetchProductDetail($pdo, $productId);
if (!$product) {
    header('Location: product_listing.php');
    exit;
}

$relatedProducts = fetchRelated($pdo, (int)$product['category_id'], $productId);
$reviews = fetchProductReviews($pdo, $productId);
$reviewStats = calculateReviewStats($reviews);

$mainImage = productImageUrl($product['image']);
$categoryName = $product['category_name'] ?: 'All Products';
$productDescription = trim((string)$product['description']) !== '' ? $product['description'] : 'No product description available';


$wishlistIds = [];
if (isset($_SESSION['member_id'])) {
    try {
        $stmtW = $pdo->prepare("SELECT product_id FROM wishlist WHERE member_id = ?");
        $stmtW->execute([$_SESSION['member_id']]);
        $wishlistIds = $stmtW->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - PetBuddy</title>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary-color: #FFB774;
            --primary-dark: #E89C55;
            --text-dark: #2F2F2F;
            --text-light: #666;
            --border-color: #e1e1e1;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .pd-product-detail {
            display: flex;
            gap: 50px;
            margin-bottom: 60px;
            align-items: flex-start;
        }

        .pd-product-images {
            flex: 1;
            position: relative;
        }

        .pd-main-image {
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #eee;
            background: #fff;
        }

        .pd-main-image img {
            width: 100%;
            height: auto;
            display: block;
            object-fit: cover;
        }

        .pd-product-info {
            flex: 1;
        }

        .pd-product-tag {
            display: inline-block;
            background: #FFF5EB;
            color: var(--primary-dark);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .pd-product-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0 0 15px 0;
            line-height: 1.2;
        }

        .pd-product-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .pd-rating-count {
            color: var(--text-light);
            font-size: 14px;
        }

        .pd-product-price {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 30px;
        }

        .pd-current-price {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .pd-stock-info {
            background: #E8F5E9;
            color: #2E7D32;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .pd-purchase-options {
            margin-bottom: 30px;
        }

        .pd-quantity-selector {
            margin-bottom: 25px;
        }

        .pd-quantity-label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .pd-quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid #eee;
            border-radius: 8px;
            width: fit-content;
            overflow: hidden;
        }

        .pd-quantity-btn {
            background: #fff;
            border: none;
            width: 40px;
            height: 40px;
            font-size: 18px;
            cursor: pointer;
            color: var(--text-dark);
            transition: 0.2s;
        }

        .pd-quantity-btn:hover {
            background: #f9f9f9;
        }

        .pd-quantity-input {
            width: 50px;
            height: 40px;
            border: none;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            -moz-appearance: textfield;
        }

        .pd-quantity-input::-webkit-outer-spin-button,
        .pd-quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .pd-action-buttons {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .pd-btn-row {
            display: flex;
            gap: 12px;
            width: 100%;
        }

        .pd-btn {
            height: 50px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .pd-btn-primary {
            flex: 1;
            background: var(--text-dark);
            color: white;
            border: none;
        }

        .pd-btn-primary:hover {
            background: #000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }


        .btn-icon-img {
            width: 20px;
            height: 20px;
            object-fit: contain;
            margin-right: 8px;
            filter: brightness(0) invert(1);
        }

        .pd-btn-primary:hover .btn-icon-img {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }


        .pd-btn-secondary {
            width: 50px;
            height: 50px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .pd-btn-secondary:hover {
            background: #fffbf6;
            transform: translateY(-3px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .pd-btn-secondary.active {
            border-color: transparent;
            background: #ffecec;
            box-shadow: inset 0 2px 5px rgba(255, 77, 77, 0.1);
        }


        .wishlist-icon {
            width: 24px;
            height: 24px;
            object-fit: contain;
            transition: all 0.3s ease;
            filter: grayscale(100%) opacity(0.4);
        }

        .pd-btn-secondary.active .wishlist-icon {
            filter: grayscale(0%) opacity(1);
            transform: scale(1.1);
        }

        .wishlist-btn.animating .wishlist-icon {
            animation: heartBeat 0.4s ease-in-out;
        }

        @keyframes heartBeat {
            0% {
                transform: scale(1);
            }

            25% {
                transform: scale(1.35);
            }

            50% {
                transform: scale(0.9);
            }

            75% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .continue-link {
            color: #888;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 1px solid transparent;
        }

        .continue-link:hover {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }


        .pd-product-tabs {
            margin-bottom: 60px;
        }

        .pd-tabs-header {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }

        .pd-tab {
            padding: 15px 30px;
            cursor: pointer;
            font-weight: 600;
            color: #888;
            position: relative;
            transition: 0.3s;
        }

        .pd-tab:hover {
            color: var(--text-dark);
        }

        .pd-tab.active {
            color: var(--primary-dark);
        }

        .pd-tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary-color);
        }

        .pd-tab-content {
            display: none;
            line-height: 1.8;
            color: #555;
        }

        .pd-tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .pd-specs-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pd-specs-table td {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .pd-specs-table td:first-child {
            font-weight: 600;
            width: 200px;
            color: var(--text-dark);
        }


        .pd-review-item {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .pd-review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .pd-reviewer {
            font-weight: 700;
            color: var(--text-dark);
        }

        .pd-review-date {
            font-size: 13px;
            color: #999;
        }


        .pd-rating-stars {
            font-size: 13px;
            color: #FFB774;
            line-height: 1;
            margin-bottom: 10px;
        }

        .pd-rating-stars i {
            margin-right: 2px;
        }

        .pd-section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            color: var(--text-dark);
        }

        .pd-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
        }

        .pd-product-card {
            display: block;
            text-decoration: none;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
        }

        .pd-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            border-color: var(--primary-color);
        }

        .pd-product-card-img {
            width: 100%;
            height: 200px;
            background: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pd-product-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pd-product-card-info {
            padding: 15px;
        }

        .pd-product-card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0 0 8px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pd-product-card-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-dark);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .pd-product-detail {
                flex-direction: column;
            }

            .pd-btn-row {
                width: 100%;
            }
        }


        #custom-toast {
            visibility: hidden;
            min-width: 200px;
            background-color: rgba(40, 40, 40, 0.95);
            color: #fff;
            text-align: center;
            border-radius: 50px;
            padding: 12px 24px;
            position: fixed;
            z-index: 9999;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%);
            font-size: 15px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        #custom-toast.show {
            visibility: visible;
            opacity: 1;
            bottom: 50px;
        }

        .toast-icon {
            width: 20px;
            height: 20px;

        }
    </style>
</head>

<body>
    <?php include_once '../include/header.php'; ?>

    <div class="container">
        <div class="pd-product-detail">
            <div class="pd-product-images">
                <div class="pd-main-image">
                    <img src="<?php echo $mainImage; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-image">
                </div>
            </div>

            <div class="pd-product-info">
                <div class="pd-product-tag"><?php echo htmlspecialchars($categoryName); ?></div>
                <h1 class="pd-product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="pd-product-rating">
                    <div class="pd-stars"><?php echo generateStars((int)round($reviewStats['average'])); ?></div>
                    <div class="pd-rating-count">(<?php echo $reviewStats['total']; ?> reviews)</div>
                </div>

                <div class="pd-product-price">
                    <span class="pd-current-price"><?php echo formatPrice((float)$product['price']); ?></span>
                    <span class="pd-stock-info">Stock: <?php echo (int)$product['stock_qty']; ?> items</span>
                </div>

                <div class="pd-purchase-options">
                    <div class="pd-quantity-selector">
                        <div class="pd-quantity-label">Quantity:</div>
                        <div class="pd-quantity-controls">
                            <button class="pd-quantity-btn minus">-</button>
                            <input type="number" class="pd-quantity-input" value="1" min="1" max="<?php echo (int)$product['stock_qty']; ?>" data-stock="<?php echo (int)$product['stock_qty']; ?>">
                            <button class="pd-quantity-btn plus">+</button>
                        </div>
                    </div>

                    <div class="pd-action-buttons">
                        <div class="pd-btn-row">
                            <button class="pd-btn pd-btn-primary" id="pd-add-to-cart" data-id="<?php echo (int)$product['product_id']; ?>">
                                <img src="../images/cart.png" class="btn-icon-img" alt="Cart">
                                Add to Cart
                            </button>

                            <?php
                            $isWished = in_array($product['product_id'], $wishlistIds ?? []);
                            $btnClass = $isWished ? 'active' : '';
                            ?>
                            <button class="pd-btn pd-btn-secondary wishlist-btn <?php echo $btnClass; ?>" onclick="toggleWishlistDetail(this, <?php echo $product['product_id']; ?>)" title="Add to Wishlist">
                                <img src="../images/heart.png" alt="Wishlist" class="wishlist-icon">
                            </button>
                        </div>

                        <a href="javascript:history.back()" class="continue-link">Or Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="pd-product-tabs">
            <div class="pd-tabs-header">
                <div class="pd-tab active" data-tab="description">Description</div>
                <div class="pd-tab" data-tab="specs">Specifications</div>
                <div class="pd-tab" data-tab="reviews">Reviews</div>
            </div>

            <div class="pd-tab-content active" id="description">
                <h3>Product Details</h3>
                <p><?php echo nl2br(htmlspecialchars($productDescription)); ?></p>
            </div>

            <div class="pd-tab-content" id="specs">
                <table class="pd-specs-table">
                    <tr>
                        <td>Product Name</td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                    </tr>
                    <tr>
                        <td>Category</td>
                        <td><?php echo htmlspecialchars($categoryName); ?></td>
                    </tr>
                    <tr>
                        <td>Stock</td>
                        <td><?php echo (int)$product['stock_qty'] > 0 ? 'In Stock' : 'Out of Stock'; ?></td>
                    </tr>
                </table>
            </div>

            <div class="pd-tab-content" id="reviews">
                <div class="pd-review-list">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="pd-review-item">
                                <div class="pd-review-header">
                                    <div class="pd-reviewer">
                                        <strong><?php echo htmlspecialchars($review['reviewer']); ?></strong>
                                    </div>
                                    <div class="pd-review-date">
                                        <?php echo date('d M Y', strtotime($review['date'])); ?>
                                    </div>
                                </div>

                                <div class="pd-rating-stars">
                                    <?php echo generateStars((int)$review['rating']); ?>
                                </div>

                                <div class="pd-review-content">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding:40px; color:#666;">
                            No reviews yet. Be the first to review!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="pd-related-products">
            <h2 class="pd-section-title">Related Products</h2>
            <div class="pd-products-grid">
                <?php if (!empty($relatedProducts)): ?>
                    <?php foreach ($relatedProducts as $related): ?>
                        <a class="pd-product-card" href="product_detail.php?id=<?php echo (int)$related['product_id']; ?>">
                            <div class="pd-product-card-img">
                                <img src="<?php echo productImageUrl($related['image']); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                            </div>
                            <div class="pd-product-card-info">
                                <h3 class="pd-product-card-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                                <div class="pd-product-card-price"><?php echo formatPrice((float)$related['price']); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="custom-toast">
        <img src="../images/success.png" alt="" class="toast-icon">
        <span id="custom-toast-msg">Added to cart!</span>
    </div>

    <?php include_once '../include/footer.php'; ?>
    <?php include '../include/chat_widget.php'; ?>

    <script>
        function safeToast(message, type = 'success') {
            const toast = document.getElementById('custom-toast');
            const msgSpan = document.getElementById('custom-toast-msg');
            const img = toast.querySelector('img');

            msgSpan.innerText = message;


            if (message.toLowerCase().includes("removed")) {

            } else if (message.toLowerCase().includes("cart")) {
                img.src = '../images/success.png';
            } else {
                img.src = '../images/success.png';
            }


            img.onerror = function() {
                this.src = '../images/success.png';
            };

            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2500);
        }

        function safeAlert(message) {
            alert(message);
        }

        function confirmLogin() {
            if (confirm("Please Login to shop. Go to login page?")) {
                window.location.href = "login.php";
            }
        }

        document.addEventListener('DOMContentLoaded', function() {

            document.querySelectorAll('.pd-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelectorAll('.pd-tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.pd-tab-content').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    const target = document.getElementById(this.getAttribute('data-tab'));
                    if (target) target.classList.add('active');
                });
            });


            const qtyInput = document.querySelector('.pd-quantity-input');
            const maxStock = qtyInput ? parseInt(qtyInput.getAttribute('data-stock')) : 999;

            document.querySelector('.pd-quantity-btn.minus')?.addEventListener('click', () => {
                let v = parseInt(qtyInput.value) || 1;
                if (v > 1) qtyInput.value = v - 1;
            });
            document.querySelector('.pd-quantity-btn.plus')?.addEventListener('click', () => {
                let v = parseInt(qtyInput.value) || 1;
                if (v < maxStock) qtyInput.value = v + 1;
            });
        });

        $(document).ready(function() {

            $('#pd-add-to-cart').click(function(e) {
                e.preventDefault();
                var $btn = $(this);
                var pid = $btn.data('id');
                var qty = $('.pd-quantity-input').val() || 1;

                if ($btn.prop('disabled')) return;
                $btn.prop('disabled', true);

                $.ajax({
                    url: "add_to_cart.php",
                    type: "POST",
                    data: {
                        product_id: pid,
                        quantity: qty
                    },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        var resString = "";
                        if (typeof response === 'object') {
                            resString = JSON.stringify(response).toLowerCase();
                        } else {
                            resString = response.toString().toLowerCase();
                        }

                        if (resString.includes("added") || resString.includes("increased") || resString.includes("success")) {
                            safeToast('Added to Cart!');
                            if (typeof refreshCartSidebar === 'function') refreshCartSidebar();
                            else setTimeout(function() {
                                location.reload();
                            }, 1000);
                            setTimeout(() => {
                                if (typeof openCart === 'function') openCart();
                            }, 500);
                        } else if (resString.includes("login")) {
                            confirmLogin();
                        } else {
                            safeAlert("Could not add item. Out of stock?");
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        safeAlert("System error. Please try again.");
                    }
                });
            });
        });


        function toggleWishlistDetail(btn, pid) {
            let $btn = $(btn);
            $btn.addClass("animating");
            setTimeout(() => $btn.removeClass("animating"), 300);

            $.ajax({
                url: 'wishlist_action.php',
                type: 'POST',
                data: {
                    product_id: pid
                },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'login_required') {
                        confirmLogin();
                    } else if (res.status === 'added') {
                        $btn.addClass('active');
                        safeToast('Saved to Wishlist');
                    } else if (res.status === 'removed') {
                        $btn.removeClass('active');

                        safeToast('Removed from Wishlist');
                    }
                }
            });
        }
    </script>
</body>

</html>