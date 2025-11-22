<?php
// home.php
session_start();

// If you have a database later, you will load categories/products like:
// require_once 'includes/db.php';
// For now, empty arrays:
$categories = [];
$products   = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy Online Pet Shop | Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --primary-color: #F4A261;
            --primary-dark: #E68E3F;
            --text-dark: #333333;
            --text-light: #ffffff;
            --bg-light: #f9f9f9;
            --border-color: #e0e0e0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            background-color: #ffffff;
            color: var(--text-dark);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* NAVBAR */
        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: bold;
            font-size: 20px;
            color: var(--primary-dark);
        }

        .logo-circle {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        .nav-links {
            display: flex;
            gap: 18px;
            font-size: 14px;
        }

        .nav-links a {
            padding: 6px 10px;
            border-radius: 999px;
        }

        .nav-links a:hover {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 6px 14px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        /* HERO SECTION */
        .hero {
            background: linear-gradient(135deg, #fff7ec, #ffffff);
            border-bottom: 1px solid var(--border-color);
        }

        .hero-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 16px 24px;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
            gap: 24px;
            align-items: center;
        }

        .hero-title {
            font-size: 32px;
            margin-bottom: 12px;
        }

        .hero-title span {
            color: var(--primary-dark);
        }

        .hero-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .hero-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .badge {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 999px;
            background-color: rgba(244, 162, 97, 0.12);
            color: var(--primary-dark);
        }

        .hero-image-box {
            position: relative;
        }

        .hero-pet-card {
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 20px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
        }

        .hero-pet-card h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }

        .hero-pet-card p {
            font-size: 13px;
            color: #666;
            margin-bottom: 12px;
        }

        .hero-pet-stats {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }

        .hero-paw {
            position: absolute;
            width: 42px;
            height: 42px;
            border-radius: 16px;
            background-color: var(--primary-color);
            color: #fff;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            right: -8px;
            top: -8px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.18);
        }

        /* PRODUCT SECTION */
        .page-section {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
        }

        .section-subtitle {
            font-size: 13px;
            color: #777;
        }

        .category-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .category-tab {
            font-size: 13px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background-color: #ffffff;
            cursor: pointer;
        }

        .category-tab.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #ffffff;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 16px;
        }

        .product-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-image {
            width: 100%;
            height: 140px;
            border-radius: 10px;
            background-color: #fff6ec;
            background-size: cover;
            background-position: center;
            margin-bottom: 10px;
        }

        .product-category {
            font-size: 11px;
            color: #999;
            margin-bottom: 2px;
        }

        .product-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .product-price {
            font-size: 15px;
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 6px;
        }

        .product-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
        }

        .product-actions a {
            font-size: 12px;
            color: #777;
        }

        .product-add-btn {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* FOOTER */
        .footer {
            border-top: 1px solid var(--border-color);
            padding: 16px;
            text-align: center;
            font-size: 12px;
            color: #888;
            background-color: #ffffff;
        }

        /* BACK TO TOP */
        #backToTop {
            position: fixed;
            right: 18px;
            bottom: 18px;
            width: 40px;
            height: 40px;
            border-radius: 999px;
            background-color: var(--primary-color);
            color: #ffffff;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            z-index: 99;
        }

        @media (max-width: 768px) {
            .hero-inner {
                grid-template-columns: 1fr;
                padding-top: 24px;
            }
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<!-- NAVIGATION -->
<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo">
            <div class="logo-circle">🐾</div>
            <span>PetBuddy</span>
        </div>

        <div class="nav-links">
            <a href="home.php">Home</a>
            <a href="products.php">Products</a>
            <a href="cart.php">Cart</a>
            <a href="orders.php">My Orders</a>
        </div>

        <div class="nav-actions">
            <?php if (!empty($_SESSION['member_id'])): ?>
                <span style="font-size: 13px;">Welcome back 👋</span>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="">
    <div class="">
            <a href="../admin/admin.php">Admin</a>
        </div>
    <div class="hero-inner">
        <div>
            <h1 class="hero-title">
                Welcome to <span>PetBuddy</span><br>
                Your Pet’s Best Friend Online Shop
            </h1>
            <p class="hero-subtitle">
                At PetBuddy, we bring you carefully selected food, toys, and supplies<br>
                for dogs, cats, and small pets. Shop easily and have everything delivered to your door.
            </p>

            <div class="hero-badges">
                <div class="badge">🚚 Free shipping for orders above RM100*</div>
                <div class="badge">💳 Multiple payment methods supported</div>
                <div class="badge">⭐ Trusted quality brands</div>
            </div>

            <div class="hero-actions">
                <a href="products.php" class="btn btn-primary">Start Shopping</a>
                <a href="#section-products" class="btn btn-outline">View Latest Products</a>
            </div>
        </div>

        <div class="hero-image-box">
            <div class="hero-pet-card">
                <h3>Today’s Pick · Happy Dog Pack</h3>
                <p>A balanced combo of dog food + chew toy + snacks, all in one bundle for a great day!</p>
                <div class="hero-pet-stats">
                    <div>
                        <span>Suitable For</span>
                        <strong>Small Dogs / Puppies</strong>
                    </div>
                    <div>
                        <span>Rating</span>
                        <strong>4.8 / 5.0</strong>
                    </div>
                    <div>
                        <span>Bundle Price</span>
                        <strong>RM 89.90</strong>
                    </div>
                </div>
            </div>
            <div class="hero-paw">🐶</div>
        </div>
    </div>
</section>

<!-- PRODUCTS SECTION -->
<section class="page-section" id="section-products">
    <div class="section-header">
        <div>
            <div class="section-title">Latest Products</div>
            <div class="section-subtitle">Check out the newest arrivals for your pets</div>
        </div>
        <a href="products.php" class="btn btn-outline" style="font-size: 12px;">View All Products</a>
    </div>

    <!-- CATEGORY TABS -->
    <div class="category-tabs">
        <button class="category-tab active" data-category="all">All</button>

        <?php foreach ($categories as $cat): ?>
            <button class="category-tab" data-category="<?php echo htmlspecialchars($cat['category_id']); ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- PRODUCT GRID -->
    <div class="products-grid" id="productsGrid">
        <?php if (empty($products)): ?>
            <p style="font-size: 14px; color:#777;">No products yet. Please add items in admin panel.</p>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card" data-category="<?php echo (int)$product['category_id']; ?>">
                    <div class="product-image"
                         style="background-image:url('<?php echo $product['photo'] ? 'uploads/products/' . htmlspecialchars($product['photo']) : 'assets/images/placeholder-pet.png'; ?>');">
                    </div>
                    <div class="product-category">
                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                    </div>
                    <div class="product-name">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </div>
                    <div class="product-price">
                        RM <?php echo number_format($product['price'], 2); ?>
                    </div>
                    <div class="product-actions">
                        <a href="product_detail.php?id=<?php echo (int)$product['product_id']; ?>">Details</a>
                        <form action="cart_add.php" method="post" style="margin:0;">
                            <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                            <button type="submit" class="btn btn-primary product-add-btn">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    PetBuddy Online Shop &copy; <?php echo date('Y'); ?> · Caring for pets starts with great products 🐾
</footer>

<!-- BACK TO TOP -->
<div id="backToTop">↑</div>

<script>
    $(function () {
        // Category filter
        $('.category-tab').on('click', function () {
            var categoryId = $(this).data('category');
            $('.category-tab').removeClass('active');
            $(this).addClass('active');

            if (categoryId === 'all') {
                $('.product-card').show();
            } else {
                $('.product-card').each(function () {
                    var cardCat = $(this).data('category').toString();
                    if (cardCat === categoryId.toString()) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        // Back to top button
        var $backToTop = $('#backToTop');
        $(window).on('scroll', function () {
            if ($(this).scrollTop() > 200) {
                $backToTop.fadeIn(200);
            } else {
                $backToTop.fadeOut(200);
            }
        });

        $backToTop.on('click', function () {
            $('html, body').animate({scrollTop: 0}, 300);
        });

        // Smooth scroll to section
        $('a[href^="#section-products"]').on('click', function (e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('#section-products').offset().top - 60
            }, 400);
        });
    });
</script>

</body>
</html>
