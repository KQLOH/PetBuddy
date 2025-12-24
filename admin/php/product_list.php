<?php
session_start();
require_once '../../user/include/db.php';

/* =======================
   AUTH
======================= */
if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'], true)
) {
    header('Location: admin_login.php');
    exit;
}

$adminRole = $_SESSION['role'];

/* =======================
   FILTERS
======================= */
$search = trim($_GET['search'] ?? '');
$categoryFilter = $_GET['category_id'] ?? 'all';
$subCategoryFilter = $_GET['sub_category_id'] ?? 'all';

/* Pagination */
$limit = 12;
$page  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

/* =======================
   Load Categories & Subcategories
======================= */
$categories = [];
$subCategories = [];

try {
    $categories = $pdo->query("SELECT category_id, name FROM product_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    // sub-categories can be optionally filtered by category
    if ($categoryFilter !== 'all' && ctype_digit((string)$categoryFilter)) {
        $stmt = $pdo->prepare("SELECT sub_category_id, name FROM sub_categories WHERE category_id = ? ORDER BY name");
        $stmt->execute([(int)$categoryFilter]);
        $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $subCategories = $pdo->query("SELECT sub_category_id, name FROM sub_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    // ignore UI dropdown failure
}

/* =======================
   QUERY products (with category name)
======================= */
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
}

if ($categoryFilter !== 'all' && ctype_digit((string)$categoryFilter)) {
    $where[] = "p.category_id = ?";
    $params[] = (int)$categoryFilter;
}

if ($subCategoryFilter !== 'all' && ctype_digit((string)$subCategoryFilter)) {
    $where[] = "p.sub_category_id = ?";
    $params[] = (int)$subCategoryFilter;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

/* Count */
$countSql = "
    SELECT COUNT(*) 
    FROM products p
    {$whereSql}
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $limit);
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

/* Main fetch */
$sql = "
    SELECT
        p.product_id,
        p.name,
        p.price,
        p.stock_qty,
        p.image,
        pc.name AS category_name,
        sc.name AS sub_category_name
    FROM products p
    LEFT JOIN product_categories pc ON pc.category_id = p.category_id
    LEFT JOIN sub_categories sc ON sc.sub_category_id = p.sub_category_id
    {$whereSql}
    ORDER BY p.product_id DESC
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Helper: keep query params */
function q(array $extra = [])
{
    $base = $_GET;
    foreach ($extra as $k => $v) $base[$k] = $v;
    return http_build_query($base);
}

/* Resolve image path
   Your DB may store: uploads/xxx.jpg
   product_list.php is in: admin/php/
   So show from: ../../user/php/uploads/xxx.jpg  (same pattern as member image)
*/
function productImageUrl(?string $dbPath): string
{
    if (!$dbPath) return 'https://via.placeholder.com/72?text=No+Image';
    $dbPath = ltrim($dbPath, '/');
    return '../../user/php/' . $dbPath;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Products</title>
    <link rel="stylesheet" href="../css/admin_product.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebarToggle" class="sidebar-toggle">☰</button>
                <div class="topbar-title">Products</div>
            </div>
        </header>

        <main class="content">

            <div class="page-header">
                <div class="page-title">Product List (<?= (int)$total ?>)</div>
                <div class="page-subtitle">Manage products, categories and stock</div>
            </div>

            <form class="filter-bar" method="get">
                <select name="category_id" onchange="this.form.submit()">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['category_id'] ?>" <?= ((string)$categoryFilter === (string)$c['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="sub_category_id" onchange="this.form.submit()">
                    <option value="all">All Subcategories</option>
                    <?php foreach ($subCategories as $sc): ?>
                        <option value="<?= (int)$sc['sub_category_id'] ?>" <?= ((string)$subCategoryFilter === (string)$sc['sub_category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input
                    type="text"
                    name="search"
                    placeholder="Search product name / description"
                    value="<?= htmlspecialchars($search) ?>">

                <button class="btn-search" type="submit">Search</button>
                <a class="btn-reset" href="product_list.php">Reset</a>

                <?php if ($adminRole === 'super_admin'): ?>
                    <button type="button" class="btn-create" onclick="openCreateProduct()">
                        + New Product
                    </button>
                <?php endif; ?>
            </form>

            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:left;">ID</th>
                            <th style="text-align:left;">Photo</th>
                            <th style="text-align:left;">Name</th>
                            <th style="text-align:left;">Category</th>
                            <th style="text-align:left;">Price (RM)</th>
                            <th style="text-align:left;">Stock</th>
                            <th style="text-align:left;">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$products): ?>
                            <tr>
                                <td colspan="7" class="no-data">No products found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?= (int)$p['product_id'] ?></td>

                                <td>
                                    <img class="product-thumb"
                                        src="<?= htmlspecialchars(productImageUrl($p['image'] ?? null)) ?>"
                                        alt="Product">
                                </td>

                                <td>
                                    <div class="p-name"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="p-sub"><?= htmlspecialchars($p['sub_category_name'] ?? '-') ?></div>
                                </td>

                                <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>

                                <td><?= number_format((float)$p['price'], 2) ?></td>

                                <td>
                                    <?php
                                    $qty = (int)$p['stock_qty'];
                                    $stockClass = $qty <= 0 ? 'stock-out' : ($qty <= 5 ? 'stock-low' : 'stock-ok');
                                    ?>
                                    <span class="stock-pill <?= $stockClass ?>">
                                        <?= $qty <= 0 ? 'Out of stock' : $qty ?>
                                    </span>
                                </td>

                                <td class="actions">
                                    <button
                                        type="button"
                                        class="btn-action btn-view"
                                        onclick="openViewProduct(<?= (int)$p['product_id'] ?>)">
                                        View
                                    </button>

                                    <button
                                        type="button"
                                        class="btn-action btn-edit"
                                        onclick="openEditProduct(<?= (int)$p['product_id'] ?>)">
                                        Edit
                                    </button>

                                    <button
                                        type="button"
                                        class="btn-action btn-delete"
                                        data-id="<?= (int)$p['product_id'] ?>"
                                        data-name="<?= htmlspecialchars($p['name']) ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= q(['p' => $page - 1]) ?>">« Prev</a>
                        <?php else: ?>
                            <span class="disabled">« Prev</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a class="<?= $i == $page ? 'current' : '' ?>" href="?<?= q(['p' => $i]) ?>">
                                    <?= $i ?>
                                </a>
                            <?php elseif (($i == $page - 3 && $page - 3 > 1) || ($i == $page + 3 && $page + 3 < $totalPages)): ?>
                                <span class="dots">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?<?= q(['p' => $page + 1]) ?>">Next >></a>
                        <?php else: ?>
                            <span class="disabled">Next >></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- Delete Modal (UI only, real delete next step) -->
    <div id="deleteModal" class="modal hidden">
        <div class="modal-box">
            <h3 id="modalTitle">Confirm deletion</h3>
            <p id="modalMessage"></p>

            <div class="modal-actions">
                <button id="modalCancel" class="btn-secondary">Cancel</button>

                <!-- next step: point to product_delete.php -->
                <form method="post" action="product_delete.php" id="deleteForm">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('sidebarToggle').onclick = () =>
            document.body.classList.toggle('sidebar-collapsed');

        const modal = document.getElementById('deleteModal');
        const modalMsg = document.getElementById('modalMessage');
        const cancelBtn = document.getElementById('modalCancel');
        const deleteId = document.getElementById('deleteId');

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.onclick = () => {
                modal.classList.remove('hidden');
                modalMsg.textContent = `Are you sure you want to delete "${btn.dataset.name}"?`;
                deleteId.value = btn.dataset.id;
            };
        });

        cancelBtn.onclick = () => modal.classList.add('hidden');
        modal.onclick = (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        };
    </script>

</body>

</html>