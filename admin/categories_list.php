<?php
// admin/categories_list.php
session_start();

// Only admin can access
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin.php');
    exit;
}

$adminName = $_SESSION['full_name'] ?? 'Admin';

/*
 * ===== TEMP CATEGORY DATA (NO DATABASE YET) =====
 * You can replace this with MySQL later.
 */

$allCategories = [
    ['category_id' => 1, 'name' => 'Dog Food',         'description' => 'Dry and wet dog food'],
    ['category_id' => 2, 'name' => 'Cat Toys',         'description' => 'Interactive fun toys for cats'],
    ['category_id' => 3, 'name' => 'Pet Accessories',  'description' => 'Collars, leashes, and more'],
    ['category_id' => 4, 'name' => 'Pet Grooming',     'description' => 'Shampoos, brushes and grooming tools'],
];

// Search filter
$search = trim($_GET['search'] ?? '');

$categories = $allCategories;

if ($search !== '') {
    $s = strtolower($search);
    $categories = array_filter($categories, function ($c) use ($s) {
        return strpos(strtolower($c['name']), $s) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PetBuddy Admin - Categories</title>
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
            transition: padding-left 0.25s ease;
            padding-left: 220px;
        }

        /* ===== SIDEBAR (same as dashboard) ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
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

        body.sidebar-collapsed {
            padding-left: 0;
        }
        body.sidebar-collapsed .sidebar {
            transform: translateX(-100%);
        }

        /* ===== MAIN AREA ===== */
        .main {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 600;
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
            background: var(--primary-color);
            color: #fff;
            padding: 8px 14px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        /* Search bar */
        .filter-bar {
            margin-bottom: 14px;
        }

        .filter-bar form {
            display: flex;
            gap: 8px;
        }

        input[type="text"] {
            padding: 6px 8px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .btn-secondary {
            border: 1px solid var(--border-color);
            background: #fff;
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 999px;
        }

        .btn-secondary:hover {
            border-color: var(--primary-color);
            color: var(--primary-dark);
        }

        .panel {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th, td {
            padding: 8px 6px;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #fafafa;
            font-weight: 600;
        }

        .actions a {
            font-size: 12px;
            margin-right: 6px;
        }

        .link-edit { color: #1976d2; }
        .link-delete { color: #d32f2f; }

        .no-data {
            padding: 10px;
            font-size: 13px;
            color: #777;
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN -->
    <div class="main">

        <header class="topbar">
            <div style="display:flex;align-items:center;gap:10px;">
                <button id="sidebarToggle" class="sidebar-toggle">☰</button>
                <div class="topbar-title">Categories</div>
            </div>
            <span class="tag-pill">Admin: <?php echo htmlspecialchars($adminName); ?></span>
        </header>

        <main class="content">
            <div class="page-header">
                <div>
                    <div class="page-title">Category List</div>
                    <div class="page-subtitle">Manage product categories in PetBuddy.</div>
                </div>
                <a href="category_add.php" class="btn-primary">+ Add Category</a>
            </div>

            <!-- Search Bar -->
            <div class="filter-bar">
                <form method="get" action="categories_list.php">
                    <input type="text" name="search" placeholder="Search category..."
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn-secondary">Search</button>
                    <a href="categories_list.php" class="btn-secondary">Reset</a>
                </form>
            </div>

            <!-- Category Table -->
            <div class="panel">
                <?php if (empty($categories)): ?>
                    <div class="no-data">No categories found.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th width="100">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td><?php echo $c['category_id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                                    <td><?php echo htmlspecialchars($c['description']); ?></td>
                                    <td class="actions">
                                        <a class="link-edit" href="category_edit.php?id=<?php echo $c['category_id']; ?>">Edit</a>
                                        <a class="link-delete"
                                           href="category_delete.php?id=<?php echo $c['category_id']; ?>"
                                           onclick="return confirm('Delete this category?');">
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
        // Sidebar toggle (same as dashboard)
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            document.body.classList.toggle('sidebar-collapsed');
        });
    </script>
</body>

</html>
