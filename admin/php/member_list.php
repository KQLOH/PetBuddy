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
$adminId   = $_SESSION['member_id'] ?? 0;
$roleFilter = $_GET['role'] ?? 'all';
$search     = trim($_GET['search'] ?? '');
$sql = "
    SELECT 
        m.member_id,
        m.full_name,
        m.email,
        m.phone,
        m.role,
        COUNT(o.order_id) AS order_count
    FROM members m
    LEFT JOIN orders o ON o.member_id = m.member_id
    WHERE 1
";
$params = [];

if ($roleFilter !== 'all') {
    $sql .= " AND m.role = ?";
    $params[] = $roleFilter;
}

if ($search !== '') {
    $sql .= " AND (m.full_name LIKE ? OR m.email LIKE ? OR m.phone LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}

$sql .= " GROUP BY m.member_id ORDER BY m.member_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Members</title>
    <link rel="stylesheet" href="../css/admin_member.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebarToggle" class="sidebar-toggle">☰</button>
                <div class="topbar-title">Members</div>
            </div>
        </header>

        <main class="content">
            <div class="page-header">
                <div class="page-title">Member List (<?= count($members) ?>)</div>
                <div class="page-subtitle">Manage registered users</div>
            </div>

            <form class="filter-bar" method="get">
                <select name="role">
                    <option value="all">All Roles</option>
                    <option value="super_admin" <?= $roleFilter === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="member" <?= $roleFilter === 'member' ? 'selected' : '' ?>>Member</option>
                </select>

                <input type="text"
                    name="search"
                    placeholder="Search name / email / phone"
                    value="<?= htmlspecialchars($search) ?>">

                <button class="btn-search">Search</button>
                <a href="member_list.php" class="btn-reset">Reset</a>
            </form>

            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php if (!$members): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">No members found</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td><?= $m['member_id'] ?></td>
                                <td><?= htmlspecialchars($m['full_name']) ?></td>
                                <td><?= htmlspecialchars($m['email']) ?></td>
                                <td><?= $m['phone'] ?: '-' ?></td>
                                <td>
                                    <?php if ($m['role'] === 'super_admin'): ?>
                                        <span class="role-super-admin">Super Admin</span>
                                    <?php elseif ($m['role'] === 'admin'): ?>
                                        <span class="role-admin">Admin</span>
                                    <?php else: ?>
                                        <span class="role-member">Member</span>
                                    <?php endif; ?>
                                </td>

                                <td class="actions">
                                    <a href="member_detail.php?id=<?= $m['member_id'] ?>"
                                        class="btn-action btn-view">
                                        View
                                    </a>

                                    <?php if ($adminRole === 'super_admin'): ?>
                                        <a href="member_edit.php?id=<?= $m['member_id'] ?>"
                                            class="btn-action btn-edit">
                                            Edit
                                        </a>

                                        <button
                                            type="button"
                                            class="btn-action btn-delete"
                                            data-id="<?= $m['member_id'] ?>"
                                            data-name="<?= htmlspecialchars($m['full_name']) ?>"
                                            data-self="<?= $adminId == $m['member_id'] ? '1' : '0' ?>"
                                            data-has-order="<?= $m['order_count'] > 0 ? '1' : '0' ?>">
                                            Delete
                                        </button>

                                    <?php endif; ?>

                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

        </main>
    </div>

    <div id="deleteModal" class="modal hidden">
        <div class="modal-box">
            <h3 id="modalTitle"></h3>
            <p id="modalMessage"></p>

            <div class="modal-actions">
                <button id="modalCancel" class="btn-secondary">Cancel</button>
                <form method="post" action="member_delete.php">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" class="btn-danger" id="modalConfirm">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('sidebarToggle').onclick = () =>
            document.body.classList.toggle('sidebar-collapsed');

        const modal = document.getElementById('deleteModal');
        const title = document.getElementById('modalTitle');
        const message = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('modalConfirm');
        const cancelBtn = document.getElementById('modalCancel');
        const deleteId = document.getElementById('deleteId');

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.onclick = () => {
                const isSelf = btn.dataset.self === '1';
                const hasOrder = btn.dataset.hasOrder === '1';

                modal.classList.remove('hidden');

                if (isSelf) {
                    title.textContent = 'Action not allowed';
                    message.textContent = 'You cannot delete your own account.';
                    confirmBtn.style.display = 'none';
                } else if (hasOrder) {
                    title.textContent = 'Cannot delete member';
                    message.textContent = 'This member has existing orders and cannot be deleted.';
                    confirmBtn.style.display = 'none';
                } else {
                    title.textContent = 'Confirm deletion';
                    message.textContent = `Are you sure you want to delete "${btn.dataset.name}"?`;
                    confirmBtn.style.display = 'inline-block';
                    deleteId.value = btn.dataset.id;
                }
            };
        });

        cancelBtn.onclick = () => {
            modal.classList.add('hidden');
            confirmBtn.style.display = 'inline-block';
        };
    </script>

</body>

</html>