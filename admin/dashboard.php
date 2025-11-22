<?php
session_start();

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin.php');
    exit;
}

$adminName = $_SESSION['full_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PetBuddy Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --primary-color: #F4A261;
            --primary-dark: #E68E3F;
            --bg-light: #f9f9f9;
            --bg-sidebar: #fff7ec;
            --text-dark: #333333;
            --border-color: #e0e0e0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #ffffff;
            color: var(--text-dark);
            transition: background-color 0.25s ease, padding-left 0.25s ease;
            padding-left: 220px;
        }

        /* ===== SIDEBAR ===== */
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
            transform: translateX(0);
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
            box-shadow: 0 4px 10px rgba(244, 162, 97, 0.4);
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
        }

        .sidebar-subtitle {
            font-size: 11px;
            color: #777;
        }

        .menu {
            list-style: none;
            margin-top: 18px;
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
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.1s ease;
        }

        .menu a:hover {
            background-color: rgba(244, 162, 97, 0.12);
            color: var(--primary-dark);
            transform: translateX(2px);
        }

        .menu a.active {
            background-color: var(--primary-color);
            color: #ffffff;
        }

        .menu-group-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #999;
            margin: 12px 4px 6px;
        }

        .menu-text {
            white-space: nowrap;
        }

        body.sidebar-collapsed {
            padding-left: 0;
        }

        body.sidebar-collapsed .sidebar {
            transform: translateX(-100%);
        }

        .main {
            min-height: 100px;
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
        }

        .topbar {
            height: 56px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            background-color: #ffffff;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 600;
        }

        .sidebar-toggle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: background-color 0.2s ease, transform 0.15s ease, border-color 0.2s ease;
        }

        .sidebar-toggle:hover {
            background-color: rgba(244, 162, 97, 0.12);
            border-color: var(--primary-color);
            transform: scale(1.05);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }

        .tag-pill {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 999px;
            background-color: rgba(244, 162, 97, 0.12);
            color: var(--primary-dark);
        }

        .content {
            padding: 18px;
            background-color: #fafafa;
            min-height: calc(100vh - 56px);
        }

        /* ===== 卡片 & 动画 ===== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .greeting-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 16px 16px 14px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: fadeInUp 0.4s ease forwards;
        }

        .greeting-title {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .greeting-text {
            font-size: 13px;
            color: #666;
        }

        .greeting-icon {
            font-size: 32px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 12px 14px;
            animation: fadeInUp 0.4s ease forwards;
            opacity: 0;
        }

        .stat-card:nth-child(1) {
            animation-delay: 0.05s;
        }

        .stat-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .stat-card:nth-child(3) {
            animation-delay: 0.15s;
        }

        .stat-card:nth-child(4) {
            animation-delay: 0.2s;
        }

        .stat-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .stat-footer {
            font-size: 11px;
            color: #999;
        }

        .panel {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 14px 14px 10px;
            animation: fadeInUp 0.45s ease forwards;
            opacity: 0;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .panel-title {
            font-size: 14px;
            font-weight: 600;
        }

        .panel-subtitle {
            font-size: 11px;
            color: #888;
        }

        .btn-link {
            border: 1px solid var(--border-color);
            background-color: #ffffff;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            text-decoration: none;
            color: #555;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        .btn-link:hover {
            border-color: var(--primary-color);
            background-color: rgba(244, 162, 97, 0.06);
            color: var(--primary-dark);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            padding: 6px 4px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            font-weight: 600;
            color: #666;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .status-pill {
            padding: 2px 6px;
            border-radius: 999px;
            font-size: 10px;
            display: inline-block;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-paid {
            background-color: #c8e6c9;
            color: #2e7d32;
        }

        .status-shipped {
            background-color: #bbdefb;
            color: #1565c0;
        }

        @media (max-width: 768px) {
            body {
                padding-left: 0;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            body.sidebar-collapsed .sidebar {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN AREA -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle">
                    ☰
                </button>
                <div class="topbar-title">Dashboard</div>
            </div>
            <div class="topbar-right">
                <span class="tag-pill">Admin: <?php echo htmlspecialchars($adminName); ?></span>
            </div>
        </header>

        <main class="content">
            <!-- Greeting -->
            <section class="greeting-card">
                <div>
                    <div class="greeting-title">Welcome back, <?php echo htmlspecialchars($adminName); ?> 👋</div>
                    <div class="greeting-text">
                        This is your PetBuddy admin dashboard. From here you can manage products, orders, members, and reviews.
                    </div>
                </div>
                <div class="greeting-icon">🐶</div>
            </section>

            <!-- Stats (static for now, replace with DB later) -->
            <section class="cards-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value">0</div>
                    <div class="stat-footer">Connect database later to show real count.</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-value">0</div>
                    <div class="stat-footer">Pending, paid, shipped, completed, cancelled.</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Members</div>
                    <div class="stat-value">0</div>
                    <div class="stat-footer">Registered PetBuddy users.</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">New Reviews</div>
                    <div class="stat-value">0</div>
                    <div class="stat-footer">Recent product feedback from customers.</div>
                </div>
            </section>

            <!-- Recent orders -->
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <div class="panel-title">Recent Orders</div>
                        <div class="panel-subtitle">Sample data only – you can connect to database later.</div>
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
                        <tr>
                            <td>#1001</td>
                            <td>Alice Tan</td>
                            <td>2025-01-20</td>
                            <td>120.50</td>
                            <td><span class="status-pill status-paid">Paid</span></td>
                        </tr>
                        <tr>
                            <td>#1000</td>
                            <td>John Lee</td>
                            <td>2025-01-18</td>
                            <td>89.90</td>
                            <td><span class="status-pill status-shipped">Shipped</span></td>
                        </tr>
                        <tr>
                            <td>#0999</td>
                            <td>Emma Wong</td>
                            <td>45.00</td>
                            <td>2025-01-17</td>
                            <td><span class="status-pill status-pending">Pending</span></td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
        // Toggle sidebar show/hide
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
        });
    </script>

</body>

</html>