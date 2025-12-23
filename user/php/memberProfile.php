<?php
session_start();
include '../include/db.php'; 

// --- Initialize Variables ---
$message = "";
$msg_type = "";
$active_tab = 'dashboard';
$member = [];
$orders = [];
$addresses = []; // New variable for address list
$stats = ['total_orders' => 0, 'total_spent' => 0];
$voucher_count = 0; // 初始化 Voucher 数量

// --- 1. Security & Session Check ---
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}
$member_id = $_SESSION['member_id'];

// CSRF Token Init
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- 2. Check for Flash Messages (PRG Pattern) ---
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
    // --- 3. FORM HANDLING (POST Request) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Security validation failed. Please refresh the page.");
        }

        $redirect_needed = false;

        // --- A. Change Password (Your existing function) ---
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
                            $message = "System error updating password."; $msg_type = "error";
                        }
                    } else {
                        $message = "Password too weak (Min 6 chars, Uppercase, Lowercase & Symbol)."; $msg_type = "error";
                    }
                } else {
                    $message = "New passwords do not match."; $msg_type = "error";
                }
            } else {
                $message = "Incorrect current password."; $msg_type = "error";
            }
        } 
        
        // --- B. Update Profile (Your existing function) ---
        else if (isset($_POST['form_type']) && $_POST['form_type'] === 'update_profile') {
            $active_tab = 'profile';
            $full_name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            // Note: We removed 'address' from here because it's now handled in the Address Book
            // But if you still want to update the main table address for legacy support, you can keep it.
            // For now, I'll update name/phone/image as per modern standard.
            
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
                    } else { $upload_error = true; $message = "Failed to upload image."; $msg_type = "error"; }
                } else { $upload_error = true; $message = "Invalid image format."; $msg_type = "error"; }
            }

            if (!$upload_error) {
                try {
                    if ($image_path) {
                        $sql = "UPDATE members SET full_name = ?, phone = ?, image = ? WHERE member_id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$full_name, $phone, $image_path, $member_id]);
                    } else {
                        $sql = "UPDATE members SET full_name = ?, phone = ? WHERE member_id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$full_name, $phone, $member_id]);
                    }
                    $_SESSION['flash_msg'] = "Profile updated successfully!";
                    $_SESSION['flash_type'] = "success";
                    $_SESSION['flash_tab'] = "profile";
                    $redirect_needed = true;
                } catch (PDOException $e) {
                    $message = "Error updating profile."; $msg_type = "error";
                }
            }
        }

        // --- C. Add New Address (NEW FUNCTION) ---
        else if (isset($_POST['form_type']) && $_POST['form_type'] === 'add_address') {
            $addr1 = trim($_POST['address_line1']);
            $addr2 = trim($_POST['address_line2']);
            $city = trim($_POST['city']);
            $state = trim($_POST['state']);
            $postcode = trim($_POST['postcode']);
            $is_default = isset($_POST['is_default']) ? 1 : 0;

            if ($is_default) {
                $pdo->prepare("UPDATE member_addresses SET is_default = 0 WHERE member_id = ?")->execute([$member_id]);
            } else {
                // If it's the first address, force default
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM member_addresses WHERE member_id = ?");
                $stmtCheck->execute([$member_id]);
                if ($stmtCheck->fetchColumn() == 0) { $is_default = 1; }
            }

            $sql = "INSERT INTO member_addresses (member_id, address_line1, address_line2, city, state, postcode, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if ($pdo->prepare($sql)->execute([$member_id, $addr1, $addr2, $city, $state, $postcode, $is_default])) {
                $_SESSION['flash_msg'] = "New address added!";
                $_SESSION['flash_type'] = "success";
                $_SESSION['flash_tab'] = "addresses";
                $redirect_needed = true;
            } else {
                $message = "Error adding address."; $msg_type = "error";
            }
        }

        // --- D. Set Default Address (NEW FUNCTION) ---
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

        // --- E. Delete Address (NEW FUNCTION) ---
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
        // 1. Get Member Info
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();

        // 2. Get Orders & Stats
        // Orders Logic (Existing)
        $orderSql = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                     FROM orders o 
                     LEFT JOIN order_items oi ON o.order_id = oi.order_id
                     WHERE o.member_id = ? 
                     GROUP BY o.order_id 
                     ORDER BY o.order_date DESC LIMIT 10";
        $stmtOrders = $pdo->prepare($orderSql);
        $stmtOrders->execute([$member_id]);
        $orders = $stmtOrders->fetchAll();

        // Address Logic (New)
        $addrSql = "SELECT * FROM member_addresses WHERE member_id = ? ORDER BY is_default DESC, created_at DESC";
        $stmtAddr = $pdo->prepare($addrSql);
        $stmtAddr->execute([$member_id]);
        $addresses = $stmtAddr->fetchAll();

        $stats['total_orders'] = count($orders);
        foreach ($orders as $o) {
            $stats['total_spent'] += $o['total_amount'];
        }

        $today = date('Y-m-d');
        // 统计有多少张 Voucher 是今天可以用的 (开始日期 <= 今天 <= 结束日期)
        $vSql = "SELECT COUNT(*) FROM vouchers WHERE start_date <= ? AND end_date >= ?";
        $vStmt = $pdo->prepare($vSql);
        $vStmt->execute([$today, $today]);
        $voucher_count = $vStmt->fetchColumn();
        // =========================================

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
    <style>
        .fade-out { animation: fadeOut 3s forwards; animation-delay: 2s; }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; visibility: hidden; } }
        .btn-loading { opacity: 0.7; pointer-events: none; cursor: wait; }
        
        /* New Address Styles */
        .address-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin-top: 20px; }
        .address-card { background: #fff; border: 1px solid #EAECF0; border-radius: 8px; padding: 15px; position: relative; }
        .address-card.default { border: 2px solid var(--primary-color); background-color: #FFF9F5; }
        .badge-default { background: var(--primary-color); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; position: absolute; top: 10px; right: 10px; }
        .address-actions { margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px; display: flex; gap: 10px; }
        .btn-sm { font-size: 0.8rem; padding: 5px 10px; border-radius: 4px; border: 1px solid #ccc; background: white; cursor: pointer; }
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
                        <img src="../images/location.png" alt="Addresses" style="width:20px; opacity:0.7;"> Address Book
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
                    
                    <div class="stat-card" onclick="window.location.href='vouchers.php'" style="cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="stat-icon-box bg-orange-light">
                            <img src="../images/voucher.png" style="width:24px;">
                        </div>
                        <div class="stat-info">
                            <p>Available Vouchers</p>
                            <h3><?php echo $voucher_count ?? 0; ?></h3>
                            
                        </div>
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
                        <button class="btn-add-addr" onclick="openAddrModal()">+ Add New</button>
                    </div>

                    <div class="address-grid">
                        <?php if (count($addresses) > 0): ?>
                            <?php foreach ($addresses as $addr): ?>
                                <div class="address-card <?php echo $addr['is_default'] ? 'default' : ''; ?>">
                                    <?php if ($addr['is_default']): ?><span class="badge-default">Default</span><?php endif; ?>
                                    <p><strong><?php echo htmlspecialchars($member['full_name']); ?></strong> (<?php echo htmlspecialchars($member['phone']); ?>)</p>
                                    <p style="color:#555; font-size:0.9rem; margin-top:5px; line-height:1.5;">
                                        <?php echo htmlspecialchars($addr['address_line1']); ?><br>
                                        <?php if(!empty($addr['address_line2'])) echo htmlspecialchars($addr['address_line2']) . "<br>"; ?>
                                        <?php echo htmlspecialchars($addr['postcode']) . " " . htmlspecialchars($addr['city']); ?><br>
                                        <?php echo htmlspecialchars($addr['state']) . ", " . htmlspecialchars($addr['country']); ?>
                                    </p>
                                    <div class="address-actions">
                                        <?php if (!$addr['is_default']): ?>
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="set_default">
                                                <input type="hidden" name="address_id" value="<?php echo $addr['address_id']; ?>">
                                                <button type="submit" class="btn-sm">Set Default</button>
                                            </form>
                                            <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this address?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="delete_address">
                                                <input type="hidden" name="address_id" value="<?php echo $addr['address_id']; ?>">
                                                <button type="submit" class="btn-sm" style="color:red; border-color:#fee2e2;">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size:0.8rem; color:green;">Selected for shipping</span>
                                        <?php endif; ?>
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
                        <button type="button" onclick="toggleModal()" class="link-btn">
                            <img src="../images/padlock.png" style="width:14px; opacity:0.6;"> Change Password
                        </button>
                    </div>

                    <form method="POST" enctype="multipart/form-data" onsubmit="return showLoading(this);">
                        <input type="hidden" name="form_type" value="update_profile">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="profile-upload-area">
                            <?php if (!empty($member['image']) && file_exists($member['image'])): ?>
                                <img id="previewImg" src="<?php echo htmlspecialchars($member['image']); ?>?v=<?php echo time(); ?>" class="profile-preview-lg">
                            <?php else: ?>
                                <div id="previewPlaceholder" class="profile-preview-lg">
                                    <img src="../images/user_placeholder.png" style="width:32px; opacity:0.3;">
                                </div>
                                <img id="previewImg" src="" class="profile-preview-lg" style="display:none;">
                            <?php endif; ?>
                            
                            <div class="upload-btn-wrapper">
                                <label for="fileInput" class="btn-upload-label">Choose file</label>
                                <span class="no-file-text" id="fileName">No file chosen</span>
                                <input type="file" name="profile_image" id="fileInput" style="display:none;" onchange="handleFileSelect(this)" accept="image/*">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($member['full_name'] ?? ''); ?>" class="form-input" required>
                            </div>
                            <div>
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" class="form-input">
                            </div>
                            <div class="col-span-2">
                                <label class="form-label">Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" class="form-input" readonly>
                            </div>
                        </div>

                        <div style="overflow:hidden; margin-top:20px;">
                            <button type="submit" class="btn-save">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <div id="pwdModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Change Password</h3>
                <button class="close-modal" onclick="toggleModal()">&times;</button>
            </div>
            <form method="POST" action="" id="pwdForm" class="modal-body" onsubmit="return showLoading(this);">
                <input type="hidden" name="form_type" value="change_password">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <div style="position: relative;">
                        <input type="password" id="current_pwd" name="current_password" class="form-input" required style="padding-right: 2.5rem;">
                        <img src="../images/show.png" class="password-toggle" onclick="toggleVisibility('current_pwd', this)">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="new_pwd" name="new_password" class="form-input" required style="padding-right: 2.5rem;">
                        <img src="../images/show.png" class="password-toggle" onclick="toggleVisibility('new_pwd', this)">
                    </div>
                    <small id="pwd_msg" style="display:block; margin-top:4px; font-size:0.75rem;"></small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_pwd" name="confirm_password" class="form-input" required style="padding-right: 2.5rem;">
                        <img src="../images/show.png" class="password-toggle" onclick="toggleVisibility('confirm_pwd', this)">
                    </div>
                    <small id="match_msg" style="display:block; margin-top:4px; font-size:0.75rem;"></small>
                </div>

                <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                    <button type="button" onclick="toggleModal()" class="btn-cancel" style="flex:1;">Cancel</button>
                    <button type="submit" id="btnUpdatePwd" class="btn-save" style="flex:1; float:none;" disabled>Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addrModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Add New Address</h3>
                <button class="close-modal" onclick="toggleAddrModal()">&times;</button>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="form_type" value="add_address">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label class="form-label">Address Line 1 *</label>
                    <input type="text" name="address_line1" class="form-input" required placeholder="Street address, P.O. box">
                </div>
                <div class="form-group">
                    <label class="form-label">Address Line 2 (Optional)</label>
                    <input type="text" name="address_line2" class="form-input" placeholder="Apartment, suite, unit, etc.">
                </div>
                <div style="display:flex; gap:10px;">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">Postcode *</label>
                        <input type="text" name="postcode" id="modal_postcode" class="form-input" maxlength="5" required placeholder="e.g. 81300">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label">City *</label>
                        <input type="text" name="city" id="modal_city" class="form-input" required placeholder="Auto-filled">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">State *</label>
                    <select name="state" id="modal_state" class="form-input" required>
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
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="is_default" checked> Set as default shipping address
                    </label>
                </div>
                <button type="submit" class="btn-save" style="width:100%;">Save Address</button>
            </form>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            document.getElementById('link-' + tabId).classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const initialTab = "<?php echo $active_tab; ?>";
            switchTab(initialTab);
        });

        // Modals
        function toggleModal() {
            const modal = document.getElementById('pwdModal');
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }
        function toggleAddrModal() {
            const modal = document.getElementById('addrModal');
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }
        function openAddrModal() { toggleAddrModal(); }

        // Logic
        function toggleVisibility(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text"; icon.src = "../images/hide.png";
            } else {
                input.type = "password"; icon.src = "../images/show.png";
            }
        }

        function handleFileSelect(input) {
            const fileNameSpan = document.getElementById('fileName');
            const previewImg = document.getElementById('previewImg');
            const placeholder = document.getElementById('previewPlaceholder');
            if (input.files && input.files[0]) {
                fileNameSpan.textContent = input.files[0].name;
                var reader = new FileReader();
                reader.onload = function(e) {
                    if(placeholder) placeholder.style.display = 'none';
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'flex';
                }
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

        setTimeout(function() {
            const alertBox = document.getElementById('alertBox');
            if(alertBox) { alertBox.style.display = 'none'; }
        }, 5000);

        // --- LOCAL Postcode Detection Logic ---
        $(document).ready(function() {
            $("#modal_postcode").on("keyup change", function() {
                var postcode = $(this).val();
                if (postcode.length === 5 && $.isNumeric(postcode)) {
                    $("#modal_city").attr("placeholder", "Searching...");
                    
                    // Call our LOCAL helper file
                    $.ajax({
                        url: "get_location.php", 
                        type: "GET",
                        data: { postcode: postcode },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                $("#modal_city").val(response.city);
                                var state = response.state;
                                $("#modal_state option").each(function() {
                                    if ($(this).val() === state || $(this).text() === state) {
                                        $(this).prop('selected', true);
                                    }
                                });
                                $("#modal_city").attr("placeholder", "City");
                            } else {
                                $("#modal_city").val("");
                                $("#modal_city").attr("placeholder", "Not found in local DB");
                            }
                        },
                        error: function() {
                            $("#modal_city").val("");
                            $("#modal_city").attr("placeholder", "Error searching");
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