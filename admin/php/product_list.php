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

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal hidden">
        <div class="modal-box">
            <h3 id="modalTitle">Confirm deletion</h3>
            <p id="modalMessage"></p>

            <div class="modal-actions">
                <button id="modalCancel" class="btn-secondary">Cancel</button>
                <form method="post" action="product_delete.php" id="deleteForm">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Product Modal -->
    <div id="createModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Create New Product</h3>
                <button class="modal-close" onclick="closeCreateModal()">&times;</button>
            </div>
            <form id="createForm" enctype="multipart/form-data">
                <div class="modal-image-section">
                    <img id="createPreview" src="https://via.placeholder.com/160?text=Preview" alt="Preview">
                    <label class="upload-btn">
                        Upload Image
                        <input type="file" name="image" accept="image/*" hidden onchange="previewCreateImage(event)">
                    </label>
                </div>
                <div class="modal-form-grid">
                    <div>
                        <label>Product Name *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div>
                        <label>Price (RM) *</label>
                        <input type="number" step="0.01" name="price" required>
                    </div>
                    <div>
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock_qty" value="0" required>
                    </div>
                    <div>
                        <label>Category</label>
                        <select name="category_id">
                            <option value="">-- None --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['category_id'] ?>">
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Sub Category</label>
                        <select name="sub_category_id">
                            <option value="">-- None --</option>
                            <?php foreach ($subCategories as $s): ?>
                                <option value="<?= $s['sub_category_id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="full">
                        <label>Description</label>
                        <textarea name="description"></textarea>
                    </div>
                </div>
                <div id="createError" class="alert error hidden"></div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeCreateModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Create Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Product Modal -->
    <div id="viewModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Product Details</h3>
                <button class="modal-close" onclick="closeViewModal()">&times;</button>
            </div>
            <div id="viewContent" class="modal-view-content">
                <div class="loading">Loading...</div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Edit Product</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editProductId">
                <div class="modal-image-section">
                    <img id="editPreview" src="https://via.placeholder.com/160?text=Preview" alt="Preview">
                    <label class="upload-btn">
                        Upload New Image
                        <input type="file" name="image" accept="image/*" hidden onchange="previewEditImage(event)">
                    </label>
                </div>
                <div class="modal-form-grid">
                    <div>
                        <label>Product Name *</label>
                        <input type="text" name="name" id="editName" required>
                    </div>
                    <div>
                        <label>Price (RM) *</label>
                        <input type="number" step="0.01" name="price" id="editPrice" required>
                    </div>
                    <div>
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock_qty" id="editStock" required>
                    </div>
                    <div>
                        <label>Category</label>
                        <select name="category_id" id="editCategory">
                            <option value="">-- None --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['category_id'] ?>">
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Sub Category</label>
                        <select name="sub_category_id" id="editSubCategory">
                            <option value="">-- None --</option>
                            <?php foreach ($subCategories as $s): ?>
                                <option value="<?= $s['sub_category_id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="full">
                        <label>Description</label>
                        <textarea name="description" id="editDescription"></textarea>
                    </div>
                </div>
                <div id="editError" class="alert error hidden"></div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sidebar toggle
        document.getElementById('sidebarToggle').onclick = () =>
            document.body.classList.toggle('sidebar-collapsed');

        // Delete Modal
        const deleteModal = document.getElementById('deleteModal');
        const modalMsg = document.getElementById('modalMessage');
        const cancelBtn = document.getElementById('modalCancel');
        const deleteId = document.getElementById('deleteId');

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.onclick = () => {
                deleteModal.classList.remove('hidden');
                modalMsg.textContent = `Are you sure you want to delete "${btn.dataset.name}"?`;
                deleteId.value = btn.dataset.id;
            };
        });

        cancelBtn.onclick = () => deleteModal.classList.add('hidden');
        deleteModal.onclick = (e) => {
            if (e.target === deleteModal) deleteModal.classList.add('hidden');
        };

        // ========== CREATE MODAL ==========
        function openCreateProduct() {
            document.getElementById('createModal').classList.remove('hidden');
            document.getElementById('createForm').reset();
            document.getElementById('createPreview').src = 'https://via.placeholder.com/160?text=Preview';
            document.getElementById('createError').classList.add('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function previewCreateImage(e) {
            if (e.target.files && e.target.files[0]) {
                document.getElementById('createPreview').src = URL.createObjectURL(e.target.files[0]);
            }
        }

        document.getElementById('createForm').onsubmit = async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const errorDiv = document.getElementById('createError');

            errorDiv.classList.add('hidden');

            try {
                const res = await fetch('product_create.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    errorDiv.textContent = data.error || 'Failed to create product';
                    errorDiv.classList.remove('hidden');
                }
            } catch (err) {
                errorDiv.textContent = 'Network error: ' + err.message;
                errorDiv.classList.remove('hidden');
            }
        };

        document.getElementById('createModal').onclick = (e) => {
            if (e.target.id === 'createModal') closeCreateModal();
        };

        // ========== VIEW MODAL ==========
        async function openViewProduct(id) {
            const modal = document.getElementById('viewModal');
            const content = document.getElementById('viewContent');
            modal.classList.remove('hidden');
            content.innerHTML = '<div class="loading">Loading...</div>';

            try {
                const res = await fetch(`product_get.php?id=${id}`);
                const data = await res.json();

                if (data.success && data.product) {
                    const p = data.product;
                    const qty = parseInt(p.stock_qty) || 0;
                    const stockClass = qty <= 0 ? 'stock-out' : (qty <= 5 ? 'stock-low' : 'stock-ok');
                    const stockLabel = qty <= 0 ? 'Out of stock' : qty;

                    content.innerHTML = `
                        <table class="view-table">
                            <tr>
                                <th>Product ID</th>
                                <td>${p.product_id}</td>
                            </tr>
                            <tr>
                                <th>Photo</th>
                                <td>
                                    <img src="${p.image_path}" alt="Product" class="product-thumb-view">
                                </td>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <td>${escapeHtml(p.name)}</td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td>${escapeHtml(p.category_name || '-')}</td>
                            </tr>
                            <tr>
                                <th>Sub Category</th>
                                <td>${escapeHtml(p.sub_category_name || '-')}</td>
                            </tr>
                            <tr>
                                <th>Price (RM)</th>
                                <td>${parseFloat(p.price).toFixed(2)}</td>
                            </tr>
                            <tr>
                                <th>Stock</th>
                                <td>
                                    <span class="stock-pill ${stockClass}">${stockLabel}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>${escapeHtml(p.description || '-').replace(/\n/g, '<br>')}</td>
                            </tr>
                        </table>
                    `;
                } else {
                    content.innerHTML = '<div class="alert error">Product not found.</div>';
                }
            } catch (err) {
                content.innerHTML = '<div class="alert error">Error loading product: ' + err.message + '</div>';
            }
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        document.getElementById('viewModal').onclick = (e) => {
            if (e.target.id === 'viewModal') closeViewModal();
        };

        // ========== EDIT MODAL ==========
        async function openEditProduct(id) {
            const modal = document.getElementById('editModal');
            const errorDiv = document.getElementById('editError');
            modal.classList.remove('hidden');
            errorDiv.classList.add('hidden');

            try {
                const res = await fetch(`product_get.php?id=${id}`);
                const data = await res.json();

                if (data.success && data.product) {
                    const p = data.product;
                    document.getElementById('editProductId').value = p.product_id;
                    document.getElementById('editName').value = p.name || '';
                    document.getElementById('editPrice').value = p.price || '';
                    document.getElementById('editStock').value = p.stock_qty || 0;
                    document.getElementById('editDescription').value = p.description || '';
                    document.getElementById('editCategory').value = p.category_id || '';
                    document.getElementById('editSubCategory').value = p.sub_category_id || '';
                    document.getElementById('editPreview').src = p.image_path || 'https://via.placeholder.com/160?text=Preview';
                } else {
                    errorDiv.textContent = 'Product not found.';
                    errorDiv.classList.remove('hidden');
                }
            } catch (err) {
                errorDiv.textContent = 'Error loading product: ' + err.message;
                errorDiv.classList.remove('hidden');
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function previewEditImage(e) {
            if (e.target.files && e.target.files[0]) {
                document.getElementById('editPreview').src = URL.createObjectURL(e.target.files[0]);
            }
        }

        document.getElementById('editForm').onsubmit = async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const errorDiv = document.getElementById('editError');

            errorDiv.classList.add('hidden');

            try {
                const res = await fetch('product_edit.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    errorDiv.textContent = data.error || 'Failed to update product';
                    errorDiv.classList.remove('hidden');
                }
            } catch (err) {
                errorDiv.textContent = 'Network error: ' + err.message;
                errorDiv.classList.remove('hidden');
            }
        };

        document.getElementById('editModal').onclick = (e) => {
            if (e.target.id === 'editModal') closeEditModal();
        };

        // Helper function
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

</body>

</html>