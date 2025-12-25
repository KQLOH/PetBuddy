<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/');
    session_start();
}

require_once 'db.php';

// --- 1. Remember Me Logic ---
if (!isset($_SESSION['member_id']) && isset($_COOKIE['remember_token'])) {
    if (isset($pdo)) {
        $token = $_COOKIE['remember_token'];
        $stmt = $pdo->prepare("SELECT member_id, full_name, role, image FROM members WHERE remember_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['member_id'] = $user['member_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_image'] = !empty($user['image']) ? '../' . $user['image'] : '../images/default-avatar.png';
        }
    }
}
?>

<?php if (isset($_SESSION['member_id']) && !isset($_COOKIE['remember_token'])): ?>
    <script>
        if (!sessionStorage.getItem('session_alive')) {
            window.location.replace("logout.php");
        }
    </script>
<?php endif; ?>

<script>
    sessionStorage.setItem('session_alive', 'true');
</script>
<?php
// Cart & Utils Includes
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

if (file_exists(__DIR__ . "/product_utils.php")) {
    require_once __DIR__ . "/product_utils.php";
}

$loggedIn = isset($_SESSION['member_id']);
$member_id = $loggedIn ? $_SESSION['member_id'] : null;
$userAvatar = '../images/default-avatar.png';
$total_price = 0;
$cart_item_count = 0;

if ($loggedIn) {
    try {
        $stmt = $pdo->prepare("SELECT image FROM members WHERE member_id=?");
        $stmt->execute([$member_id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['image'])) {
            $userAvatar = '../' . $row['image'];
        }
    } catch (PDOException $e) { /* Ignore */
    }
}

$categories = [];
$grouped_subs = [];

try {
    $stmt_cat = $pdo->prepare("SELECT * FROM product_categories ORDER BY category_id ASC");
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    $stmt_sub = $pdo->prepare("SELECT * FROM sub_categories ORDER BY category_id ASC, name ASC");
    $stmt_sub->execute();
    $all_subs = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);

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
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
        }

        .marquee-content {
            display: inline-flex;
            align-items: center;
            padding-left: 100%;
            animation: marquee-flow 25s linear infinite;
        }

        /* PNG Icon in Marquee (White Filter) */
        .marquee-icon {
            width: 16px;
            height: 16px;
            margin: 0 6px;
            vertical-align: middle;
            filter: brightness(0) invert(1);
            object-fit: contain;
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

/* 1. Update the container: Remove background and fix dimensions */
.logo-circle {
    width: auto;       /* Allow width to adjust based on image aspect ratio */
    height: auto;      /* Allow height to adjust */
    background: transparent; /* REMOVED the orange background color */
    border-radius: 0;  /* REMOVED the circular shape */
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px; /* Add a little space between image and "PetBuddy" text */
}

/* 2. Update the image: Make it bigger and ensure original colors show */
.logo-icon-img { 
    height: 50px;      /* Set desired height (was ~20px before). Adjust this value if needed. */
    width: auto;       /* Maintain aspect ratio */
    object-fit: contain;
    filter: none;      /* Ensure no filters (like the white one) are applied */
}

        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .nav-links a.nav-link {
            font-size: 16px;
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

        /* Navigation Action Buttons (Search, User, Cart) */
        .nav-icon-btn {
            width: 44px;
            height: 44px;
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
            background: #f5f5f5;
        }

        /* General PNG Icon Style */
        .custom-icon {
            width: 24px;
            height: 24px;
            object-fit: contain;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .nav-icon-btn:hover .custom-icon {
            opacity: 1;
        }

        /* === Cart Badge === */
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
            border: 2px solid #fff;
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
            gap: 8px;
        }

        .search-box button:hover {
            background: var(--primary-dark);
        }

        .search-btn-icon {
            width: 18px;
            height: 18px;
            object-fit: contain;
            filter: brightness(0) invert(1);
            /* Make white */
        }

        /* === 5. User Dropdown === */
        .user-menu-dropdown {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-width: 150px;
            z-index: 1000;
            padding: 8px 0;
            overflow: hidden;
        }

        .user-menu-dropdown a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 14px;
            transition: background-color 0.15s;
        }

        .user-menu-dropdown a:hover {
            background-color: #f9f9f9;
            color: var(--primary-color);
        }

        /* === 6. Sidebar & Overlay === */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
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
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
            transition: 0.4s cubic-bezier(0.25, 1, 0.5, 1);
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
            font-size: 18px;
            font-weight: 700;
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
        }

        .close-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-icon-img {
            width: 18px;
            height: 18px;
            opacity: 0.5;
            transition: 0.2s;
        }

        .close-btn:hover .close-icon-img {
            opacity: 1;
            transform: scale(1.1);
        }

        /* === 7. Cart Sidebar === */
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

        /* Qty Control */
        .qty-control-wrapper {
            display: flex;
            align-items: center;
            margin-top: 8px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: fit-content;
        }

        .qty-btn {
            border: none;
            background: transparent;
            padding: 4px 10px;
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
            color: #aaa;
            cursor: pointer;
            padding: 5px;
            font-size: 13px;
            align-self: flex-start;
            margin-top: 5px;
            text-decoration: underline;
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
            border-radius: 50px;
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

        /* === 9. Categories Dropdown & Menu === */
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
            gap: 6px;
            text-decoration: none;
            color: var(--text-dark);
            font-size: 16px;
            font-weight: 500;
        }

        .nav-dropdown-wrapper:hover>a {
            color: var(--primary-dark);
        }

        /* FIX: Force small size for the 'Down' arrow in categories menu */
        .nav-dropdown-wrapper>a .custom-icon {
            width: 12px;
            height: 12px;
            opacity: 0.5;
            margin-top: 2px;
        }

        /* Dropdown Box */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            min-width: 220px;
            padding: 8px 0;
            z-index: 1100;
            border: 1px solid #f0f0f0;
            margin-top: 15px;
        }

        .nav-dropdown-wrapper:hover .dropdown-menu {
            display: block;
            animation: fadeIn 0.2s ease-in-out;
        }

        /* Invisible bridge to prevent closing on hover */
        .dropdown-menu::before {
            content: "";
            position: absolute;
            top: -20px;
            left: 0;
            width: 100%;
            height: 20px;
            background: transparent;
        }

        .dropdown-item-group {
            position: relative;
        }

        .dropdown-item-group>a,
        .dropdown-menu>a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            color: #444;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
            white-space: nowrap;
        }

        .dropdown-item-group>a:hover,
        .dropdown-menu>a:hover {
            background-color: #FFF5EC;
            color: var(--primary-dark);
        }

        /* Submenu (Right side) */
        .submenu {
            display: none;
            position: absolute;
            left: 100%;
            top: -5px;
            min-width: 200px;
            background: #fff;
            box-shadow: 4px 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
            border-radius: 8px;
            padding: 8px 0;
            z-index: 1101;
        }

        .dropdown-item-group:hover .submenu {
            display: block;
            animation: fadeIn 0.2s ease-in-out;
        }

        .submenu a {
            display: block;
            padding: 10px 20px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        .submenu a:hover {
            background-color: #f9f9f9;
            color: var(--primary-color);
        }

        .arrow-right-icon {
            width: 10px;
            height: 10px;
            opacity: 0.3;
            object-fit: contain;
        }

        /* === 10. Free Shipping Bar === */
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
            height: 6px;
            background: #eee;
            border-radius: 10px;
            overflow: hidden;
        }

        .fs-bar-fill {
            height: 100%;
            width: 0%;
            background: var(--primary-color);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .fs-success-text {
            color: #28a745 !important;
        }

        .fs-success-bar {
            background: #28a745 !important;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px) translateX(-50%);
            }

            to {
                opacity: 1;
                transform: translateY(0) translateX(-50%);
            }
        }

        /* Z-Index Overrides */
        .swal2-container,
        .swal2-popup,
        #toast-container,
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
    </style>
</head>

<body>

    <div class="announcement-bar">
        <div class="marquee-content">
            <img src="../images/announcement.png" class="marquee-icon">
            <img src="../images/pawprints1.png" class="marquee-icon">
            <img src="../images/cart.png" class="marquee-icon">
            Today's Special Offer: 12% off all pet food! Limited-time promotion!
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <img src="../images/delivery-truck.png" class="marquee-icon">
            Free Shipping on orders over $50! Shop now!
        </div>
    </div>

    <nav class="navbar">
        <div class="navbar-inner">
            <a href="home.php" class="logo">
                <div class="logo-circle">
                    <img src="../images/logo.png" class="logo-icon-img" alt="Logo">
                </div>
                <span>PetBuddy</span>
            </a>

            <div class="nav-links">
                <a href="home.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">Home</a>

                <div class="nav-dropdown-wrapper">
                    <a href="product_listing.php" class="<?= basename($_SERVER['PHP_SELF']) === 'product_listing.php' && !isset($_GET['category']) ? 'active' : '' ?>">
                        Categories <img src="../images/down.png" alt="Search" class="custom-icon">
                    </a>
                    <div class="dropdown-menu">
                        <a href="product_listing.php">View All Products</a>
                        <hr style="border:0; border-top:1px solid #eee; margin:5px 0;">

                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php
                                $cat_id = $cat['category_id'];
                                $has_subs = isset($grouped_subs[$cat_id]) && count($grouped_subs[$cat_id]) > 0;
                                ?>

                                <div class="dropdown-item-group">
                                    <a href="product_listing.php?category=<?= $cat_id ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                        <?php if ($has_subs): ?> <img src="../images/arrow_right.png" class="arrow-right-icon"> <?php endif; ?>
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
                    <img src="../images/search.png" alt="Search" class="custom-icon">
                </button>

                <div class="user-avatar-dropdown" style="position:relative;">
                    <button class="nav-icon-btn" onclick="toggleUserDropdown()">
                        <?php if ($loggedIn && !empty($userAvatar)): ?>
                            <img src="<?= $userAvatar ?>" style="width:26px;height:26px;border-radius:50%;cursor:pointer;object-fit:cover;">
                        <?php else: ?>
                            <img src="../images/user.png" class="custom-icon" alt="User">
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
                    <img src="../images/shopping-cart.png" class="custom-icon" alt="Cart">
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
                <img src="../images/search.png" alt="Search" class="search-btn-icon">
                <span>Search</span>
            </button>
        </form>
    </div>

    <div id="loginOverlay" class="overlay" onclick="closeAllSidebars()"></div>

    <div id="cartSidebar" class="sidebar-panel cart-sidebar">
        <div class="sidebar-header cart-header">
            <span>Shopping Cart</span>
            <button class="close-btn" onclick="closeCart()">
                <img src="../images/error.png" class="close-icon-img" alt="Close">
            </button>
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

        // --- 5. Update Cart Badge ---
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

        document.documentElement.style.opacity = "1";
    </script>

</body>

</html>