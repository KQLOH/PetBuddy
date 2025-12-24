<?php
if (session_status() === PHP_SESSION_NONE) {
    // lifetime => 0 意味着只要浏览器进程完全关闭，Cookie 就失效
    session_set_cookie_params(['lifetime' => 0, 'path' => '/']);
    session_start();
}

// 2. Database Connection
require_once 'db.php';

// 3. Cart Functions (Path Check)
if (file_exists(__DIR__ . "/../php/cart_function.php")) {
    require_once __DIR__ . "/../php/cart_function.php";
} elseif (file_exists("cart_function.php")) {
    require_once "cart_function.php";
} else {
    if (!function_exists('getCartItems')) {
        function getCartItems($p, $m)
        {
            return [];
        }
    }
}

// 4. Include Product Utils (Safe)
if (file_exists(__DIR__ . "/product_utils.php")) {
    require_once __DIR__ . "/product_utils.php";
}

// 5. User State Initialization
$loggedIn = isset($_SESSION['member_id']);
$member_id = $loggedIn ? $_SESSION['member_id'] : null;
$userAvatar = '../images/default-avatar.png'; // 注意路径，Header通常被php文件夹内的文件引用
$total_price = 0;
$cart_item_count = 0;

// 6. Fetch User Avatar
if ($loggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT image FROM members WHERE member_id=?");
        $stmt->execute([$member_id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['image'])) {
            // 这里使用了 product_utils 里的函数，或者直接用路径
            // 假设头像存的是相对路径
            $userAvatar = $row['image'];
        }
    } catch (PDOException $e) { /* Ignore */
    }
}

// 7. ✨✨✨ Fetch Categories & Sub-Categories (新逻辑) ✨✨✨
$categories = [];
$grouped_subs = [];

try {
    // A. 获取主分类
    $stmt_cat = $pdo->prepare("SELECT * FROM product_categories ORDER BY category_id ASC");
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // B. 获取所有子分类
    $stmt_sub = $pdo->prepare("SELECT * FROM sub_categories ORDER BY category_id ASC, name ASC");
    $stmt_sub->execute();
    $all_subs = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

    // C. 整理子分类
    foreach ($all_subs as $sub) {
        $parent_id = $sub['category_id'];
        $grouped_subs[$parent_id][] = $sub;
    }
} catch (PDOException $e) {
    error_log("Category fetch error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PetBuddy Header</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        /* === 1. Variables & Reset === */
        :root {
            --primary-color: #FFB774;
            --primary-dark: #E89C55;
            --text-dark: #2F2F2F;
            --border-color: #e8e8e8;
            --announcement-height: 30px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Inter", system-ui, sans-serif;
        }

        body {
            background: #fff;
            padding-top: calc(var(--announcement-height) + 80px);
        }

        /* === 2. Announcement Bar === */
        .announcement-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: var(--announcement-height);
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            overflow: hidden;
            z-index: 1001;
            display: flex;
            align-items: center;
            font-size: 15px;
            font-weight: 500;
            white-space: nowrap;
        }

        .marquee-content {
            display: inline-block;
            padding-left: 100%;
            animation: marquee-flow 25s linear infinite;
        }

        @keyframes marquee-flow {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(-100%, 0);
            }
        }

        /* === 3. Navigation Bar === */
        .navbar {
            width: 100%;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: var(--announcement-height);
            left: 0;
            z-index: 1000;
        }

        .navbar-inner {
            max-width: 1150px;
            margin: auto;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            text-decoration: none;
        }

        .logo-circle {
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-links {
            display: flex;
            gap: 25px;
        }

        .nav-links a.nav-link {
            font-size: 17px;
            font-weight: 500;
            color: var(--text-dark);
            text-decoration: none;
            transition: 0.2s;
        }

        .nav-links a.nav-link:hover,
        .nav-links a.nav-link.active {
            color: var(--primary-dark);
        }

        .nav-links a.nav-link.active {
            font-weight: 600;
            color: var(--primary-color) !important;
        }

        .nav-icon-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: transparent;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: 0.2s;
            position: relative;
        }

        .nav-icon-btn:hover {
            background: rgba(0, 0, 0, 0.06);
        }

        svg {
            stroke: #444;
            width: 26px;
            height: 26px;
        }

        .custom-icon {
            width: 26px;
            height: 26px;
            object-fit: contain;
        }

        /* === Cart Badge (Notification) === */
        .cart-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background: #ff4d4d;
            color: white;
            border-radius: 10px;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            padding: 0 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }

        .cart-badge.hidden {
            display: none;
        }

        /* === 4. Search Bar Dropdown === */
        .search-container {
            width: 100%;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            max-height: 0;
            overflow: hidden;
            transition: 0.35s ease;
            position: fixed;
            top: calc(var(--announcement-height) + 81px);
            z-index: 999;
        }

        .search-container.active {
            max-height: 170px;
            padding: 18px 0;
        }

        .search-box {
            max-width: 620px;
            margin: auto;
            display: flex;
            border: 1px solid var(--primary-color);
            border-radius: 50px;
            overflow: hidden;
        }

        .search-box input {
            flex: 1;
            padding: 13px 20px;
            border: none;
            outline: none;
            font-size: 16px;
        }

        .search-box button {
            padding: 0 28px;
            border: none;
            background: var(--primary-color);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .search-box button:hover {
            background: var(--primary-dark);
        }

        .search-btn-icon {
            width: 18px;
            height: 18px;
            object-fit: contain;
            filter: invert(100%);
        }

        /* === 5. User Dropdown Menu === */
        .user-menu-dropdown {
            display: none;
            position: absolute;
            top: 45px;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            min-width: 140px;
            z-index: 1000;
            padding: 5px 0;
        }

        .user-menu-dropdown a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 15px;
            transition: background-color 0.15s;
        }

        .user-menu-dropdown a:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }

        /* === 6. Sidebar Common Styles === */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: 0.3s ease;
            z-index: 2000;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .sidebar-panel {
            position: fixed;
            right: -450px;
            top: 0;
            height: 100vh;
            background: #fff;
            box-shadow: -4px 0 12px rgba(0, 0, 0, 0.15);
            transition: 0.35s ease;
            z-index: 2001;
            display: flex;
            flex-direction: column;
        }

        .sidebar-panel.active {
            right: 0;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
            font-weight: 700;
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #666;
        }

        .close-btn:hover {
            color: #000;
        }

        /* === 7. Cart Sidebar Specifics === */
        .cart-sidebar {
            width: 420px;
            right: -450px;
        }

        .cart-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px 30px;
            scroll-behavior: smooth;
        }

        .cart-body::-webkit-scrollbar {
            width: 6px;
        }

        .cart-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .cart-body::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 3px;
        }

        .cart-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid #f5f5f5;
            padding-bottom: 15px;
        }

        .cart-item img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            background: #f9f9f9;
            border: 1px solid #eee;
        }

        .cart-item-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .cart-item-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
            line-height: 1.4;
        }

        .cart-item-price {
            color: var(--primary-dark);
            font-weight: 700;
            font-size: 14px;
        }

        .qty-control-wrapper {
            display: flex;
            align-items: center;
            margin-top: 8px;
            background: #f5f5f5;
            border-radius: 4px;
            width: fit-content;
        }

        .qty-btn {
            border: none;
            background: transparent;
            padding: 2px 8px;
            cursor: pointer;
            font-size: 16px;
            color: #555;
        }

        .qty-display {
            font-size: 13px;
            font-weight: 600;
            padding: 0 5px;
            min-width: 20px;
            text-align: center;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            padding: 5px;
            font-size: 14px;
            align-self: flex-start;
            margin-top: 5px;
        }

        .remove-btn:hover {
            color: #ff4d4d;
        }

        .cart-footer {
            padding: 25px 30px;
            border-top: 1px solid #eee;
            background: #fff;
            flex-shrink: 0;
            z-index: 10;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
        }

        .btn-checkout {
            display: block;
            width: 100%;
            background: var(--primary-color);
            color: white;
            text-align: center;
            padding: 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
            box-shadow: 0 4px 10px rgba(255, 183, 116, 0.3);
        }

        .btn-checkout:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-view-cart {
            display: block;
            width: 100%;
            text-align: center;
            padding: 12px;
            margin-top: 10px;
            color: #888;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-view-cart:hover {
            color: var(--text-dark);
        }

        /* === 8. Z-Index Fixes === */
        .swal2-container,
        .swal2-popup {
            z-index: 9999 !important;
        }

        #toast-container {
            z-index: 9999 !important;
        }

        .modal,
        .alert {
            z-index: 9999 !important;
        }

        @media (max-width: 500px) {
            .sidebar-panel {
                width: 100%;
                right: -100%;
            }
        }

        /* === ✨✨✨ 9. Categories Dropdown & Sub-Menu (Updated) ✨✨✨ === */
        .nav-dropdown-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            height: 100%;
            cursor: pointer;
        }

        .nav-dropdown-wrapper>a {
            display: flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 17px;
            font-weight: 500;
        }

        .nav-dropdown-wrapper:hover>a {
            color: var(--primary-dark);
        }

        /* 主下拉菜单 */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            min-width: 200px;
            padding: 5px 0;
            z-index: 1100;
            border: 1px solid #eee;
            margin-top: 10px;
        }

        .nav-dropdown-wrapper:hover .dropdown-menu {
            display: block;
            animation: fadeIn 0.2s ease-in-out;
        }

        .dropdown-menu::before {
            content: "";
            position: absolute;
            top: -15px;
            left: 0;
            width: 100%;
            height: 20px;
            background: transparent;
        }

        /* 每一个分类项（容器） */
        .dropdown-item-group {
            position: relative;
            /* 为了定位子菜单 */
        }

        /* 分类链接样式 */
        .dropdown-item-group>a,
        .dropdown-menu>a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 15px;
            transition: 0.2s;
            white-space: nowrap;
        }

        .dropdown-item-group>a:hover,
        .dropdown-menu>a:hover {
            background-color: #FFF5EC;
            color: var(--primary-dark);
        }

        /* 二级子菜单 (Sub-menu) */
        .submenu {
            display: none;
            position: absolute;
            left: 100%;
            /* 显示在右侧 */
            top: 0;
            min-width: 200px;
            background: #fff;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 5px 0;
            z-index: 1101;
        }

        /* 鼠标悬停显示子菜单 */
        .dropdown-item-group:hover .submenu {
            display: block;
            animation: fadeIn 0.2s ease-in-out;
        }

        .submenu a {
            display: block;
            padding: 10px 15px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
        }

        .submenu a:hover {
            background-color: #f9f9f9;
            color: var(--primary-color);
            padding-left: 20px;
            transition: 0.2s;
        }

        .arrow-right {
            font-size: 10px;
            color: #ccc;
        }

        /* === 10. Free Shipping Bar Styles === */
        .fs-container {
            padding: 0 0 15px 0;
            background: transparent;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
            text-align: center;
        }

        .fs-text {
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .fs-text span {
            font-weight: 700;
            color: var(--primary-dark);
        }

        .fs-bar-bg {
            width: 100%;
            height: 8px;
            background: #eee;
            border-radius: 10px;
            overflow: hidden;
        }

        .fs-bar-fill {
            height: 100%;
            width: 0%;
            background: var(--primary-color);
            border-radius: 10px;
            transition: width 0.5s ease, background-color 0.3s ease;
        }

        .fs-success-text {
            color: #28a745 !important;
        }

        .fs-success-bar {
            background: #28a745 !important;
        }
    </style>
</head>

<body>

    <div class="announcement-bar">
        <div class="marquee-content">
            ✨ 🐾 🛒 Today's Special Offer: 12% off all pet food! Limited-time promotion! | 🚚 Free Shipping on orders over $50! Shop now!
        </div>
    </div>

    <nav class="navbar">
        <div class="navbar-inner">
            <a href="home.php" class="logo">
                <div class="logo-circle">🐾</div>
                <span>PetBuddy</span>
            </a>

            <div class="nav-links">
                <a href="home.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">Home</a>

                <div class="nav-dropdown-wrapper">
                    <a href="product_listing.php" class="<?= basename($_SERVER['PHP_SELF']) === 'product_listing.php' && !isset($_GET['category']) ? 'active' : '' ?>">
                        Categories ▾
                    </a>
                    <div class="dropdown-menu">
                        <a href="product_listing.php">View All Products</a>
                        <hr style="border:0; border-top:1px solid #eee; margin:5px 0;">

                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                $cat_id = $cat['category_id'];
                                // 检查当前分类下有没有子分类
                                $has_subs = isset($grouped_subs[$cat_id]) && count($grouped_subs[$cat_id]) > 0;
                                ?>

                                <div class="dropdown-item-group">
                                    <a href="product_listing.php?category=<?= $cat_id ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                        <?php if ($has_subs): ?> <span class="arrow-right">▶</span> <?php endif; ?>
                                    </a>

                                    <?php if ($has_subs): ?>
                                        <div class="submenu">
                                            <?php foreach ($grouped_subs[$cat_id] as $sub): ?>
                                                <a href="product_listing.php?category=<?= $cat_id ?>&sub_category=<?= $sub['sub_category_id'] ?>">
                                                    <?= htmlspecialchars($sub['name']) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="#">No Categories Found</a>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="about.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : '' ?>">About</a>
                <a href="contact.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : '' ?>">Contact</a>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                <button class="nav-icon-btn" onclick="toggleSearchBar()">
                    <img src="../images/search-interface-symbol.png" alt="Search" class="custom-icon">
                </button>

                <div class="user-avatar-dropdown" style="position:relative;">
                    <button class="nav-icon-btn" onclick="toggleUserDropdown()">
                        <?php if ($loggedIn && !empty($userAvatar)): ?>
                            <img src="<?= $userAvatar ?>" style="width:26px;height:26px;border-radius:50%;cursor:pointer;object-fit:cover;">
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        <?php endif; ?>
                    </button>

                    <div id="userDropdown" class="user-menu-dropdown">
                        <?php if ($loggedIn): ?>
                            <a href="memberProfile.php">Profile</a>
                            <a href="wishlist.php">Wishlist</a>
                            <a href="logout.php">Logout</a>
                        <?php else: ?>
                            <a href="login.php">Login</a>
                            <a href="register.php">Sign Up</a>
                        <?php endif; ?>
                    </div>
                </div>

                <button class="nav-icon-btn" onclick="openCart()" id="cartIconBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2l1 5h10l1-5z" />
                        <path d="M3 7h18l-2 13H5L3 7z" />
                    </svg>
                    <?php if ($loggedIn && $cart_item_count > 0): ?>
                        <span class="cart-badge" id="cartBadge"><?= $cart_item_count > 99 ? '99+' : $cart_item_count ?></span>
                    <?php else: ?>
                        <span class="cart-badge hidden" id="cartBadge">0</span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </nav>

    <div class="search-container" id="searchBar">
        <form action="product_listing.php" method="get" class="search-box">
            <input type="text" name="search" placeholder="Search pet food, toys, grooming...">
            <button type="submit">
                <img src="../images/search-interface-symbol.png" alt="Search" class="search-btn-icon">
                <span>Search</span>
            </button>
        </form>
    </div>

    <div id="loginOverlay" class="overlay" onclick="closeAllSidebars()"></div>

    <div id="cartSidebar" class="sidebar-panel cart-sidebar">
        <div class="sidebar-header cart-header">
            <span>Shopping Cart</span>
            <button class="close-btn" onclick="closeCart()">&times;</button>
        </div>

        <div class="cart-body" id="cartBody">
            <?php if (!$loggedIn): ?>
                <div style="text-align: center; margin-top: 60px; color: #888;">
                    <p>Please <a href="login.php" class="primary-link" style="color:var(--primary-color);">Login</a> to view cart.</p>
                </div>
            <?php else: ?>
                <?php
                if (isset($pdo)) {
                    $cart_items = getCartItems($pdo, $member_id);

                    foreach ($cart_items as $item) {
                        $cart_item_count += (int)($item['quantity'] ?? 0);
                    }

                    $ui_path_1 = __DIR__ . '/../php/cart_ui.php';
                    $ui_path_2 = 'cart_ui.php';

                    if (file_exists($ui_path_1)) {
                        include $ui_path_1;
                    } elseif (file_exists($ui_path_2)) {
                        include $ui_path_2;
                    } else {
                        echo "<p style='padding:20px'>Cart UI file missing.</p>";
                    }
                }
                ?>
            <?php endif; ?>
        </div>

        <div class="cart-footer" id="cartFooter" style="<?= ($loggedIn && isset($total_price) && $total_price > 0) ? '' : 'display:none;' ?>">


            <div class="cart-total">
                <span>Subtotal:</span>
                <span style="color: var(--primary-dark);">RM <span id="cartSidebarTotal"><?= number_format($total_price, 2) ?></span></span>
            </div>
            <a href="checkout.php?items=all" class="btn-checkout">Checkout Now</a>
            <a href="cart.php" class="btn-view-cart">View & Edit Cart</a>
        </div>
    </div>

    <script>
        // --- 1. Search Bar Toggle ---
        function toggleSearchBar() {
            const bar = document.getElementById("searchBar");
            if (bar) bar.classList.toggle("active");
        }

        // --- 2. User Dropdown Toggle ---
        function toggleUserDropdown() {
            const dropdown = document.getElementById("userDropdown");
            if (dropdown) {
                dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
            }
        }

        document.addEventListener('click', function(e) {
            const container = document.querySelector('.user-avatar-dropdown');
            const dropdown = document.getElementById("userDropdown");
            if (container && !container.contains(e.target) && dropdown && dropdown.style.display === "block") {
                dropdown.style.display = "none";
            }
        });

        // --- 3. Sidebar Functions ---

        function closeAllSidebars() {
            const cart = document.getElementById("cartSidebar");
            const overlay = document.getElementById("loginOverlay");
            if (cart) cart.classList.remove("active");
            if (overlay) overlay.classList.remove("active");
        }

        function openCart() {
            const dropdown = document.getElementById("userDropdown");
            if (dropdown) dropdown.style.display = "none";

            const cart = document.getElementById("cartSidebar");
            const overlay = document.getElementById("loginOverlay");

            if (cart && overlay) {
                cart.classList.add("active");
                overlay.classList.add("active");
                updateFreeShipping();
            }
        }

        function closeCart() {
            const cart = document.getElementById("cartSidebar");
            const overlay = document.getElementById("loginOverlay");
            if (cart) cart.classList.remove("active");
            if (overlay) overlay.classList.remove("active");
        }

        // --- 4. Free Shipping Logic ---
        function updateFreeShipping() {
            const totalEl = document.getElementById('cartSidebarTotal');
            const footer = document.getElementById('cartFooter');

            const barFill = document.getElementById('fsProgress');
            const msgText = document.getElementById('fsMessage');

            if (!barFill || !msgText) return;

            let isHidden = false;
            if (footer) {
                const style = window.getComputedStyle(footer);
                if (style.display === 'none') isHidden = true;
            }

            let currentTotal = 0;

            if (totalEl && !isHidden) {
                let textVal = totalEl.innerText.replace(/,/g, '');
                currentTotal = parseFloat(textVal);
                if (isNaN(currentTotal)) currentTotal = 0;
            }

            const threshold = 50.00;

            if (currentTotal >= threshold) {
                barFill.style.width = '100%';
                barFill.classList.add('fs-success-bar');
                msgText.innerHTML = '🎉 Congratulations! You got <strong>Free Shipping!</strong>';
                msgText.classList.add('fs-success-text');
            } else if (currentTotal > 0) {
                let diff = (threshold - currentTotal).toFixed(2);
                let percentage = (currentTotal / threshold) * 100;
                if (percentage > 100) percentage = 100;

                barFill.style.width = percentage + '%';
                barFill.classList.remove('fs-success-bar');
                msgText.classList.remove('fs-success-text');
                msgText.innerHTML = `Add <span>RM ${diff}</span> more for <br><strong>Free Shipping!</strong> 🚚`;
            } else {
                barFill.style.width = '0%';
                barFill.classList.remove('fs-success-bar');
                msgText.classList.remove('fs-success-text');
                msgText.innerHTML = `Add <span>RM 50.00</span> more for <br><strong>Free Shipping!</strong> 🚚`;
            }
        }

        // --- 5. Update Cart Badge (Notification) ---
        function updateCartBadge() {
            const badge = document.getElementById('cartBadge');
            if (!badge) return;

            let count = 0;


            const countEl = document.getElementById('ajax-cart-count');
            if (countEl) {
                count = parseInt(countEl.value) || 0;
            }


            if (count === 0) {
                const cartBody = document.getElementById('cartBody');
                if (cartBody) {

                    const qtyDisplays = cartBody.querySelectorAll('.qty-display');
                    qtyDisplays.forEach(qtyEl => {
                        const qty = parseInt(qtyEl.textContent) || 0;
                        count += qty;
                    });
                }
            }

            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        // --- 6. Double Watcher ---
        document.addEventListener("DOMContentLoaded", function() {
            updateFreeShipping();
            updateCartBadge();

            const priceNode = document.getElementById('cartSidebarTotal');
            if (priceNode) {
                const priceObserver = new MutationObserver(() => {
                    updateFreeShipping();
                    updateCartBadge();
                });
                priceObserver.observe(priceNode, {
                    childList: true,
                    characterData: true,
                    subtree: true
                });
            }

            const footerNode = document.getElementById('cartFooter');
            if (footerNode) {
                const footerObserver = new MutationObserver(() => {
                    updateFreeShipping();
                    updateCartBadge();
                });
                footerObserver.observe(footerNode, {
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });
            }


            const cartBody = document.getElementById('cartBody');
            if (cartBody) {
                const cartObserver = new MutationObserver(() => {
                    setTimeout(updateCartBadge, 100);
                });
                cartObserver.observe(cartBody, {
                    childList: true,
                    subtree: true,
                    characterData: true
                });
            }

            const countInput = document.getElementById('ajax-cart-count');
            if (countInput) {
                const countObserver = new MutationObserver(() => updateCartBadge());
                countObserver.observe(countInput, {
                    attributes: true,
                    attributeFilter: ['value']
                });
            }
        });

        window.onload = function() {
            if (!sessionStorage.getItem('tab_active')) {
                <?php if ($loggedIn): ?>
                    window.location.href = 'logout.php';
                <?php endif; ?>
            }
            sessionStorage.setItem('tab_active', 'true');
        };

    if (!sessionStorage.getItem('tab_active')) {
        <?php if ($loggedIn): ?>
            window.location.replace('logout.php'); 
        <?php else: ?>
            document.documentElement.style.opacity = "1";
        <?php endif; ?>
    } else {
        document.documentElement.style.opacity = "1";
    }
    sessionStorage.setItem('tab_active', 'true');
    </script>

</body>

</html>