<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin protection
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin.php'); // 你的 login page
    exit;
}

$adminName = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --primary-color: #F4A261;
            --primary-dark: #E68E3F;
            --bg-light: #f9f9f9;
            --text-dark: #333333;
            --border-color: #e0e0e0;
        }

        body {
            margin: 0;
            background-color: var(--bg-light);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text-dark);
        }

        .admin-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .admin-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #ffffff;
        }

        .admin-title {
            font-size: 18px;
            font-weight: 700;
        }

        .admin-nav {
            display: flex;
            gap: 14px;
            margin-left: 20px;
        }

        .admin-nav a {
            font-size: 14px;
            text-decoration: none;
            color: var(--text-dark);
            padding: 6px 10px;
            border-radius: 999px;
        }

        .admin-nav a:hover {
            background-color: rgba(244, 162, 97, 0.15);
            color: var(--primary-dark);
        }

        .admin-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-badge {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 999px;
            background-color: rgba(244, 162, 97, 0.12);
            color: var(--primary-dark);
        }

        .logout-btn {
            text-decoration: none;
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 999px;
            background-color: var(--primary-color);
            color: #ffffff;
        }

        .logout-btn:hover {
            background-color: var(--primary-dark);
        }

        .admin-content {
            padding: 20px;
        }
    </style>
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
            <?php echo htmlspecialchars($adminName); ?>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</header>

<div class="admin-content">
