<?php
// 引入 $pdo 对象
require_once '../include/db.php';
require_once '../include/product_utils.php';

$categoryId = isset($_GET['category']) ? max(0, (int)$_GET['category']) : 0;
$searchTerm = trim($_GET['search'] ?? '');

/**
 * PDO 版本：获取所有分类
 */
function fetchCategories(PDO $pdo): array {
    $sql = "SELECT category_id, name, description FROM product_categories ORDER BY name";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * PDO 版本：根据分类和搜索词获取商品
 */
function fetchProducts(PDO $pdo, int $categoryId, string $searchTerm = ''): array {
    $products = [];
    
    // 依然保留了之前的修复：p.image AS image
    $sql = "SELECT p.product_id, p.category_id, p.name, p.description, p.price, p.stock_qty, 
                   p.image AS image, 
                   c.name AS category_name
            FROM products p
            LEFT JOIN product_categories c ON p.category_id = c.category_id
            WHERE 1=1";

    $params = [];

    if ($categoryId > 0) {
        $sql .= " AND p.category_id = ?"; 
        $params[] = $categoryId;
    }

    if ($searchTerm !== '') {
        $sql .= " AND (p.name LIKE ? OR c.name LIKE ?)"; 
        $like = '%' . $searchTerm . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY p.product_id DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<div style='color:red; padding:20px;'>SQL Error: " . $e->getMessage() . "</div>";
        $products = [];
    }

    return $products;
}

// === 使用 $pdo 对象调用函数 ===
$categories = fetchCategories($pdo);
$products = fetchProducts($pdo, $categoryId, $searchTerm);
// =============================

$currentCategoryLabel = 'All Products';
$pageTitle = 'All Products';

if ($categoryId > 0) {
    foreach ($categories as $category) {
        if ((int)$category['category_id'] === $categoryId) {
            $currentCategoryLabel = $category['name'];
            $pageTitle = $category['name'];
            break;
        }
    }
}

$clearSearchUrl = 'product_listing.php' . ($categoryId > 0 ? '?category=' . $categoryId : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> - PetBuddy</title>
    <link rel="stylesheet" href="../css/product_page.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<?php include_once '../include/header.php'; ?>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="page-subtitle">
                <?php if ($categoryId > 0): ?>
                    Find the perfect <?php echo htmlspecialchars(strtolower($currentCategoryLabel), ENT_QUOTES, 'UTF-8'); ?> for your beloved pets.
                <?php else: ?>
                    Find the perfect products for your beloved pets, from food to toys, from cleaning to health care, everything you need.
                <?php endif; ?>
            </p>
            <?php if ($searchTerm !== ''): ?>
                <div class="search-feedback">
                    Showing results for <span class="search-chip">"<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>"</span>
                    <a class="clear-search" href="<?php echo htmlspecialchars($clearSearchUrl, ENT_QUOTES, 'UTF-8'); ?>">Clear search</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a class="product-image-link" href="product_detail.php?id=<?php echo (int)$product['product_id']; ?>">
                            <div class="product-image">
                                <img src="<?php echo productImageUrl($product['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="product-badge"><?php echo htmlspecialchars($product['category_name'] ?: 'Other', ENT_QUOTES, 'UTF-8'); ?></div>
                            </div>
                        </a>
                        <div class="product-info">
                            <a class="product-title-link" href="product_detail.php?id=<?php echo (int)$product['product_id']; ?>">
                                <div class="product-category">
                                    <?php echo htmlspecialchars($product['category_name'] ?: 'Other', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <h3 class="product-title"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                </a>
                            <div class="product-price">
                                <span class="price"><?php echo htmlspecialchars(formatPrice((float)$product['price']), ENT_QUOTES, 'UTF-8'); ?></span>
                                
                                <button class="add-to-cart-btn add-btn" data-id="<?php echo (int)$product['product_id']; ?>">
                                    <span class="cart-icon"><img src="../images/add-to-cart.png" alt="Cart"></span>
                                    <span class="cart-text">Add to Cart</span>
                                </button>
                                
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No products found</h3>
                    <p>Please try different keywords or browse other categories.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include_once '../include/footer.php'; ?>
    <?php include '../include/chat_widget.php'; ?>

</body>
</html>