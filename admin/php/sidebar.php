<?php
// admin/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);

function isActive(string $file, string $currentPage): string {
    return $file === $currentPage ? 'active' : '';
}
?>

<style>
/* Sidebar 全局样式 */
:root {
    --primary-color: #F4A261;
    --primary-dark: #E68E3F;
    --bg-sidebar: #fff7ec;
    --text-dark: #333333;
    --border-color: #e0e0e0;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 220px;
    background: var(--bg-sidebar);
    border-right: 1px solid var(--border-color);
    padding: 16px 14px;
    transition: transform 0.25s ease;
    overflow-y: auto;
    z-index: 20;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}

.logo-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
}

.sidebar-title {
    font-size: 18px;
    font-weight: 700;
}

.sidebar-subtitle {
    font-size: 11px;
    color: #777;
}

.menu-group-title {
    font-size: 11px;
    text-transform: uppercase;
    color: #999;
    margin: 12px 4px 6px;
}

.menu {
    list-style: none;
    padding-left: 0;
}

.menu li {
    margin-bottom: 6px;
}

.menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 10px;
    border-radius: 10px;
    font-size: 13px;
    color: #444;
    text-decoration: none;
    transition: 0.2s ease;
}

.menu a:hover {
    background-color: rgba(244,162,97,0.14);
    color: var(--primary-dark);
}

.menu a.active {
    background-color: var(--primary-color);
    color: #ffffff;
}

/* Mobile 时 sidebar 关闭样式由 body.sidebar-collapsed 控制 */
body.sidebar-collapsed .sidebar {
    transform: translateX(-100%);
}
</style>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-circle">🐾</div>
        <div>
            <div class="sidebar-title">PetBuddy</div>
            <div class="sidebar-subtitle">Admin Panel</div>
        </div>
    </div>

    <div class="menu-group-title">Overview</div>
    <ul class="menu">
        <li>
            <a href="dashboard.php"
               class="<?php echo isActive('dashboard.php', $currentPage); ?>">
               Dashboard
            </a>
        </li>
    </ul>

    <div class="menu-group-title">Management</div>
    <ul class="menu">
        <li><a href="products_list.php" class="<?php echo isActive('products_list.php', $currentPage); ?>">Products</a></li>
        <li><a href="categories_list.php" class="<?php echo isActive('categories_list.php', $currentPage); ?>">Categories</a></li>
        <li><a href="orders_list.php" class="<?php echo isActive('orders_list.php', $currentPage); ?>">Orders</a></li>
        <li><a href="member_list.php" class="<?php echo isActive('members_list.php', $currentPage); ?>">Members</a></li>
        <li><a href="reviews_list.php" class="<?php echo isActive('reviews_list.php', $currentPage); ?>">Reviews</a></li>
        <li><a href="chat.php" class="<?php echo isActive('chat.php', $currentPage); ?>">chat</a></li>
    </ul>

    <div class="menu-group-title">Other</div>
    <ul class="menu">
        <li><a href="../home.php">Back to Shop</a></li>
        <li><a href="../logout.php">Logout</a></li>
    </ul>
</aside>
