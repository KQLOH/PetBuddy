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

$categories = $pdo->query("
    SELECT category_id, name, description
    FROM product_categories
    ORDER BY category_id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories</title>
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
    </header>

    <main class="content">

        <div class="page-header">
            <div>
                <div class="page-title">Category List (<?= count($categories) ?>)</div>
                <div class="page-subtitle">Manage product categories</div>
            </div>

            <button class="btn-primary" onclick="openCreateModal()">+ New Category</button>
        </div>

        <div class="panel">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>

                <?php if (!$categories): ?>
                    <tr>
                        <td colspan="4" class="no-data">No categories found</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><?= $c['category_id'] ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['description'] ?: '-') ?></td>
                        <td class="actions">

                            <button class="btn-action btn-view"
                                    onclick="openViewModal(<?= $c['category_id'] ?>)">
                                View
                            </button>

                            <button class="btn-action btn-edit"
                                    onclick="openEditModal(<?= $c['category_id'] ?>)">
                                Edit
                            </button>

                            <button class="btn-action btn-delete"
                                    onclick="openDeleteModal(<?= $c['category_id'] ?>, '<?= htmlspecialchars($c['name']) ?>')">
                                Delete
                            </button>

                        </td>
                    </tr>
                <?php endforeach; ?>

                </tbody>
            </table>
        </div>

    </main>
</div>

<!-- ================= VIEW MODAL ================= -->
<div id="viewModal" class="modal hidden">
    <div class="modal-box modal-large">
        <div class="modal-header">
            <h3>Category Details</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewContent">
            <div class="loading">Loading...</div>
        </div>
    </div>
</div>

<!-- ================= CREATE / EDIT MODAL ================= -->
<div id="formModal" class="modal hidden">
    <div class="modal-box modal-large">
        <div class="modal-header">
            <h3 id="formTitle"></h3>
            <button class="modal-close" onclick="closeModal('formModal')">&times;</button>
        </div>

        <div class="modal-body">
            <form id="categoryForm">
                <input type="hidden" name="category_id" id="categoryId">

                <div class="form-grid">
                    <div>
                        <label>Category Name *</label>
                        <input type="text" name="name" required>
                    </div>

                    <div class="full">
                        <label>Description</label>
                        <textarea name="description"></textarea>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary"
                            onclick="closeModal('formModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= DELETE MODAL ================= -->
<div id="deleteModal" class="modal hidden">
    <div class="modal-box">
        <h3>Confirm Deletion</h3>
        <p id="deleteMessage"></p>

        <div class="modal-actions">
            <button class="btn-secondary" onclick="closeModal('deleteModal')">
                Cancel
            </button>
            <button class="btn-danger" onclick="confirmDelete()">
                Delete
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('sidebarToggle').onclick = () =>
    document.body.classList.toggle('sidebar-collapsed');

let deleteId = null;

/* ========== VIEW ========== */
function openViewModal(id) {
    openModal('viewModal');
    fetch(`category_get.php?id=${id}`)
        .then(r => r.json())
        .then(c => {
            document.getElementById('viewContent').innerHTML = `
                <div class="detail-grid">
                    <div>
                        <label>ID</label>
                        <p>${c.category_id}</p>
                    </div>
                    <div>
                        <label>Name</label>
                        <p>${c.name}</p>
                    </div>
                    <div class="full">
                        <label>Description</label>
                        <p>${c.description || '-'}</p>
                    </div>
                </div>
            `;
        });
}

/* ========== CREATE ========== */
function openCreateModal() {
    document.getElementById('formTitle').textContent = 'Add Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    openModal('formModal');
}

/* ========== EDIT ========== */
function openEditModal(id) {
    openModal('formModal');
    document.getElementById('formTitle').textContent = 'Edit Category';

    fetch(`category_get.php?id=${id}`)
        .then(r => r.json())
        .then(c => {
            document.getElementById('categoryId').value = c.category_id;
            document.querySelector('[name=name]').value = c.name;
            document.querySelector('[name=description]').value = c.description || '';
        });
}

/* ========== SAVE ========== */
document.getElementById('categoryForm').onsubmit = e => {
    e.preventDefault();
    const form = e.target;
    const url = form.category_id.value
        ? 'category_update.php'
        : 'category_create.php';

    fetch(url, {
        method: 'POST',
        body: new FormData(form)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) location.reload();
        else alert(res.error || 'Action failed');
    });
};

/* ========== DELETE ========== */
function openDeleteModal(id, name) {
    deleteId = id;
    document.getElementById('deleteMessage').textContent =
        `Are you sure you want to delete "${name}"?`;
    openModal('deleteModal');
}

function confirmDelete() {
    fetch('category_delete.php', {
        method: 'POST',
        body: new URLSearchParams({ id: deleteId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) location.reload();
        else alert(res.error);
    });
}

/* ========== MODAL HELPERS ========== */
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
}
</script>

</body>
</html>
