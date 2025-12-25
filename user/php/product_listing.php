<?php
session_start();
require_once '../include/db.php';
require_once '../include/product_utils.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 30;
$offset = ($page - 1) * $limit;

$categoryId    = isset($_GET['category']) ? max(0, (int)$_GET['category']) : 0;
$subCategoryId = isset($_GET['sub_category']) ? max(0, (int)$_GET['sub_category']) : 0;
$searchTerm    = trim($_GET['search'] ?? '');
$minPrice      = isset($_GET['min_price']) ? max(0, (float)$_GET['min_price']) : 0;
$maxPrice      = isset($_GET['max_price']) ? max(0, (float)$_GET['max_price']) : 0;
$sortBy        = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$inStockOnly   = isset($_GET['in_stock']) && $_GET['in_stock'] == '1' ? true : false;

$wishlistIds = [];
if (isset($_SESSION['member_id'])) {
    try {
        $stmtW = $pdo->prepare("SELECT product_id FROM wishlist WHERE member_id = ?");
        $stmtW->execute([$_SESSION['member_id']]);
        $wishlistIds = $stmtW->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
    }
}

$products = [];
$totalRows = 0;
$totalPages = 0;
$pageTitle = 'All Products';

try {
    $whereSQL = "WHERE 1=1";
    $params = [];

    if ($searchTerm !== '') {
        $whereSQL .= " AND (p.name LIKE ? OR c.name LIKE ?)";
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $pageTitle = 'Search: "' . htmlspecialchars($searchTerm) . '"';
    } elseif ($subCategoryId > 0) {
        $whereSQL .= " AND p.sub_category_id = ?";
        $params[] = $subCategoryId;
        $stmtSub = $pdo->prepare("SELECT name FROM sub_categories WHERE sub_category_id = ?");
        $stmtSub->execute([$subCategoryId]);
        $subName = $stmtSub->fetchColumn();
        if ($subName) $pageTitle = $subName;
    } elseif ($categoryId > 0) {
        $whereSQL .= " AND p.category_id = ?";
        $params[] = $categoryId;
        $stmtCat = $pdo->prepare("SELECT name FROM product_categories WHERE category_id = ?");
        $stmtCat->execute([$categoryId]);
        $catName = $stmtCat->fetchColumn();
        if ($catName) $pageTitle = $catName;
    }

    if ($minPrice > 0) {
        $whereSQL .= " AND p.price >= ?";
        $params[] = $minPrice;
    }
    if ($maxPrice > 0) {
        $whereSQL .= " AND p.price <= ?";
        $params[] = $maxPrice;
    }
    if ($inStockOnly) {
        $whereSQL .= " AND p.stock_qty > 0";
    }

    $countSql = "SELECT COUNT(*) FROM products p 
                 LEFT JOIN product_categories c ON p.category_id = c.category_id 
                 $whereSQL";
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $totalRows = $stmtCount->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    $orderSQL = "";
    switch ($sortBy) {
        case 'price_low':
            $orderSQL = " ORDER BY p.price ASC";
            break;
        case 'price_high':
            $orderSQL = " ORDER BY p.price DESC";
            break;
        case 'oldest':
            $orderSQL = " ORDER BY p.product_id ASC";
            break;
        case 'name_asc':
            $orderSQL = " ORDER BY p.name ASC";
            break;
        case 'name_desc':
            $orderSQL = " ORDER BY p.name DESC";
            break;
        case 'newest':
        default:
            $orderSQL = " ORDER BY p.product_id DESC";
            break;
    }

    $sql = "SELECT p.product_id, p.category_id, p.sub_category_id, p.name, p.price, p.stock_qty, 
                   p.image, c.name AS category_name
            FROM products p
            LEFT JOIN product_categories c ON p.category_id = c.category_id
            $whereSQL
            $orderSQL
            LIMIT ? OFFSET ?";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key + 1, $val);
    }
    $stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    error_log($e->getMessage());
}

function getQueryString($newPage)
{
    $params = $_GET;
    $params['page'] = $newPage;
    return http_build_query($params);
}
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
            color: white;
            text-decoration: none;
            margin-left: 5px;
            background: #FFB774;
            padding: 2px 8px;
            border-radius: 4px;
            margin: 0 5px;
        }

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

            .filter-group,
            .filter-actions,
            .btn-filter,
            .btn-reset {
                width: 100%;
                flex: 1;
            }
        }


        .products-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            margin-top: 30px;
        }


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

        .p-img-box {
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #f8f8f8, #fff);
            display: block;
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
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
        }

        .p-title:hover {
            color: #FFB774;
        }

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


        .p-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

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

        .btn-add img {
            width: 18px;
            height: 18px;
            object-fit: contain;
            margin-right: 5px;
            filter: brightness(0) invert(1);
        }

        .btn-heart-action {
            text-align: center;
            border: 2px solid #eee;
            padding: 10px;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-heart-action:hover {
            border-color: #FFB774;
            background: #fff9f4;
            transform: translateY(-2px);
        }

        .btn-heart-action img {
            width: 20px;
            height: 20px;
            object-fit: contain;
            transition: all 0.3s ease;
            filter: grayscale(100%) opacity(0.5);
        }

        .btn-heart-action.active {
            border-color: #ff4d4d;
            background: #fff0f0;
        }

        .btn-heart-action.active img {
            filter: grayscale(0%) opacity(1);
            transform: scale(1.1);
        }

        .btn-heart-action:hover img {
            filter: grayscale(0%) opacity(0.8);
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


        /* Toast Styles (for success messages) */
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

        /* Modal Prompt Styles (for login required) */
        #custom-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 10000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        #custom-modal-overlay.show {
            display: flex;
        }

        #custom-modal {
            background: white;
            border-radius: 16px;
            padding: 40px 30px 30px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
            position: relative;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #E8F5E9;
        }

        .modal-icon img {
            width: 40px;
            height: 40px;
        }

        .modal-icon.error {
            background: #FFEBEE;
        }

        .modal-icon.warning {
            background: #FFF3E0;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #2F2F2F;
            margin: 0 0 10px 0;
        }

        .modal-message {
            font-size: 15px;
            color: #666;
            margin: 0 0 25px 0;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .modal-btn {
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 100px;
        }

        .modal-btn-primary {
            background: #FFB774;
            color: white;
        }

        .modal-btn-primary:hover {
            background: #E89C55;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 183, 116, 0.4);
        }

        .modal-btn-secondary {
            background: #f5f5f5;
            color: #666;
        }

        .modal-btn-secondary:hover {
            background: #e8e8e8;
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 40px;
            margin-bottom: 20px;
        }

        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            gap: 8px;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 10px;
            border-radius: 8px;
            background-color: #fff;
            border: 1px solid #e0e0e0;
            color: #555;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .page-link:hover {
            border-color: #FFB774;
            color: #FFB774;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .page-link.active {
            background: linear-gradient(135deg, #FFB774, #E89C55);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 10px rgba(255, 183, 116, 0.4);
        }

        .page-link.disabled {
            background-color: #f9f9f9;
            color: #ccc;
            cursor: not-allowed;
            pointer-events: none;
            border-color: #eee;
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
                    <img src="../images/filter.png" style="width:16px;height:16px;filter:brightness(0) invert(1);" alt=""> Filter
                </button>
            </div>
        </div>

        <div class="filter-section">
            <form id="filterForm" method="GET" action="product_listing.php">
                <?php if ($categoryId > 0): ?> <input type="hidden" name="category" value="<?= $categoryId ?>"> <?php endif; ?>
                <?php if ($subCategoryId > 0): ?> <input type="hidden" name="sub_category" value="<?= $subCategoryId ?>"> <?php endif; ?>
                <?php if ($searchTerm !== ''): ?> <input type="hidden" name="search" value="<?= htmlspecialchars($searchTerm) ?>"> <?php endif; ?>

                <div class="filter-row">
                    <div class="filter-group">
                        <label>Price Range (RM)</label>
                        <div class="price-inputs">
                            <input type="number" name="min_price" id="min_price" placeholder="Min" value="<?= $minPrice > 0 ? $minPrice : '' ?>" min="0" step="0.01">
                            <span>-</span>
                            <input type="number" name="max_price" id="max_price" placeholder="Max" value="<?= $maxPrice > 0 ? $maxPrice : '' ?>" min="0" step="0.01">
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
                <img src="../images/package.png" style="width:50px; opacity:0.3; margin-bottom:15px;" alt="Empty">
                <p>No products found.</p>
                <a href="product_listing.php" style="color: #FFB774; margin-top:10px; display:inline-block;">View All</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $p): ?>

                    <?php
                    $isWished = in_array($p['product_id'], $wishlistIds);
                    $btnClass = $isWished ? 'active' : '';
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
                                    <img src="../images/cart.png" alt="Cart"> Add
                                </button>

                                <button class="btn-heart-action <?= $btnClass ?>" onclick="toggleWishlist(this, <?= $p['product_id'] ?>)">
                                    <img src="../images/heart.png" alt="Wishlist">
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <ul class="pagination">
                
                <?php if ($page > 1): ?>
                    <li><a href="?<?= getQueryString($page - 1) ?>" class="page-link">< Prev</a></li>
                <?php else: ?>
                    <li><span class="page-link disabled">< Prev</span></li>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                if ($start > 1) { 
                    echo '<li><a href="?'.getQueryString(1).'" class="page-link">1</a></li>';
                    if ($start > 2) echo '<li><span class="page-link disabled">...</span></li>';
                }

                for ($i = $start; $i <= $end; $i++): 
                    $activeClass = ($i == $page) ? 'active' : '';
                ?>
                    <li><a href="?<?= getQueryString($i) ?>" class="page-link <?= $activeClass ?>"><?= $i ?></a></li>
                <?php endfor; ?>

                <?php 
                if ($end < $totalPages) {
                    if ($end < $totalPages - 1) echo '<li><span class="page-link disabled">...</span></li>';
                    echo '<li><a href="?'.getQueryString($totalPages).'" class="page-link">'.$totalPages.'</a></li>';
                }
                ?>

                <?php if ($page < $totalPages): ?>
                    <li><a href="?<?= getQueryString($page + 1) ?>" class="page-link">Next ></a></li>
                <?php else: ?>
                    <li><span class="page-link disabled">Next ></span></li>
                <?php endif; ?>

            </ul>
        </div>
        <?php endif; ?>

    </div>

    <!-- Toast Notification -->
    <div id="custom-toast">
        <img src="../images/success.png" alt="" class="toast-icon">
        <span id="custom-toast-msg">Added to cart!</span>
    </div>

    <!-- Modal Prompt (for login required) -->
    <div id="custom-modal-overlay">
        <div id="custom-modal">
            <div class="modal-icon" id="modal-icon">
                <img src="../images/success.png" alt="">
            </div>
            <h3 class="modal-title" id="modal-title">Login Required</h3>
            <p class="modal-message" id="modal-message">Please login first to add items to cart.</p>
            <div class="modal-buttons" id="modal-buttons">
                <button class="modal-btn modal-btn-primary" id="modal-ok-btn">Go to Login</button>
                <button class="modal-btn modal-btn-secondary" id="modal-cancel-btn">Cancel</button>
            </div>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>
    <?php include '../include/chat_widget.php'; ?>

    <script>
        function safeToast(message, showLoginBtn = false) {
            // If login required, show modal
            if (showLoginBtn) {
                const overlay = document.getElementById('custom-modal-overlay');
                const modal = document.getElementById('custom-modal');
                const iconDiv = document.getElementById('modal-icon');
                const iconImg = iconDiv.querySelector('img');
                const titleEl = document.getElementById('modal-title');
                const messageEl = document.getElementById('modal-message');
                const buttonsDiv = document.getElementById('modal-buttons');

                titleEl.textContent = 'Login Required';
                messageEl.textContent = message;
                iconDiv.className = 'modal-icon';
                iconImg.src = '../images/success.png';

                buttonsDiv.innerHTML = '';
                const loginBtn = document.createElement('button');
                loginBtn.className = 'modal-btn modal-btn-primary';
                loginBtn.textContent = 'Go to Login';
                loginBtn.onclick = function() {
                    overlay.classList.remove('show');
                    window.location.href = 'login.php';
                };
                buttonsDiv.appendChild(loginBtn);

                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'modal-btn modal-btn-secondary';
                cancelBtn.textContent = 'Cancel';
                cancelBtn.onclick = function() {
                    overlay.classList.remove('show');
                };
                buttonsDiv.appendChild(cancelBtn);

                overlay.classList.add('show');
                return;
            }

            // Otherwise, show toast (bottom notification)
            const toast = document.getElementById('custom-toast');
            const msgSpan = document.getElementById('custom-toast-msg');
            const img = toast.querySelector('img');

            msgSpan.innerText = message;

            if (message.toLowerCase().includes("remove") || message.toLowerCase().includes("delete")) {
                img.src = '../images/dusbin.png';
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


        // Close modal when clicking overlay
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('custom-modal-overlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.classList.remove('show');
                    }
                });
            }
        });

        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('show_filter') === '1') {
                $('.filter-section').addClass('show');
                $('#toggleFilterBtn').addClass('active');
            }

            $('#toggleFilterBtn').on('click', function() {
                $('.filter-section').toggleClass('show');
                $(this).toggleClass('active');
            });

            $('#resetFilterBtn').on('click', function(e) {
                e.preventDefault();
                let resetUrl = $(this).attr('href');
                resetUrl += (resetUrl.indexOf('?') > -1 ? '&' : '?') + 'show_filter=1';
                window.location.href = resetUrl;
            });

            $('#filterForm').on('submit', function(e) {
                if ($('.filter-section').hasClass('show')) {
                    if ($('#show_filter_input').length === 0) {
                        $(this).append('<input type="hidden" name="show_filter" id="show_filter_input" value="1">');
                    }
                }
            });

            $('#sort').on('change', function() {
                if ($('.filter-section').hasClass('show')) {
                    if ($('#show_filter_input').length === 0) {
                        $('#filterForm').append('<input type="hidden" name="show_filter" id="show_filter_input" value="1">');
                    }
                }
                $('#filterForm').submit();
            });

            $('input[name="in_stock"]').on('change', function() {
                if ($('.filter-section').hasClass('show')) {
                    if ($('#show_filter_input').length === 0) {
                        $('#filterForm').append('<input type="hidden" name="show_filter" id="show_filter_input" value="1">');
                    }
                }
                $('#filterForm').submit();
            });
        });


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
                        safeToast("Please login first to add items to cart.", true);
                    } else if (res === "added" || res === "quantity increased") {
                        safeToast("Added to Cart!");
                        refreshCartSidebar();
                        setTimeout(() => {
                            if (typeof openCart === 'function') openCart();
                        }, 500);
                    } else {
                        safeToast("Failed to add item. Out of stock?");
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).css('opacity', '1');
                }
            });
        }


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


        function toggleWishlist(btn, pid) {
            let $btn = $(btn);
            let $img = $btn.find("img");

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
                        safeToast("Please login to save items to wishlist.", true);
                    } else if (res.status === 'added') {
                        $btn.addClass('active');

                        safeToast("Saved to Wishlist!");
                    } else if (res.status === 'removed') {
                        $btn.removeClass('active');

                        safeToast("Removed from Wishlist!");
                    }
                }
            });
        }
    </script>
</body>

</html>