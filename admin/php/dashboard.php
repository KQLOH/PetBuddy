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

try {
    $stmtAdmin = $pdo->prepare("SELECT image FROM members WHERE member_id = ?");
    $stmtAdmin->execute([$_SESSION['member_id']]);
    $adminData = $stmtAdmin->fetch();

    if (!empty($adminData['image'])) {
        $adminImg = '../../user/' . ltrim($adminData['image'], '/');
    } else {
        $adminImg = '../images/default_avatar.png';
    }
} catch (PDOException $e) {
    $adminImg = '../images/default_avatar.png';
}

try {
    $statusStmt = $pdo->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
    $orderStatuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
    $totalCount = array_sum(array_column($orderStatuses, 'count'));

    $salesStmt = $pdo->query("
        SELECT DATE(order_date) as date, SUM(total_amount) as total 
        FROM orders 
        WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(order_date)
        ORDER BY date ASC
    ");
    $dailySales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
    $maxSale = !empty($dailySales) ? max(array_column($dailySales, 'total')) : 1;
} catch (PDOException $e) {
    $orderStatuses = [];
    $dailySales = [];
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
                <section class="greeting-card">
                    <div>
                        <div class="greeting-title">
                            Welcome back, <?= htmlspecialchars($adminName) ?> 👋
                        </div>
                        <div class="greeting-text">
                            Manage products, orders, members and reviews here.
                        </div>
                    </div>
                    <div class="greeting-icon">
                        <img src="<?= $adminImg ?>"
                            alt="Profile"
                            style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    </div>
                </section>
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

            <section class="charts-row">

                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">Weekly Sales Trend (RM)</div>
                    </div>
                    <div class="css-bar-chart">
                        <?php foreach ($dailySales as $day):
                            $height = ($day['total'] / $maxSale) * 100; ?>
                            <div class="bar-wrapper">
                                <div class="bar" style="height: <?= $height ?>%;">
                                    <span class="bar-value"><?= round($day['total']) ?></span>
                                </div>
                                <div class="bar-label"><?= date('m-d', strtotime($day['date'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">Order Distribution</div>
                    </div>
                    <div class="pie-container">
                        <?php
                        $conic = "";
                        $offset = 0;
                        $colors = [
                            'pending' => '#FFB774',
                            'completed' => '#2E7D32',
                            'shipped' => '#0288D1',
                            'cancelled' => '#D32F2F',
                            'paid' => '#FFD700'
                        ];
                        foreach ($orderStatuses as $status) {
                            $percent = ($status['count'] / $totalCount) * 100;
                            $color = $colors[$status['status']] ?? '#ccc';
                            $conic .= "$color $offset% " . ($offset + $percent) . "%, ";
                            $offset += $percent;
                        }
                        $conic = rtrim($conic, ", ");
                        ?>
                        <div class="pie-chart" style="background: conic-gradient(<?= $conic ?>);"></div>

                        <div class="legend">
                            <?php foreach ($orderStatuses as $status): ?>
                                <div class="legend-item">
                                    <span class="legend-color" style="background: <?= $colors[$status['status']] ?? '#ccc' ?>;"></span>
                                    <span><?= ucfirst($status['status']) ?>: <strong><?= $status['count'] ?></strong></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
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