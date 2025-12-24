<?php
// ✨ 1. 必须放在第一行
session_start();

require_once '../include/db.php';
require_once '../include/product_utils.php';

// === 2. 获取筛选参数 ===
$categoryId    = isset($_GET['category']) ? max(0, (int)$_GET['category']) : 0;
$subCategoryId = isset($_GET['sub_category']) ? max(0, (int)$_GET['sub_category']) : 0;
$searchTerm    = trim($_GET['search'] ?? '');

// === 筛选器参数 ===
$minPrice = isset($_GET['min_price']) ? max(0, (float)$_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? max(0, (float)$_GET['max_price']) : 0;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest'; // newest, oldest, price_low, price_high, name_asc, name_desc
$inStockOnly = isset($_GET['in_stock']) && $_GET['in_stock'] == '1' ? true : false;

// === ✨ 3. (关键步骤) 获取当前用户已收藏的商品 ID ===
$wishlistIds = [];
if (isset($_SESSION['member_id'])) {
    try {
        // 只查 ID，结果会是像 [14, 25, 30] 这样的数组
        $stmtW = $pdo->prepare("SELECT product_id FROM wishlist WHERE member_id = ?");
        $stmtW->execute([$_SESSION['member_id']]);
        $wishlistIds = $stmtW->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        // 忽略错误
    }
}

// === 4. 数据库查询商品 ===
$products = [];
$pageTitle = 'All Products';

try {
    $sql = "SELECT p.product_id, p.category_id, p.sub_category_id, p.name, p.price, p.stock_qty, 
                   p.image, c.name AS category_name
            FROM products p
            LEFT JOIN product_categories c ON p.category_id = c.category_id
            WHERE 1=1";

    $params = [];

    // 筛选逻辑
    if ($searchTerm !== '') {
        $sql .= " AND (p.name LIKE ? OR c.name LIKE ?)";
        $like = '%' . $searchTerm . '%';
        $params[] = $like;
        $params[] = $like;
        $pageTitle = 'Search: "' . htmlspecialchars($searchTerm) . '"';
    } elseif ($subCategoryId > 0) {
        $sql .= " AND p.sub_category_id = ?";
        $params[] = $subCategoryId;
        // 获取子分类标题
        $stmtSub = $pdo->prepare("SELECT name FROM sub_categories WHERE sub_category_id = ?");
        $stmtSub->execute([$subCategoryId]);
        $subName = $stmtSub->fetchColumn();
        if ($subName) $pageTitle = $subName;
    } elseif ($categoryId > 0) {
        $sql .= " AND p.category_id = ?";
        $params[] = $categoryId;
        // 获取主分类标题
        $stmtCat = $pdo->prepare("SELECT name FROM product_categories WHERE category_id = ?");
        $stmtCat->execute([$categoryId]);
        $catName = $stmtCat->fetchColumn();
        if ($catName) $pageTitle = $catName;
    }

    // 价格范围筛选
    if ($minPrice > 0) {
        $sql .= " AND p.price >= ?";
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $sql .= " AND p.price <= ?";
        $params[] = $maxPrice;
    }

    // 库存筛选
    if ($inStockOnly) {
        $sql .= " AND p.stock_qty > 0";
    }

    // 排序
    switch ($sortBy) {
        case 'price_low':
            $sql .= " ORDER BY p.price ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY p.price DESC";
            break;
        case 'oldest':
            $sql .= " ORDER BY p.product_id ASC";
            break;
        case 'name_asc':
            $sql .= " ORDER BY p.name ASC";
            break;
        case 'name_desc':
            $sql .= " ORDER BY p.name DESC";
            break;
        case 'newest':
        default:
            $sql .= " ORDER BY p.product_id DESC";
            break;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}

// 检查是否有筛选条件
$hasFilters = ($minPrice > 0 || $maxPrice > 0 || $sortBy != 'newest' || $inStockOnly);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle); ?> - PetBuddy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <link rel="stylesheet" href="../css/style.css">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <style>
        /* === 页面容器 === */
        .page-container {
            max-width: 1300px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: 80vh;
            animation: fadeIn 0.5s ease;
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

        /* === 头部样式 === */
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f5f5f5;
            padding-bottom: 15px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #222;
            margin: 0;
        }

        .page-subtitle {
            color: #666;
            font-size: 15px;
            margin-top: 5px;
        }

        .search-feedback {
            margin-top: 15px;
            color: #666;
            font-size: 14px;
        }

        .search-chip {
            background: #FFB774;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            margin: 0 5px;
        }

        .clear-search {
            color: #ff4d4d;
            text-decoration: underline;
            margin-left: 5px;
        }

        /* === Filter Toggle Button === */
        .btn-filter-toggle {
            background: linear-gradient(135deg, #FFB774, #E89C55);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(255, 183, 116, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-filter-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 183, 116, 0.4);
        }

        .btn-filter-toggle.active {
            background: linear-gradient(135deg, #E89C55, #FFB774);
        }

        /* === 筛选器样式 === */
        .filter-section {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
            display: none;
            animation: slideDown 0.3s ease;
        }

        .filter-section.show {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .filter-row {
            display: flex;
            align-items: flex-end;
            gap: 30px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 0 1 auto;
            min-width: 150px;
        }

        .filter-group:first-child {
            flex: 1 1 200px;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 0;
        }

        .filter-group input[type="number"],
        .filter-group select {
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #fff;
        }

        .filter-group input[type="number"]:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #FFB774;
            box-shadow: 0 0 0 3px rgba(255, 183, 116, 0.1);
        }

        .price-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .price-inputs input {
            flex: 1;
        }

        .price-inputs span {
            color: #999;
            font-weight: 500;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 0;
        }

        .checkbox-label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #FFB774;
        }

        .checkbox-label span {
            font-size: 14px;
            color: #555;
            user-select: none;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .btn-filter {
            background: linear-gradient(135deg, #FFB774, #E89C55);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(255, 183, 116, 0.3);
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 183, 116, 0.4);
        }

        .btn-reset {
            background: #f5f5f5;
            color: #666;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: 2px solid #e0e0e0;
        }

        .btn-reset:hover {
            background: #e8e8e8;
            border-color: #d0d0d0;
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }

            .filter-actions {
                width: 100%;
            }

            .btn-filter,
            .btn-reset {
                flex: 1;
            }
        }

        /* === 网格布局 (一行5个) === */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        /* === 卡片设计 === */
        .p-card {
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

        .p-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            border-color: #FFB774;
        }

        /* 图片区域 */
        .p-img-box {
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #f8f8f8, #fff);
            display: block;
        }

        .p-img-box::before {
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

        .p-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .p-card:hover .p-img-box img {
            transform: scale(1.08);
        }

        /* Badge */
        .p-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            color: #555;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* 内容 */
        .p-info {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .p-title {
            font-size: 15px;
            font-weight: 600;
            color: #222;
            margin-bottom: 8px;
            line-height: 1.4;
            text-decoration: none;

            /* 👇 标准单行省略写法 (无黄线) 👇 */
            display: -webkit-box;
            -webkit-box-orient: vertical;
            line-clamp: 2;
            /* 去掉 -webkit-，尝试使用标准属性 */
            -webkit-line-clamp: 2;
            /* 为了兼容性，最好还是保留这一行 */
            overflow: hidden;
        }

        .p-title:hover {
            color: #FFB774;
        }

        /* 价格 */
        .p-price-row {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            margin-bottom: 15px;
            margin-top: auto;
        }

        .currency {
            font-size: 12px;
            color: #999;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .amount {
            font-size: 20px;
            color: #FFB774;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        /* 按钮组 */
        .p-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        /* 黑色 Add 按钮 */
        .btn-add {
            background: linear-gradient(135deg, #2F2F2F, #1a1a1a);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            padding: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #000, #2F2F2F);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        .btn-add:active {
            transform: translateY(0);
        }

        /* ✨ 爱心按钮样式 ✨ */
        .btn-heart-action {
            text-align: center;
            border: 2px solid #eee;
            padding: 10px;
            border-radius: 8px;
            background: #fff;
            color: #ccc;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-heart-action:hover {
            border-color: #FFB774;
            color: #FFB774;
            background: #fff9f4;
            transform: translateY(-2px);
        }

        /* ✨ 激活状态 (亮起) ✨ */
        .btn-heart-action.active {
            border-color: #ff4d4d;
            color: #ff4d4d;
            background: #fff0f0;
        }

        .btn-heart-action.animating {
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

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 60px;
            color: #999;
        }

        /* 响应式 */
        @media (max-width: 1300px) {
            .products-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 992px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    <?php include '../include/header.php'; ?>

    <div class="page-container">

        <div class="page-header">
            <div>
                <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p class="page-subtitle">Find the perfect products for your pets.</p>
            </div>

            <div style="display: flex; align-items: center; gap: 15px;">
                <?php if ($searchTerm !== ''): ?>
                    <div class="search-feedback">
                        Results for <span class="search-chip">"<?= htmlspecialchars($searchTerm) ?>"</span>
                        <a class="clear-search" href="product_listing.php">Clear</a>
                    </div>
                <?php endif; ?>
                <button id="toggleFilterBtn" class="btn-filter-toggle">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form id="filterForm" method="GET" action="product_listing.php">
                <!-- 保留原有的筛选参数 -->
                <?php if ($categoryId > 0): ?>
                    <input type="hidden" name="category" value="<?= $categoryId ?>">
                <?php endif; ?>
                <?php if ($subCategoryId > 0): ?>
                    <input type="hidden" name="sub_category" value="<?= $subCategoryId ?>">
                <?php endif; ?>
                <?php if ($searchTerm !== ''): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>">
                <?php endif; ?>

                <div class="filter-row">
                    <div class="filter-group">
                        <label>Price Range (RM)</label>
                        <div class="price-inputs">
                            <input type="number" name="min_price" id="min_price" placeholder="Min" 
                                   value="<?= $minPrice > 0 ? $minPrice : '' ?>" min="0" step="0.01">
                            <span>-</span>
                            <input type="number" name="max_price" id="max_price" placeholder="Max" 
                                   value="<?= $maxPrice > 0 ? $maxPrice : '' ?>" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label>Sort By</label>
                        <select name="sort" id="sort">
                            <option value="newest" <?= $sortBy == 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sortBy == 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="price_low" <?= $sortBy == 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_high" <?= $sortBy == 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="name_asc" <?= $sortBy == 'name_asc' ? 'selected' : '' ?>>Name: A to Z</option>
                            <option value="name_desc" <?= $sortBy == 'name_desc' ? 'selected' : '' ?>>Name: Z to A</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="in_stock" value="1" <?= $inStockOnly ? 'checked' : '' ?>>
                            <span>In Stock Only</span>
                        </label>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">Apply Filters</button>
                        <?php
                        // 构建Reset URL，只保留category、sub_category和search参数
                        $resetParams = [];
                        if ($categoryId > 0) $resetParams[] = 'category=' . $categoryId;
                        if ($subCategoryId > 0) $resetParams[] = 'sub_category=' . $subCategoryId;
                        if ($searchTerm !== '') $resetParams[] = 'search=' . urlencode($searchTerm);
                        $resetUrl = 'product_listing.php' . (!empty($resetParams) ? '?' . implode('&', $resetParams) : '');
                        ?>
                        <a href="<?= $resetUrl ?>" class="btn-reset" id="resetFilterBtn">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open" style="font-size:40px; margin-bottom:15px; opacity:0.3;"></i>
                <p>No products found.</p>
                <a href="product_listing.php" style="color: #FFB774; margin-top:10px; display:inline-block;">View All</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $p): ?>

                    <?php
                    // 如果当前 ID 在已收藏数组里
                    $isWished = in_array($p['product_id'], $wishlistIds);

                    // 如果已收藏：按钮加 .active 类，图标是实心 fa-solid
                    // 如果未收藏：按钮无类，图标是空心 fa-regular
                    $btnClass = $isWished ? 'active' : '';
                    $iconClass = $isWished ? 'fa-solid' : 'fa-regular';
                    ?>

                    <div class="p-card">
                        <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="p-img-box">
                            <img src="<?= productImageUrl($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            <div class="p-badge"><?= htmlspecialchars($p['category_name'] ?? 'Pet Item') ?></div>
                        </a>

                        <div class="p-info">
                            <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="p-title">
                                <?= htmlspecialchars($p['name']) ?>
                            </a>

                            <div class="p-price-row">
                                <span class="currency">RM</span>
                                <span class="amount"><?= number_format($p['price'], 2) ?></span>
                            </div>

                            <div class="p-actions">
                                <button class="btn-add" onclick="addToCart(<?= $p['product_id'] ?>)">
                                    <i class="fas fa-shopping-cart"></i> Add
                                </button>

                                <button class="btn-heart-action <?= $btnClass ?>" onclick="toggleWishlist(this, <?= $p['product_id'] ?>)">
                                    <i class="<?= $iconClass ?> fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <?php include '../include/footer.php'; ?>
    <?php include '../include/chat_widget.php'; ?>

    <script>
        // === 0. 筛选器功能 ===
        $(document).ready(function() {
            // 检查URL参数，如果有show_filter=1则显示筛选器
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('show_filter') === '1') {
                $('.filter-section').addClass('show');
                $('#toggleFilterBtn').addClass('active');
            }

            // 切换筛选器显示/隐藏
            $('#toggleFilterBtn').on('click', function() {
                $('.filter-section').toggleClass('show');
                $(this).toggleClass('active');
            });

            // Reset 按钮点击时，在URL中添加show_filter=1参数
            $('#resetFilterBtn').on('click', function(e) {
                e.preventDefault();
                let resetUrl = $(this).attr('href');
                // 如果URL已经有参数，添加&，否则添加?
                resetUrl += (resetUrl.indexOf('?') > -1 ? '&' : '?') + 'show_filter=1';
                window.location.href = resetUrl;
            });

            // 表单提交时，如果筛选器是打开的，添加show_filter=1参数
            $('#filterForm').on('submit', function(e) {
                if ($('.filter-section').hasClass('show')) {
                    // 创建一个隐藏的input来添加show_filter参数
                    if ($('#show_filter_input').length === 0) {
                        $(this).append('<input type="hidden" name="show_filter" id="show_filter_input" value="1">');
                    }
                }
            });

            // 排序选择框改变时自动提交
            $('#sort').on('change', function() {
                if ($('.filter-section').hasClass('show')) {
                    if ($('#show_filter_input').length === 0) {
                        $('#filterForm').append('<input type="hidden" name="show_filter" id="show_filter_input" value="1">');
                    }
                }
                $('#filterForm').submit();
            });

            // 库存复选框改变时自动提交
            $('input[name="in_stock"]').on('change', function() {
                if ($('.filter-section').hasClass('show')) {
                    if ($('#show_filter_input').length === 0) {
                        $('#filterForm').append('<input type="hidden" name="show_filter" id="show_filter_input" value="1">');
                    }
                }
                $('#filterForm').submit();
            });
        });

        // === 1. 加入购物车 (带自动弹出侧边栏) ===
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
                        Swal.fire({
                            title: 'Login Required',
                            text: 'Please login first.',
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'Login',
                            confirmButtonColor: '#2F2F2F'
                        }).then((r) => {
                            if (r.isConfirmed) window.location.href = 'login.php';
                        });
                    } else if (res === "added" || res === "quantity increased") {
                        // A. 弹出成功提示
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

                        // B. 刷新数据
                        refreshCartSidebar();

                        // C. ✨✨✨ 自动弹出侧边栏 (延迟0.3秒) ✨✨✨
                        setTimeout(() => {
                            if (typeof openCart === 'function') openCart();
                        }, 300);
                    } else {
                        Swal.fire('Error', 'Failed to add item.', 'error');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).css('opacity', '1');
                }
            });
        }

        // === 2. 刷新侧边栏函数 ===
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

                    setTimeout(function() {
                        if (typeof updateCartBadge === 'function') updateCartBadge();
                    }, 200);
                }
            });
        }

        // === 3. 收藏功能 (切换状态) ===
        function toggleWishlist(btn, pid) {
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
                        // 变成实心红
                        $btn.addClass('active');
                        $icon.removeClass('fa-regular').addClass('fa-solid');
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else if (res.status === 'removed') {
                        // 变成空心灰
                        $btn.removeClass('active');
                        $icon.removeClass('fa-solid').addClass('fa-regular');
                    }
                }
            });
        }
    </script>

</body>

</html>