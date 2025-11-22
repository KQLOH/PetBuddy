<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

include '../include/db.php';

$member_id = $_SESSION['member_id'];
$user_name = $_SESSION['full_name'] ?? 'Member';

// FETCH CATEGORIES
$categories = [];
if (isset($pdo)) {
    try {
        $stmtCat = $pdo->query("SELECT * FROM product_categories ORDER BY name ASC");
        $categories = $stmtCat->fetchAll();
    } catch (PDOException $e) { }

    // FETCH PRODUCTS
    $products = [];
    try {
        $sqlProd = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN product_categories c ON p.category_id = c.category_id 
                    ORDER BY p.product_id DESC";
        $stmtProd = $pdo->query($sqlProd);
        $products = $stmtProd->fetchAll();
    } catch (PDOException $e) { }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - PetBuddy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Local Overrides for Home Layout */
        .hero-section {
            background: linear-gradient(135deg, #fff7ec 0%, #ffffff 100%);
            padding: 3rem 1rem;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .product-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .category-tab.active {
            background-color: var(--primary-color) !important;
            color: white !important;
            border-color: var(--primary-color) !important;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-50 font-sans text-gray-700">

    <!-- INCLUDED HEADER -->
    <?php include '../include/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">
                Welcome back, <span class="text-pet-primary"><?php echo htmlspecialchars($user_name); ?></span>!
            </h1>
            <p class="text-gray-600 text-lg mb-8">
                Your pet missed you! Check out the latest treats and toys we've stocked just for them.
            </p>
            <div class="flex justify-center gap-4">
                <div class="px-4 py-2 bg-orange-100 text-orange-700 rounded-full text-sm font-semibold">
                    <i class="fas fa-shipping-fast mr-2"></i> Free Shipping > RM100
                </div>
                <div class="px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                    <i class="fas fa-award mr-2"></i> Premium Quality
                </div>
            </div>
        </div>
    </section>

    <!-- Shop Section -->
    <div id="shop" class="max-w-6xl mx-auto px-4 py-10">
        
        <div class="flex justify-between items-end mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Our Products</h2>
            
            <!-- Category Filters -->
            <div class="flex gap-2 overflow-x-auto pb-2">
                <button class="category-tab active px-4 py-1 rounded-full border border-gray-300 bg-white text-sm hover:bg-gray-50 transition" data-category="all">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="category-tab px-4 py-1 rounded-full border border-gray-300 bg-white text-sm hover:bg-gray-50 transition" data-category="<?php echo $cat['category_id']; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="productGrid">
            <?php if (empty($products)): ?>
                <div class="col-span-full text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
                    <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No products available right now.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <?php 
                        // Handle Image Path
                        $imgFile = !empty($product['image']) ? $product['image'] : 'placeholder.png';
                        // If not http, assume local uploads folder (going up one level to root/uploads)
                        $imgSrc = (strpos($imgFile, 'http') === 0) ? $imgFile : '../uploads/products/' . $imgFile;
                    ?>
                    <div class="product-card bg-white rounded-xl border border-gray-100 overflow-hidden flex flex-col h-full" data-category="<?php echo $product['category_id']; ?>">
                        <div class="h-48 bg-gray-100 relative">
                            <!-- Image with fallback -->
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" 
                                 onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="w-full h-full object-cover">
                            
                            <?php if($product['stock_qty'] < 5 && $product['stock_qty'] > 0): ?>
                                <span class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded">Low Stock</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-4 flex-grow flex flex-col">
                            <div class="text-xs text-gray-400 mb-1 uppercase tracking-wide"><?php echo htmlspecialchars($product['category_name'] ?? 'Pet Supplies'); ?></div>
                            <h3 class="font-bold text-gray-800 text-lg mb-1 truncate" title="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h3>
                            <p class="text-gray-500 text-sm mb-4 line-clamp-2 flex-grow">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </p>
                            
                            <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-50">
                                <span class="text-xl font-bold text-pet-primary">RM <?php echo number_format($product['price'], 2); ?></span>
                                <button class="w-8 h-8 rounded-full bg-pet-secondary text-white flex items-center justify-center hover:bg-teal-600 transition shadow-sm" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- INCLUDED FOOTER -->
    <?php include '../include/footer.php'; ?>

    <script>
        $(document).ready(function() {
            // Category Filter
            $('.category-tab').click(function() {
                $('.category-tab').removeClass('active');
                $(this).addClass('active');
                
                const categoryId = $(this).data('category');
                
                if (categoryId === 'all') {
                    $('.product-card').fadeIn();
                } else {
                    $('.product-card').hide();
                    $('.product-card[data-category="' + categoryId + '"]').fadeIn();
                }
            });
        });

        function addToCart(productId) {
            // Simple visual feedback
            // In a real app, you would AJAX post to 'cart_add.php'
            alert("Added Item ID " + productId + " to cart! (Functionality to be implemented)");
        }
    </script>

</body>
</html>