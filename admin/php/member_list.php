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
$adminName = $_SESSION['full_name'] ?? 'Admin';
$roleFilter = $_GET['role'] ?? 'all';
$search     = trim($_GET['search'] ?? '');

$sort = $_GET['sort'] ?? 'member_id';
$dir  = $_GET['dir'] ?? 'DESC';

$allowedSorts = [
    'member_id'   => 'm.member_id',
    'full_name'   => 'm.full_name',
    'email'       => 'm.email',
    'phone'       => 'm.phone',
    'role'        => 'm.role'
];

if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'member_id';
}
$sortSqlColumn = $allowedSorts[$sort];

$dir = strtoupper($dir);
if (!in_array($dir, ['ASC', 'DESC'])) {
    $dir = 'DESC';
}

$limit = 12;
$page  = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($roleFilter !== 'all') {
    $where[] = "m.role = ?";
    $params[] = $roleFilter;
}

if ($search !== '') {
    $where[] = "(m.full_name LIKE ? OR m.email LIKE ? OR m.phone LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$countSql = "
    SELECT COUNT(DISTINCT m.member_id)
    FROM members m
    LEFT JOIN orders o ON o.member_id = m.member_id
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
        m.member_id,
        m.full_name,
        m.email,
        m.phone,
        m.role,
        COUNT(o.order_id) AS order_count
    FROM members m
    LEFT JOIN orders o ON o.member_id = m.member_id
    {$whereSql}
    GROUP BY m.member_id
    ORDER BY {$sortSqlColumn} {$dir}
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

function q(array $extra = [])
{
    $base = $_GET;
    foreach ($extra as $k => $v) $base[$k] = $v;
    return http_build_query($base);
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Members</title>
    <link rel="stylesheet" href="../css/admin_member.css">
    <link rel="stylesheet" href="../css/admin_btn.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebarToggle" class="sidebar-toggle"><img src="../images/menu.png"></button>
                <div class="topbar-title">Members</div>
            </div>
            <span class="tag-pill" style="margin-right: 20px;">Admin: <?= htmlspecialchars($adminName) ?></span>
        </header>

        <main class="content">
            <div class="page-header">
                <div class="page-title">Member List (<?= (int)$total ?>)</div>
                <div class="page-subtitle">Manage registered users</div>
            </div>

            <form class="filter-bar" method="get">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">

                <select name="role" onchange="this.form.submit()">
                    <option value="all">All Roles</option>
                    <option value="super_admin" <?= $roleFilter === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="member" <?= $roleFilter === 'member' ? 'selected' : '' ?>>Member</option>
                </select>

                <input type="text"
                    name="search"
                    placeholder="Search name / email / phone"
                    value="<?= htmlspecialchars($search) ?>">

                <button class="btn-search" type="submit">Search</button>
                <a href="member_list.php" class="btn-reset">Reset</a>
                <?php if ($adminRole === 'super_admin'): ?>
                    <button type="button" class="btn-pill-add" style="margin-left: auto;" onclick="openAddAdminModal()">
                        <span class="icon">+</span> Add Admin
                    </button>
                <?php endif; ?>
            </form>

            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th><?= sortLink('member_id', 'ID') ?></th>
                            <th><?= sortLink('full_name', 'Name') ?></th>
                            <th><?= sortLink('email', 'Email') ?></th>
                            <th><?= sortLink('phone', 'Phone') ?></th>
                            <th><?= sortLink('role', 'Role') ?></th>
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
                            <tr data-member-id="<?= $m['member_id'] ?>">
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

    <div id="viewModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Member Details</h3>
                <button type="button" class="modal-close" id="btn-error" onclick="closeViewModal()">
                    <img src="../images/error.png">
                </button>
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
                <button type="button" class="modal-close" id="btn-error" onclick="closeEditModal()">
                    <img src="../images/error.png">
                </button>
            </div>
            <div class="modal-body" id="editModalContent">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>

    <div id="addAdminModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Add New Admin</h3>
                <button type="button" class="modal-close" id="btn-error" onclick="closeAddAdminModal()">
                    <img src="../images/error.png">
                </button>
            </div>
            <form id="addAdminForm" onsubmit="saveAdmin(event)" enctype="multipart/form-data">
                <div class="modal-image-section">
                    <img id="admin-preview-img" src="../images/default_product.jpg" alt="Preview">
                    <label class="upload-btn">
                        Upload Image
                        <input type="file" name="profile_image" id="admin_profile_image" accept="image/*" hidden onchange="handleAdminImageSelect(this)">
                    </label>
                </div>
                <div class="modal-form-grid">
                    <div>
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required placeholder="Enter full name">
                    </div>
                    <div>
                        <label>Email Address *</label>
                        <input type="email" name="email" required placeholder="admin@petbuddy.com">
                    </div>
                    <div>
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="Enter phone number">
                    </div>
                    <div>
                        <label>Date of Birth</label>
                        <input type="date" name="dob">
                    </div>
                    <div class="full">
                        <label>Address</label>
                        <textarea name="address" placeholder="Enter address"></textarea>
                    </div>
                    <div>
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">-- Select --</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label>Password *</label>
                        <input type="password" name="password" id="admin_password" required placeholder="Min 8 characters" oninput="validatePassword()">
                        <div class="error-message" id="password-error"></div>
                        <div style="font-size: 11px; color: #777; margin-top: 5px;">
                            Must contain: uppercase, lowercase, number, and special character
                        </div>
                    </div>
                    <div>
                        <label>Confirm Password *</label>
                        <input type="password" name="confirm_password" id="admin_confirm_password" required placeholder="Confirm password" oninput="validatePasswordMatch()">
                        <div class="error-message" id="confirm-password-error"></div>
                    </div>
                    <div>
                        <label>Assigned Role</label>
                        <input type="text" value="Admin" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                        <input type="hidden" name="role" value="admin">
                    </div>
                </div>
                <div class="error-message" id="admin-image-error"></div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeAddAdminModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Save Administrator</button>
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
                            <form id="editMemberForm" onsubmit="saveMember(event, ${memberId})" enctype="multipart/form-data">
                                <div class="modal-image-section">
                                    <img id="edit-member-preview-img" src="${member.image_path}" alt="Preview">
                                    <label class="upload-btn">
                                        Upload New Image
                                        <input type="file" name="profile_image" id="edit_member_profile_image" accept="image/*" hidden onchange="handleEditMemberImageSelect(this)">
                                    </label>
                                </div>
                                <div class="modal-form-grid">
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
                                    <div>
                                        <label>Reset Password (optional)</label>
                                        <input type="password" name="password" id="edit_member_password" placeholder="Min 8 characters" oninput="validateEditMemberPassword()">
                                        <div class="error-message" id="edit-member-password-error"></div>
                                        <div style="font-size: 11px; color: #777; margin-top: 5px;">
                                            Must contain: uppercase, lowercase, number, and special character
                                        </div>
                                    </div>
                                    <div>
                                        <label>Confirm Password</label>
                                        <input type="password" name="confirm_password" id="edit_member_confirm_password" placeholder="Confirm password" oninput="validateEditMemberPasswordMatch()">
                                        <div class="error-message" id="edit-member-confirm-password-error"></div>
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

        function handleEditMemberImageSelect(input) {
            const file = input.files[0];
            const errorDiv = document.getElementById('edit-member-image-error');
            const previewImg = document.getElementById('edit-member-preview-img');

            if (errorDiv) errorDiv.textContent = '';

            if (file) {
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    if (errorDiv) errorDiv.textContent = 'Please upload JPG, PNG, or GIF files only.';
                    input.value = '';
                    return;
                }

                if (file.size > 5000000) {
                    if (errorDiv) errorDiv.textContent = 'Image file is too large. Maximum size is 5MB.';
                    input.value = '';
                    return;
                }

                previewImg.src = URL.createObjectURL(file);
            }
        }

        function openAddAdminModal() {
            const modal = document.getElementById('addAdminModal');
            modal.classList.remove('hidden');
            document.getElementById('addAdminForm').reset();
            document.getElementById('admin-preview-img').src = '../images/default_product.jpg';
            document.getElementById('password-error').textContent = '';
            document.getElementById('confirm-password-error').textContent = '';
            document.getElementById('admin-image-error').textContent = '';
        }

        function closeAddAdminModal() {
            document.getElementById('addAdminModal').classList.add('hidden');
            document.getElementById('addAdminForm').reset();
            document.getElementById('admin-preview-img').src = '../images/default_product.jpg';
            document.getElementById('password-error').textContent = '';
            document.getElementById('confirm-password-error').textContent = '';
            document.getElementById('admin-image-error').textContent = '';
        }

        function handleAdminImageSelect(input) {
            const file = input.files[0];
            const errorDiv = document.getElementById('admin-image-error');
            const previewImg = document.getElementById('admin-preview-img');

            errorDiv.textContent = '';

            if (file) {
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    errorDiv.textContent = 'Please upload JPG, PNG, or GIF files only.';
                    input.value = '';
                    previewImg.src = '../images/default_product.jpg';
                    return;
                }

                if (file.size > 5000000) {
                    errorDiv.textContent = 'Image file is too large. Maximum size is 5MB.';
                    input.value = '';
                    previewImg.src = '../images/default_product.jpg';
                    return;
                }

                previewImg.src = URL.createObjectURL(file);
            } else {
                previewImg.src = '../images/default-avatar.png';
            }
        }

        function validatePassword() {
            const password = document.getElementById('admin_password').value;
            const errorDiv = document.getElementById('password-error');

            if (password === '') {
                errorDiv.textContent = '';
                return false;
            }

            if (password.length < 8) {
                errorDiv.textContent = 'Password must be at least 8 characters long.';
                return false;
            }

            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
            if (!passwordRegex.test(password)) {
                errorDiv.textContent = 'Password must contain: uppercase, lowercase, number, and special character.';
                return false;
            }

            errorDiv.textContent = '';
            validatePasswordMatch();
            return true;
        }

        function validatePasswordMatch() {
            const password = document.getElementById('admin_password').value;
            const confirmPassword = document.getElementById('admin_confirm_password').value;
            const errorDiv = document.getElementById('confirm-password-error');

            if (confirmPassword === '') {
                errorDiv.textContent = '';
                return false;
            }

            if (password !== confirmPassword) {
                errorDiv.textContent = 'Passwords do not match.';
                return false;
            }

            errorDiv.textContent = '';
            return true;
        }

        function saveAdmin(event) {
            event.preventDefault();
            const form = event.target;
            
            if (!validatePassword()) {
                showCustomAlert('error', 'Validation Error', 'Please fix password errors before submitting.');
                return;
            }

            if (!validatePasswordMatch()) {
                showCustomAlert('error', 'Validation Error', 'Passwords do not match.');
                return;
            }

            const formData = new FormData(form);

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';

            fetch('admin_add.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeAddAdminModal();
                        showCustomAlert('success', 'Success!', 'New admin has been added successfully.', () => {
                            window.location.reload();
                        });
                    } else {
                        showCustomAlert('error', 'Error', data.error || 'Failed to add admin');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(error => {
                    showCustomAlert('error', 'System Error', 'Could not connect to the server.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        }

        function validateEditMemberPassword() {
            const passwordInput = document.getElementById('edit_member_password');
            const errorDiv = document.getElementById('edit-member-password-error');
            
            if (!passwordInput || !errorDiv) return true;

            const password = passwordInput.value;

            if (password === '') {
                errorDiv.textContent = '';
                return true;
            }

            if (password.length < 8) {
                errorDiv.textContent = 'Password must be at least 8 characters long.';
                return false;
            }

            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
            if (!passwordRegex.test(password)) {
                errorDiv.textContent = 'Password must contain: uppercase, lowercase, number, and special character.';
                return false;
            }

            errorDiv.textContent = '';
            validateEditMemberPasswordMatch();
            return true;
        }

        function validateEditMemberPasswordMatch() {
            const passwordInput = document.getElementById('edit_member_password');
            const confirmPasswordInput = document.getElementById('edit_member_confirm_password');
            const errorDiv = document.getElementById('edit-member-confirm-password-error');
            
            if (!passwordInput || !confirmPasswordInput || !errorDiv) return true;

            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (password === '' && confirmPassword === '') {
                errorDiv.textContent = '';
                return true;
            }

            if (password !== '' && confirmPassword === '') {
                errorDiv.textContent = 'Please confirm your password.';
                return false;
            }

            if (confirmPassword !== '' && password === '') {
                errorDiv.textContent = 'Please enter password first.';
                return false;
            }

            if (password !== confirmPassword) {
                errorDiv.textContent = 'Passwords do not match.';
                return false;
            }

            errorDiv.textContent = '';
            return true;
        }

        function saveMember(event, memberId) {
            event.preventDefault();
            const form = event.target;
            
            const passwordInput = document.getElementById('edit_member_password');
            const confirmPasswordInput = document.getElementById('edit_member_confirm_password');
            
            if (passwordInput && passwordInput.value !== '') {
                if (!validateEditMemberPassword()) {
                    showCustomAlert('error', 'Validation Error', 'Please fix password errors before submitting.');
                    return;
                }

                if (!validateEditMemberPasswordMatch()) {
                    showCustomAlert('error', 'Validation Error', 'Passwords do not match.');
                    return;
                }
            } else if (confirmPasswordInput && confirmPasswordInput.value !== '') {
                showCustomAlert('error', 'Validation Error', 'Please enter password first.');
                return;
            }

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
                        closeEditModal();
                        showCustomAlert('success', 'Updated!', 'Member details saved successfully.', () => {
                            window.location.reload();
                        });
                    } else {
                        showCustomAlert('error', 'Error', data.error || 'Failed to update member');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(error => {
                    showCustomAlert('error', 'System Error', 'Could not connect to the server.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        }

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

        function removeMemberRow(memberId) {
            const row = document.querySelector(`tr[data-member-id="${memberId}"]`);
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    const tbody = document.querySelector('table tbody');
                    const remainingRows = tbody.querySelectorAll('tr[data-member-id]');
                    if (remainingRows.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No members found</td></tr>';
                    }
                    updateMemberCount();
                }, 300);
            }
        }

        function updateMemberCount() {
            const tbody = document.querySelector('table tbody');
            const rows = tbody.querySelectorAll('tr[data-member-id]');
            const pageTitle = document.querySelector('.page-title');
            if (pageTitle) {
                const count = rows.length;
                pageTitle.textContent = `Member List (${count})`;
            }
        }

        function setupDeleteButtons() {
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.onclick = () => {
                    const memberId = btn.dataset.id;
                    const memberName = btn.dataset.name;
                    const isSelf = btn.dataset.self === '1';
                    const hasOrder = btn.dataset.hasOrder === '1';

                    if (isSelf) {
                        showCustomAlert('error', 'Action not allowed', 'You cannot delete your own account.');
                    } else if (hasOrder) {
                        showCustomAlert('error', 'Cannot delete member', 'This member has existing orders and cannot be deleted.');
                    } else {
                        showCustomAlert('confirm', 'Delete Member?', `Are you sure you want to delete "${memberName}"?`, () => {
                            const params = new URLSearchParams();
                            params.append('id', memberId);

                            fetch('member_delete.php', {
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
                                        removeMemberRow(memberId);
                                        showCustomAlert('success', 'Deleted!', 'The member has been removed.');
                                    } else {
                                        showCustomAlert('error', 'Error', data.error || 'Delete failed.');
                                    }
                                })
                                .catch(err => {
                                    console.error('Error:', err);
                                    showCustomAlert('error', 'System Error', 'Could not connect to the server.');
                                });
                        });
                    }
                };
            });
        }

        setupDeleteButtons();

        document.getElementById('viewModal').addEventListener('click', function(e) {
            if (e.target === this) closeViewModal();
        });

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) closeEditModal();
        });

        document.getElementById('addAdminModal').addEventListener('click', function(e) {
            if (e.target === this) closeAddAdminModal();
        });

        function setStatus(input, isValid, msg) {
            const $input = $(input);
            $input.next('.validation-msg').remove(); 

            if (!isValid) {
                $input.addClass('input-error');
                $input.after('<span class="validation-msg">' + msg + '</span>');
            } else {
                $input.removeClass('input-error');
            }
            return isValid;
        }

        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function validatePassword(password) {
            const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
            return re.test(password);
        }

        $(document).ready(function() {

            $('#addAdminForm input').on('input blur', function() {
                const name = $(this).attr('name');
                const val = $(this).val().trim();

                if (name === 'full_name') setStatus(this, val !== '', 'Full name is required');
                
                if (name === 'email') setStatus(this, validateEmail(val), 'Invalid email format');
                
                if (name === 'password') {
                    setStatus(this, validatePassword(val), 'Weak password (Min 8, Upper, Lower, Number, Special)');
                    const confirmVal = $('#admin_confirm_password').val();
                    if(confirmVal !== '') setStatus($('#admin_confirm_password'), confirmVal === val, 'Passwords do not match');
                }
                
                if (name === 'confirm_password') {
                    const original = $('#admin_password').val();
                    setStatus(this, val === original, 'Passwords do not match');
                }
            });

            $('#addAdminForm').on('submit', function(e) {
                e.preventDefault(); 
                let valid = true;

                const $name = $(this).find('input[name="full_name"]');
                if (!setStatus($name, $name.val().trim() !== '', 'Full name is required')) valid = false;

                const $email = $(this).find('input[name="email"]');
                if (!setStatus($email, validateEmail($email.val().trim()), 'Invalid email format')) valid = false;

                const $pass = $('#admin_password');
                const passVal = $pass.val();
                if (!setStatus($pass, validatePassword(passVal), 'Weak password')) valid = false;

                const $confirm = $('#admin_confirm_password');
                if (!setStatus($confirm, $confirm.val() === passVal && passVal !== '', 'Passwords do not match')) valid = false;

                if (valid) {
                    performAjaxSaveAdmin(this);
                }
            });

            $(document).on('input blur', '#editMemberForm input', function() {
                const name = $(this).attr('name');
                const val = $(this).val().trim();

                if (name === 'full_name') setStatus(this, val !== '', 'Full name is required');
                if (name === 'email') setStatus(this, validateEmail(val), 'Invalid email format');
                
                if (name === 'password' || name === 'confirm_password') {
                    const $pass = $('#edit_member_password');
                    const $confirm = $('#edit_member_confirm_password');
                    const pVal = $pass.val();
                    const cVal = $confirm.val();

                    if (pVal !== '') {
                        if(name === 'password') setStatus($pass, validatePassword(pVal), 'Weak password');
                        if (cVal !== '') setStatus($confirm, cVal === pVal, 'Mismatch');
                    } else {
                        $pass.removeClass('input-error').next('.validation-msg').remove();
                        $confirm.removeClass('input-error').next('.validation-msg').remove();
                    }
                }
            });

            $(document).on('submit', '#editMemberForm', function(e) {
                e.preventDefault();
                let valid = true;
                const form = this;
                const memberId = $(form).data('id');

                const $name = $(form).find('input[name="full_name"]');
                if (!setStatus($name, $name.val().trim() !== '', 'Full name is required')) valid = false;

                const $email = $(form).find('input[name="email"]');
                if (!setStatus($email, validateEmail($email.val().trim()), 'Invalid email format')) valid = false;

                const $pass = $('#edit_member_password');
                const $confirm = $('#edit_member_confirm_password');
                const pVal = $pass.val();
                const cVal = $confirm.val();

                if (pVal !== '') {
                    if (!setStatus($pass, validatePassword(pVal), 'Weak password')) valid = false;
                    if (!setStatus($confirm, cVal === pVal, 'Passwords do not match')) valid = false;
                } else if (cVal !== '') {
                    setStatus($pass, false, 'Enter password first');
                    valid = false;
                }

                if (valid) {
                    performAjaxSaveMember(form, memberId);
                }
            });

        });

        function performAjaxSaveAdmin(form) {
            const formData = new FormData(form);
            const $btn = $(form).find('button[type="submit"]');
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');

            fetch('admin_add.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeAddAdminModal();
                        showCustomAlert('success', 'Success!', 'New admin added.', () => window.location.reload());
                    } else {
                        showCustomAlert('error', 'Error', data.error || 'Failed');
                        $btn.prop('disabled', false).text(originalText);
                    }
                })
                .catch(() => {
                    showCustomAlert('error', 'System Error', 'Connection failed');
                    $btn.prop('disabled', false).text(originalText);
                });
        }

        function performAjaxSaveMember(form, memberId) {
            const formData = new FormData(form);
            formData.append('id', memberId);
            const $btn = $(form).find('button[type="submit"]');
            const originalText = $btn.text();
            $btn.prop('disabled', true).text('Saving...');

            fetch('member_edit.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeEditModal();
                        showCustomAlert('success', 'Updated!', 'Details saved.', () => window.location.reload());
                    } else {
                        showCustomAlert('error', 'Error', data.error || 'Failed');
                        $btn.prop('disabled', false).text(originalText);
                    }
                })
                .catch(() => {
                    showCustomAlert('error', 'System Error', 'Connection failed');
                    $btn.prop('disabled', false).text(originalText);
                });
        }
    </script>
</body>

</html>