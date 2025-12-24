<?php
// admin/php/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);

function isActive(string $file, string $currentPage): string
{
    return $file === $currentPage ? 'active' : '';
}
?>

<link rel="stylesheet" href="../css/admin_sidebar.css">

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
                class="<?= isActive('dashboard.php', $currentPage); ?>">
                Dashboard
            </a>
        </li>
    </ul>

    <div class="menu-group-title">Management</div>
    <ul class="menu">
        <li><a href="product_list.php" class="<?= isActive('product_list.php', $currentPage); ?>">Products</a></li>
        <li><a href="category_list.php" class="<?= isActive('category_list.php', $currentPage); ?>">Categories</a></li>
        <li><a href="orders_list.php" class="<?= isActive('order_list.php', $currentPage); ?>">Orders</a></li>
        <li><a href="member_list.php" class="<?= isActive('member_list.php', $currentPage); ?>">Members</a></li>
        <li><a href="reviews_list.php" class="<?= isActive('review_list.php', $currentPage); ?>">Reviews</a></li>
        <li><a href="chat.php" class="<?= isActive('chat.php', $currentPage); ?>">Chat</a></li>
    </ul>

    <div class="menu-group-title">Other</div>
    <ul class="menu">
        <li><a href="../../home.php">Back to Shop</a></li>
        <li><a href="admin_logout.php">Logout</a></li>
    </ul>
</aside>