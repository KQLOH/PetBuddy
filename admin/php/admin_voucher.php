<?php
session_start();
require_once '../../user/include/db.php';

// 权限检查：Admin 和 Super Admin 都可以访问
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: admin_login.php');
    exit;
}

$adminName = $_SESSION['full_name'] ?? 'Admin';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// 排序逻辑
$sort = $_GET['sort'] ?? 'voucher_id';
$dir  = strtoupper($_GET['dir'] ?? 'DESC');
$allowedSorts = ['voucher_id', 'code', 'discount_amount', 'end_date'];
if (!in_array($sort, $allowedSorts)) $sort = 'voucher_id';
if (!in_array($dir, ['ASC', 'DESC'])) $dir = 'DESC';

$params = [];
$whereSql = "";
if ($search !== '') {
    $whereSql = " WHERE code LIKE ? ";
    $params[] = "%$search%";
}

// 获取总数用于分页
$countSql = "SELECT COUNT(*) FROM vouchers $whereSql";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$total = $stmtCount->fetchColumn();
$totalPages = ceil($total / $limit);

// 获取数据
$sql = "SELECT * FROM vouchers $whereSql ORDER BY $sort $dir LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 辅助函数：生成 URL 参数
function q($extra = [])
{
    $base = $_GET;
    foreach ($extra as $k => $v) $base[$k] = $v;
    return http_build_query($base);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Voucher Management</title>
    <link rel="stylesheet" href="../css/admin_member.css">
    <link rel="stylesheet" href="../css/admin_btn.css">
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div class="topbar-title">Voucher Management</div>
            </div>
            <span class="tag-pill">Admin: <?= htmlspecialchars($adminName) ?></span>
        </header>

        <main class="content">
            <div class="page-header">
                <div class="page-title">Vouchers (<?= $total ?>)</div>
                <div class="page-subtitle">Create and manage discount codes</div>
            </div>

            <form class="filter-bar" method="get">
                <input type="text" name="search" placeholder="Search by code..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn-search" type="submit">Search</button>
                <a href="admin_voucher.php" class="btn-reset">Reset</a>

                <a href="javascript:void(0)" onclick="openVoucherModal()" class="btn-pill-add" style="margin-left: auto;">
                    <span>+</span> Create Voucher
                </a>
            </form>

            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Discount (RM)</th>
                            <th>Min Spend</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers as $v):
                            $isExpired = strtotime($v['end_date']) < time();
                        ?>
                            <tr>
                                <td><?= $v['voucher_id'] ?></td>
                                <td style="font-weight: bold; color: #2E7D32;"><?= htmlspecialchars($v['code']) ?></td>
                                <td><?= number_format($v['discount_amount'], 2) ?></td>
                                <td><?= number_format($v['min_amount'], 2) ?></td>
                                <td><?= $v['end_date'] ?></td>
                                <td>
                                    <span class="<?= $isExpired ? 'status-expired' : 'status-active' ?>">
                                        <?= $isExpired ? 'Expired' : 'Active' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button type="button" class="btn-action btn-edit" onclick="openEditVoucherModal(<?= $v['voucher_id'] ?>)">
                                        Edit
                                    </button>

                                    <button type="button" class="btn-action btn-delete"
                                        onclick="confirmDeleteVoucher(<?= $v['voucher_id'] ?>, '<?= htmlspecialchars($v['code']) ?>')">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?<?= q(['p' => $i]) ?>" class="<?= $i == $page ? 'current' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="voucherModal" class="modal hidden">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Create New Voucher</h3>
                <button type="button" class="modal-close" id="btn-error" onclick="closeVoucherModal()">
                    <img src="../images/error.png">
                </button>
            </div>
            <form id="voucherForm" onsubmit="saveVoucher(event)">
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-weight:600;">Voucher Code *</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" name="code" id="v_code_input" required placeholder="e.g. PETBUDDY88" style="flex:1; padding:12px; border:1px solid #ddd; border-radius:8px;">
                            <button type="button" onclick="generateVoucherCode()" class="btn-pill-add" style="padding:0 15px; font-size:12px; height:42px;">Auto</button>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:600;">Discount (RM) *</label>
                            <input type="number" step="0.01" name="discount_amount" required placeholder="0.00" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:600;">Min Spend (RM) *</label>
                            <input type="number" step="0.01" name="min_amount" value="0.00" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:600;">Start Date</label>
                            <input type="date" name="start_date" required value="<?= date('Y-m-d') ?>" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:5px; font-weight:600;">End Date</label>
                            <input type="date" name="end_date" required style="width:100%; padding:12px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding:20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:10px;">
                    <button type="button" class="btn-secondary" onclick="closeVoucherModal()" style="padding:10px 20px; border-radius:50px; border:1px solid #ddd; background:#fff; cursor:pointer;">Cancel</button>
                    <button type="submit" class="btn-pill-add" style="padding:10px 30px;">Save Voucher</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openVoucherModal() {
            document.getElementById('voucherModal').classList.remove('hidden');
        }

        function closeVoucherModal() {
            document.getElementById('voucherModal').classList.add('hidden');
            document.getElementById('voucherForm').reset();
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
            const formData = new FormData(event.target);

            fetch('admin_voucher_save.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        closeVoucherModal();
                        if (typeof showSidebarAlert === 'function') {
                            showSidebarAlert('success', 'Success', 'Voucher created!', () => window.location.reload());
                        } else {
                            alert('Voucher created successfully!');
                            window.location.reload();
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(err => alert('System error occurred.'));
        }

        // --- 编辑弹窗逻辑 ---
        function openEditVoucherModal(id) {
            fetch(`admin_voucher_get.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const v = data.voucher;
                        // 假设你有一个专门的编辑弹窗 ID 为 editVoucherModal
                        // 或者你可以复用 addVoucherModal，但需要修改标题和增加隐藏的 ID input
                        // 这里演示复用逻辑：
                        const modal = document.getElementById('addVoucherModal');
                        modal.querySelector('h3').innerText = "Edit Voucher";

                        // 填充数据
                        const form = document.getElementById('addVoucherForm');
                        form.code.value = v.code;
                        form.discount_amount.value = v.discount_amount;
                        form.min_amount.value = v.min_amount;
                        form.start_date.value = v.start_date;
                        form.end_date.value = v.end_date;

                        // 动态添加一个隐藏的 ID 字段用于提交
                        let idInput = form.querySelector('input[name="voucher_id"]');
                        if (!idInput) {
                            idInput = document.createElement('input');
                            idInput.type = 'hidden';
                            idInput.name = 'voucher_id';
                            form.appendChild(idInput);
                        }
                        idInput.value = v.voucher_id;

                        // 修改提交函数指向编辑脚本
                        form.onsubmit = handleEditSubmit;

                        modal.classList.remove('hidden');
                    }
                });
        }

        function handleEditSubmit(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            fetch('admin_voucher_edit.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showSidebarAlert('success', 'Updated!', 'Voucher has been updated.', () => location.reload());
                    } else {
                        alert(data.message);
                    }
                });
        }

        function confirmDeleteVoucher(id, code) {
            showSidebarAlert('confirm', 'Delete Voucher?', `Are you sure you want to delete voucher "${code}"?`, () => {
                const params = new URLSearchParams();
                params.append('id', id);

                fetch('admin_voucher_delete.php', {
                        method: 'POST',
                        body: params
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showSidebarAlert('success', 'Deleted!', 'Voucher removed successfully.', () => location.reload());
                        } else {
                            alert(data.message);
                        }
                    });
            });
        }
    </script>
</body>

</html>