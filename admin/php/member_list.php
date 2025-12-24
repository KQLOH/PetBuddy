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
                                    <button type="button"
                                        class="btn-action btn-view"
                                        data-id="<?= $m['member_id'] ?>"
                                        onclick="openViewModal(<?= $m['member_id'] ?>)">
                                        View
                                    </button>

                                    <?php if ($adminRole === 'super_admin'): ?>
                                        <button type="button"
                                            class="btn-action btn-edit"
                                            data-id="<?= $m['member_id'] ?>"
                                            onclick="openEditModal(<?= $m['member_id'] ?>)">
                                            Edit
                                        </button>

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

    <div id="viewModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Member Details</h3>
                <button type="button" class="modal-close" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="viewModalContent">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Edit Member</h3>
                <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <div class="modal-body" id="editModalContent">
                <div class="loading">Loading...</div>
            </div>
        </div>
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

        function openViewModal(memberId) {
            const modal = document.getElementById('viewModal');
            const content = document.getElementById('viewModalContent');
            modal.classList.remove('hidden');
            content.innerHTML = '<div class="loading">Loading...</div>';

            fetch(`member_get.php?id=${memberId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const member = data.member;
                        content.innerHTML = `
                            <div class="member-detail-modal">
                                <div class="member-header">
                                    <img src="${member.image_path}" class="member-avatar-lg" alt="Profile">
                                    <div>
                                        <h2>${escapeHtml(member.full_name)}</h2>
                                        ${getRoleBadge(member.role)}
                                    </div>
                                </div>
                                <div class="member-info-grid">
                                    <div>
                                        <label>Member ID</label>
                                        <p>${escapeHtml(member.member_id)}</p>
                                    </div>
                                    <div>
                                        <label>Full Name</label>
                                        <p>${escapeHtml(member.full_name)}</p>
                                    </div>
                                    <div>
                                        <label>Email</label>
                                        <p>${escapeHtml(member.email)}</p>
                                    </div>
                                    <div>
                                        <label>Phone</label>
                                        <p>${member.phone || '-'}</p>
                                    </div>
                                    <div>
                                        <label>Address</label>
                                        <p>${member.address ? escapeHtml(member.address).replace(/\n/g, '<br>') : '-'}</p>
                                    </div>
                                    <div>
                                        <label>Gender</label>
                                        <p>${member.gender ? member.gender.charAt(0).toUpperCase() + member.gender.slice(1) : '-'}</p>
                                    </div>
                                    <div>
                                        <label>Date of Birth</label>
                                        <p>${member.dob || '-'}</p>
                                    </div>
                                    <div>
                                        <label>Role</label>
                                        <p>${member.role.replace('_', ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        content.innerHTML = `<div class="error">Error: ${data.error || 'Failed to load member data'}</div>`;
                    }
                })
                .catch(error => {
                    content.innerHTML = `<div class="error">Error loading member data: ${error.message}</div>`;
                });
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.add('hidden');
        }

        function openEditModal(memberId) {
            const modal = document.getElementById('editModal');
            const content = document.getElementById('editModalContent');
            modal.classList.remove('hidden');
            content.innerHTML = '<div class="loading">Loading...</div>';

            fetch(`member_get.php?id=${memberId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const member = data.member;
                        const isSuperAdmin = <?= $adminRole === 'super_admin' ? 'true' : 'false' ?>;
                        content.innerHTML = `
                            <form id="editMemberForm" onsubmit="saveMember(event, ${memberId})">
                                <div class="member-edit-header">
                                    <img src="${member.image_path}" class="member-edit-avatar" alt="Avatar">
                                    <div class="member-edit-title">
                                        <h2>${escapeHtml(member.full_name)}</h2>
                                        ${getRoleBadge(member.role)}
                                    </div>
                                </div>
                                <div class="form-grid">
                                    <div>
                                        <label>Full Name *</label>
                                        <input type="text" name="full_name" value="${escapeHtml(member.full_name)}" required>
                                    </div>
                                    <div>
                                        <label>Email *</label>
                                        <input type="email" name="email" value="${escapeHtml(member.email)}" required>
                                    </div>
                                    <div>
                                        <label>Phone</label>
                                        <input type="text" name="phone" value="${escapeHtml(member.phone || '')}">
                                    </div>
                                    <div>
                                        <label>Date of Birth</label>
                                        <input type="date" name="dob" value="${member.dob || ''}">
                                    </div>
                                    <div class="full">
                                        <label>Address</label>
                                        <textarea name="address">${escapeHtml(member.address || '')}</textarea>
                                    </div>
                                    <div>
                                        <label>Gender</label>
                                        <select name="gender">
                                            <option value="">-- Select --</option>
                                            <option value="male" ${member.gender === 'male' ? 'selected' : ''}>Male</option>
                                            <option value="female" ${member.gender === 'female' ? 'selected' : ''}>Female</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Role</label>
                                        <input type="text" value="${member.role.replace('_', ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')}" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                                    </div>
                                    <div class="full form-section">Security</div>
                                    <div class="full">
                                        <label>Reset Password (optional)</label>
                                        <input type="password" name="password" placeholder="Min 6 characters">
                                    </div>
                                </div>
                                <div class="modal-actions">
                                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
                                    <button type="submit" class="btn-primary">Save Changes</button>
                                </div>
                            </form>
                        `;
                    } else {
                        content.innerHTML = `<div class="error">Error: ${data.error || 'Failed to load member data'}</div>`;
                    }
                })
                .catch(error => {
                    content.innerHTML = `<div class="error">Error loading member data: ${error.message}</div>`;
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function saveMember(event, memberId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('id', memberId);

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            fetch('member_edit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            closeEditModal();
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(data.error || 'Failed to update member', 'error');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(error => {
                    showToast('Error: ' + error.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getRoleBadge(role) {
            if (role === 'super_admin') {
                return '<span class="role-super-admin">Super Admin</span>';
            } else if (role === 'admin') {
                return '<span class="role-admin">Admin</span>';
            } else {
                return '<span class="role-member">Member</span>';
            }
        }

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

        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewModal();
        });

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });
    </script>
</body>

</html>