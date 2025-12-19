<?php
require_once '../include/db.php'; // 这里引入了 $pdo
require_once '../include/product_utils.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: product_listing.php');
    exit;
}

/**
 * PDO 版本：获取商品详情
 */
function fetchProductDetail(PDO $pdo, int $productId): ?array {
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

/**
 * PDO 版本：获取相关商品
 */
function fetchRelated(PDO $pdo, int $categoryId, int $productId, int $limit = 3): array {
    $related = [];
    $sql = "SELECT product_id, name, price, image
            FROM products
            WHERE category_id = :cat_id AND product_id <> :prod_id
            ORDER BY RAND()
            LIMIT :limit";
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

/**
 * PDO 版本：获取商品评论
 */
function fetchProductReviews(PDO $pdo, int $productId): array {
    $reviews = [];
    // 尝试查找评论表
    $tableNames = ['product_reviews', 'reviews', 'product_ratings'];
    $foundTable = null;
    
    foreach ($tableNames as $tableName) {
        try {
            $pdo->query("SELECT 1 FROM $tableName LIMIT 1");
            $foundTable = $tableName;
            break;
        } catch (PDOException $e) {
            continue;
        }
    }
    
    if ($foundTable) {
        // 获取列名以构建动态查询
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $foundTable");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 构建查询字段
            $selectFields = [];
            $fieldMap = [
                'review_id' => ['review_id', 'id'],
                'product_id' => ['product_id'],
                'rating' => ['rating', 'star_rating', 'stars'],
                'comment' => ['comment', 'review_text', 'review', 'content', 'message'],
                'reviewer' => ['customer_name', 'reviewer_name', 'name', 'user_name', 'username'],
                'date' => ['created_at', 'date_created', 'review_date', 'created_date', 'date']
            ];
            
            foreach ($fieldMap as $key => $possibleFields) {
                foreach ($possibleFields as $field) {
                    if (in_array($field, $columns)) {
                        $selectFields[$key] = $field;
                        break;
                    }
                }
            }
            
            if (!empty($selectFields) && isset($selectFields['product_id']) && 
                (isset($selectFields['rating']) || isset($selectFields['comment']))) {
                
                $sqlFields = array_values($selectFields);
                $orderField = $selectFields['date'] ?? $selectFields['review_id'] ?? 'product_id';
                
                // 注意：这里为了简化，我们假设表名和字段名是安全的。在生产环境中应更严格。
                $sql = "SELECT " . implode(', ', $sqlFields) . "
                        FROM $foundTable
                        WHERE " . $selectFields['product_id'] . " = :pid
                        ORDER BY $orderField DESC
                        LIMIT 50";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['pid' => $productId]);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($results as $row) {
                    $reviews[] = [
                        'review_id' => isset($selectFields['review_id']) ? ($row[$selectFields['review_id']] ?? null) : null,
                        'rating' => isset($selectFields['rating']) ? (int)($row[$selectFields['rating']] ?? 5) : 5,
                        'comment' => isset($selectFields['comment']) ? ($row[$selectFields['comment']] ?? '') : '',
                        'reviewer' => isset($selectFields['reviewer']) ? ($row[$selectFields['reviewer']] ?? 'Anonymous') : 'Anonymous',
                        'date' => isset($selectFields['date']) ? ($row[$selectFields['date']] ?? date('Y-m-d')) : date('Y-m-d')
                    ];
                }
            }
        } catch (PDOException $e) {
            // 忽略错误
        }
    }
    
    return $reviews;
}

/**
 * 计算评论统计 (通用逻辑，不需要改)
 */
function calculateReviewStats(array $reviews): array {
    if (empty($reviews)) {
        return [
            'average' => 0,
            'total' => 0,
            'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
            'percentages' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
        ];
    }
    
    $total = count($reviews);
    $sum = 0;
    $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    
    foreach ($reviews as $review) {
        $rating = (int)$review['rating'];
        $sum += $rating;
        if (isset($distribution[$rating])) {
            $distribution[$rating]++;
        }
    }
    
    $average = $total > 0 ? round($sum / $total, 1) : 0;
    $percentages = [];
    foreach ($distribution as $star => $count) {
        $percentages[$star] = $total > 0 ? round(($count / $total) * 100, 0) : 0;
    }
    
    return [
        'average' => $average,
        'total' => $total,
        'distribution' => $distribution,
        'percentages' => $percentages
    ];
}

/**
 * 生成星星 HTML (通用逻辑，不需要改)
 */
function generateStars(int $rating): string {
    $html = '';
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<span class="icon-star"></span>';
    }
    
    if ($hasHalfStar) {
        $html .= '<span class="icon-star-half"></span>';
        $fullStars++;
    }
    
    $emptyStars = 5 - $fullStars;
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<span class="icon-star-empty"></span>';
    }
    
    return $html;
}

// === 主逻辑：使用 $pdo 调用函数 ===
$product = fetchProductDetail($pdo, $productId);

if (!$product) {
    // 如果找不到商品，跳转回列表
    header('Location: product_listing.php');
    exit;
}

$relatedProducts = fetchRelated($pdo, (int)$product['category_id'], $productId);
$reviews = fetchProductReviews($pdo, $productId);
$reviewStats = calculateReviewStats($reviews);

// 图片处理
$mainImage = productImageUrl($product['image']);
$thumbnails = [$mainImage, $mainImage, $mainImage, $mainImage]; // 这里你可以扩展从相册表获取图片
$categoryName = $product['category_name'] ?: 'All Products';
$categoryLink = 'product_listing.php' . ((int)$product['category_id'] > 0 ? '?category=' . (int)$product['category_id'] : '');
$productDescription = trim((string)$product['description']) !== '' ? $product['description'] : 'No product description available';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> - PetBuddy</title>
    <link rel="stylesheet" href="../css/product_page.css">
</head>
<body>
<?php include_once '../include/header.php'; ?>
    <div class="container">
        <div class="pd-product-detail">
            <div class="pd-product-images">
                <div class="pd-main-image">
                    <img src="<?php echo $mainImage; ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" id="main-image">
                </div>
                <div class="pd-thumbnail-images">
                    <?php foreach ($thumbnails as $index => $thumbnail): ?>
                        <div class="pd-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" data-image="<?php echo $thumbnail; ?>">
                            <img src="<?php echo $thumbnail; ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="pd-product-info">
                <div class="pd-product-tag"><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></div>
                <h1 class="pd-product-title"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                
                <div class="pd-product-rating">
                    <div class="pd-stars">
                        <?php echo generateStars((int)round($reviewStats['average'])); ?>
                    </div>
                    <div class="pd-rating-count">(<?php echo $reviewStats['total']; ?> reviews)</div>
                </div>
                
                <div class="pd-product-price">
                    <span class="pd-current-price"><?php echo formatPrice((float)$product['price']); ?></span>
                    <span class="pd-stock-info">Stock: <?php echo (int)$product['stock_qty']; ?> items</span>
                </div>
                
                <p class="pd-product-description">
                    <?php echo nl2br(htmlspecialchars($productDescription, ENT_QUOTES, 'UTF-8')); ?>
                </p>
                
                <div class="pd-product-features">
                    <ul class="pd-feature-list">
                        <li><span class="pd-icon-check"></span> 100% Natural Organic Ingredients</li>
                        <li><span class="pd-icon-check"></span> No Artificial Additives</li>
                        <li><span class="pd-icon-check"></span> Rich in Omega-3 and Omega-6 Fatty Acids</li>
                        <li><span class="pd-icon-check"></span> Added Prebiotics for Digestion</li>
                        <li><span class="pd-icon-check"></span> Suitable for All Breeds and Ages</li>
                    </ul>
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
                        <button class="pd-btn pd-btn-primary add-btn" data-id="<?php echo (int)$product['product_id']; ?>">
                            <span class="pd-icon-cart"><img src="../images/add-to-cart.png" alt="Cart" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"></span> Add to Cart
                        </button>
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
                <p><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?> is made with carefully selected ingredients to ensure your pet receives the quality nutrition they need. Every batch undergoes rigorous quality control to guarantee safety and reliability.</p>
                
                <h4>Key Features:</h4>
                <ul>
                    <li>Organic chicken as the main protein source</li>
                    <li>Added organic vegetables and fruits for natural vitamins</li>
                    <li>Rich in Omega-3 and Omega-6 fatty acids for healthy skin and coat</li>
                    <li>Added prebiotics for intestinal health</li>
                    <li>Grain-free, suitable for pets with sensitive stomachs</li>
                </ul>
                
                <h4>Suitable For:</h4>
                <p>Suitable for most pets for daily consumption. For special dietary needs, please consult a veterinarian or our customer service.</p>
            </div>
            
            <div class="pd-tab-content" id="specs">
                <table class="pd-specs-table">
                    <tr>
                        <td>Product Name</td>
                        <td><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>Category</td>
                        <td><?php echo htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                    <tr>
                        <td>Stock Status</td>
                        <td><?php echo (int)$product['stock_qty'] > 0 ? 'In Stock' : 'Out of Stock'; ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="pd-tab-content" id="reviews">
                <div class="pd-reviews-summary">
                    <div class="pd-overall-rating">
                        <div class="pd-rating-score"><?php echo number_format($reviewStats['average'], 1); ?></div>
                        <div class="pd-rating-stars">
                            <?php echo generateStars((int)round($reviewStats['average'])); ?>
                        </div>
                        <div class="pd-rating-total">Based on <?php echo $reviewStats['total']; ?> reviews</div>
                    </div>
                    
                    <div class="pd-rating-bars">
                        <?php for ($star = 5; $star >= 1; $star--): ?>
                        <div class="pd-rating-bar">
                            <div class="pd-rating-label"><?php echo $star; ?> Star<?php echo $star > 1 ? 's' : ''; ?></div>
                            <div class="pd-bar-container">
                                <div class="pd-bar" style="width: <?php echo $reviewStats['percentages'][$star]; ?>%;"></div>
                            </div>
                            <div class="pd-rating-percent"><?php echo $reviewStats['percentages'][$star]; ?>%</div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="pd-review-list">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="pd-review-item">
                                <div class="pd-review-header">
                                    <div class="pd-reviewer"><?php echo htmlspecialchars($review['reviewer'] ?? 'Anonymous', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="pd-review-date">
                                        <?php echo formatReviewDate($review['date'] ?? ''); ?>
                                    </div>
                                </div>
                                <div class="pd-rating-stars">
                                    <?php echo generateStars($review['rating']); ?>
                                </div>
                                <div class="pd-review-content">
                                    <?php echo nl2br(htmlspecialchars($review['comment'], ENT_QUOTES, 'UTF-8')); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="review-item">
                            <div class="review-content" style="text-align: center; color: #666; padding: 40px;">
                                No reviews yet. Be the first to review this product!
                            </div>
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
                                <img src="<?php echo productImageUrl($related['image']); ?>" alt="<?php echo htmlspecialchars($related['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="pd-product-card-info">
                                <h3 class="pd-product-card-title"><?php echo htmlspecialchars($related['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="pd-product-card-price"><?php echo formatPrice((float)$related['price']); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No more related products available. Please browse other categories.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include_once '../include/footer.php'; ?>
    <?php include '../include/chat_widget.php'; ?>
    <script>
        // Image switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const thumbnails = document.querySelectorAll('.pd-thumbnail');
            const mainImage = document.getElementById('main-image');
            
            if (thumbnails.length > 0 && mainImage) {
                thumbnails.forEach(thumbnail => {
                    thumbnail.addEventListener('click', function() {
                        thumbnails.forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        const newImage = this.getAttribute('data-image');
                        if (newImage) {
                            mainImage.src = newImage;
                        }
                    });
                });
            }
            
            // Tab switching functionality
            const tabs = document.querySelectorAll('.pd-tab');
            const tabContents = document.querySelectorAll('.pd-tab-content');
            
            if (tabs.length > 0) {
                tabs.forEach(tab => {
                    tab.addEventListener('click', function() {
                        tabs.forEach(t => t.classList.remove('active'));
                        tabContents.forEach(content => content.classList.remove('active'));
                        this.classList.add('active');
                        const tabId = this.getAttribute('data-tab');
                        const targetContent = document.getElementById(tabId);
                        if (targetContent) {
                            targetContent.classList.add('active');
                        }
                    });
                });
            }
            
            // Quantity selector functionality
            const minusBtn = document.querySelector('.pd-quantity-btn.minus');
            const plusBtn = document.querySelector('.pd-quantity-btn.plus');
            const quantityInput = document.querySelector('.pd-quantity-input');
            const stockQty = quantityInput ? (parseInt(quantityInput.getAttribute('data-stock')) || 999) : 999;
            
            function updateQuantityButtons() {
                if (!quantityInput) return;
                const currentValue = parseInt(quantityInput.value) || 1;
                if (minusBtn) minusBtn.disabled = currentValue <= 1;
                if (plusBtn) plusBtn.disabled = currentValue >= stockQty;
            }
            
            if (minusBtn && quantityInput) {
                minusBtn.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value) || 1;
                    if (value > 1) {
                        quantityInput.value = value - 1;
                        updateQuantityButtons();
                    }
                });
            }
            
            if (plusBtn && quantityInput) {
                plusBtn.addEventListener('click', function() {
                    let value = parseInt(quantityInput.value) || 1;
                    if (value < stockQty) {
                        quantityInput.value = value + 1;
                        updateQuantityButtons();
                    }
                });
            }
            
            if (quantityInput) {
                quantityInput.addEventListener('input', function() {
                    let value = parseInt(this.value) || 1;
                    if (value < 1) {
                        this.value = 1;
                    }
                    if (value > stockQty) {
                        this.value = stockQty;
                    }
                    updateQuantityButtons();
                });
                updateQuantityButtons();
            }
        });
    </script>
</body>
</html>