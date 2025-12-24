<?php
session_start();
include '../include/db.php';
require_once "../include/product_utils.php";

$message = "";
$msg_type = "";
$active_tab = 'dashboard';
$member = [];
$orders = [];
$addresses = [];
$stats = ['total_orders' => 0, 'total_spent' => 0];
$voucher_count = 0;
$my_vouchers = [];

if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}
$member_id = $_SESSION['member_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $msg_type = $_SESSION['flash_type'];
    if (isset($_SESSION['flash_tab'])) {
        $active_tab = $_SESSION['flash_tab'];
        unset($_SESSION['flash_tab']);
    }
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_type']);
}

if (isset($pdo)) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Security validation failed.");
        }

        $redirect_needed = false;

        if (isset($_POST['form_type']) && $_POST['form_type'] === 'change_password') {
            $active_tab = 'profile';
            $current_pwd = $_POST['current_password'] ?? '';
            $new_pwd = $_POST['new_password'] ?? '';
            $confirm_pwd = $_POST['confirm_password'] ?? '';

            $stmt = $pdo->prepare("SELECT password_hash FROM members WHERE member_id = ?");
            $stmt->execute([$member_id]);
            $userAuth = $stmt->fetch();

            if ($userAuth && password_verify($current_pwd, $userAuth['password_hash'])) {
                if ($new_pwd === $confirm_pwd) {
                    if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}$/', $new_pwd)) {
                        $new_hash = password_hash($new_pwd, PASSWORD_DEFAULT);
                        $updStmt = $pdo->prepare("UPDATE members SET password_hash = ? WHERE member_id = ?");
                        if ($updStmt->execute([$new_hash, $member_id])) {
                            $_SESSION['flash_msg'] = "Password changed successfully!";
                            $_SESSION['flash_type'] = "success";
                            $_SESSION['flash_tab'] = "profile";
                            $redirect_needed = true;
                        } else {
                            $message = "System error updating password.";
                            $msg_type = "error";
                        }
                    } else {
                        $message = "Password too weak.";
                        $msg_type = "error";
                    }
                } else {
                    $message = "New passwords do not match.";
                    $msg_type = "error";
                }
            } else {
                $message = "Incorrect current password.";
                $msg_type = "error";
            }
        } else if (isset($_POST['form_type']) && $_POST['form_type'] === 'update_profile') {
            $active_tab = 'profile';
            $full_name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            $image_path = null;
            $upload_error = false;

            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $target_dir = "uploads/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $file_ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
                $new_filename = "mem_" . $member_id . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;

                $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
                if ($check !== false && in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                        $image_path = $target_file;
                    } else {
                        $upload_error = true;
                        $message = "Upload failed.";
                        $msg_type = "error";
                    }
                } else {
                    $upload_error = true;
                    $message = "Invalid image.";
                    $msg_type = "error";
                }
            }

            if (!$upload_error) {
                try {
                    if ($image_path) {
                        $sql = "UPDATE members SET full_name = ?, phone = ?, image = ? WHERE member_id = ?";
                        $pdo->prepare($sql)->execute([$full_name, $phone, $image_path, $member_id]);
                    } else {
                        $sql = "UPDATE members SET full_name = ?, phone = ? WHERE member_id = ?";
                        $pdo->prepare($sql)->execute([$full_name, $phone, $member_id]);
                    }
                    $_SESSION['flash_msg'] = "Profile updated successfully!";
                    $_SESSION['flash_type'] = "success";
                    $_SESSION['flash_tab'] = "profile";
                    $redirect_needed = true;
                } catch (PDOException $e) {
                    $message = "Error updating profile.";
                    $msg_type = "error";
                }
            }
        } else if (isset($_POST['form_type']) && $_POST['form_type'] === 'save_address') {
            $addr_id = $_POST['address_id'] ?? '';
            $r_name = trim($_POST['recipient_name']);
            $r_phone = trim($_POST['recipient_phone']);
            $addr1 = trim($_POST['address_line1']);
            $addr2 = trim($_POST['address_line2']);
            $city = trim($_POST['city']);
            $state = trim($_POST['state']);
            $postcode = trim($_POST['postcode']);
            $is_default = isset($_POST['is_default']) ? 1 : 0;

            if ($is_default) {
                $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?")->execute([$member_id]);
            }

            if (!empty($addr_id)) {
                $sql = "UPDATE member_addresses SET recipient_name=?, recipient_phone=?, address_line1=?, address_line2=?, city=?, state=?, postcode=?, is_default=? WHERE address_id=? AND member_id=?";
                $params = [$r_name, $r_phone, $addr1, $addr2, $city, $state, $postcode, $is_default, $addr_id, $member_id];
                $msg = "Address updated successfully!";
            } else {
                if ($is_default == 0) {
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM member_addresses WHERE member_id = ?");
                    $stmtCheck->execute([$member_id]);
                    if ($stmtCheck->fetchColumn() == 0) {
                        $is_default = 1;
                    }
                }
                $sql = "INSERT INTO member_addresses (recipient_name, recipient_phone, address_line1, address_line2, city, state, postcode, is_default, member_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [$r_name, $r_phone, $addr1, $addr2, $city, $state, $postcode, $is_default, $member_id];
                $msg = "New address added!";
            }

            if ($pdo->prepare($sql)->execute($params)) {
                $_SESSION['flash_msg'] = $msg;
                $_SESSION['flash_type'] = "success";
                $_SESSION['flash_tab'] = "addresses";
                $redirect_needed = true;
            } else {
                $message = "Error saving address.";
                $msg_type = "error";
            }
        } else if (isset($_POST['action']) && $_POST['action'] === 'set_default') {
            $addr_id = $_POST['address_id'];
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?")->execute([$member_id]);
            $pdo->prepare("UPDATE member_addresses SET is_default = 1 WHERE address_id = ? AND member_id = ?")->execute([$addr_id, $member_id]);
            $pdo->commit();
            $_SESSION['flash_msg'] = "Default address updated!";
            $_SESSION['flash_type'] = "success";
            $_SESSION['flash_tab'] = "addresses";
            $redirect_needed = true;
        } else if (isset($_POST['action']) && $_POST['action'] === 'delete_address') {
            $addr_id = $_POST['address_id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM member_addresses WHERE address_id = ? AND member_id = ?");
                if ($stmt->execute([$addr_id, $member_id])) {
                    $_SESSION['flash_msg'] = "Address deleted successfully.";
                    $_SESSION['flash_type'] = "success";
                    $_SESSION['flash_tab'] = "addresses";
                    $redirect_needed = true;
                }
            } catch (PDOException $e) {
                if ($e->getCode() == '23000') {
                    $message = "Cannot delete this address because it is used in past orders.";
                } else {
                    $message = "System error: " . $e->getMessage();
                }
                $msg_type = "error";
                $active_tab = "addresses";
            }
        } else if (isset($_POST['action']) && in_array($_POST['action'], ['cancel_order', 'complete_order', 'request_return'])) {
            $order_id = $_POST['order_id'];
            $new_status = '';
            $allow_update = false;

            $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE order_id = ? AND member_id = ?");
            $stmtCheck->execute([$order_id, $member_id]);
            $current_status = $stmtCheck->fetchColumn();

            if ($_POST['action'] === 'cancel_order') {
                if (in_array($current_status, ['pending', 'paid'])) {
                    $new_status = 'cancelled';
                    $allow_update = true;
                }
            } elseif ($_POST['action'] === 'complete_order') {
                if ($current_status === 'shipped') {
                    $new_status = 'completed';
                    $allow_update = true;
                }
            } elseif ($_POST['action'] === 'request_return') {
                if (in_array($current_status, ['shipped', 'completed'])) {
                    $new_status = 'return_requested';
                    $allow_update = true;
                }
            }

            if ($allow_update) {
                $stmtUpdate = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                if ($stmtUpdate->execute([$new_status, $order_id])) {
                    $_SESSION['flash_msg'] = "Order status updated.";
                    $_SESSION['flash_type'] = "success";
                    $_SESSION['flash_tab'] = "orders";
                    $redirect_needed = true;
                }
            } else {
                $message = "Action not allowed for current order status.";
                $msg_type = "error";
            }
        }

        if ($redirect_needed) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();

        $orderSql = "SELECT o.*, 
                     COUNT(oi.order_item_id) as item_count,
                     (SELECT p.image FROM order_items oi2 JOIN products p ON oi2.product_id = p.product_id WHERE oi2.order_id = o.order_id LIMIT 1) as first_img,
                     (SELECT p.name FROM order_items oi3 JOIN products p ON oi3.product_id = p.product_id WHERE oi3.order_id = o.order_id LIMIT 1) as first_item_name
                     FROM orders o 
                     LEFT JOIN order_items oi ON o.order_id = oi.order_id
                     WHERE o.member_id = ? 
                     GROUP BY o.order_id 
                     ORDER BY o.order_date DESC";
        $stmtOrders = $pdo->prepare($orderSql);
        $stmtOrders->execute([$member_id]);
        $orders = $stmtOrders->fetchAll();

        $stats['total_orders'] = count($orders);
        foreach ($orders as $o) {
            if ($o['status'] !== 'cancelled') {
                $stats['total_spent'] += $o['total_amount'];
            }
        }

        $today = date('Y-m-d');
        $vSql = "SELECT * FROM vouchers WHERE start_date <= ? AND end_date >= ? ORDER BY end_date ASC";
        $vStmt = $pdo->prepare($vSql);
        $vStmt->execute([$today, $today]);
        $my_vouchers = $vStmt->fetchAll(PDO::FETCH_ASSOC);
        $voucher_count = count($my_vouchers);

        $addrSql = "SELECT * FROM member_addresses WHERE member_id = ? ORDER BY is_default DESC, created_at DESC";
        $stmtAddr = $pdo->prepare($addrSql);
        $stmtAddr->execute([$member_id]);
        $addresses = $stmtAddr->fetchAll();
    } catch (PDOException $e) {
        $message = "Error loading data.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - PetBuddy</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/memberProfileStyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <?php include '../include/header.php'; ?>

    <?php if ($message): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: '<?php echo $msg_type === "success" ? "success" : "error"; ?>',
                    title: '<?php echo $msg_type === "success" ? "Success" : "Oops..."; ?>',
                    text: "<?php echo $message; ?>",
                    confirmButtonColor: '#F4A261',
                    confirmButtonText: 'OK',
                    timer: 3000,
                    timerProgressBar: true
                });
            });
        </script>
    <?php endif; ?>

    <div class="dashboard-container">

        <aside>
            <div class="card-box">
                <div class="user-brief">
                    <?php if (!empty($member['image']) && file_exists($member['image'])): ?>
                        <img src="<?php echo htmlspecialchars($member['image']); ?>?v=<?php echo time(); ?>" class="user-avatar">
                    <?php else: ?>
                        <div class="user-avatar"><?php echo strtoupper(substr($member['full_name'] ?? 'U', 0, 1)); ?></div>
                    <?php endif; ?>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($member['full_name'] ?? 'User'); ?></h4>
                        <p><?php echo htmlspecialchars($member['email'] ?? ''); ?></p>
                    </div>
                </div>

                <nav class="sidebar-nav">
                    <div id="link-dashboard" class="sidebar-link active" onclick="switchTab('dashboard')">
                        <img src="../images/dashboard.png" style="width:20px; opacity:0.7;"> Dashboard
                    </div>
                    <div id="link-orders" class="sidebar-link" onclick="switchTab('orders')">
                        <img src="../images/purchase-order.png" style="width:20px; opacity:0.7;"> My Orders
                    </div>
                    <div id="link-addresses" class="sidebar-link" onclick="switchTab('addresses')">
                        <img src="../images/phone-book.png" style="width:20px; opacity:0.7;"> Address Book
                    </div>
                    <div id="link-profile" class="sidebar-link" onclick="switchTab('profile')">
                        <img src="../images/profileSetting.png" style="width:20px; opacity:0.7;"> Settings
                    </div>
                    <a href="#" onclick="confirmLogout()" class="sidebar-link text-red">
                        <img src="../images/exit.png" style="width:20px; opacity:0.7;"> Logout
                    </a>
                </nav>
            </div>
        </aside>

        <main>
            <div class="breadcrumb">
                <a href="home.php">Home</a> / <span>My Account</span>
            </div>

            <div id="dashboard" class="tab-content">
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon-box bg-blue-light"><img src="../images/cart.png" style="width:24px;"></div>
                        <div class="stat-info">
                            <p>Total Orders</p>
                            <h3><?php echo $stats['total_orders']; ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-box bg-green-light"><img src="../images/wallet.png" style="width:24px;"></div>
                        <div class="stat-info">
                            <p>Total Spent</p>
                            <h3>RM <?php echo number_format($stats['total_spent'], 2); ?></h3>
                        </div>
                    </div>
                    <div class="stat-card" onclick="switchTab('orders')" style="cursor: pointer;">
                        <div class="stat-icon-box bg-orange-light"><img src="../images/voucher.png" style="width:24px;"></div>
                        <div class="stat-info">
                            <p>Available Vouchers</p>
                            <h3><?php echo $voucher_count; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="card-box">
                    <div class="section-header" style="margin-bottom: 1.5rem;">
                        <h3 class="section-title" style="margin:0;">My Active Vouchers</h3>
                        <a href="vouchers.php" class="link-orange" style="font-size:0.9rem;">View All</a>
                    </div>
                    <?php if (count($my_vouchers) > 0): ?>
                        <div class="voucher-dashboard-grid">
                            <?php foreach (array_slice($my_vouchers, 0, 3) as $v):
                                $minSpend = (float)$v['min_amount'];
                            ?>
                                <div class="dashboard-voucher-card">
                                    <div class="d-voucher-left">
                                        <div class="d-voucher-amount">RM <?php echo number_format($v['discount_amount'], 0); ?></div>
                                        <div class="d-voucher-label">OFF</div>
                                    </div>
                                    <div class="d-voucher-right">
                                        <div class="d-voucher-code"><?php echo htmlspecialchars($v['code']); ?></div>
                                        <div class="d-voucher-condition">
                                            <?php echo ($minSpend > 0) ? "Min. spend RM " . number_format($minSpend, 0) : "No min. spend"; ?>
                                        </div>
                                        <div class="d-voucher-expiry">Exp: <?php echo date('d M Y', strtotime($v['end_date'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 2rem 0;">
                            <div class="empty-icon" style="width:50px; height:50px; margin-bottom:10px;"><img src="../images/voucher.png" style="width:24px; opacity:0.4;"></div>
                            <p class="empty-text">No active vouchers available right now.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="orders" class="tab-content">
                <div class="card-box" style="min-height: 400px; background: transparent; border:none; box-shadow:none; padding:0;">

                    <div class="order-tabs">
                        <div class="order-tab active" onclick="filterOrders('all', this)">All</div>
                        <div class="order-tab" onclick="filterOrders('pending', this)">To Pay</div>
                        <div class="order-tab" onclick="filterOrders('paid', this)">To Ship</div>
                        <div class="order-tab" onclick="filterOrders('shipped', this)">To Receive</div>
                        <div class="order-tab" onclick="filterOrders('completed', this)">Completed</div>
                        <div class="order-tab" onclick="filterOrders('cancelled', this)">Cancelled</div>
                        <div class="order-tab" onclick="filterOrders('return_requested', this)">Return/Refund</div>
                    </div>

                    <?php if (count($orders) > 0): ?>
                        <div class="order-card-list">
                            <?php foreach ($orders as $order):
                                $imgSrc = !empty($order['first_img']) ? productImageUrl($order['first_img']) : '../images/no-image.png';
                                $moreItems = $order['item_count'] - 1;
                                $itemText = htmlspecialchars($order['first_item_name']);
                                if ($moreItems > 0) {
                                    $itemText .= " <span style='color:#999; font-weight:400;'>+ $moreItems other items</span>";
                                }

                                $filterStatus = $order['status'];
                                if ($order['status'] == 'return_requested' || $order['status'] == 'returned') {
                                    $filterStatus = 'return_requested';
                                }
                            ?>
                                <div class="order-card" data-status="<?php echo $filterStatus; ?>">
                                    <div class="order-header">
                                        <span>Order <strong>#<?php echo $order['order_id']; ?></strong></span>
                                        <span><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></span>
                                    </div>
                                    <div class="order-body" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)" style="cursor:pointer;">
                                        <div class="order-img-wrapper"><img src="<?php echo $imgSrc; ?>" alt="Product"></div>
                                        <div class="order-info">
                                            <div class="order-product-title"><?php echo $itemText; ?></div>
                                            <div class="order-meta">Total: <strong>RM <?php echo number_format($order['total_amount'], 2); ?></strong></div>
                                        </div>
                                        <div><span class="order-status-badge status-<?php echo strtolower($order['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?></span></div>
                                    </div>

                                    <div class="order-actions">
                                        <?php if (in_array($order['status'], ['pending', 'paid'])): ?>
                                            <form method="POST" onsubmit="return confirmSubmit(event, 'Are you sure you want to cancel this order?');" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="cancel_order">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" class="btn-order-action btn-cancel">Cancel Order</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($order['status'] == 'shipped'): ?>
                                            <form method="POST" onsubmit="return confirmSubmit(event, 'Confirm you have received the order? This cannot be undone.');" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="complete_order">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" class="btn-order-action btn-receive">Order Received</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (in_array($order['status'], ['shipped', 'completed'])): ?>
                                            <form method="POST" onsubmit="return confirmSubmit(event, 'Request a return/refund?');" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="request_return">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" class="btn-order-action btn-return">Return / Refund</button>
                                            </form>
                                        <?php endif; ?>

                                        <button type="button" class="btn-order-action btn-view" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="background:white; padding:40px; border-radius:10px; border:1px solid #eee;">
                            <div class="empty-icon"><img src="../images/purchase-order.png" alt="Box"></div>
                            <p class="empty-text">You haven't placed any orders yet.</p>
                            <a href="home.php#shop" class="link-orange">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="addresses" class="tab-content">
                <div class="card-box">
                    <div class="section-header">
                        <h3 class="section-title">My Address Book</h3>
                        <button class="btn-add-addr" onclick="openAddrModal('add')">+ Add New</button>
                    </div>

                    <div class="address-grid">
                        <?php if (count($addresses) > 0): ?>
                            <?php foreach ($addresses as $addr): ?>
                                <div class="address-card <?php echo $addr['is_default'] ? 'default' : ''; ?>">
                                    <?php if ($addr['is_default']): ?><span class="badge-default">Default</span><?php endif; ?>

                                    <p style="font-size:1.1rem; font-weight:700; margin-bottom:5px;">
                                        <?php echo htmlspecialchars($addr['recipient_name'] ?? $member['full_name']); ?>
                                    </p>
                                    <p style="color:#666; font-size:0.9rem; margin-bottom:10px;">
                                        <?php echo htmlspecialchars($addr['recipient_phone'] ?? $member['phone']); ?>
                                    </p>

                                    <div style="border-top:1px solid #eee; margin:10px 0;"></div>

                                    <p style="color:#555; font-size:0.95rem; line-height:1.5;">
                                        <?php echo htmlspecialchars($addr['address_line1']); ?><br>
                                        <?php if (!empty($addr['address_line2'])) echo htmlspecialchars($addr['address_line2']) . "<br>"; ?>
                                        <?php echo htmlspecialchars($addr['postcode']) . " " . htmlspecialchars($addr['city']); ?><br>
                                        <?php echo htmlspecialchars($addr['state']) . ", Malaysia"; ?>
                                    </p>

                                    <div class="address-actions">
                                        <button class="btn-link-action" onclick='openAddrModal("edit", <?php echo json_encode($addr); ?>)'>
                                            <img src="../images/edit.png" style="width:16px;"> Edit
                                        </button>

                                        <div style="display:flex; gap:10px;">
                                            <?php if (!$addr['is_default']): ?>
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="action" value="set_default">
                                                    <input type="hidden" name="address_id" value="<?php echo $addr['address_id']; ?>">
                                                    <button type="submit" class="btn-link-action" style="color:var(--primary-dark);margin-top:10px;">Set Default</button>
                                                </form>
                                                <form method="POST" style="margin:0;" onsubmit="return confirmSubmit(event, 'Delete this address?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="action" value="delete_address">
                                                    <input type="hidden" name="address_id" value="<?php echo $addr['address_id']; ?>">
                                                    <button type="submit" class="btn-link-action text-red">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state" style="grid-column: 1/-1;">
                                <p class="empty-text">You have no saved addresses.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="profile" class="tab-content">
                <div class="card-box">
                    <div class="section-header">
                        <h3 class="section-title">General Settings</h3>
                        <div style="display:flex; gap:10px;">
                            <button type="button" id="editProfileBtn" onclick="enableProfileEdit()" class="btn-edit-profile">Edit Profile</button>
                            <button type="button" onclick="toggleModal('pwdModal')" class="link-btn">
                                <img src="../images/padlock.png" style="width:14px;"> Change Password
                            </button>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="profileForm" onsubmit="return confirmSubmit(event, 'Are you sure you want to save these changes?');">
                        <input type="hidden" name="form_type" value="update_profile">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                        <div class="profile-upload-area">
                            <img id="previewImg" src="<?php echo !empty($member['image']) ? htmlspecialchars($member['image']) . '?v=' . time() : '../images/user_placeholder.png'; ?>" class="profile-preview-lg">
                            <div class="upload-btn-wrapper" id="uploadWrapper" style="display:none;">
                                <label for="fileInput" class="btn-upload-label">Change Photo</label>
                                <input type="file" name="profile_image" id="fileInput" style="display:none;" onchange="handleFileSelect(this)" accept="image/*">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-input editable-field" value="<?php echo htmlspecialchars($member['full_name']); ?>" readonly required></div>
                            <div><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-input editable-field" value="<?php echo htmlspecialchars($member['phone']); ?>" readonly></div>
                            <div class="col-span-2"><label class="form-label">Email Address (Cannot change)</label><input type="email" value="<?php echo htmlspecialchars($member['email']); ?>" class="form-input" readonly style="opacity:0.7;"></div>
                        </div>

                        <div id="saveProfileBtnGroup" style="overflow:hidden;">
                            <button type="button" class="btn-cancel" onclick="cancelProfileEdit()">Cancel</button>
                            <button type="submit" class="btn-save">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <div id="addrModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="addrModalTitle">Add New Address</h3>
                <button class="close-modal" onclick="toggleModal('addrModal')">&times;</button>
            </div>
            <form method="POST" class="modal-body" id="addrForm">
                <input type="hidden" name="form_type" value="save_address">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="address_id" id="modal_address_id" value="">

                <div class="form-row-split">
                    <div class="form-col"><label>Recipient Name *</label><input type="text" name="recipient_name" id="modal_r_name" required></div>
                    <div class="form-col"><label>Phone *</label><input type="text" name="recipient_phone" id="modal_r_phone" required></div>
                </div>
                <div class="form-group"><label>Address Line 1 *</label><input type="text" name="address_line1" id="modal_line1" required placeholder="Street address, P.O. box"></div>
                <div class="form-group"><label>Address Line 2 (Optional)</label><input type="text" name="address_line2" id="modal_line2" placeholder="Apartment, suite, unit, etc."></div>
                <div class="form-row-split">
                    <div class="form-col"><label>Postcode *</label><input type="text" name="postcode" id="modal_postcode" maxlength="5" required placeholder="e.g. 81300"></div>
                    <div class="form-col"><label>City *</label><input type="text" name="city" id="modal_city" required placeholder="Auto-filled"></div>
                </div>
                <div class="form-group"><label>State *</label>
                    <select name="state" id="modal_state" required>
                        <option value="">Select State</option>
                        <option value="Johor">Johor</option>
                        <option value="Kedah">Kedah</option>
                        <option value="Kelantan">Kelantan</option>
                        <option value="Kuala Lumpur">Kuala Lumpur</option>
                        <option value="Labuan">Labuan</option>
                        <option value="Melaka">Melaka</option>
                        <option value="Negeri Sembilan">Negeri Sembilan</option>
                        <option value="Pahang">Pahang</option>
                        <option value="Penang">Penang</option>
                        <option value="Perak">Perak</option>
                        <option value="Perlis">Perlis</option>
                        <option value="Putrajaya">Putrajaya</option>
                        <option value="Sabah">Sabah</option>
                        <option value="Sarawak">Sarawak</option>
                        <option value="Selangor">Selangor</option>
                        <option value="Terengganu">Terengganu</option>
                    </select>
                </div>
                <div class="checkbox-group"><input type="checkbox" name="is_default" id="modal_is_default"><label for="modal_is_default">Set as default shipping address</label></div>
                <button type="submit" class="btn-save-full">Save Address</button>
            </form>
        </div>
    </div>

    <div id="pwdModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button class="close-modal" onclick="toggleModal('pwdModal')">&times;</button>
            </div>
            <form method="POST" action="" id="pwdForm" class="modal-body" onsubmit="return showLoading(this);">
                <input type="hidden" name="form_type" value="change_password">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group"><label>Current Password</label>
                    <div style="position: relative;"><input type="password" name="current_password" id="current_pwd" required style="padding-right: 40px;"><img src="../images/show.png" class="password-toggle" onclick="toggleVisibility('current_pwd', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 20px; cursor: pointer; opacity: 0.6;"></div>
                </div>
                <div class="form-group"><label>New Password</label>
                    <div style="position: relative;"><input type="password" name="new_password" id="new_pwd" required style="padding-right: 40px;"><img src="../images/show.png" class="password-toggle" onclick="toggleVisibility('new_pwd', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 20px; cursor: pointer; opacity: 0.6;"></div><small id="pwd_msg" style="display:block; margin-top:6px; font-size:0.8rem; color:#666; min-height:18px;"></small>
                </div>
                <div class="form-group" style="margin-bottom: 24px;"><label>Confirm Password</label>
                    <div style="position: relative;"><input type="password" name="confirm_password" id="confirm_pwd" required style="padding-right: 40px;"><img src="../images/show.png" class="password-toggle" onclick="toggleVisibility('confirm_pwd', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 20px; cursor: pointer; opacity: 0.6;"></div><small id="match_msg" style="display:block; margin-top:6px; font-size:0.8rem; color:#666; min-height:18px;"></small>
                </div>
                <button type="submit" id="btnUpdatePwd" class="btn-save-full">Update Password</button>
            </form>
        </div>
    </div>

    <div id="orderModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Order Details <span id="modal_order_id" style="color:#888; font-weight:400;"></span></h3>
                <button class="close-modal" onclick="toggleModal('orderModal')">&times;</button>
            </div>

            <div class="modal-body" style="padding-top: 0;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 20px; background:#f9fafb; padding:15px; border-radius:8px;">
                    <div>
                        <div style="font-size:0.85rem; color:#666;">Order Date</div>
                        <strong id="modal_order_date" style="color:#333;"></strong>
                    </div>
                    <div>
                        <div style="font-size:0.85rem; color:#666;">Status</div>
                        <span id="modal_order_status" class="order-status-badge"></span>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:0.85rem; color:#666;">Total Amount</div>
                        <strong id="modal_order_total" style="color:var(--primary-color); font-size:1.1rem;"></strong>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h4 style="font-size:0.95rem; margin-bottom:8px; color:#333;">Shipping Address</h4>
                    <p id="modal_shipping_info" style="font-size:0.9rem; color:#555; line-height:1.5;"></p>
                </div>

                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead style="background:#f4f4f4; position:sticky; top:0;">
                            <tr>
                                <th style="padding:10px; text-align:left; font-size:0.85rem; color:#555;">Product</th>
                                <th style="padding:10px; text-align:center; font-size:0.85rem; color:#555;">Qty</th>
                                <th style="padding:10px; text-align:right; font-size:0.85rem; color:#555;">Price</th>
                            </tr>
                        </thead>
                        <tbody id="modal_order_items_body"></tbody>
                    </table>
                </div>

                <div style="margin-top: 20px; text-align:right; font-size:0.9rem; color:#555;">
                    <div style="display:flex; justify-content:flex-end; gap:20px;">
                        <span>Subtotal: <strong id="modal_subtotal"></strong></span>
                        <span>Shipping: <strong id="modal_shipping"></strong></span>
                        <span>Discount: <strong id="modal_discount" style="color:red;"></strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('active'));

            const content = document.getElementById(tabId);
            if (content) content.classList.add('active');

            const link = document.getElementById('link-' + tabId);
            if (link) link.classList.add('active');
        }

        function enableProfileEdit() {
            document.querySelectorAll('.editable-field').forEach(input => {
                input.removeAttribute('readonly');
                input.style.backgroundColor = '#fff';
                input.style.borderColor = 'var(--primary-color)';
                input.style.cursor = 'text';
            });
            document.getElementById('editProfileBtn').style.display = 'none';
            document.getElementById('saveProfileBtnGroup').style.display = 'flex';
            document.getElementById('uploadWrapper').style.display = 'flex';
        }

        function cancelProfileEdit() {
            document.getElementById('profileForm').reset();
            document.querySelectorAll('.editable-field').forEach(input => {
                input.setAttribute('readonly', 'true');
                input.style.backgroundColor = '';
                input.style.borderColor = '';
                input.style.cursor = 'not-allowed';
            });
            document.getElementById('editProfileBtn').style.display = 'block';
            document.getElementById('saveProfileBtnGroup').style.display = 'none';
            document.getElementById('uploadWrapper').style.display = 'none';
        }

        function openAddrModal(mode, data = null) {
            const modal = document.getElementById('addrModal');
            const title = document.getElementById('addrModalTitle');
            const form = document.getElementById('addrForm');

            if (mode === 'edit' && data) {
                title.textContent = "Edit Address";
                document.getElementById('modal_address_id').value = data.address_id;
                document.getElementById('modal_r_name').value = data.recipient_name || "";
                document.getElementById('modal_r_phone').value = data.recipient_phone || "";
                document.getElementById('modal_line1').value = data.address_line1;
                document.getElementById('modal_line2').value = data.address_line2;
                document.getElementById('modal_postcode').value = data.postcode;
                document.getElementById('modal_city').value = data.city;
                document.getElementById('modal_state').value = data.state;
                document.getElementById('modal_is_default').checked = (data.is_default == 1);
            } else {
                title.textContent = "Add New Address";
                form.reset();
                document.getElementById('modal_address_id').value = "";
            }
            modal.style.display = 'flex';
        }

        function toggleModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }

        function toggleAddrModal() {
            toggleModal('addrModal');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const initialTab = "<?php echo $active_tab; ?>";
            switchTab(initialTab);
        });

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function confirmLogout() {
            Swal.fire({
                title: 'Logout?',
                text: "Are you sure you want to log out?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#F4A261',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Logout'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "logout.php";
                }
            });
        }

        function confirmSubmit(event, message) {
            event.preventDefault();
            const form = event.target;
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#F4A261',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            return false;
        }

        function showLoading(form) {
            const btn = form.querySelector('button[type="submit"]');
            btn.innerHTML = "Saving...";
            btn.classList.add('btn-loading');
            return true;
        }

        function toggleVisibility(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.src = "../images/hide.png";
            } else {
                input.type = "password";
                icon.src = "../images/show.png";
            }
        }

        $(document).ready(function() {
            $("#modal_postcode").on("keyup change", function() {
                var postcode = $(this).val();
                if (postcode.length === 5 && $.isNumeric(postcode)) {
                    $("#modal_city").attr("placeholder", "Searching...");
                    $.ajax({
                        url: "get_location.php",
                        type: "GET",
                        data: {
                            postcode: postcode
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                $("#modal_city").val(response.city);
                                $("#modal_state").val(response.state);
                                $("#modal_city").attr("placeholder", "City");
                            } else {
                                $("#modal_city").val("");
                                $("#modal_city").attr("placeholder", "Not found");
                            }
                        }
                    });
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('btnUpdatePwd');
            const newPwd = document.getElementById('new_pwd');
            const confirmPwd = document.getElementById('confirm_pwd');
            const pwdMsg = document.getElementById('pwd_msg');
            const matchMsg = document.getElementById('match_msg');

            if (newPwd && confirmPwd) {
                function validate() {
                    const val = newPwd.value;
                    const confirmVal = confirmPwd.value;
                    let valid = true;
                    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}$/;

                    if (!regex.test(val)) {
                        pwdMsg.textContent = "Min 6 chars, Upper, Lower & Symbol";
                        pwdMsg.style.color = "#D92D20";
                        valid = false;
                    } else {
                        pwdMsg.textContent = "Strong";
                        pwdMsg.style.color = "#027A48";
                    }

                    if (val !== confirmVal) {
                        matchMsg.textContent = "Mismatch";
                        matchMsg.style.color = "#D92D20";
                        valid = false;
                    } else if (confirmVal.length > 0) {
                        matchMsg.textContent = "Match";
                        matchMsg.style.color = "#027A48";
                    } else {
                        matchMsg.textContent = "";
                    }

                    if (valid && val.length > 0) {
                        btn.disabled = false;
                        btn.style.opacity = "1";
                        btn.style.cursor = "pointer";
                    } else {
                        btn.disabled = true;
                        btn.style.opacity = "0.5";
                        btn.style.cursor = "not-allowed";
                    }
                }
                newPwd.addEventListener('keyup', validate);
                confirmPwd.addEventListener('keyup', validate);
            }
        });

        function viewOrderDetails(orderId) {
            document.getElementById('modal_order_items_body').innerHTML = '<tr><td colspan="3" style="text-align:center; padding:20px;">Loading...</td></tr>';
            toggleModal('orderModal');

            $.ajax({
                url: 'get_order_details.php',
                type: 'GET',
                data: {
                    order_id: orderId
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        const order = res.order;
                        const items = res.items;

                        document.getElementById('modal_order_id').innerText = '#' + order.order_id;
                        document.getElementById('modal_order_date').innerText = new Date(order.order_date).toLocaleDateString('en-GB', {
                            day: 'numeric',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        document.getElementById('modal_order_total').innerText = 'RM ' + parseFloat(order.total_amount).toFixed(2);

                        const statusSpan = document.getElementById('modal_order_status');
                        statusSpan.innerText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                        statusSpan.className = 'order-status-badge status-' + order.status.toLowerCase();

                        document.getElementById('modal_shipping_info').innerHTML =
                            `<strong>${order.shipping_name}</strong> (${order.shipping_phone})<br>${order.shipping_address}`;

                        let rows = '';
                        let subtotal = 0;
                        items.forEach(item => {
                            const totalItemPrice = item.quantity * item.unit_price;
                            subtotal += totalItemPrice;
                            rows += `
                                <tr style="border-bottom:1px solid #eee;">
                                    <td style="padding:10px; display:flex; align-items:center; gap:10px;">
                                        <img src="${item.image_url}" style="width:40px; height:40px; border-radius:4px; object-fit:cover; border:1px solid #eee;">
                                        <span style="font-size:0.9rem; color:#333;">${item.name}</span>
                                    </td>
                                    <td style="padding:10px; text-align:center; font-size:0.9rem;">x${item.quantity}</td>
                                    <td style="padding:10px; text-align:right; font-size:0.9rem; font-weight:600;">RM ${parseFloat(item.unit_price).toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        document.getElementById('modal_order_items_body').innerHTML = rows;

                        document.getElementById('modal_subtotal').innerText = 'RM ' + subtotal.toFixed(2);
                        document.getElementById('modal_shipping').innerText = 'RM ' + parseFloat(order.shipping_fee).toFixed(2);
                        document.getElementById('modal_discount').innerText = '- RM ' + parseFloat(order.discount_amount).toFixed(2);

                    } else {
                        alert("Error: " + res.message);
                        toggleModal('orderModal');
                    }
                },
                error: function() {
                    alert("System error fetching order details.");
                    toggleModal('orderModal');
                }
            });
        }

        function filterOrders(status, tabElement) {
            document.querySelectorAll('.order-tab').forEach(t => t.classList.remove('active'));
            tabElement.classList.add('active');

            const cards = document.querySelectorAll('.order-card');
            cards.forEach(card => {
                if (status === 'all') {
                    card.classList.remove('hidden');
                } else {
                    if (card.dataset.status === status) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                }
            });
        }
    </script>
</body>

</html>