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

            if (empty($current_pwd) || empty($new_pwd) || empty($confirm_pwd)) {
                $message = "All password fields are required.";
                $msg_type = "error";
            } elseif ($new_pwd !== $confirm_pwd) {
                $message = "New passwords do not match.";
                $msg_type = "error";
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}$/', $new_pwd)) {
                $message = "Password must be at least 6 characters, contain 1 uppercase, 1 lowercase, and 1 symbol.";
                $msg_type = "error";
            } else {
                $stmt = $pdo->prepare("SELECT password_hash FROM members WHERE member_id = ?");
                $stmt->execute([$member_id]);
                $userAuth = $stmt->fetch();

                if ($userAuth && password_verify($current_pwd, $userAuth['password_hash'])) {
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
                    $message = "Incorrect current password.";
                    $msg_type = "error";
                }
            }
        } else if (isset($_POST['form_type']) && $_POST['form_type'] === 'update_profile') {
            $active_tab = 'profile';
            $full_name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if (empty($full_name)) {
                $message = "Full Name cannot be empty.";
                $msg_type = "error";
            } elseif (!empty($phone) && !preg_match('/^[0-9\-\+ ]{9,15}$/', $phone)) {
                $message = "Invalid phone number format.";
                $msg_type = "error";
            } else {
                $clean_phone = preg_replace('/[^0-9]/', '', $phone);

                $image_path = null;
                $upload_error = false;

                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $target_dir = "../uploads/";
                    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                    $file_ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));

                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (in_array($file_ext, $allowed_types)) {
                        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
                        if ($check !== false) {
                            $new_filename = "mem_" . $member_id . "_" . time() . "." . $file_ext;
                            $target_file = $target_dir . $new_filename;
                            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                                $image_path = "uploads/" . $new_filename;
                            } else {
                                $upload_error = true;
                                $message = "Failed to move uploaded file.";
                                $msg_type = "error";
                            }
                        } else {
                            $upload_error = true;
                            $message = "File is not an image.";
                            $msg_type = "error";
                        }
                    } else {
                        $upload_error = true;
                        $message = "Only JPG, JPEG, PNG & GIF files are allowed.";
                        $msg_type = "error";
                    }
                }

                if (!$upload_error) {
                    try {
                        if ($image_path) {
                            $sql = "UPDATE members SET full_name = ?, phone = ?, image = ? WHERE member_id = ?";
                            $pdo->prepare($sql)->execute([$full_name, $clean_phone, $image_path, $member_id]);
                        } else {
                            $sql = "UPDATE members SET full_name = ?, phone = ? WHERE member_id = ?";
                            $pdo->prepare($sql)->execute([$full_name, $clean_phone, $member_id]);
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
            }
        } else if (isset($_POST['form_type']) && $_POST['form_type'] === 'save_address') {
            $active_tab = 'addresses';
            $addr_id = $_POST['address_id'] ?? '';
            $r_name = trim($_POST['recipient_name']);
            $r_phone = trim($_POST['recipient_phone']);
            $addr1 = trim($_POST['address_line1']);
            $addr2 = trim($_POST['address_line2']);
            $city = trim($_POST['city']);
            $state = trim($_POST['state']);
            $postcode = trim($_POST['postcode']);
            $is_default = isset($_POST['is_default']) ? 1 : 0;

            if (empty($r_name) || empty($r_phone) || empty($addr1) || empty($city) || empty($state) || empty($postcode)) {
                $message = "Please fill in all required fields marked with *.";
                $msg_type = "error";
            } elseif (!preg_match('/^\d{5}$/', $postcode)) {
                $message = "Postcode must be exactly 5 digits.";
                $msg_type = "error";
            } elseif (!preg_match('/^[0-9\-\+ ]{9,15}$/', $r_phone)) {
                $message = "Invalid phone number format.";
                $msg_type = "error";
            } else {
                $clean_phone = preg_replace('/[^0-9]/', '', $r_phone);

                if ($is_default) {
                    $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?")->execute([$member_id]);
                }

                if (!empty($addr_id)) {
                    $sql = "UPDATE member_addresses SET recipient_name=?, recipient_phone=?, address_line1=?, address_line2=?, city=?, state=?, postcode=?, is_default=? WHERE address_id=? AND member_id=?";
                    $params = [$r_name, $clean_phone, $addr1, $addr2, $city, $state, $postcode, $is_default, $addr_id, $member_id];
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
                    $params = [$r_name, $clean_phone, $addr1, $addr2, $city, $state, $postcode, $is_default, $member_id];
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
            }
        } else if (isset($_POST['action']) && $_POST['action'] === 'set_default') {
            $active_tab = 'addresses';
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
            $active_tab = 'addresses';
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
            }
        } else if (isset($_POST['action']) && in_array($_POST['action'], ['cancel_order', 'complete_order', 'request_return'])) {
            $active_tab = 'orders';
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
                $message = "Action not allowed.";
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
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/memberProfileStyle.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <?php include '../include/header.php'; ?>

    <?php if ($message): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                showCustomAlert('<?php echo $msg_type; ?>', '<?php echo $msg_type == "success" ? "Success" : "Error"; ?>', '<?php echo $message; ?>');
            });
        </script>
    <?php endif; ?>

    <div class="dashboard-container">

        <aside>
            <div class="card-box">
                <div class="user-brief">
                    <?php
                    $image_path = !empty($member['image']) ? '../' . $member['image'] : '';
                    $image_exists = !empty($image_path) && file_exists($image_path);
                    if ($image_exists): ?>
                        <img src="<?php echo htmlspecialchars($image_path); ?>?v=<?php echo time(); ?>" class="user-avatar">
                    <?php else: ?>
                        <div class="user-avatar"><?php echo strtoupper(substr($member['full_name'] ?? 'U', 0, 1)); ?></div>
                    <?php endif; ?>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($member['full_name'] ?? 'User'); ?></h4>
                        <p><?php echo htmlspecialchars($member['email'] ?? ''); ?></p>
                    </div>
                </div>

                <nav class="sidebar-nav">
                    <div id="link-dashboard" class="sidebar-link active" onclick="switchTab('dashboard')">Dashboard</div>
                    <div id="link-orders" class="sidebar-link" onclick="switchTab('orders')">My Orders</div>
                    <div id="link-addresses" class="sidebar-link" onclick="switchTab('addresses')">Address Book</div>
                    <div id="link-profile" class="sidebar-link" onclick="switchTab('profile')">Settings</div>
                    <a href="#" onclick="confirmLogout()" class="sidebar-link text-red" style="text-decoration: none;">Logout</a>
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
                    <div class="stat-card">
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

                                        <?php if ($order['status'] == 'completed'): ?>
                                            <button type="button" class="btn-order-action btn-view" onclick="openReviewModal(<?php echo $order['order_id']; ?>)">
                                                <img src="../images/review.png" style="width:14px; margin-right:5px;"> Review
                                            </button>
                                        <?php endif; ?>

                                        <button type="button" class="btn-order-action btn-view" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                            View Details
                                        </button>

                                        <button type="button" class="btn-order-action btn-view" onclick="sendReceiptEmail(<?php echo $order['order_id']; ?>, this)">
                                            <img src="../images/mail.png" alt="Email" style="width: 16px; height: 16px; margin-right: 6px; vertical-align: middle;"> E-Receipt (Email)
                                        </button>

                                        <a href="download_receipt.php?order_id=<?php echo $order['order_id']; ?>" class="btn-order-action btn-view">
                                            <img src="../images/pdf.png" alt="PDF" style="width: 16px; height: 16px; margin-right: 6px; vertical-align: middle;"> E-Receipt (PDF)
                                        </a>
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
                        <button type="button" class="btn-add-addr" onclick="openAddrModal('add')">+ Add New</button>
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
                            <img id="previewImg" src="<?php echo !empty($member['image']) ? htmlspecialchars('../' . $member['image']) . '?v=' . time() : '../images/user_placeholder.png'; ?>" class="profile-preview-lg">
                            <div class="upload-btn-wrapper" id="uploadWrapper" style="display:none;">
                                <label for="fileInput" class="btn-upload-label">Change Photo</label>
                                <input type="file" name="profile_image" id="fileInput" style="display:none;" onchange="handleFileSelect(this)" accept="image/*">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-input editable-field" value="<?php echo htmlspecialchars($member['full_name']); ?>" readonly required></div>
                            <div><label class="form-label">Phone Number</label><input type="text" name="phone" class="form-input editable-field" value="<?php echo htmlspecialchars($member['phone']); ?>" readonly></div>
                            <div class="col-span-2"><label class="form-label">Email Address</label><input type="email" value="<?php echo htmlspecialchars($member['email']); ?>" class="form-input" readonly style="opacity:0.7;"></div>
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

    <div id="addrModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 id="addrModalTitle">Add New Address</h3>
                <button class="close-modal" onclick="toggleModal('addrModal')">
                    <img src="../images/error.png" style="width:16px; height:16px;">
                </button>
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
                <button class="close-modal" onclick="toggleModal('pwdModal')">
                    <img src="../images/error.png" style="width:16px; height:16px;">
                </button>
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
                <button class="close-modal" onclick="toggleModal('orderModal')">
                    <img src="../images/error.png" style="width:16px; height:16px;">
                </button>
            </div>

            <div class="modal-body" style="padding-top: 0;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 20px; background:#f9fafb; padding:15px; border-radius:8px;">
                    <div>
                        <div style="font-size:0.85rem; color:#666;">Order Date</div><strong id="modal_order_date" style="color:#333;"></strong>
                    </div>
                    <div>
                        <div style="font-size:0.85rem; color:#666;">Status</div><span id="modal_order_status" class="order-status-badge"></span>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:0.85rem; color:#666;">Total Amount</div><strong id="modal_order_total" style="color:var(--primary-color); font-size:1.1rem;"></strong>
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

    <div id="reviewModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Rate Products</h3>
                <button class="close-modal" onclick="toggleModal('reviewModal')">
                    <img src="../images/error.png" style="width:16px; height:16px;">
                </button>            </div>
            <div class="modal-body" id="reviewModalBody" style="max-height: 60vh; overflow-y: auto;">
            </div>
            <div style="padding: 15px; text-align: right; border-top: 1px solid #eee;">
                <button class="btn-save-full" onclick="submitAllReviews()">Submit Reviews</button>
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

        let pendingForm = null;

        function showCustomAlert(type, title, text, autoClose = false) {
            const overlay = document.getElementById('customAlert');
            const icon = document.getElementById('customAlertIcon');
            const btnCancel = document.getElementById('customAlertCancel');

            document.getElementById('customAlertTitle').innerText = title;
            document.getElementById('customAlertText').innerText = text;

            icon.className = 'custom-alert-icon';
            if (type === 'success') {
                icon.classList.add('icon-success');
                icon.innerHTML = '✓';
            } else if (type === 'error') {
                icon.classList.add('icon-error');
                icon.innerHTML = '✕';
            } else {
                icon.classList.add('icon-confirm');
                icon.innerHTML = '?';
            }

            btnCancel.style.display = 'none';
            document.getElementById('customAlertConfirm').onclick = closeCustomAlert;

            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);

            if (autoClose) setTimeout(closeCustomAlert, 2000);
        }

        function confirmSubmit(event, message) {
            event.preventDefault();
            pendingForm = event.target;

            const overlay = document.getElementById('customAlert');
            const icon = document.getElementById('customAlertIcon');
            const btnCancel = document.getElementById('customAlertCancel');

            document.getElementById('customAlertTitle').innerText = 'Are you sure?';
            document.getElementById('customAlertText').innerText = message;

            icon.className = 'custom-alert-icon icon-confirm';
            icon.innerHTML = '?';

            btnCancel.style.display = 'block';
            btnCancel.onclick = closeCustomAlert;

            const btnConfirm = document.getElementById('customAlertConfirm');
            btnConfirm.innerText = 'Yes';
            btnConfirm.onclick = () => {
                pendingForm.submit();
                closeCustomAlert();
            };

            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);

            return false;
        }

        function confirmLogout() {
            const overlay = document.getElementById('customAlert');
            const icon = document.getElementById('customAlertIcon');
            const btnCancel = document.getElementById('customAlertCancel');

            document.getElementById('customAlertTitle').innerText = 'Logout?';
            document.getElementById('customAlertText').innerText = "Are you sure you want to log out?";

            icon.className = 'custom-alert-icon icon-confirm';
            icon.innerHTML = '?';

            btnCancel.style.display = 'block';
            btnCancel.onclick = closeCustomAlert;

            const btnConfirm = document.getElementById('customAlertConfirm');
            btnConfirm.innerText = 'Yes, Logout';
            btnConfirm.onclick = () => {
                window.location.href = "logout.php";
            };

            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('show'), 10);
        }

        function closeCustomAlert() {
            const overlay = document.getElementById('customAlert');
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
                document.getElementById('customAlertConfirm').innerText = 'OK';
            }, 300);
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

        $(document).ready(function() {

            function setStatus(input, isValid, msg) {
                $(input).next('.validation-msg').remove();

                if (!isValid) {
                    $(input).addClass('input-error').removeClass('input-success');
                    $(input).after('<span class="validation-msg">' + msg + '</span>');
                    $(input).closest('form').find('button[type="submit"]').prop('disabled', true).css('opacity', '0.5');
                } else {
                    $(input).removeClass('input-error').addClass('input-success');
                    if ($(input).closest('form').find('.input-error').length === 0) {
                        $(input).closest('form').find('button[type="submit"]').prop('disabled', false).css('opacity', '1');
                    }
                }
            }

            $('input[name="full_name"], input[name="recipient_name"], input[name="address_line1"], input[name="city"]').on('input blur', function() {
                let val = $(this).val().trim();
                setStatus(this, val.length > 0, "This field cannot be empty.");
            });

            $('input[name="phone"], input[name="recipient_phone"]').on('input blur', function() {
                let val = $(this).val().replace(/[^0-9]/g, '');
                let isValid = (val.length >= 9 && val.length <= 15);
                setStatus(this, isValid, "Phone must be 9-15 digits.");
            });

            $('input[name="postcode"]').on('input blur', function() {
                let val = $(this).val();
                let isValid = /^\d{5}$/.test(val);
                setStatus(this, isValid, "Postcode must be exactly 5 digits.");
            });

            $('#new_pwd').on('input blur', function() {
                let val = $(this).val();
                let regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}$/;

                if (val.length === 0) {
                    setStatus(this, false, "Password required.");
                } else if (!regex.test(val)) {
                    setStatus(this, false, "Weak: Need 1 Upper, 1 Lower, 1 Symbol, Min 6 chars.");
                } else {
                    setStatus(this, true, "");
                }

                $('#confirm_pwd').trigger('input');
            });

            $('#confirm_pwd').on('input blur', function() {
                let confirmVal = $(this).val();
                let originalVal = $('#new_pwd').val();

                if (confirmVal === "") {
                    setStatus(this, false, "Please confirm password.");
                } else if (confirmVal !== originalVal) {
                    setStatus(this, false, "Passwords do not match.");
                } else {
                    setStatus(this, true, "");
                }
            });

            $('form').on('submit', function(e) {
                $(this).find('input[required]').each(function() {
                    if ($(this).val().trim() === '') {
                        setStatus(this, false, "This field is required.");
                    }
                });

                if ($(this).find('.input-error').length > 0) {
                    e.preventDefault();
                    if (typeof showCustomAlert === 'function') {
                        showCustomAlert('error', 'Error', 'Please fix the highlighted errors before saving.');
                    } else {
                        alert("Please fix the highlighted errors before saving.");
                    }
                    return false;
                }
            });

        });

        function sendReceiptEmail(orderId, btnElement) {
            const btn = btnElement;
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<img src="../images/mail.png" alt="Email" style="width: 16px; height: 16px; margin-right: 6px; vertical-align: middle; opacity: 0.6;"> Sending...';

            $.ajax({
                url: 'send_receipt.php',
                type: 'POST',
                data: {
                    order_id: orderId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        btn.innerHTML = '<img src="../images/mail.png" alt="Email" style="width: 16px; height: 16px; margin-right: 6px; vertical-align: middle;"> Sent!';
                        btn.style.background = '#4CAF50';
                        setTimeout(function() {
                            btn.innerHTML = originalText;
                            btn.style.background = '#FFB774';
                            btn.disabled = false;
                        }, 2000);
                        if (typeof showCustomAlert === 'function') {
                            showCustomAlert('success', 'Success', response.message, true);
                        } else {
                            alert('✓ ' + response.message);
                        }
                    } else {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        if (typeof showCustomAlert === 'function') {
                            showCustomAlert('error', 'Error', response.message);
                        } else {
                            alert('✗ ' + response.message);
                        }
                    }
                },
                error: function() {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    if (typeof showCustomAlert === 'function') {
                        showCustomAlert('error', 'Error', 'Failed to send receipt. Please try again.');
                    } else {
                        alert('✗ Error sending receipt. Please try again.');
                    }
                }
            });
        }

        function openReviewModal(orderId) {
            document.getElementById('reviewModalBody').innerHTML = '<div style="text-align:center; padding:20px;">Loading products...</div>';
            toggleModal('reviewModal');

            $.ajax({
                url: 'get_order_details.php',
                type: 'GET',
                data: {
                    order_id: orderId
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        let html = '';
                        res.items.forEach(item => {
                            html += `
                                <div class="review-item" data-product-id="${item.product_id}">
                                    <div class="review-product-info">
                                        <img src="${item.image_url}" class="review-product-img">
                                        <div>
                                            <div style="font-weight:600; font-size:0.95rem;">${item.name}</div>
                                            <div style="font-size:0.85rem; color:#888;">x${item.quantity}</div>
                                        </div>
                                    </div>
                                    <div style="margin-bottom:8px;">Rating:</div>
                                    <div class="star-rating">
                                        <input type="radio" id="star5_${item.product_id}" name="rating_${item.product_id}" value="5"><label for="star5_${item.product_id}">★</label>
                                        <input type="radio" id="star4_${item.product_id}" name="rating_${item.product_id}" value="4"><label for="star4_${item.product_id}">★</label>
                                        <input type="radio" id="star3_${item.product_id}" name="rating_${item.product_id}" value="3"><label for="star3_${item.product_id}">★</label>
                                        <input type="radio" id="star2_${item.product_id}" name="rating_${item.product_id}" value="2"><label for="star2_${item.product_id}">★</label>
                                        <input type="radio" id="star1_${item.product_id}" name="rating_${item.product_id}" value="1"><label for="star1_${item.product_id}">★</label>
                                    </div>
                                    <textarea class="review-textarea" placeholder="Write your review here..."></textarea>
                                </div>
                            `;
                        });
                        document.getElementById('reviewModalBody').innerHTML = html;
                    } else {
                        document.getElementById('reviewModalBody').innerHTML = '<p style="color:red; text-align:center;">Failed to load items.</p>';
                    }
                }
            });
        }

        function submitAllReviews() {
            const items = document.querySelectorAll('.review-item');
            let promises = [];

            items.forEach(div => {
                const pid = div.getAttribute('data-product-id');
                const ratingInput = div.querySelector(`input[name="rating_${pid}"]:checked`);
                const comment = div.querySelector('textarea').value;

                if (ratingInput) {
                    const rating = ratingInput.value;
                    const req = $.ajax({
                        url: 'submit_review.php',
                        type: 'POST',
                        data: {
                            product_id: pid,
                            rating: rating,
                            comment: comment
                        }
                    });
                    promises.push(req);
                }
            });

            if (promises.length === 0) {
                showCustomAlert('error', 'Oops', 'Please select a star rating for at least one product.');
                return;
            }

            Promise.all(promises).then(() => {
                showCustomAlert('success', 'Thank You!', 'Your reviews have been submitted.', true);
                toggleModal('reviewModal');
            }).catch(() => {
                showCustomAlert('error', 'Error', 'Some reviews failed to save.');
            });
        }
    </script>
</body>

</html>