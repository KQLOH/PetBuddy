<?php
// admin/products_list.php
session_start();

// Only admin can access
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin.php');
    exit;
}

// ===== DATABASE CONNECTION =====
require_once '../db.php';  // Go up one level since we're in admin folder

$adminName = $_SESSION['full_name'] ?? 'Admin';

// ===== FETCH CATEGORIES FROM DATABASE =====
$categories = [];
$sql = "SELECT category_id, name FROM product_categories ORDER BY name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// ===== GET FILTER VALUES =====
$selectedCategory = $_GET['category'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// ===== BUILD SQL QUERY WITH FILTERS =====
$sql = "SELECT 
            p.product_id,
            p.name,
            p.price,
            p.stock_qty,
            p.image,
            p.category_id,
            pc.name AS category_name
        FROM products p
        LEFT JOIN product_categories pc ON p.category_id = pc.category_id
        WHERE 1=1";

$params = [];
$types = '';

// Category filter
if ($selectedCategory !== 'all' && $selectedCategory !== '') {
    $sql .= " AND p.category_id = ?";
    $params[] = (int)$selectedCategory;
    $types .= 'i';
}

// Search filter
if ($search !== '') {
    $sql .= " AND p.name LIKE ?";
    $params[] = '%' . $search . '%';
    $types .= 's';
}

$sql .= " ORDER BY p.product_id DESC";

// ===== EXECUTE QUERY WITH PREPARED STATEMENT =====
$products = [];
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
} else {
    // No parameters, execute directly
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PetBuddy Admin - Products</title>
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

        *{
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

        /* ===== SIDEBAR (same as dashboard) ===== */
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

        body.sidebar-collapsed {
            padding-left: 0;
        }

        body.sidebar-collapsed .sidebar {
            transform: translateX(-100%);
        }

        /* ===== MAIN ===== */
        .main {
            min-height: 100vh;
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .page-title {
            font-size: 20px;
            font-weight: 600;
        }

        .page-subtitle {
            font-size: 12px;
            color: #777;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
            align-items: center;
        }

        .filter-bar form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        select,
        input[type="text"] {
            padding: 6px 8px;
            font-size: 13px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .btn-secondary {
            border: 1px solid var(--border-color);
            background-color: #fff;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            color: #555;
        }

        .btn-secondary:hover {
            border-color: var(--primary-color);
            color: var(--primary-dark);
        }

        .panel {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 10px 10px 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th,
        td {
            padding: 8px 6px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
        }

        th {
            background-color: #fafafa;
            font-weight: 600;
            color: #555;
        }

        .product-image-thumb {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            background-color: #fff3e0;
        }

        .actions a {
            font-size: 12px;
            margin-right: 6px;
            text-decoration: none;
        }

        .link-edit {
            color: #1976d2;
        }

        .link-delete {
            color: #d32f2f;
        }

        .no-data {
            padding: 10px;
            font-size: 13px;
            color: #777;
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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
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
                <div class="topbar-title">Products</div>
            </div>
            <div class="topbar-right">
                <span class="tag-pill">Admin: <?php echo htmlspecialchars($adminName); ?></span>
            </div>
        </header>

        <main class="content">
            <div class="page-header">
                <div>
                    <div class="page-title">Product List</div>
                    <div class="page-subtitle">Manage all products in PetBuddy online shop</div>
                </div>
                <a href="product_add.php" class="btn-primary">+ Add New Product</a>
            </div>

            <div class="filter-bar">
                <form method="get" action="products_list.php">
                    <label style="font-size:12px;">Category:</label>
                    <select name="category">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"
                                <?php if ($selectedCategory == $cat['category_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label style="font-size:12px;">Search:</label>
                    <input type="text" name="search" placeholder="Product name..."
                           value="<?php echo htmlspecialchars($search); ?>">

                    <button type="submit" class="btn-secondary">Filter</button>
                    <a href="products_list.php" class="btn-secondary">Reset</a>
                </form>
            </div>

            <div class="panel">
                <?php if (empty($products)): ?>
                    <div class="no-data">No products found. Try changing the filter or add new products.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price (RM)</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?php echo (int)$p['product_id']; ?></td>
                                    <td>
                                        <?php if (!empty($p['image'])): ?>
                                            <img src="../uploads/products/<?php echo htmlspecialchars($p['image']); ?>"
                                                 alt="Product Image" class="product-image-thumb">
                                        <?php else: ?>
                                            <div style="width:50px;height:50px;border-radius:8px;
                                                        background:#fff3e0;display:flex;
                                                        align-items:center;justify-content:center;font-size:10px;color:#999;">
                                                No Img
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td><?php echo htmlspecialchars($p['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($p['price'], 2); ?></td>
                                    <td><?php echo (int)$p['stock_qty']; ?></td>
                                    <td class="actions">
                                        <a class="link-edit" href="product_edit.php?id=<?php echo (int)$p['product_id']; ?>">Edit</a>
                                        <a class="link-delete"
                                           href="product_delete.php?id=<?php echo (int)$p['product_id']; ?>"
                                           onclick="return confirm('Are you sure you want to delete this product?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar (same as dashboard)
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.body.classList.toggle('sidebar-collapsed');
        });
    </script>

</body>
</html>