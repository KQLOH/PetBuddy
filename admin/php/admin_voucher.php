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
$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;


$sort = $_GET['sort'] ?? 'voucher_id';
$dir  = strtoupper($_GET['dir'] ?? 'DESC');
$allowedSorts = [
    'voucher_id'      => 'voucher_id',
    'code'            => 'code',
    'discount_amount' => 'discount_amount',
    'end_date'        => 'end_date'
];
if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'voucher_id';
}
$sortSqlColumn = $allowedSorts[$sort];

if (!in_array($dir, ['ASC', 'DESC'])) {
    $dir = 'DESC';
}

$params = [];
$whereSql = "";
if ($search !== '') {
    $whereSql = " WHERE code LIKE ? ";
    $params[] = "%$search%";
}
    
$countSql = "SELECT COUNT(*) FROM vouchers $whereSql";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = (int)ceil($total / $limit);
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}


$sql = "SELECT * FROM vouchers $whereSql ORDER BY $sortSqlColumn $dir LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Vouchers</title>
    <link rel="stylesheet" href="../css/admin_product.css">
    <link rel="stylesheet" href="../css/admin_btn.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebarToggle" class="sidebar-toggle"><img src="../images/menu.png"></button>
                <div class="topbar-title">Vouchers</div>
            </div>
            <span class="tag-pill" style="margin-right: 20px;">Admin: <?= htmlspecialchars($adminName) ?></span>
        </header>

        <main class="content">
            <div class="page-header">
                <div class="page-title">Voucher List (<?= (int)$total ?>)</div>
                <div class="page-subtitle">Create and manage discount codes</div>
            </div>

            <form class="filter-bar" method="get">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">

                <input type="text"
                    name="search"
                    placeholder="Search by code..."
                    value="<?= htmlspecialchars($search) ?>">

                <button class="btn-search" type="submit">Search</button>
                <a href="admin_voucher.php" class="btn-reset">Reset</a>

                <button type="button" class="btn-create" onclick="openCreateVoucherModal()">
                    + New Voucher
                </button>
            </form>

            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th><?= sortLink('voucher_id', 'ID') ?></th>
                            <th><?= sortLink('code', 'Code') ?></th>
                            <th><?= sortLink('discount_amount', 'Discount (RM)') ?></th>
                            <th>Min Spend (RM)</th>
                            <th><?= sortLink('end_date', 'Expiry Date') ?></th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$vouchers): ?>
                            <tr>
                                <td colspan="7" class="no-data">No vouchers found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($vouchers as $v):
                            $isExpired = strtotime($v['end_date']) < time();
                        ?>
                            <tr data-voucher-id="<?= $v['voucher_id'] ?>">
                                <td><?= $v['voucher_id'] ?></td>
                                <td style="font-weight: bold; color: #2E7D32;"><?= htmlspecialchars($v['code']) ?></td>
                                <td><?= number_format($v['discount_amount'], 2) ?></td>
                                <td><?= number_format($v['min_amount'], 2) ?></td>
                                <td><?= date('Y-m-d', strtotime($v['end_date'])) ?></td>
                                <td>
                                    <span class="<?= $isExpired ? 'stock-out' : 'stock-ok' ?>">
                                        <?= $isExpired ? 'Expired' : 'Active' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn-action btn-edit" onclick="openEditVoucherModal(<?= $v['voucher_id'] ?>)">
                                        Edit
                                    </button>

                                    <button type="button" class="btn-action btn-delete"
                                        data-id="<?= $v['voucher_id'] ?>"
                                        data-code="<?= htmlspecialchars($v['code']) ?>">
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
                <h3>Create New Voucher</h3>
                <button class="modal-close" id="btn-error" onclick="closeCreateVoucherModal()">
                    <img src="../images/error.png">
                </button>
            </div>
            <form id="createForm" onsubmit="saveVoucher(event)">
                <div class="modal-body">
                    <div class="modal-form-grid">
                        <div class="full">
                            <label>Voucher Code *</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="code" id="v_code_input" required placeholder="e.g. PETBUDDY88" style="flex: 1;">
                                <button type="button" onclick="generateVoucherCode()" class="btn-secondary">Auto Generate</button>
                            </div>
                        </div>
                        <div>
                            <label>Discount Amount (RM) *</label>
                            <input type="number" step="0.01" name="discount_amount" required placeholder="0.00">
                        </div>
                        <div>
                            <label>Min Spend (RM) *</label>
                            <input type="number" step="0.01" name="min_amount" value="0.00" required>
                        </div>
                        <div>
                            <label>Start Date *</label>
                            <input type="date" name="start_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div>
                            <label>End Date *</label>
                            <input type="date" name="end_date" required>
                        </div>
                    </div>
                </div>
                <div id="createError" class="alert error hidden"></div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeCreateVoucherModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Create Voucher</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal hidden">
        <div class="modal-box modal-large">
            <div class="modal-header">
                <h3>Edit Voucher</h3>
                <button type="button" class="modal-close" id="btn-error" onclick="closeEditVoucherModal()">
                    <img src="../images/error.png">
                </button>
            </div>
            <form id="editForm" onsubmit="saveEditVoucher(event)">
                <input type="hidden" name="voucher_id" id="editVoucherId">
                <div class="modal-body">
                    <div class="modal-form-grid">
                        <div class="full">
                            <label>Voucher Code *</label>
                            <input type="text" name="code" id="editCode" required>
                        </div>
                        <div>
                            <label>Discount Amount (RM) *</label>
                            <input type="number" step="0.01" name="discount_amount" id="editDiscount" required>
                        </div>
                        <div>
                            <label>Min Spend (RM) *</label>
                            <input type="number" step="0.01" name="min_amount" id="editMinAmount" required>
                        </div>
                        <div>
                            <label>Start Date *</label>
                            <input type="date" name="start_date" id="editStartDate" required>
                        </div>
                        <div>
                            <label>End Date *</label>
                            <input type="date" name="end_date" id="editEndDate" required>
                        </div>
                    </div>
                </div>
                <div id="editError" class="alert error hidden"></div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeEditVoucherModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Update Voucher</button>
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

        function openCreateVoucherModal() {
            document.getElementById('createModal').classList.remove('hidden');
            document.getElementById('createForm').reset();
            document.getElementById('createError').classList.add('hidden');
        }

        function closeCreateVoucherModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function generateVoucherCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('v_code_input').value = code;
        }

        function saveVoucher(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Creating...';

            fetch('admin_voucher_save.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeCreateVoucherModal();
                        showCustomAlert('success', 'Success!', 'New voucher has been created.', () => {
                            window.location.reload();
                        });
                    } else {
                        const errDiv = document.getElementById('createError');
                        errDiv.textContent = data.message || data.error || 'Failed to create voucher';
                        errDiv.classList.remove('hidden');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(err => {
                    showCustomAlert('error', 'System Error', 'Could not connect to the server.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        }

        function openEditVoucherModal(id) {
            const modal = document.getElementById('editModal');
            const errorDiv = document.getElementById('editError');
            modal.classList.remove('hidden');
            errorDiv.classList.add('hidden');

            fetch(`admin_voucher_get.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.voucher) {
                        const v = data.voucher;
                        document.getElementById('editVoucherId').value = v.voucher_id;
                        document.getElementById('editCode').value = v.code;
                        document.getElementById('editDiscount').value = v.discount_amount;
                        document.getElementById('editMinAmount').value = v.min_amount;
                        document.getElementById('editStartDate').value = v.start_date;
                        document.getElementById('editEndDate').value = v.end_date;
                    } else {
                        showCustomAlert('error', 'Error', 'Failed to load voucher data.');
                        closeEditVoucherModal();
                    }
                })
                .catch(err => {
                    showCustomAlert('error', 'System Error', 'Could not connect to the server.');
                    closeEditVoucherModal();
                });
        }

        function closeEditVoucherModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function saveEditVoucher(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';

            fetch('admin_voucher_edit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeEditVoucherModal();
                        showCustomAlert('success', 'Updated!', 'Voucher details saved successfully.', () => {
                            window.location.reload();
                        });
                    } else {
                        const errDiv = document.getElementById('editError');
                        errDiv.textContent = data.message || data.error || 'Failed to update voucher';
                        errDiv.classList.remove('hidden');
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }
                })
                .catch(err => {
                    showCustomAlert('error', 'System Error', 'Could not connect to the server.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        }

        function removeVoucherRow(voucherId) {
            const row = document.querySelector(`tr[data-voucher-id="${voucherId}"]`);
            if (row) {
                row.style.transition = 'opacity 0.3s ease';
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    const tbody = document.querySelector('table tbody');
                    const remainingRows = tbody.querySelectorAll('tr[data-voucher-id]');
                    if (remainingRows.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="7" class="no-data">No vouchers found.</td></tr>';
                    }
                    updateVoucherCount();
                }, 300);
            }
        }

        function updateVoucherCount() {
            const tbody = document.querySelector('table tbody');
            const rows = tbody.querySelectorAll('tr[data-voucher-id]');
            const pageTitle = document.querySelector('.page-title');
            if (pageTitle) {
                const count = rows.length;
                pageTitle.textContent = `Voucher List (${count})`;
            }
        }

        function setupDeleteButtons() {
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.onclick = () => {
                    const voucherId = btn.dataset.id;
                    const voucherCode = btn.dataset.code;

                    showCustomAlert('confirm', 'Delete Voucher?', `Are you sure you want to delete voucher "${voucherCode}"?`, () => {
                        const params = new URLSearchParams();
                        params.append('id', voucherId);

                        fetch('admin_voucher_delete.php', {
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
                                    removeVoucherRow(voucherId);
                                    showCustomAlert('success', 'Deleted!', 'The voucher has been removed.');
                                } else {
                                    showCustomAlert('error', 'Error', data.message || data.error || 'Delete failed.');
                                }
                            })
                            .catch(err => {
                                console.error('Error:', err);
                                showCustomAlert('error', 'System Error', 'Could not connect to the server.');
                            });
                    });
                };
            });
        }

        setupDeleteButtons();

        window.onclick = (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.add('hidden');
            }
        };
    </script>
</body>

</html>
