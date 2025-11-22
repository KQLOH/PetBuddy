<?php
session_start();

// Include database connection
// Assumes index.php is in the root folder and db.php is in 'include/'
include '../include/db.php';

// Initialize variables
$categories = [];
$products = [];
$user_name = null;
$is_logged_in = false;

// 1. CHECK LOGIN STATUS
if (isset($_SESSION['member_id'])) {
    $is_logged_in = true;
    $user_name = $_SESSION['full_name'] ?? 'Member';
}

// 2. FETCH CATEGORIES
if (isset($pdo)) {
    try {
        $stmtCat = $pdo->query("SELECT * FROM product_categories ORDER BY name ASC");
        $categories = $stmtCat->fetchAll();
    } catch (PDOException $e) {
        // Silently fail or log error
    }

    // 3. FETCH LATEST PRODUCTS (Limit 12)
    try {
        // We assume table 'products' has columns: product_id, name, price, image, category_id
        // We JOIN with categories to get the category name
        $sqlProd = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN product_categories c ON p.category_id = c.category_id 
                    ORDER BY p.product_id DESC 
                    LIMIT 12";
        $stmtProd = $pdo->query($sqlProd);
        $products = $stmtProd->fetchAll();
    } catch (PDOException $e) {
        // Silently fail
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy Online Pet Shop | Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Tailwind CSS (Optional, if you want to mix with your custom CSS) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Your Custom CSS from css/style.css -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Keep your specific homepage styles here to override or add to global css */
        :root {
            --primary-color: #FF9F1C; /* PetBuddy Orange */
            --primary-dark: #E68E3F;
            --text-dark: #333333;
            --border-color: #e0e0e0;
        }

        /* HERO SECTION */
        .hero {
            background: linear-gradient(135deg, #fff7ec, #ffffff);
            border-bottom: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .hero-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 60px 16px;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
            gap: 40px;
            align-items: center;
        }

        .hero-title {
            font-size: 3rem;
            line-height: 1.2;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--text-dark);
        }

        .hero-title span {
            color: var(--primary-color);
        }

        .hero-subtitle {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .hero-badges {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .badge {
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 999px;
            background-color: rgba(255, 159, 28, 0.15);
            color: #d37800;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-hero {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn-hero:hover {
            transform: translateY(-2px);
            background-color: var(--primary-dark);
        }

        /* PRODUCTS SECTION */
        .page-section {
            max-width: 1100px;
            margin: 0 auto;
            padding: 60px 16px;
        }

        .category-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            justify-content: center;
        }

        .category-tab {
            font-size: 0.9rem;
            padding: 8px 20px;
            border-radius: 999px;
            border: 1px solid var(--border-color);
            background-color: #ffffff;
            cursor: pointer;
            transition: all 0.2s;
        }

        .category-tab.active, .category-tab:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #ffffff;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 24px;
        }

        .product-card {
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #f0f0f0;
            padding: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .product-image {
            width: 100%;
            height: 180px;
            border-radius: 12px;
            background-color: #f9f9f9;
            background-size: cover;
            background-position: center;
            margin-bottom: 16px;
            position: relative;
        }

        .product-tag {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(255,255,255,0.9);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #555;
        }

        .product-name {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 8px;
            color: #2d3748;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-price {
            font-size: 1.1rem;
            color: var(--primary-color);
            font-weight: 800;
        }

        .product-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
        }

        .btn-add-cart {
            background-color: #2EC4B6; /* Teal */
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-add-cart:hover {
            background-color: #25a094;
        }

        /* BACK TO TOP */
        #backToTop {
            position: fixed;
            right: 30px;
            bottom: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: #ffffff;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            z-index: 99;
            transition: transform 0.2s;
        }
        #backToTop:hover { transform: scale(1.1); }
    </style>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<!-- We assume header.php handles the navigation bar -->
<?php include '../include/header.php'; ?>

<!-- HERO SECTION -->
<section class="hero">
    <div class="hero-inner">
        <div>
            <h1 class="hero-title">
                <?php if ($is_logged_in): ?>
                    Welcome back,<br> <span><?php echo htmlspecialchars($user_name); ?>!</span>
                <?php else: ?>
                    Make Your Pet<br> <span>Happy & Healthy</span>
                <?php endif; ?>
            </h1>
            
            <p class="hero-subtitle">
                <?php if ($is_logged_in): ?>
                    We have some new arrivals picked just for you. Check out our latest collection of premium food and toys.
                <?php else: ?>
                    At PetBuddy, we bring you carefully selected food, toys, and supplies for dogs, cats, and small pets.
                <?php endif; ?>
            </p>

            <div class="hero-badges">
                <div class="badge"><i class="fas fa-truck"></i> Free shipping > RM100</div>
                <div class="badge"><i class="fas fa-shield-alt"></i> 100% Secure Payment</div>
                <div class="badge"><i class="fas fa-star"></i> Top Rated Brands</div>
            </div>

            <div class="hero-actions">
                <a href="#section-products" class="btn-hero shadow-lg">
                    <i class="fas fa-shopping-bag mr-2"></i> Shop Now
                </a>
            </div>
        </div>

        <!-- Hero Image / Illustration -->
        <div class="relative hidden md:block">
            <div class="absolute top-0 right-0 bg-orange-100 w-64 h-64 rounded-full -z-10 blur-3xl opacity-50"></div>
            <img src="https://images.unsplash.com/photo-1450778869180-41d0601e046e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" 
                 alt="Happy Dog" 
                 class="rounded-3xl shadow-2xl transform rotate-3 border-4 border-white w-full object-cover h-80">
            
            <!-- Floating Card -->
            <div class="absolute -bottom-6 -left-6 bg-white p-4 rounded-xl shadow-xl border border-gray-100 max-w-xs">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                        <i class="fas fa-bone"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 text-sm">Tasty Treats</h4>
                        <p class="text-xs text-gray-500">Best seller of the week!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PRODUCTS SECTION -->
<section class="page-section" id="section-products">
    <div class="text-center mb-10">
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Latest Products</h2>
        <p class="text-gray-500">Fresh arrivals for your furry friends</p>
    </div>

    <!-- DYNAMIC CATEGORY TABS -->
    <div class="category-tabs">
        <button class="category-tab active" data-category="all">All</button>
        <?php foreach ($categories as $cat): ?>
            <button class="category-tab" data-category="<?php echo htmlspecialchars($cat['category_id']); ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- DYNAMIC PRODUCT GRID -->
    <div class="products-grid" id="productsGrid">
        <?php if (empty($products)): ?>
            <div class="col-span-full text-center py-12">
                <div class="text-gray-300 text-5xl mb-4"><i class="fas fa-box-open"></i></div>
                <p class="text-gray-500">No products found. Check back later!</p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <?php 
                    // Determine Image path (Handle both 'photo' or 'image' column names if schema varies)
                    // Assuming your DB uses 'image' based on CREATE TABLE, but falling back to 'photo' just in case.
                    $imgFile = !empty($product['image']) ? $product['image'] : ($product['photo'] ?? '');
                    
                    // If it's a full URL, use it, else append 'uploads/'
                    $imgSrc = (strpos($imgFile, 'http') === 0) ? $imgFile : ((!empty($imgFile)) ? 'uploads/products/' . $imgFile : 'https://via.placeholder.com/300x300?text=No+Image');
                ?>
                <div class="product-card group" data-category="<?php echo (int)$product['category_id']; ?>">
                    <div class="product-image" style="background-image:url('<?php echo htmlspecialchars($imgSrc); ?>');">
                        <?php if(isset($product['category_name'])): ?>
                            <div class="product-tag shadow-sm"><?php echo htmlspecialchars($product['category_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-name" title="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </div>
                    
                    <div class="product-footer">
                        <div class="product-price">RM <?php echo number_format($product['price'], 2); ?></div>
                        
                        <form action="cart_add.php" method="post" class="m-0">
                            <input type="hidden" name="product_id" value="<?php echo (int)$product['product_id']; ?>">
                            <button type="submit" class="btn-add-cart">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </form>
                    </div>
                    
                    <!-- Link wrapper for details -->
                    <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="absolute inset-0 z-0" aria-label="View Details"></a>
                    <!-- Ensure button is clickable by giving it higher z-index -->
                    <style>.btn-add-cart { position: relative; z-index: 10; }</style>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php include '../include/footer.php'; ?>

<!-- BACK TO TOP -->
<div id="backToTop"><i class="fas fa-arrow-up"></i></div>

<script>
    $(function () {
        // Category Filter Logic
        $('.category-tab').on('click', function () {
            var categoryId = $(this).data('category');
            
            // Update UI classes
            $('.category-tab').removeClass('active');
            $(this).addClass('active');

            // Filter Items
            if (categoryId === 'all') {
                $('.product-card').fadeIn(300);
            } else {
                $('.product-card').each(function () {
                    // Force string comparison
                    var cardCat = $(this).data('category').toString();
                    if (cardCat === categoryId.toString()) {
                        $(this).fadeIn(300);
                    } else {
                        $(this).fadeOut(200);
                    }
                });
            }
        });

        // Back to Top Logic
        var $backToTop = $('#backToTop');
        $(window).on('scroll', function () {
            if ($(this).scrollTop() > 300) {
                $backToTop.css('display', 'flex').fadeIn(200);
            } else {
                $backToTop.fadeOut(200);
            }
        });

        $backToTop.on('click', function () {
            $('html, body').animate({scrollTop: 0}, 500);
        });

        // Smooth Scroll for Anchor Links
        $('a[href^="#"]').on('click', function (e) {
            e.preventDefault();
            var target = $(this.getAttribute('href'));
            if(target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 500);
            }
        });
    });
</script>

</body>
</html>