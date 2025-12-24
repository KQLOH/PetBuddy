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
    $related = [];
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
    if (empty($reviews)) return ['average' => 0, 'total' => 0, 'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0], 'percentages' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
    $total = count($reviews);
    $sum = 0;
    $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($reviews as $review) {
        $rating = (int)$review['rating'];
        $sum += $rating;
        if (isset($distribution[$rating])) $distribution[$rating]++;
    }
    $average = $total > 0 ? round($sum / $total, 1) : 0;
    $percentages = [];
    foreach ($distribution as $star => $count) {
        $percentages[$star] = $total > 0 ? round(($count / $total) * 100, 0) : 0;
    }
    return ['average' => $average, 'total' => $total, 'distribution' => $distribution, 'percentages' => $percentages];
}

function generateStars(int $rating): string
{
    $html = '';
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    for ($i = 0; $i < $fullStars; $i++) $html .= '<span class="icon-star"></span>';
    if ($hasHalfStar) {
        $html .= '<span class="icon-star-half"></span>';
        $fullStars++;
    }
    for ($i = 0; $i < 5 - $fullStars; $i++) $html .= '<span class="icon-star-empty"></span>';
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
    <link rel="stylesheet" href="../css/product_page.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .pd-action-buttons {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            width: 100%;
        }

        .pd-btn-row {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .pd-btn-primary {
            flex: 1;
            justify-content: center;
        }

        .pd-btn-secondary {
            background: #fff;
            border: 1px solid #ddd;
            color: #ccc;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 8px;
            width: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .pd-btn-secondary:hover {
            border-color: #FFB774;
            color: #FFB774;
            background: #fff9f4;
        }

        .pd-btn-secondary.active {
            border-color: #ff4d4d;
            color: #ff4d4d;
            background: #fff0f0;
        }

        .wishlist-btn.animating i {
            animation: heartPop 0.3s ease-in-out;
        }

        @keyframes heartPop {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.3);
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
            color: #FFB774;
            border-bottom: 1px solid #FFB774;
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
                                <span class="pd-icon-cart"><i class="fas fa-shopping-cart"></i></span> Add to Cart
                            </button>

                            <?php
                            $isWished = in_array($product['product_id'], $wishlistIds ?? []);
                            $heartClass = $isWished ? 'fas' : 'far';
                            $btnClass = $isWished ? 'active' : '';
                            ?>
                            <button class="pd-btn pd-btn-secondary wishlist-btn <?php echo $btnClass; ?>" onclick="toggleWishlistDetail(this, <?php echo $product['product_id']; ?>)" title="Add to Wishlist">
                                <i class="<?php echo $heartClass; ?> fa-heart" style="font-size: 18px;"></i>
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
                                <?php echo generateStars($review['rating']); ?>
                            </div>

                            <div class="pd-review-content">
                                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="review-item" style="text-align:center; padding:40px; color:#666;">
                        No reviews yet. Be the first to review!
                    </div>
                <?php endif; ?>
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

    <?php include_once '../include/footer.php'; ?>
    <?php include '../include/chat_widget.php'; ?>

    <script>
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
                        var res = response.trim();

                        if (res.includes("added") || res.includes("increased") || res.includes("success")) {

                            const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });
                            Toast.fire({
                                icon: 'success',
                                title: 'Added to Cart!'
                            });

                            if (typeof refreshCartSidebar === 'function') {
                                refreshCartSidebar();
                            }

                            setTimeout(() => {
                                if (typeof openCart === 'function') {
                                    openCart();
                                }
                            }, 300);

                        } else if (res.includes("login")) {
                            Swal.fire({
                                title: "Please Login",
                                text: "Login to shop.",
                                icon: "warning",
                                confirmButtonText: "Login"
                            }).then((r) => {
                                if (r.isConfirmed) window.location.href = "login.php";
                            });
                        } else {
                            Swal.fire("Error", "Could not add item.", "error");
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        });

        function toggleWishlistDetail(btn, pid) {
            let $btn = $(btn);
            let $icon = $btn.find("i");
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
                        Swal.fire({
                            title: 'Login Required',
                            text: 'Please login to save items.',
                            icon: 'warning',
                            confirmButtonText: 'Login',
                            confirmButtonColor: '#2F2F2F'
                        }).then((r) => {
                            if (r.isConfirmed) window.location.href = 'login.php';
                        });
                    } else if (res.status === 'added') {
                        $btn.addClass('active');
                        $icon.removeClass('far').addClass('fas');
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Saved to Wishlist'
                        });
                    } else if (res.status === 'removed') {
                        $btn.removeClass('active');
                        $icon.removeClass('fas').addClass('far');
                    }
                }
            });
        }
    </script>
</body>

</html>