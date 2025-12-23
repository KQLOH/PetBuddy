<?php
session_start();
include '../include/db.php'; 

// --- Initialize Variables ---
$message = "";
$msg_type = "";
$active_tab = 'dashboard';
$member = [];
$orders = [];
$addresses = []; 
$stats = ['total_orders' => 0, 'total_spent' => 0];
$voucher_count = 0;

// --- 1. Security & Session Check ---
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}
$member_id = $_SESSION['member_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- 2. Check for Flash Messages ---
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

        // --- A. Change Password (UNCHANGED) ---
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
                        } else { $message = "System error updating password."; $msg_type = "error"; }
                    } else { $message = "Password too weak."; $msg_type = "error"; }
                } else { $message = "New passwords do not match."; $msg_type = "error"; }
            } else { $message = "Incorrect current password."; $msg_type = "error"; }
        } 
        
        // --- B. Update Profile (UNCHANGED LOGIC) ---
        else if (isset($_POST['form_type']) && $_POST['form_type'] === 'update_profile') {
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
                if($check !== false && in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                        $image_path = $target_file;
                    } else { $upload_error = true; $message = "Upload failed."; $msg_type = "error"; }
                } else { $upload_error = true; $message = "Invalid image."; $msg_type = "error"; }
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
                } catch (PDOException $e) { $message = "Error updating profile."; $msg_type = "error"; }
            }
        }

        // --- C. Save Address (UPDATED: Handles Add AND Edit) ---
        else if (isset($_POST['form_type']) && $_POST['form_type'] === 'save_address') {
            
            // New Fields
            $addr_id = $_POST['address_id'] ?? ''; // If ID exists, we are editing
            $r_name = trim($_POST['recipient_name']);
            $r_phone = trim($_POST['recipient_phone']);
            $addr1 = trim($_POST['address_line1']);
            $addr2 = trim($_POST['address_line2']);
            $city = trim($_POST['city']);
            $state = trim($_POST['state']);
            $postcode = trim($_POST['postcode']);
            $is_default = isset($_POST['is_default']) ? 1 : 0;

            // Handle Default Logic (Reset others to 0 first)
            if ($is_default) {
                $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?")->execute([$member_id]);
            }

            if (!empty($addr_id)) {
                // === EDIT MODE ===
                $sql = "UPDATE member_addresses SET recipient_name=?, recipient_phone=?, address_line1=?, address_line2=?, city=?, state=?, postcode=?, is_default=? WHERE address_id=? AND member_id=?";
                $params = [$r_name, $r_phone, $addr1, $addr2, $city, $state, $postcode, $is_default, $addr_id, $member_id];
                $msg = "Address updated successfully!";
            } else {
                // === ADD MODE ===
                // First address check
                if ($is_default == 0) {
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM member_addresses WHERE member_id = ?");
                    $stmtCheck->execute([$member_id]);
                    if ($stmtCheck->fetchColumn() == 0) { $is_default = 1; }
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
                $message = "Error saving address."; $msg_type = "error";
            }
        }

        // --- D. Set Default Address (UNCHANGED) ---
        else if (isset($_POST['action']) && $_POST['action'] === 'set_default') {
            $addr_id = $_POST['address_id'];
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?")->execute([$member_id]);
            $pdo->prepare("UPDATE member_addresses SET is_default = 1 WHERE address_id = ? AND member_id = ?")->execute([$addr_id, $member_id]);
            $pdo->commit();
            $_SESSION['flash_msg'] = "Default address updated!";
            $_SESSION['flash_type'] = "success";
            $_SESSION['flash_tab'] = "addresses";
            $redirect_needed = true;
        }

        // --- E. Delete Address (UNCHANGED) ---
        else if (isset($_POST['action']) && $_POST['action'] === 'delete_address') {
            $addr_id = $_POST['address_id'];
            if ($pdo->prepare("DELETE FROM member_addresses WHERE address_id = ? AND member_id = ?")->execute([$addr_id, $member_id])) {
                $_SESSION['flash_msg'] = "Address deleted.";
                $_SESSION['flash_type'] = "success";
                $_SESSION['flash_tab'] = "addresses";
                $redirect_needed = true;
            }
        }

        if ($redirect_needed) {
            header("Location: " . $_SERVER['PHP_SELF']); 
            exit;
        }
    }

    // --- 4. FETCH DATA ---
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();

        // Orders
        $orderSql = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                     FROM orders o 
                     LEFT JOIN order_items oi ON o.order_id = oi.order_id
                     WHERE o.member_id = ? 
                     GROUP BY o.order_id 
                     ORDER BY o.order_date DESC LIMIT 10";
        $stmtOrders = $pdo->prepare($orderSql);
        $stmtOrders->execute([$member_id]);
        $orders = $stmtOrders->fetchAll();

        $stats['total_orders'] = count($orders);
        foreach ($orders as $o) { $stats['total_spent'] += $o['total_amount']; }

        // Vouchers
        $today = date('Y-m-d');
        $vSql = "SELECT COUNT(*) FROM vouchers WHERE start_date <= ? AND end_date >= ?";
        $vStmt = $pdo->prepare($vSql);
        $vStmt->execute([$today, $today]);
        $voucher_count = $vStmt->fetchColumn();

        // Addresses (UPDATED to fetch new columns)
        $addrSql = "SELECT * FROM member_addresses WHERE member_id = ? ORDER BY is_default DESC, created_at DESC";
        $stmtAddr = $pdo->prepare($addrSql);
        $stmtAddr->execute([$member_id]);
        $addresses = $stmtAddr->fetchAll();

    } catch (PDOException $e) {
        $message = "Error loading data."; $msg_type = "error";
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
    <style>
        .fade-out { animation: fadeOut 3s forwards; animation-delay: 2s; }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; visibility: hidden; } }
        .btn-loading { opacity: 0.7; pointer-events: none; cursor: wait; }
        
        /* Address Card Styles */
        .address-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px; margin-top: 20px; }
        .address-card { background: #fff; border: 1px solid #EAECF0; border-radius: 8px; padding: 20px; position: relative; transition: all 0.2s; }
        .address-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: var(--primary-color); }
        .address-card.default { border: 2px solid var(--primary-color); background-color: #FFF9F5; }
        .badge-default { background: var(--primary-color); color: white; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; position: absolute; top: 15px; right: 15px; }
        
        /* Action Buttons Row */
        .address-actions { margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px; display: flex; align-items: center; justify-content: space-between; }
        .btn-link-action { background:none; border:none; cursor:pointer; font-size: 0.85rem; color:#666; display:flex; align-items:center; gap:5px; padding: 5px 8px; border-radius: 4px; transition:0.2s; }
        .btn-link-action:hover { background: #f0f0f0; color: #333; }
        .text-red:hover { background: #fee2e2; color: red; }

        /* Profile Readonly Styles */
        .form-input[readonly] { background-color: #f9fafb; border-color: #e5e7eb; color: #6b7280; cursor: not-allowed; }
        .btn-edit-profile { background: #fff; border: 1px solid var(--primary-color); color: var(--primary-color); padding: 8px 16px; border-radius: 6px; cursor: pointer; transition: 0.2s; font-weight:600; }
        .btn-edit-profile:hover { background: var(--primary-color); color: white; }
        #saveProfileBtnGroup { display: none; margin-top: 20px; gap: 10px; }
        .btn-add-addr { background: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; float: right; }
    </style>
</head>
<body>

    <?php include '../include/header.php'; ?>

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
                        <img src="../images/dashboard.png" alt="Dashboard"> Dashboard
                    </div>
                    <div id="link-orders" class="sidebar-link" onclick="switchTab('orders')">
                        <img src="../images/purchase-order.png" alt="Orders"> My Orders
                    </div>
                    <div id="link-addresses" class="sidebar-link" onclick="switchTab('addresses')">
                        <img src="../images/phone-book.png" alt="Addresses" style="width:20px; opacity:0.7;"> Address Book
                    </div>
                    <div id="link-profile" class="sidebar-link" onclick="switchTab('profile')">
                        <img src="../images/profileSetting.png" alt="Settings"> Settings
                    </div>
                    <a href="#" onclick="confirmLogout()" class="sidebar-link text-red">
                        <img src="../images/exit.png" alt="Logout"> Logout
                    </a>
                </nav>
            </div>
        </aside>

        <main>
            <div class="breadcrumb">
                <a href="home.php">Home</a> / <span>My Account</span>
            </div>

            <?php if ($message): ?>
                <div id="alertBox" class="alert <?php echo $msg_type == 'success' ? 'alert-success' : 'alert-error'; ?> fade-out">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div id="dashboard" class="tab-content">
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon-box bg-blue-light"><img src="../images/cart.png" style="width:24px;"></div>
                        <div class="stat-info"><p>Total Orders</p><h3><?php echo $stats['total_orders']; ?></h3></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-box bg-green-light"><img src="../images/wallet.png" style="width:24px;"></div>
                        <div class="stat-info"><p>Total Spent</p><h3>RM <?php echo number_format($stats['total_spent'], 2); ?></h3></div>
                    </div>
                    <div class="stat-card" onclick="window.location.href='vouchers.php'" style="cursor: pointer;">
                        <div class="stat-icon-box bg-orange-light"><img src="../images/voucher.png" style="width:24px;"></div>
                        <div class="stat-info"><p>Available Vouchers</p><h3><?php echo $voucher_count ?? 0; ?></h3></div>
                    </div>
                </div>

                <div class="card-box">
                    <h3 class="section-title">Recent Activity</h3>
                    <?php if (count($orders) > 0): ?>
                        <div class="activity-list">
                            <?php foreach (array_slice($orders, 0, 3) as $order): ?>
                                <div class="activity-item">
                                    <div>
                                        <strong>Order #<?php echo $order['order_id']; ?></strong><br>
                                        <span style="font-size:0.85rem; color:#777;"><?php echo date('d M Y', strtotime($order['order_date'])); ?></span>
                                    </div>
                                    <div class="text-right">
                                        <div style="font-weight:bold;">RM <?php echo number_format($order['total_amount'], 2); ?></div>
                                        <span class="status-badge <?php echo $order['status']=='completed'?'status-completed':'status-pending'; ?>"><?php echo ucfirst($order['status']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state"><p class="empty-text">No recent activity.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="orders" class="tab-content">
                <div class="card-box" style="min-height: 400px;">
                    <h3 class="section-title">Order History</h3>
                    <?php if (count($orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead><tr><th>Order ID</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                            <td><?php echo $order['item_count']; ?> Items</td>
                                            <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td><span class="status-badge <?php echo $order['status']=='completed'?'status-completed':'status-pending'; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="margin-top: 4rem;">
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
                                        <?php if(!empty($addr['address_line2'])) echo htmlspecialchars($addr['address_line2']) . "<br>"; ?>
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
                                                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this address?');">
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
                            <div class="empty-state" style="grid-column: 1/-1;"><p class="empty-text">You have no saved addresses.</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="profile" class="tab-content">
                <div class="card-box">
                    <div class="section-header">
                        <h3 class="section-title">General Settings</h3>
                        <div style="display:flex; gap:10px;">
                            <button type="button" id="editProfileBtn" onclick="enableProfileEdit()" class="btn-edit-profile">
                                Edit Profile
                            </button>
                            <button type="button" onclick="toggleModal('pwdModal')" class="link-btn">
                                <img src="../images/padlock.png" style="width:14px;"> Change Password
                            </button>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="profileForm" onsubmit="return confirm('Are you sure you want to save these changes?');">
                        <input type="hidden" name="form_type" value="update_profile">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="profile-upload-area">
                            <img id="previewImg" src="<?php echo !empty($member['image']) ? htmlspecialchars($member['image']).'?v='.time() : '../images/user_placeholder.png'; ?>" class="profile-preview-lg">
                            <div class="upload-btn-wrapper" id="uploadWrapper" style="display:none;">
                                <label for="fileInput" class="btn-upload-label">Change Photo</label>
                                <input type="file" name="profile_image" id="fileInput" style="display:none;" onchange="handleFileSelect(this)" accept="image/*">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-input editable-field" value="<?php echo htmlspecialchars($member['full_name']); ?>" readonly required>
                            </div>
                            <div>
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-input editable-field" value="<?php echo htmlspecialchars($member['phone']); ?>" readonly>
                            </div>
                            <div class="col-span-2">
                                <label class="form-label">Email Address (Cannot change)</label>
                                <input type="email" value="<?php echo htmlspecialchars($member['email']); ?>" class="form-input" readonly style="opacity:0.7;">
                            </div>
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
                    <div class="form-col">
                        <label>Recipient Name *</label>
                        <input type="text" name="recipient_name" id="modal_r_name" required>
                    </div>
                    <div class="form-col">
                        <label>Phone *</label>
                        <input type="text" name="recipient_phone" id="modal_r_phone" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Address Line 1 *</label>
                    <input type="text" name="address_line1" id="modal_line1" required placeholder="Street address, P.O. box">
                </div>

                <div class="form-group">
                    <label>Address Line 2 (Optional)</label>
                    <input type="text" name="address_line2" id="modal_line2" placeholder="Apartment, suite, unit, etc.">
                </div>

                <div class="form-row-split">
                    <div class="form-col">
                        <label>Postcode *</label>
                        <input type="text" name="postcode" id="modal_postcode" maxlength="5" required placeholder="e.g. 81300">
                    </div>
                    <div class="form-col">
                        <label>City *</label>
                        <input type="text" name="city" id="modal_city" required placeholder="Auto-filled">
                    </div>
                </div>

                <div class="form-group">
                    <label>State *</label>
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

                <div class="checkbox-group">
                    <input type="checkbox" name="is_default" id="modal_is_default">
                    <label for="modal_is_default">Set as default shipping address</label>
                </div>

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
                
                <div class="form-group">
                    <label>Current Password</label>
                    <div style="position: relative;">
                        <input type="password" name="current_password" id="current_pwd" required style="padding-right: 40px;">
                        <img src="../images/show.png" class="password-toggle" onclick="toggleVisibility('current_pwd', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 20px; cursor: pointer; opacity: 0.6;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>New Password</label>
                    <div style="position: relative;">
                        <input type="password" name="new_password" id="new_pwd" required style="padding-right: 40px;">
                        <img src="../images/show.png" class="password-toggle" onclick="toggleVisibility('new_pwd', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 20px; cursor: pointer; opacity: 0.6;">
                    </div>
                    <small id="pwd_msg" style="display:block; margin-top:6px; font-size:0.8rem; color:#666; min-height:18px;"></small>
                </div>
                
                <div class="form-group" style="margin-bottom: 24px;">
                    <label>Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" name="confirm_password" id="confirm_pwd" required style="padding-right: 40px;">
                        <img src="../images/show.png" class="password-toggle" onclick="toggleVisibility('confirm_pwd', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); width: 20px; cursor: pointer; opacity: 0.6;">
                    </div>
                    <small id="match_msg" style="display:block; margin-top:6px; font-size:0.8rem; color:#666; min-height:18px;"></small>
                </div>

                <button type="submit" id="btnUpdatePwd" class="btn-save-full">Update Password</button>
            </form>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>

<script>
        // --- Tab Logic ---
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('active'));
            
            // Activate content
            const content = document.getElementById(tabId);
            if(content) content.classList.add('active');
            
            // Activate sidebar link
            const link = document.getElementById('link-' + tabId);
            if(link) link.classList.add('active');
        }

        // --- Profile Edit Logic (Fixed Cancel) ---
        function enableProfileEdit() {
            // Enable inputs
            document.querySelectorAll('.editable-field').forEach(input => {
                input.removeAttribute('readonly');
                input.style.backgroundColor = '#fff';
                input.style.borderColor = 'var(--primary-color)';
                input.style.cursor = 'text';
            });
            // Show Save/Cancel, Hide Edit
            document.getElementById('editProfileBtn').style.display = 'none';
            document.getElementById('saveProfileBtnGroup').style.display = 'flex';
            document.getElementById('uploadWrapper').style.display = 'flex'; // Changed to flex for better alignment
        }

        function cancelProfileEdit() {
            // 1. Reset the form values to what they were before editing
            document.getElementById('profileForm').reset();

            // 2. Lock the inputs again
            document.querySelectorAll('.editable-field').forEach(input => {
                input.setAttribute('readonly', 'true');
                input.style.backgroundColor = ''; // Reverts to CSS default (gray)
                input.style.borderColor = '';     // Reverts to CSS default
                input.style.cursor = 'not-allowed';
            });

            // 3. Reset Button Visibility
            document.getElementById('editProfileBtn').style.display = 'block'; // Show Edit button
            document.getElementById('saveProfileBtnGroup').style.display = 'none'; // Hide Save/Cancel
            document.getElementById('uploadWrapper').style.display = 'none'; // Hide Upload button
            
            // IMPORTANT: We DO NOT call window.location.reload() here.
            // This keeps the user on the current tab instantly.
        }

        // --- Address Modal Logic (Add vs Edit) ---
        function openAddrModal(mode, data = null) {
            const modal = document.getElementById('addrModal');
            const title = document.getElementById('addrModalTitle');
            const form = document.getElementById('addrForm');

            if (mode === 'edit' && data) {
                // Edit Mode: Pre-fill data
                title.textContent = "Edit Address";
                document.getElementById('modal_address_id').value = data.address_id;
                document.getElementById('modal_r_name').value = data.recipient_name || "";
                document.getElementById('modal_r_phone').value = data.recipient_phone || "";
                document.getElementById('modal_line1').value = data.address_line1;
                document.getElementById('modal_line2').value = data.address_line2;
                document.getElementById('modal_postcode').value = data.postcode;
                document.getElementById('modal_city').value = data.city;
                document.getElementById('modal_state').value = data.state;
                
                // Handle Checkbox
                document.getElementById('modal_is_default').checked = (data.is_default == 1);
            } else {
                // Add Mode: Clear form
                title.textContent = "Add New Address";
                form.reset();
                document.getElementById('modal_address_id').value = ""; // Empty ID
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

        // --- General Utilities ---
        document.addEventListener('DOMContentLoaded', function() {
            // Load the active tab from PHP
            const initialTab = "<?php echo $active_tab; ?>";
            switchTab(initialTab);

            // Auto-dismiss alerts
            setTimeout(() => { 
                const alert = document.getElementById('alertBox'); 
                if(alert) alert.style.display = 'none'; 
            }, 5000);
        });

        function handleFileSelect(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) { document.getElementById('previewImg').src = e.target.result; }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function confirmLogout() {
            if(confirm("Are you sure you want to log out?")) { window.location.href = "logout.php"; }
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
                input.type = "text"; icon.src = "../images/hide.png";
            } else {
                input.type = "password"; icon.src = "../images/show.png";
            }
        }

        // --- Postcode Auto-fill ---
        $(document).ready(function() {
            $("#modal_postcode").on("keyup change", function() {
                var postcode = $(this).val();
                if (postcode.length === 5 && $.isNumeric(postcode)) {
                    $("#modal_city").attr("placeholder", "Searching...");
                    $.ajax({
                        url: "get_location.php",
                        type: "GET",
                        data: { postcode: postcode },
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
        
        // --- Password Validation Logic ---
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('btnUpdatePwd');
            const newPwd = document.getElementById('new_pwd');
            const confirmPwd = document.getElementById('confirm_pwd');
            const pwdMsg = document.getElementById('pwd_msg');
            const matchMsg = document.getElementById('match_msg');

            if(newPwd && confirmPwd) {
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
    </script>
</body>
</html>