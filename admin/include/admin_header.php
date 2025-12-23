<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin','super_admin'])) {
    header('Location: admin_login.php');
    exit;
}

$adminName = $_SESSION['full_name'] ?? 'Admin';
$adminRole = $_SESSION['role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Admin Header CSS -->
    <link rel="stylesheet" href="../css/admin_header.css">
</head>
<body>

<header class="admin-header">
    <div class="admin-left">
        <div class="logo-circle">🐾</div>
        <div class="admin-title">PetBuddy Admin</div>

        <nav class="admin-nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="products.php">Products</a>
            <a href="orders.php">Orders</a>
            <a href="members.php">Members</a>
        </nav>
    </div>

    <div class="admin-right">
        <div class="admin-badge">
            <?= htmlspecialchars($adminName) ?>
            (<?= htmlspecialchars(str_replace('_',' ', ucfirst($adminRole))) ?>)
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</header>

<div class="admin-content">
<!-- 页面内容从这里开始 -->
