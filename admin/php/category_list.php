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
$adminName = $_SESSION['full_name'] ?? 'Admin';

$search = trim($_GET['search'] ?? '');
$sort   = $_GET['sort'] ?? 'category_id';

$dir    = $_GET['dir'] ?? 'ASC'; 

$allowedSorts = [
    'category_id' => 'category_id',
    'name'        => 'name',
    'description' => 'description'
];

if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'category_id';
}

$dir = strtoupper($dir);
if (!in_array($dir, ['ASC', 'DESC'])) {
    $dir = 'ASC';
}

$limit  = 12;
$page   = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like);
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$countSql = "SELECT COUNT(*) FROM product_categories {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $limit);
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$sql = "
    SELECT category_id, name, description
    FROM product_categories
    {$whereSql}
    ORDER BY {$sort} {$dir}
    LIMIT {$limit} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = [];
if ($cats) {
    $catIds = array_column($cats, 'category_id');
    $inQuery = implode(',', array_fill(0, count($catIds), '?'));
    
    $subSql = "SELECT sub_category_id, category_id, name FROM sub_categories WHERE category_id IN ($inQuery) ORDER BY name ASC";
    $subStmt = $pdo->prepare($subSql);
    $subStmt->execute($catIds);
    $allSubs = $subStmt->fetchAll(PDO::FETCH_ASSOC);

    $subsMap = [];
    foreach ($allSubs as $s) {
        $subsMap[$s['category_id']][] = $s;
    }

    foreach ($cats as $c) {
        $c['subs'] = $subsMap[$c['category_id']] ?? [];
        $categories[] = $c;
    }
}

function q(array $extra = []) {
    $base = $_GET;
    foreach ($extra as $k => $v) $base[$k] = $v;
    return http_build_query($base);
}

function sortLink($columnKey, $label) {
    global $sort, $dir;
    $newDir = ($sort === $columnKey && $dir === 'ASC') ? 'DESC' : 'ASC';
    
    $iconHtml = '';
    if ($sort === $columnKey) {
        if ($dir === 'ASC') {
            $iconHtml = '<img src="../images/up.png" class="sort-icon" alt="Asc">';
        } else {
            $iconHtml = '<img src="../images/down.png" class="sort-icon" alt="Desc">';
        }
    }
    
    $url = '?' . q(['sort' => $columnKey, 'dir' => $newDir, 'p' => 1]);
    return '<a href="' . htmlspecialchars($url) . '" class="sort-link">' . $label . $iconHtml . '</a>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories - PetBuddy Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="../css/admin_product.css">
    <link rel="stylesheet" href="../css/admin_category.css">
    
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebarToggle" class="sidebar-toggle">☰</button>
                <div class="topbar-title">Categories</div>
            </div>
            <span class="tag-pill" style="margin-right: 20px;">Admin: <?= htmlspecialchars($adminName) ?></span>
        </header>

        <main class="content">

            <div class="page-header">
                <div>
                    <div class="page-title">Category List (<?= $total ?>)</div>
                    <div class="page-subtitle">Manage product categories and sub-categories</div>
                </div>
            </div>

            <form class="filter-bar" method="get">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">

                <input type="text" name="search" placeholder="Search Category Name..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn-search" type="submit">Search</button>
                <a class="btn-reset" href="category_list.php">Reset</a>

                <button type="button" class="btn-create" onclick="openCatModal()" style="margin-left: auto;">+ New Category</button>
            </form>

            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?= sortLink('category_id', 'ID') ?></th>
                            <th style="width: 200px;"><?= sortLink('name', 'Category Name') ?></th>
                            <th><?= sortLink('description', 'Description') ?></th>
                            <th>Subcategories</th>
                            <th style="width: 160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="5" class="no-data">No categories found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td style="color:#666;">#<?= $c['category_id'] ?></td>
                                    
                                    <td style="font-weight:600; color:#344054;">
                                        <?= htmlspecialchars($c['name']) ?>
                                    </td>
                                    
                                    <td style="font-size:13px; color:#666;">
                                        <?= htmlspecialchars($c['description'] ?: '-') ?>
                                    </td>
                                    
                                    <td>
                                        <?php foreach ($c['subs'] as $sub): ?>
                                            <span class="sub-tag">
                                                <?= htmlspecialchars($sub['name']) ?>
                                                <span class="remove-sub" onclick="deleteSub(<?= $sub['sub_category_id'] ?>, '<?= htmlspecialchars($sub['name']) ?>')">&times;</span>
                                            </span>
                                        <?php endforeach; ?>
                                        <button class="btn-add-sub" onclick="openSubModal(<?= $c['category_id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">+ Add</button>
                                    </td>
                                    
                                    <td class="actions">
                                        <button class="btn-action btn-edit" onclick="openCatModal(<?= $c['category_id'] ?>)">Edit</button>
                                        <button class="btn-action btn-delete" onclick="deleteCat(<?= $c['category_id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                                <a class="<?= $i == $page ? 'current' : '' ?>" href="?<?= q(['p' => $i]) ?>"><?= $i ?></a>
                            <?php elseif (($i == $page - 3) || ($i == $page + 3)): ?>
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

    <div id="catModal" class="modal hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="catModalTitle" style="margin:0; font-weight:700;">Category</h3>
                <button class="btn-secondary" style="border:none; background:none; font-size:24px; cursor:pointer;" onclick="closeModal('catModal')">&times;</button>
            </div>
            <form id="catForm">
                <div class="modal-body" style="padding:20px 0;">
                    <input type="hidden" name="category_id" id="catId">
                    <div style="margin-bottom:15px;">
                        <label style="font-weight:600; font-size:13px; color:#344054;">Name *</label>
                        <input type="text" name="name" id="catName" required style="width:100%; padding:10px; margin-top:5px; border:1px solid #D0D5DD; border-radius:6px;">
                    </div>
                    <div>
                        <label style="font-weight:600; font-size:13px; color:#344054;">Description</label>
                        <textarea name="description" id="catDesc" style="width:100%; padding:10px; margin-top:5px; height:80px; border:1px solid #D0D5DD; border-radius:6px; resize:vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-actions" style="text-align:right;">
                    <button type="button" class="btn-secondary" onclick="closeModal('catModal')">Cancel</button>
                    <button type="submit" class="btn-create" style="border:none;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="subModal" class="modal hidden">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="margin:0; font-weight:700;">Add Subcategory</h3>
                <button class="btn-secondary" style="border:none; background:none; font-size:24px; cursor:pointer;" onclick="closeModal('subModal')">&times;</button>
            </div>
            <form id="subForm">
                <div class="modal-body" style="padding:20px 0;">
                    <p style="margin-bottom:15px; color:#666; font-size:14px;">Adding to: <strong id="parentCatName" style="color:var(--primary-color);"></strong></p>
                    <input type="hidden" name="category_id" id="parentCatId">
                    <label style="font-weight:600; font-size:13px; color:#344054;">Subcategory Name *</label>
                    <input type="text" name="name" required style="width:100%; padding:10px; margin-top:5px; border:1px solid #D0D5DD; border-radius:6px;">
                </div>
                <div class="modal-actions" style="text-align:right;">
                    <button type="button" class="btn-secondary" onclick="closeModal('subModal')">Cancel</button>
                    <button type="submit" class="btn-create" style="border:none;">Add Subcategory</button>
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
    document.getElementById('sidebarToggle').onclick = () => document.body.classList.toggle('sidebar-collapsed');
    
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }

    function showCustomAlert(type, title, text, callback = null) {
        const overlay = document.getElementById('customAlert');
        const icon = document.getElementById('customAlertIcon');
        const btnCancel = document.getElementById('customAlertCancel');
        const btnConfirm = document.getElementById('customAlertConfirm');
        
        document.getElementById('customAlertTitle').innerText = title;
        document.getElementById('customAlertText').innerText = text;
        
        icon.className = 'custom-alert-icon';
        if (type === 'success') { icon.classList.add('icon-success'); icon.innerHTML = '✓'; }
        else if (type === 'error') { icon.classList.add('icon-error'); icon.innerHTML = '✕'; }
        else { icon.classList.add('icon-confirm'); icon.innerHTML = '?'; }
        
        if (type === 'confirm') {
            btnCancel.style.display = 'block';
            btnConfirm.innerText = 'Yes, Delete';
            btnConfirm.className = 'btn-alert btn-alert-confirm'; 
            btnConfirm.style.backgroundColor = '#D92D20';
            
            btnConfirm.onclick = () => {
                closeCustomAlert();
                if (callback) callback();
            };
            btnCancel.onclick = closeCustomAlert;
        } else {
            btnCancel.style.display = 'none';
            btnConfirm.innerText = 'OK';
            btnConfirm.style.backgroundColor = '#F4A261';
            btnConfirm.onclick = closeCustomAlert;
        }
        
        overlay.style.display = 'flex';
        setTimeout(() => overlay.classList.add('show'), 10);
    }

    function closeCustomAlert() {
        const overlay = document.getElementById('customAlert');
        overlay.classList.remove('show');
        setTimeout(() => overlay.style.display = 'none', 300);
    }

    function openCatModal(id = null) {
        document.getElementById('catForm').reset();
        document.getElementById('catId').value = '';
        document.getElementById('catModalTitle').textContent = id ? 'Edit Category' : 'New Category';
        
        if (id) {
            fetch(`category_get.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if(data.error) {
                        showCustomAlert('error', 'Error', data.error);
                    } else {
                        document.getElementById('catId').value = data.category_id;
                        document.getElementById('catName').value = data.name;
                        document.getElementById('catDesc').value = data.description || '';
                        openModal('catModal');
                    }
                })
                .catch(err => showCustomAlert('error', 'System Error', 'Failed to fetch data. Ensure category_get.php exists.'));
        } else {
            openModal('catModal');
        }
    }

    document.getElementById('catForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const url = document.getElementById('catId').value ? 'category_update.php' : 'category_create.php';
        
        fetch(url, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    showCustomAlert('success', 'Success', 'Category saved successfully!');
                    closeModal('catModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showCustomAlert('error', 'Error', res.error);
                }
            });
    };

    function deleteCat(id, name) {
        showCustomAlert('confirm', 'Delete Category?', `Are you sure you want to delete "${name}"? This cannot be undone.`, () => {
            fetch('category_delete.php', {
                method: 'POST',
                body: new URLSearchParams({id: id})
            }).then(r => r.json()).then(res => {
                if(res.success) {
                    showCustomAlert('success', 'Deleted', 'Category deleted.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showCustomAlert('error', 'Cannot Delete', res.error);
                }
            });
        });
    }

    function openSubModal(catId, catName) {
        document.getElementById('subForm').reset();
        document.getElementById('parentCatId').value = catId;
        document.getElementById('parentCatName').textContent = catName;
        openModal('subModal');
    }

    document.getElementById('subForm').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('subcategory_create.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    showCustomAlert('success', 'Success', 'Subcategory added!');
                    closeModal('subModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showCustomAlert('error', 'Error', res.error);
                }
            });
    };

    function deleteSub(id, name) {
        showCustomAlert('confirm', 'Delete Subcategory?', `Remove "${name}"?`, () => {
            fetch('subcategory_delete.php', {
                method: 'POST',
                body: new URLSearchParams({id: id})
            }).then(r => r.json()).then(res => {
                if(res.success) {
                    showCustomAlert('success', 'Deleted', 'Subcategory removed.');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showCustomAlert('error', 'Error', res.error);
                }
            });
        });
    }
    
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.add('hidden');
        }
    }
</script>
</body>
</html>