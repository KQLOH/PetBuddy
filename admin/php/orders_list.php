<?php
session_start();
require_once '../../user/include/db.php';

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    header('Location: admin_login.php');
    exit;
}

$adminRole = $_SESSION['role'];
$adminName = $_SESSION['full_name'] ?? 'Admin';

$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$sort = $_GET['sort'] ?? 'order_id';
$dir  = $_GET['dir'] ?? 'DESC';

$allowedSorts = [
    'order_id'     => 'o.order_id',
    'order_date'   => 'o.order_date',
    'full_name'    => 'm.full_name',
    'total_amount' => 'o.total_amount',
    'status'       => 'o.status'
];

if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'order_id';
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

if ($search !== '') {
    $where[] = "(o.order_id LIKE ? OR m.full_name LIKE ? OR m.email LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like);
}

if ($statusFilter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

$countSql = "SELECT COUNT(*) FROM orders o JOIN members m ON o.member_id = m.member_id {$whereSql}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $limit);
if ($totalPages > 0 && $page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$sql = "
    SELECT o.order_id, o.order_date, o.total_amount, o.status, m.full_name, m.email
    FROM orders o
    JOIN members m ON o.member_id = m.member_id
    {$whereSql}
    ORDER BY {$sortSqlColumn} {$dir}
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        if ($dir === 'ASC') {
            $iconHtml = '<img src="../images/up.png" class="sort-icon" alt="Asc">';
        } else {
            $iconHtml = '<img src="../images/down.png" class="sort-icon" alt="Desc">';
        }
    }

    $url = '?' . q(['sort' => $columnKey, 'dir' => $newDir, 'p' => 1]);
    return '<a href="' . htmlspecialchars($url) . '" class="sort-link">' . $label . $iconHtml . '</a>';
}

$allStatuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled', 'return_requested', 'returned'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Orders - PetBuddy Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin_product.css">
    <style>
        .sort-link {
            text-decoration: none;
            color: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            user-select: none;
            cursor: pointer;
        }

        .sort-link:hover {
            color: var(--primary-color);
        }

        .sort-icon {
            width: 12px;
            height: auto;
            opacity: 0.7;
            vertical-align: middle;
            margin-top: -2px;
        }

        .search-wrapper-list {
            position: relative;
            display: inline-block;
        }

        .search-icon-list {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            opacity: 0.5;
        }

        .filter-bar input[type="text"] {
            padding-left: 34px !important;
        }

        .status-pill {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-pending {
            background-color: #FFF4E5;
            color: #B54708;
            border: 1px solid #FEDF89;
        }

        .status-paid {
            background-color: #ECFDF3;
            color: #027A48;
            border: 1px solid #A6F4C5;
        }

        .status-shipped {
            background-color: #F0F9FF;
            color: #026AA2;
            border: 1px solid #B9E6FE;
        }

        .status-completed {
            background-color: #EDF7ED;
            color: #1E4620;
            border: 1px solid #C8E6C9;
        }

        .status-cancelled {
            background-color: #FEF3F2;
            color: #B42318;
            border: 1px solid #FECDCA;
        }

        .status-return_requested,
        .status-returned {
            background-color: #F8F9FA;
            color: #344054;
            border: 1px solid #D0D5DD;
        }

        .item-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .item-table th {
            background: #F9FAFB;
            text-align: left;
            padding: 12px;
            color: #475467;
            font-weight: 600;
            border-bottom: 1px solid #EAECF0;
        }

        .item-table td {
            border-bottom: 1px solid #EAECF0;
            padding: 12px;
            vertical-align: middle;
            color: #344054;
        }

        .item-thumb {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            margin-right: 12px;
            border: 1px solid #EAECF0;
            vertical-align: middle;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid #eee;
        }

        .info-group h4 {
            font-size: 11px;
            color: #667085;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .info-group p {
            margin: 4px 0;
            font-size: 14px;
            color: #101828;
            line-height: 1.5;
        }

        .custom-alert-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .custom-alert-overlay.show {
            opacity: 1;
        }

        .custom-alert-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }

        .custom-alert-overlay.show .custom-alert-box {
            transform: scale(1);
        }

        .custom-alert-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9fafb;
            border: 2px solid #eee;
        }

        .custom-alert-icon img {
            width: 30px;
            height: 30px;
            object-fit: contain;
        }

        .custom-alert-title {
            margin: 0 0 10px;
            font-size: 1.25rem;
            color: #333;
        }

        .custom-alert-text {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .custom-alert-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-alert {
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-weight: 600;
        }

        .btn-alert-confirm {
            background: #F4A261;
            color: white;
        }

        .btn-alert-confirm:hover {
            background: #E68E3F;
        }

        .btn-alert-cancel {
            background: #F2F4F7;
            color: #333;
        }

        .btn-alert-cancel:hover {
            background: #E4E7EC;
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <header class="topbar">
            <div class="topbar-left">
                <button id="sidebarToggle" class="sidebar-toggle">☰</button>
                <div class="topbar-title">Orders</div>
            </div>
            <span class="tag-pill" style="margin-right: 20px;">Admin: <?= htmlspecialchars($adminName) ?></span>
        </header>

        <main class="content">

            <div class="page-header">
                <div>
                    <div class="page-title">Order List (<?= (int)$total ?>)</div>
                    <div class="page-subtitle">View and manage customer orders</div>
                </div>
            </div>

            <form class="filter-bar" method="get">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="dir" value="<?= htmlspecialchars($dir) ?>">

                <select name="status" onchange="this.form.submit()">
                    <option value="all">All Status</option>
                    <?php foreach ($allStatuses as $s): ?>
                        <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace('_', ' ', $s)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="search-wrapper-list">
                    <img src="../images/search.png" class="search-icon-list">
                    <input type="text"
                        name="search"
                        placeholder="Order ID / Customer / Email"
                        value="<?= htmlspecialchars($search) ?>">
                </div>

                <button class="btn-search" type="submit">Search</button>
                <a class="btn-reset" href="orders_list.php">Reset</a>
            </form>

            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th style="text-align:left; width:100px;">
                                <?= sortLink('order_id', 'Order ID') ?>
                            </th>
                            <th style="text-align:left;">
                                <?= sortLink('order_date', 'Date') ?>
                            </th>
                            <th style="text-align:left;">
                                <?= sortLink('full_name', 'Customer') ?>
                            </th>
                            <th style="text-align:left;">
                                <?= sortLink('total_amount', 'Total') ?>
                            </th>
                            <th style="text-align:left;">
                                <?= sortLink('status', 'Status') ?>
                            </th>
                            <th style="text-align:left;">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!$orders): ?>
                            <tr>
                                <td colspan="6" class="no-data">No orders found matching your criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td style="font-weight: 500;">#<?= (int)$o['order_id'] ?></td>

                                    <td>
                                        <div style="font-weight:500;"><?= date('d M Y', strtotime($o['order_date'])) ?></div>
                                        <div style="font-size:11px; color:#667085;"><?= date('h:i A', strtotime($o['order_date'])) ?></div>
                                    </td>

                                    <td>
                                        <div class="p-name"><?= htmlspecialchars($o['full_name']) ?></div>
                                        <div class="p-sub"><?= htmlspecialchars($o['email']) ?></div>
                                    </td>

                                    <td style="font-weight:600; color:var(--text-dark);">
                                        RM <?= number_format((float)$o['total_amount'], 2) ?>
                                    </td>

                                    <td>
                                        <span class="status-pill status-<?= $o['status'] ?>">
                                            <?= str_replace('_', ' ', $o['status']) ?>
                                        </span>
                                    </td>

                                    <td class="actions">
                                        <button type="button" class="btn-action btn-view" onclick="openViewOrder(<?= (int)$o['order_id'] ?>)">
                                            View
                                        </button>

                                        <button type="button" class="btn-action btn-edit" onclick="openUpdateStatus(<?= (int)$o['order_id'] ?>, '<?= $o['status'] ?>')">
                                            Update
                                        </button>
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
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="font-size:18px; font-weight:700;">Order Details <span id="viewOrderId" style="color:#888; font-weight:400;"></span></h3>
                <button type="button" class="btn-secondary" onclick="closeModal('viewModal')" style="border:none; background:none; cursor:pointer; padding:0;">
                    <img src="../images/error.png" style="width:16px; height:16px; vertical-align:middle;">
                </button>
            </div>

            <div id="viewModalContent">
                <div style="text-align:center; padding:40px; color:#888;">Loading...</div>
            </div>

            <div class="modal-actions" style="margin-top:20px; text-align:right;">
                <button class="btn-secondary" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <div id="statusModal" class="modal hidden">
        <div class="modal-box">
            <h3 style="margin-bottom:20px; font-size:18px; font-weight:700; color:#333;">Update Order Status</h3>
            <form id="statusForm" onsubmit="submitStatusUpdate(event)">
                <input type="hidden" name="order_id" id="statusOrderId">
                <div style="margin-bottom: 25px;">
                    <label style="display:block; margin-bottom:8px; font-size:13px; font-weight:600; color:#344054;">New Status</label>
                    <select name="new_status" id="statusSelect" style="width:100%; padding:10px; border:1px solid #D0D5DD; border-radius:8px; font-size:14px; color:#333;">
                        <?php foreach ($allStatuses as $s): ?>
                            <option value="<?= $s ?>"><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
                    <button type="submit" class="btn-primary" style="border:none; cursor:pointer;">Save Changes</button>
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
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
        });

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        function showCustomAlert(type, title, text, autoClose = false) {
            const overlay = document.getElementById('customAlert');
            const iconContainer = document.getElementById('customAlertIcon');
            const btnCancel = document.getElementById('customAlertCancel');

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

            btnCancel.style.display = 'none';
            document.getElementById('customAlertConfirm').onclick = closeCustomAlert;

            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);

            if (autoClose) setTimeout(closeCustomAlert, 2000);
        }

        function closeCustomAlert() {
            const overlay = document.getElementById('customAlert');
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
                document.getElementById('customAlertConfirm').innerText = 'OK';
            }, 300);
        }

        function openViewOrder(orderId) {
            document.getElementById('viewModal').classList.remove('hidden');
            document.getElementById('viewOrderId').innerText = '#' + orderId;
            const content = document.getElementById('viewModalContent');
            content.innerHTML = '<div style="text-align:center; padding:40px; color:#888;">Loading...</div>';

            fetch(`order_get_details.php?id=${orderId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const o = data.order;
                        const items = data.items;
                        let rows = '';
                        items.forEach(item => {
                            rows += `
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center;">
                                            <img src="${item.image}" class="item-thumb">
                                            <span style="font-weight:500;">${item.name}</span>
                                        </div>
                                    </td>
                                    <td style="text-align:center;">x${item.quantity}</td>
                                    <td style="text-align:right;">RM ${parseFloat(item.unit_price).toFixed(2)}</td>
                                </tr>
                            `;
                        });

                        content.innerHTML = `
                            <div class="info-grid">
                                <div class="info-group">
                                    <h4>Customer Information</h4>
                                    <p><strong>Name:</strong> ${o.full_name}</p>
                                    <p><strong>Email:</strong> ${o.email}</p>
                                    <p><strong>Phone:</strong> ${o.phone || '-'}</p>
                                </div>
                                <div class="info-group">
                                    <h4>Shipping Details</h4>
                                    <p><strong>Recipient:</strong> ${o.recipient_name}</p>
                                    <p>${o.address_line1} ${o.address_line2 || ''}</p>
                                    <p>${o.postcode} ${o.city}, ${o.state}</p>
                                </div>
                            </div>
                            <h4 style="font-size:11px; color:#667085; text-transform:uppercase; font-weight:700; letter-spacing:0.5px; margin-bottom:10px;">Order Items</h4>
                            <div style="border:1px solid #EAECF0; border-radius:8px; overflow:hidden;">
                                <table class="item-table">
                                    <thead><tr><th>Product</th><th style="text-align:center; width:80px;">Qty</th><th style="text-align:right; width:120px;">Price</th></tr></thead>
                                    <tbody>${rows}</tbody>
                                </table>
                            </div>
                            <div style="text-align:right; margin-top:20px; font-size:18px; color:#101828;">
                                Total Amount: <strong>RM ${parseFloat(o.total_amount).toFixed(2)}</strong>
                            </div>
                        `;
                    } else {
                        content.innerHTML = `<div style="text-align:center; color:#d92d20; padding:20px;">Error: ${data.error}</div>`;
                    }
                })
                .catch(err => {
                    content.innerHTML = `<div style="text-align:center; color:#d92d20; padding:20px;">System error loading details.</div>`;
                });
        }

        function openUpdateStatus(orderId, currentStatus) {
            document.getElementById('statusModal').classList.remove('hidden');
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('statusSelect').value = currentStatus;
        }

        function submitStatusUpdate(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('statusForm'));
            const btn = document.querySelector('#statusForm button[type="submit"]');
            const originalText = btn.innerText;

            btn.innerText = "Saving...";
            btn.disabled = true;

            fetch('order_update_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showCustomAlert('success', 'Success', 'Order status updated successfully!');
                        closeModal('statusModal');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showCustomAlert('error', 'Error', data.error);
                    }
                })
                .catch(err => showCustomAlert('error', 'System Error', 'Could not update status.'))
                .finally(() => {
                    btn.innerText = originalText;
                    btn.disabled = false;
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