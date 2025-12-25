<?php
session_start();
require_once '../../user/include/db.php';

if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'], true)
) {
    header('Location: admin_login.php');
    exit;
}

$adminRole = $_SESSION['role'];
$search = trim($_GET['search'] ?? '');
$categoryFilter = $_GET['category_id'] ?? 'all';
$subCategoryFilter = $_GET['sub_category_id'] ?? 'all';
$limit = 12;
$page  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;
$categories = [];
$subCategories = [];

try {
    $categories = $pdo->query("SELECT category_id, name FROM product_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    if ($categoryFilter !== 'all' && ctype_digit((string)$categoryFilter)) {
        $stmt = $pdo->prepare("SELECT sub_category_id, name FROM sub_categories WHERE category_id = ? ORDER BY name");
        $stmt->execute([(int)$categoryFilter]);
        $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $subCategories = $pdo->query("SELECT sub_category_id, name FROM sub_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
}

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

$sort = $_GET['sort'] ?? 'product_id';
$dir  = $_GET['dir'] ?? 'DESC';

$allowedSorts = [
    'product_id'   => 'p.product_id',
    'name'         => 'p.name',
    'category'     => 'pc.name',
    'price'        => 'p.price',
    'stock_qty'    => 'p.stock_qty'
];

if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'product_id';
}
$sortSqlColumn = $allowedSorts[$sort];

$dir = strtoupper($dir);
if (!in_array($dir, ['ASC', 'DESC'])) {
    $dir = 'DESC';
}

function sortLink($columnKey, $label)
{
    global $sort, $dir;
    $newDir = ($sort === $columnKey && $dir === 'ASC') ? 'DESC' : 'ASC';

    $iconHtml = '';
    if ($sort === $columnKey) {
        $iconPath = ($dir === 'ASC') ? "../images/up.png" : "../images/down.png";
        $iconHtml = ' <img src="' . $iconPath . '" class="sort-icon" alt="sort">';
    }

    $url = '?' . q(['sort' => $columnKey, 'dir' => $newDir, 'p' => 1]);
    return '<a href="' . htmlspecialchars($url) . '" style="text-decoration:none; color:inherit; font-weight:bold; display:inline-flex; align-items:center; gap:4px;">' . $label . $iconHtml . '</a>';
}

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
    ORDER BY {$sortSqlColumn} {$dir}
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

function q(array $extra = [])
{
    $base = $_GET;
    foreach ($extra as $k => $v) $base[$k] = $v;
    return http_build_query($base);
}

function productImageUrl(?string $dbPath): string
{
    if (!$dbPath) {
        return '../images/default_product.jpg';
    }

    return '../../user/php/' . $dbPath;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Products</title>
    <link rel="stylesheet" href="../css/admin_product.css">
    <link rel="stylesheet" href="../css/admin_btn.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebarToggle" class="sidebar-toggle"><img src="../images/menu.png"></button>
                <div class="topbar-title">Products</div>
            </div>
        </header>

        <main class="content">

            <div class="page-header">
                <div class="page-title">Product List (<?= (int)$total ?>)</div>
                <div class="page-subtitle">Manage products, categories and stock</div>
            </div>

            <form class="filter-bar" method="get">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">

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
                            <th><?= sortLink('product_id', 'ID') ?></th>
                            <th>Photo</th>
                            <th><?= sortLink('name', 'Name') ?></th>
                            <th><?= sortLink('category', 'Category') ?></th>
                            <th><?= sortLink('price', 'Price (RM)') ?></th>
                            <th><?= sortLink('stock_qty', 'Stock') ?></th>
                            <th>Action</th>
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

                                    <button type="button" class="btn-action btn-delete" data-id="<?= (int)$p['product_id'] ?>" data-name="<?= htmlspecialchars($p['name']) ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= q(['p' => $page - 1]) ?>">
                                << Prev</a>
                                <?php else: ?>
                                    <span class="disabled">
                                        << Prev</span>
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
    <div id="createModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Create New Product</h3>
                <button class="modal-close" id="btn-error" onclick="closeCreateModal()"><img src="../images/error.png">
                </button>
            </div>
            <form id="createForm" enctype="multipart/form-data">
                <div class="modal-image-section">
                    <img id="createPreview" src="../images/default_product.jpg" alt="Preview">
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

    <div id="viewModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Product Details</h3>
                <button type="button" id="btn-error" onclick="closeViewModal('viewModal')">
                    <img src="../images/error.png">
                </button>
            </div>
            <div id="viewContent" class="modal-view-content">
                <div class="loading">Loading...</div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeViewModal()">Close</button>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Edit Product</h3>
                <button type="button" id="btn-error" onclick="closeEditModal()">
                    <img src="../images/error.png">
                </button>
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

    <div id="customAlert" class="custom-alert-overlay">
        <div class="custom-alert-box">
            <div id="customAlertIcon" class="custom-alert-icon"></div>
            <h3 id="customAlertTitle" class="custom-alert-title"></h3>
            <p id="customAlertText" class="custom-alert-text"></p>
            <div id="customAlertButtons" class="custom-alert-buttons">
                <button id="customAlertCancel" class="btn-alert btn-alert-cancel" style="display:none">Cancel</button>
                <button id="customAlertConfirm" class="btn-alert btn-alert-confirm">OK</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('sidebarToggle').onclick = () =>
            document.body.classList.toggle('sidebar-collapsed');

        function showCustomAlert(type, title, text, callback = null) {
            const overlay = document.getElementById('customAlert');
            const iconContainer = document.getElementById('customAlertIcon');
            const btnCancel = document.getElementById('customAlertCancel');
            const btnConfirm = document.getElementById('customAlertConfirm');

            document.getElementById('customAlertTitle').innerText = title;
            document.getElementById('customAlertText').innerText = text;

            iconContainer.innerHTML = '';
            const img = document.createElement('img');
            if (type === 'success') {
                img.src = '../images/success.png';
            } else if (type === 'error') {
                img.src = '../images/error.png';
            } else {
                img.src = '../images/warning.png';
            }
            iconContainer.appendChild(img);

            if (type === 'confirm') {
                btnCancel.style.display = 'block';
                btnConfirm.innerText = 'Yes, Delete';
                btnConfirm.style.backgroundColor = '#D92D20';
            } else {
                btnCancel.style.display = 'none';
                btnConfirm.innerText = 'OK';
                btnConfirm.style.backgroundColor = '#F4A261';
            }

            btnConfirm.onclick = () => {
                closeCustomAlert();
                if (callback) callback();
            };
            btnCancel.onclick = closeCustomAlert;

            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);
        }

        function closeCustomAlert() {
            const overlay = document.getElementById('customAlert');
            overlay.classList.remove('show');
            setTimeout(() => overlay.style.display = 'none', 300);
        }

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.onclick = () => {
                const productId = btn.dataset.id;
                const productName = btn.dataset.name;

                showCustomAlert('confirm', 'Delete Product?', `Are you sure you want to delete "${productName}"?`, () => {

                    const params = new URLSearchParams();
                    params.append('id', productId); // 必须匹配 PHP 里的 $_POST['id']

                    fetch('product_delete.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: params
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                showCustomAlert('success', 'Deleted!', 'The product has been removed.', () => {
                                    window.location.reload();
                                });
                            } else {
                                showCustomAlert('error', 'Error', data.error || 'Delete failed.');
                            }
                            window.location.reload();
                        })
                        .catch(err => {
                            console.error('Error:', err);
                            showCustomAlert('error', 'System Error', 'Could not connect to the server.');
                        });
                });
            };
        });

        function openCreateProduct() {
            document.getElementById('createModal').classList.remove('hidden');
            document.getElementById('createForm').reset();
            document.getElementById('createPreview').src = '../images/default_product.jpg';
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
            const formData = new FormData(e.target);
            try {
                const res = await fetch('product_create.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    closeCreateModal();
                    showCustomAlert('success', 'Success!', 'New product has been added.', () => {
                        window.location.reload();
                    });
                } else {
                    const errDiv = document.getElementById('createError');
                    errDiv.textContent = data.error;
                    errDiv.classList.remove('hidden');
                }
            } catch (err) {
                alert('Network error');
            }
        };

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
                    content.innerHTML = `
                    <table class="view-table">
                        <tr><th>Product ID</th><td>#${p.product_id}</td></tr>
                        <tr><th>Photo</th><td><img src="${p.image_path}" class="product-thumb-view" style="max-width:100px;"></td></tr>
                        <tr><th>Name</th><td>${escapeHtml(p.name)}</td></tr>
                        <tr><th>Category</th><td>${escapeHtml(p.category_name || '-')}</td></tr>
                        <tr><th>Price</th><td>RM ${parseFloat(p.price).toFixed(2)}</td></tr>
                        <tr><th>Stock</th><td>${p.stock_qty}</td></tr>
                        <tr><th>Description</th><td>${escapeHtml(p.description || '-')}</td></tr>
                    </table>`;
                } else {
                    content.innerHTML = '<div class="alert error">Product not found.</div>';
                }
            } catch (err) {
                content.innerHTML = '<div class="alert error">Error loading data.</div>';
            }
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        async function openEditProduct(id) {
            const modal = document.getElementById('editModal');
            const errorDiv = document.getElementById('editError');
            modal.classList.remove('hidden');
            errorDiv.classList.add('hidden');

            try {
                const res = await fetch(`product_get.php?id=${id}`);
                const data = await res.json();
                if (data.success) {
                    const p = data.product;

                    document.getElementById('editProductId').value = p.product_id;
                    document.getElementById('editName').value = p.name;
                    document.getElementById('editPrice').value = p.price;
                    document.getElementById('editStock').value = p.stock_qty;
                    document.getElementById('editDescription').value = p.description;
                    document.getElementById('editPreview').src = p.image_path ? p.image_path : '../images/default_product.png';
                    document.getElementById('editCategory').value = p.category_id || "";
                    document.getElementById('editSubCategory').value = p.sub_category_id || "";

                }
            } catch (err) {
                alert('Error loading product data');
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
            const formData = new FormData(e.target);
            try {
                const res = await fetch('product_edit.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    closeEditModal();
                    showCustomAlert('success', 'Updated!', 'Product details saved successfully.', () => {
                        window.location.reload();
                    });
                } else {
                    const errDiv = document.getElementById('editError');
                    errDiv.textContent = data.error;
                    errDiv.classList.remove('hidden');
                }
            } catch (err) {
                alert('Network error');
            }
        };

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        window.onclick = (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.add('hidden');
            }
        };
    </script>

</body>

</html>