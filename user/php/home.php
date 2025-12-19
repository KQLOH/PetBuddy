<?php
// 临时开启错误报告，方便调试
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

require_once '../include/db.php'; // 引入 $pdo 对象
require_once '../include/product_utils.php';

/**
 * Load categories with representative images
 * @param PDO $pdo 数据库连接对象
 */
function loadCategoriesWithImages(PDO $pdo): array {
    $categories = [];
    $sql = "SELECT c.category_id, c.name, c.description,
                   (SELECT p.image 
                    FROM products p 
                    WHERE p.category_id = c.category_id 
                    AND p.image IS NOT NULL 
                    AND p.image != '' 
                    ORDER BY p.product_id DESC 
                    LIMIT 1) AS category_image
            FROM product_categories c
            ORDER BY c.name";
    
    try {
        $stmt = $pdo->query($sql);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // 忽略错误，返回空数组
    }
    return $categories;
}

/**
 * Load top rated products based on reviews
 * @param PDO $pdo 数据库连接对象
 */
function loadTopRatedProducts(PDO $pdo, int $limit = 4): array {
    $products = [];
    
    // 尝试查找评论表 (使用 PDO 方式检查表存在)
    $tableNames = ['product_reviews', 'reviews', 'product_ratings'];
    $foundTable = null;
    $ratingField = 'rating'; // 假设最常用的评分字段
    $productIdField = 'product_id';
    
    foreach ($tableNames as $tableName) {
        try {
            // 尝试执行一个简单查询来检查表是否存在
            $pdo->query("SELECT $productIdField, $ratingField FROM $tableName LIMIT 1");
            $foundTable = $tableName;
            break;
        } catch (\PDOException $e) {
            continue;
        }
    }
    
    if ($foundTable) {
        // 如果找到评论表，则查询评分最高的商品
        $sql = "SELECT p.product_id, p.name, p.description, p.price, p.image,
                       c.name AS category_name,
                       AVG(r.$ratingField) AS avg_rating,
                       COUNT(r.$productIdField) AS review_count
                FROM products p
                LEFT JOIN product_categories c ON p.category_id = c.category_id
                LEFT JOIN $foundTable r ON p.product_id = r.$productIdField
                WHERE p.image IS NOT NULL AND p.image != ''
                GROUP BY p.product_id, p.name, p.description, p.price, p.image, c.name
                HAVING review_count > 0
                ORDER BY avg_rating DESC, review_count DESC
                LIMIT :limit";
    } else {
        // 评论表不存在或查询失败，则获取最新商品 (Fallback)
        $sql = "SELECT p.product_id, p.name, p.description, p.price, p.image,
                       c.name AS category_name
                FROM products p
                LEFT JOIN product_categories c ON p.category_id = c.category_id
                WHERE p.image IS NOT NULL AND p.image != ''
                ORDER BY p.product_id DESC
                LIMIT :limit";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        // 使用 bindValue 绑定 Limit 参数
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // 数据库操作失败时返回空数组
        $products = [];
    }
    
    return $products;
}

/**
 * Load latest reviews
 * @param PDO $pdo 数据库连接对象
 */
function loadLatestReviews(PDO $pdo, int $limit = 3): array {
    $reviews = [];
    
    // 尝试查找评论表 (使用 PDO 方式检查表存在)
    $tableNames = ['product_reviews', 'reviews', 'product_ratings'];
    $foundTable = null;
    $fields = ['rating', 'comment', 'reviewer_name', 'created_at', 'product_id']; 
    $orderField = 'created_at'; // 假设最新的评论日期字段
    
    foreach ($tableNames as $tableName) {
        try {
            // 尝试执行一个简单查询来检查表是否存在
            $pdo->query("SELECT * FROM $tableName LIMIT 1"); 
            $foundTable = $tableName;
            break;
        } catch (\PDOException $e) {
            continue;
        }
    }
    
    if ($foundTable) {
        // 假设表里有这些字段，并尝试查询
        $sql = "SELECT product_id, 
                       COALESCE(rating, 5) as rating, 
                       COALESCE(comment, review_text, message) as comment,
                       COALESCE(reviewer_name, user_name, 'Anonymous') as reviewer,
                       COALESCE(created_at, review_date) as date
                FROM $foundTable
                ORDER BY $orderField DESC
                LIMIT :limit";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $row) {
                $reviews[] = [
                    'rating' => (int)($row['rating'] ?? 5),
                    'comment' => $row['comment'] ?? '',
                    'reviewer' => $row['reviewer'] ?? 'Anonymous',
                    'date' => $row['date'] ?? date('Y-m-d')
                ];
            }
        } catch (PDOException $e) {
            // 查询失败时返回空数组
        }
    }
    
    return $reviews;
}

/**
 * Load hero slider products from database
 * @param PDO $pdo 数据库连接对象
 */
function loadHeroSlides(PDO $pdo, int $limit = 5): array {
    $slides = [];
    $sql = "SELECT p.product_id, p.name, p.description, p.image, p.price,
                   c.name AS category_name
            FROM products p
            LEFT JOIN product_categories c ON p.category_id = c.category_id
            WHERE p.image IS NOT NULL AND p.image != '' 
            ORDER BY p.product_id DESC 
            LIMIT :limit";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            if (!empty($row['image'])) {
                $slides[] = [
                    'product_id' => $row['product_id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    // 确保使用 productImageUrl 函数处理路径
                    'image' => productImageUrl($row['image']), 
                    'price' => $row['price'],
                    'category_name' => $row['category_name']
                ];
            }
        }
    } catch (PDOException $e) {
        // 数据库查询失败
    }
    
    // If no slides from database, use default
    if (empty($slides)) {
        $slides[] = [
            'product_id' => 0,
            'name' => 'Premium Pet Products',
            'description' => 'Discover our carefully crafted pet food made with natural ingredients and love',
            'image' => '../images/dog.jpg',
            'price' => 0,
            'category_name' => 'Featured'
        ];
    }
    
    return $slides;
}

// =========================================================
// 最终执行逻辑：所有函数调用都使用 $pdo 对象
// =========================================================

// Fetch data
$categories = loadCategoriesWithImages($pdo);
$topProducts = loadTopRatedProducts($pdo, 4);

// If no top products, get latest products from database (Fallback)
if (empty($topProducts)) {
    $fallbackSql = "SELECT p.product_id, p.name, p.description, p.price, p.image,
                           c.name AS category_name
                    FROM products p
                    LEFT JOIN product_categories c ON p.category_id = c.category_id
                    WHERE p.image IS NOT NULL AND p.image != ''
                    ORDER BY p.product_id DESC
                    LIMIT 4";
    try {
        $stmt_fallback = $pdo->query($fallbackSql);
        $topProducts = $stmt_fallback->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $topProducts = [];
    }
}

$reviews = loadLatestReviews($pdo, 3);
$heroSlides = loadHeroSlides($pdo, 5);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetBuddy - Premium Pet Products</title>
    <link rel="stylesheet" href="../css/product_page.css">
</head>
<body>
<?php include_once '../include/header.php'; ?>

    <section class="hp-hero">
        <div class="hp-slides">
            <?php foreach ($heroSlides as $index => $slide): ?>
                <div class="hp-slide">
                    <img src="<?php echo htmlspecialchars($slide['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                         alt="<?php echo htmlspecialchars($slide['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                         class="hp-slide-img">
                    <div class="hp-hero-content">
                        <div class="hp-hero-text">
                            <h1><?php echo htmlspecialchars($slide['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                            <p><?php echo htmlspecialchars(truncateText($slide['description'], 100), ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="hp-hero-buttons">
                                <?php if ($slide['product_id'] > 0): ?>
                                    <a href="product_detail.php?id=<?php echo (int)$slide['product_id']; ?>" class="hp-btn">Shop Now</a>
                                <?php else: ?>
                                    <a href="product_listing.php" class="hp-btn">Shop Now</a>
                                <?php endif; ?>
                                <a href="#story" class="hp-btn hp-btn-outline">Our Story</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="hp-slider-indicators">
            <?php foreach ($heroSlides as $index => $slide): ?>
                <div class="hp-indicator <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></div>
            <?php endforeach; ?>
        </div>
        
    </section>

    <section class="hp-trust-badges">
        <div class="hp-container">
            <div class="hp-badges-container">
                <div class="hp-badge">
                    <img src="../images/planet-earth.png" alt="Grain-Free & Natural" class="hp-badge-icon">
                    <span>Grain-Free & Natural</span>
                </div>
                <div class="badge">
                    <img src="../images/veterinary.png" alt="Vet Recommended" class="hp-badge-icon">
                    <span>Vet Recommended</span>
                </div>
                <div class="badge">
                    <img src="../images/fast-delivery.png" alt="Regular Delivery" class="hp-badge-icon">
                    <span>Regular Delivery</span>
                </div>
                <div class="badge">
                    <img src="../images/love.png" alt="Satisfaction Guaranteed" class="hp-badge-icon">
                    <span>Satisfaction Guaranteed</span>
                </div>
            </div>
        </div>
    </section>

    <section class="hp-categories">
        <div class="hp-container">
            <h2 class="hp-section-title">Choose for Your Beloved Pet</h2>
            <div class="hp-categories-grid">
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <div class="hp-category-card" onclick="window.location.href='product_listing.php?category=<?php echo (int)$category['category_id']; ?>'">
                            <?php 
                            $catImage = $category['category_image'] ?? '';
                            $catImageUrl = '../images/dog.jpg'; // Default Fallback

                            if (!empty($catImage)) {
                                $catImageUrl = productImageUrl($catImage);
                            } else {
                                // Fallback logic using PDO (Simplified from original MySQLi logic)
                                $fallbackSql = "SELECT image FROM products 
                                               WHERE category_id = :cat_id
                                               AND image IS NOT NULL AND image != '' 
                                               ORDER BY product_id DESC 
                                               LIMIT 1";
                                try {
                                    $stmt = $pdo->prepare($fallbackSql);
                                    $stmt->bindValue(':cat_id', $category['category_id'], PDO::PARAM_INT);
                                    $stmt->execute();
                                    $fallbackRow = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if ($fallbackRow && !empty($fallbackRow['image'])) {
                                        $catImageUrl = productImageUrl($fallbackRow['image']);
                                    }
                                } catch (PDOException $e) {
                                    // Keep default image
                                }
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($catImageUrl, ENT_QUOTES, 'UTF-8'); ?>" 
                                 alt="<?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                 onerror="this.onerror=null; this.src='../images/dog1.jpg';">
                            <div class="hp-category-content">
                                <h3><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="hp-category-card">
                        <img src="../images/dog2.jpg" alt="Dog Supplies">
                        <div class="category-content">
                            <h3>Dog Supplies</h3>
                        </div>
                    </div>
                    <div class="hp-category-card">
                        <img src="../images/cat2 .jpg" alt="Cat Supplies">
                        <div class="category-content">
                            <h3>Cat Supplies</h3>
                        </div>
                    </div>

  
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="hp-products">
        <div class="hp-container">
            <div class="hp-section-header">
                <h2 class="hp-section-title">Pet Favorites</h2>
                <a href="product_listing.php" class="hp-view-all">View All →</a>
            </div>
            <div class="hp-products-grid">
                <?php if (!empty($topProducts)): ?>
                    <?php foreach ($topProducts as $product): ?>
                        <?php 
                        $productImage = $product['image'] ?? '';
                        $imageUrl = productImageUrl($productImage);
                        ?>
                        <div class="hp-product-card">
                            <a class="hp-product-image-link" href="product_detail.php?id=<?php echo (int)$product['product_id']; ?>">
                                <div class="hp-product-image">
                                    <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                         onerror="this.onerror=null; this.src='../images/dog1.jpg';">
                                    <span class="hp-product-tag">Hot</span>
                                </div>
                            </a>
                            <div class="hp-product-info">
                                <a href="product_detail.php?id=<?php echo (int)$product['product_id']; ?>">
                                    <h3 class="hp-product-title"><?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p class="hp-product-features"><?php echo htmlspecialchars($product['category_name'] ?: 'Premium Quality', ENT_QUOTES, 'UTF-8'); ?></p>
                                </a>
                                <div class="hp-product-price">
                                    <span class="hp-price"><?php echo htmlspecialchars(formatPrice((float)$product['price']), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <button class="hp-add-to-cart-btn" data-product-id="<?php echo (int)$product['product_id']; ?>" data-product-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <span class="cart-icon"><img src="../images/add-to-cart.png" alt="Add to Cart" style="width: 18px; height: 18px; vertical-align: middle;"></span>
                                        <span class="cart-text">Add to Cart</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="product-card">
                        <div class="hp-product-image">
                            <img src="../images/dog.jpg" alt="No Products Available">
                        </div>
                        <div class="product-info">
                            <h3 class="product-title">No Products Available</h3>
                            <p class="product-features">Please check back later</p>
                            <div class="product-price">MYR 0.00</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="hp-brand-story" id="story">
        <div class="container">
            <div class="hp-story-grid">
                <div class="hp-story-image">
                    <img src="../images/cat2.jpg" alt="Our Story">
                </div>
                <div class="hp-story-content">
                    <h3>Our Commitment</h3>
                    <h2>We Only Provide Food We Would Feed Our Own Pets</h2>
                    <p>At PetBuddy, we believe that every pet deserves the best nutrition. From the source of ingredients, we strictly select every piece of meat and vegetable, rejecting any unnecessary additives, just to let your beloved pets eat healthy and happy. Every batch of products undergoes strict quality testing to ensure safety and peace of mind. Our mission is to provide premium pet food that meets the highest standards of quality and nutrition.</p>
                    <a href="about.php" class="hp-read-more">Read More →</a>
                </div>
            </div>
        </div>
    </section>

    <section class="hp-testimonials">
        <div class="container">
            <h2 class="hp-section-title">What Pet Owners Say</h2>
            <div class="hp-testimonials-grid">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="hp-testimonial-card">
                            <div class="hp-stars">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <span class="hp-star-icon"><?php echo $i < $review['rating'] ? '★' : '☆'; ?></span>
                                <?php endfor; ?>
                            </div>
                            <p class="hp-testimonial-text">"<?php echo htmlspecialchars(truncateText($review['comment'], 100), ENT_QUOTES, 'UTF-8'); ?>"</p>
                            <div class="hp-testimonial-author">
                                <div class="hp-author-avatar"><?php echo strtoupper(substr($review['reviewer'], 0, 1)); ?></div>
                                <div>
                                    <strong><?php echo htmlspecialchars($review['reviewer'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div>
                                        <?php echo formatReviewDate($review['date'] ?? ''); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="testimonial-card">
                        <div class="stars">
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                        </div>
                        <p class="testimonial-text">"My picky eater CoCo finally loves to eat! His digestion has improved so much, I'm really surprised!"</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">C</div>
                            <div>
                                <strong>CoCo's Mom</strong>
                                <div>Golden Retriever</div>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <div class="stars">
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                        </div>
                        <p class="testimonial-text">"Since switching to PetBuddy, Mimi's coat has become super smooth, friends all ask me how I take care of her!"</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">M</div>
                            <div>
                                <strong>Mimi's Dad</strong>
                                <div>Ragdoll Cat</div>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card">
                        <div class="stars">
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                            <span class="hp-star-icon">★</span>
                        </div>
                        <p class="testimonial-text">"The regular delivery service is so convenient, I never have to worry about forgetting to buy food, and the price is very reasonable!"</p>
                        <div class="testimonial-author">
                            <div class="author-avatar">D</div>
                            <div>
                                <strong>Doudou's Owner</strong>
                                <div>Corgi</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="hp-newsletter">
        <div class="container">
            <h2>Start Your Pet's Health Journey Together</h2>
            <p>Subscribe to our newsletter for exclusive offers and scientific pet care guides.</p>
            <form class="hp-newsletter-form" id="newsletter-form">
                <input type="email" class="hp-newsletter-input" id="newsletter-email" placeholder="Enter your email address" required>
                <button type="submit" class="btn-primary">Subscribe</button>
            </form>
        </div>
    </section>

<?php include_once '../include/footer.php'; ?>
<?php include '../include/chat_widget.php'; ?>

    <script>
        // Modern Homepage Slider Class - Updated with new design logic
        class HomepageSlider {
            constructor() {
                this.slides = document.querySelector('.hp-slides');
                this.indicators = document.querySelectorAll('.hp-indicator');
                this.prevBtn = document.querySelector('.prev-btn');
                this.nextBtn = document.querySelector('.next-btn');
                this.totalSlides = document.querySelectorAll('.hp-slide').length;
                this.currentSlide = 0;
                this.slideInterval = null;
                this.isAnimating = false;
                
                this.init();
            }
            
            init() {
                if (this.totalSlides > 1) {
                    this.startAutoplay();
                    this.bindEvents();
                    
                    // Pause autoplay on hover
                    const hero = document.querySelector('.hp-hero');
                    if (hero) {
                        hero.addEventListener('mouseenter', () => {
                            clearInterval(this.slideInterval);
                        });
                        
                        hero.addEventListener('mouseleave', () => {
                            this.startAutoplay();
                        });
                    }
                }
            }
            
            bindEvents() {
                if (this.prevBtn) {
                    this.prevBtn.addEventListener('click', () => this.prevSlide());
                }
                if (this.nextBtn) {
                    this.nextBtn.addEventListener('click', () => this.nextSlide());
                }
                
                this.indicators.forEach(indicator => {
                    indicator.addEventListener('click', (e) => {
                        const slideIndex = parseInt(e.target.getAttribute('data-slide'));
                        this.goToSlide(slideIndex);
                    });
                });
                
                // Touch swipe support
                let touchStartX = 0;
                if (this.slides) {
                    this.slides.addEventListener('touchstart', (e) => {
                        touchStartX = e.touches[0].clientX;
                    });
                    
                    this.slides.addEventListener('touchend', (e) => {
                        const touchEndX = e.changedTouches[0].clientX;
                        const diff = touchStartX - touchEndX;
                        
                        if (Math.abs(diff) > 50) {
                            if (diff > 0) this.nextSlide();
                            else this.prevSlide();
                        }
                    });
                }
            }
            
            nextSlide() {
                if (this.isAnimating) return;
                this.currentSlide = (this.currentSlide + 1) % this.totalSlides;
                this.updateSlider();
            }
            
            prevSlide() {
                if (this.isAnimating) return;
                this.currentSlide = (this.currentSlide - 1 + this.totalSlides) % this.totalSlides;
                this.updateSlider();
            }
            
            goToSlide(slideIndex) {
                if (this.isAnimating) return;
                this.currentSlide = slideIndex;
                this.updateSlider();
            }
            
            updateSlider() {
                this.isAnimating = true;
                
                if (this.slides) {
                    // Use 20% translation since slides width is 500% (each slide is 20%)
                    this.slides.style.transform = `translateX(-${this.currentSlide * 20}%)`;
                }
                
                // Update indicators
                this.indicators.forEach((indicator, index) => {
                    if (index === this.currentSlide) {
                        indicator.classList.add('active');
                    } else {
                        indicator.classList.remove('active');
                    }
                });
                
                // Restart autoplay
                this.restartAutoplay();
            
                setTimeout(() => {
                    this.isAnimating = false;
                }, 800);
            }
            
            startAutoplay() {
                this.slideInterval = setInterval(() => this.nextSlide(), 3000);
            }

            restartAutoplay() {
                clearInterval(this.slideInterval);
                this.startAutoplay();
            }
        }

        // Page initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize slider
            new HomepageSlider();

            // Simple scroll animation effect
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Add animation to elements
            const animatedElements = document.querySelectorAll('.product-card, .testimonial-card, .badge');
            animatedElements.forEach(el => {
                el.style.opacity = 0;
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });

            // Add to Cart button functionality
            const addToCartButtons = document.querySelectorAll('.hp-add-to-cart-btn');
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const productId = this.getAttribute('data-product-id');
                    const productName = this.getAttribute('data-product-name');
                    const originalHTML = this.innerHTML;
                    
                    // Visual feedback - show correct icon
                    this.classList.add('added');
                    this.innerHTML = '<span class="cart-icon"><img src="../images/correct.png" alt="Correct" style="width: 18px; height: 18px; vertical-align: middle;"></span><span class="cart-text">Added!</span>';
                    this.style.pointerEvents = 'none';
                    
                    // Show cart notification
                    showCartNotification(productName);
                    
                    // Here you can add actual cart functionality
                    // For example: addToCart(productId);
                    console.log('Added to cart:', productId, productName);
                    
                    // Reset after 2 seconds
                    setTimeout(() => {
                        this.classList.remove('added');
                        this.innerHTML = originalHTML;
                        this.style.pointerEvents = 'auto';
                    }, 2000);
                });
            });

            // Cart Notification Function
            window.showCartNotification = function(productName, quantity = 1) {
                // Remove any existing notification first
                let notification = document.getElementById('cart-notification');
                if (notification) {
                    notification.remove();
                }
                
                // Create new notification element
                notification = document.createElement('div');
                notification.id = 'cart-notification';
                notification.className = 'cart-notification';
                
                // Update notification content with quantity
                const quantityText = quantity > 1 ? ` x${quantity}` : '';
                notification.innerHTML = `
                    <div class="cart-notification-icon">
                        <img src="../image/correct.png" alt="Success" style="width: 24px; height: 24px;">
                    </div>
                    <div class="cart-notification-text">
                        <strong>Added to Cart!</strong><br>
                        <span style="font-size: 0.9em; opacity: 0.9;">${productName || 'Product'}${quantityText}</span>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Force reflow to ensure initial state
                notification.offsetHeight;
                
                // Show notification with a small delay
                setTimeout(() => {
                    notification.classList.add('show');
                }, 10);
                
                // Hide notification after 3 seconds
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 300);
                }, 3000);
            };

            // Newsletter form submission
            const newsletterForm = document.getElementById('newsletter-form');
            const newsletterEmail = document.getElementById('newsletter-email');
            if (newsletterForm && newsletterEmail) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const email = newsletterEmail.value.trim();
                    if (email) {
                        // Show success message
                        alert('Thank you for subscribing!');
                        
                        // Clear the email input
                        newsletterEmail.value = '';
                        
                        // Here you can add actual newsletter subscription functionality
                        console.log('Subscribed email:', email);
                    }
                });
            }
        });
    </script>

</body>
</html>