<?php
session_start();

if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'])
) {
    header('Location: admin_login.php');
    exit;
}

require_once '../../user/include/db.php';

$adminName = $_SESSION['full_name'] ?? 'Admin';

try {
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $totalMembers  = $pdo->query("SELECT COUNT(*) FROM members WHERE role='member'")->fetchColumn();
    $totalReviews  = $pdo->query("SELECT COUNT(*) FROM product_reviews")->fetchColumn();
} catch (PDOException $e) {
    $totalProducts = $totalOrders = $totalMembers = $totalReviews = 0;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            o.order_id,
            o.order_date,
            o.total_amount,
            o.status,
            m.full_name
        FROM orders o
        JOIN members m ON o.member_id = m.member_id
        ORDER BY o.order_date DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentOrders = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentOrders = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PetBuddy Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                <div class="topbar-title">Dashboard</div>
            </div>
            <div class="topbar-right">
                <span class="tag-pill">Admin: <?= htmlspecialchars($adminName) ?></span>
            </div>
        </header>

        <main class="content">
            <section class="greeting-card">
                <div>
                    <div class="greeting-title">
                        Welcome back, <?= htmlspecialchars($adminName) ?> 👋
                    </div>
                    <div class="greeting-text">
                        Manage products, orders, members and reviews here.
                    </div>
                </div>
                <div class="greeting-icon">🐶</div>
            </section>

            <section class="cards-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= $totalProducts ?></div>
                    <div class="stat-footer">Listed products</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value"><?= $totalOrders ?></div>
                    <div class="stat-footer">Customer orders</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Members</div>
                    <div class="stat-value"><?= $totalMembers ?></div>
                    <div class="stat-footer">Registered users</div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Total Reviews</div>
                    <div class="stat-value"><?= $totalReviews ?></div>
                    <div class="stat-footer">Product feedback</div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Recent Orders</div>
                        <div class="panel-subtitle">Latest 5 orders</div>
                    </div>
                    <a href="orders_list.php" class="btn-link">View all</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total (RM)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="5">No orders found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?= $order['order_id'] ?></td>
                                    <td><?= htmlspecialchars($order['full_name']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($order['order_date'])) ?></td>
                                    <td><?= number_format($order['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="status-pill status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </tbody>
                </table>
            </section>

        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
        });
    </script>

</body>

</html>