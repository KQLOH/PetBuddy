<?php
session_start();
include '../include/db.php'; 

$message = "";
$msg_type = "";
$active_tab = 'dashboard'; // Default tab
$member = [];
$orders = [];
$stats = ['total_orders' => 0, 'total_spent' => 0];

if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}
$member_id = $_SESSION['member_id'];

if (isset($pdo)) {
    // --- FORM HANDLING ---
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // 1. Change Password
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
                            $message = "Password changed successfully!";
                            $msg_type = "success";
                        } else {
                            $message = "System error updating password.";
                            $msg_type = "error";
                        }
                    } else {
                        $message = "Password too weak (Min 6 chars, Uppercase, Lowercase & Symbol).";
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
        } 
        
        // 2. Update Profile
        else {
            $active_tab = 'profile';
            $full_name = $_POST['full_name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $address = $_POST['address'] ?? '';
            
            $image_path = null;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $target_dir = "uploads/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true); 
                
                $file_ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
                $new_filename = "mem_" . $member_id . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;
                
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($file_ext, $allowed)) {
                    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                        $image_path = $target_file;
                    } else {
                        $message = "Failed to upload image.";
                        $msg_type = "error";
                    }
                } else {
                    $message = "Only JPG, PNG, and GIF allowed.";
                    $msg_type = "error";
                }
            }

            try {
                if ($image_path) {
                    $sql = "UPDATE members SET full_name = ?, phone = ?, address = ?, image = ? WHERE member_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$full_name, $phone, $address, $image_path, $member_id]);
                } else {
                    $sql = "UPDATE members SET full_name = ?, phone = ?, address = ? WHERE member_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$full_name, $phone, $address, $member_id]);
                }
                $message = "Profile updated successfully!";
                $msg_type = "success";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "Unknown column 'address'") !== false) {
                    $message = "Error: 'address' column missing in DB.";
                } else {
                    $message = "Error updating profile.";
                }
                $msg_type = "error";
            }
        }
    }

    // --- FETCH DATA ---
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();

        $orderSql = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                     FROM orders o 
                     LEFT JOIN order_items oi ON o.order_id = oi.order_id
                     WHERE o.member_id = ? 
                     GROUP BY o.order_id 
                     ORDER BY o.order_date DESC 
                     LIMIT 10";
        $stmtOrders = $pdo->prepare($orderSql);
        $stmtOrders->execute([$member_id]);
        $orders = $stmtOrders->fetchAll();

        $stats['total_orders'] = count($orders);
        foreach ($orders as $o) {
            $stats['total_spent'] += $o['total_amount'];
        }
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
    <title>My Dashboard - PetBuddy</title>
    <!-- Global Styles -->
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- INLINE CSS FOR DASHBOARD -->
    <style>
        /* --- Layout --- */
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            width: 100%;
            flex: 1;
        }
        @media (max-width: 768px) {
            .dashboard-container { grid-template-columns: 1fr; }
        }

        .breadcrumb { margin-bottom: 1.5rem; font-size: 0.9rem; color: #888; }
        .breadcrumb a { color: #888; text-decoration: none; }

        /* --- Card Components --- */
        .card-box {
            background: var(--white);
            border-radius: 12px;
            border: 1px solid #EAECF0;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* --- Sidebar --- */
        .user-brief {
            display: flex; align-items: center; gap: 1rem; padding-bottom: 1.5rem; margin-bottom: 1rem; border-bottom: 1px solid #F2F4F7;
        }
        .user-avatar {
            width: 48px; height: 48px; border-radius: 50%; background: #F2F4F7; border: 1px solid #EAECF0;
            display: flex; align-items: center; justify-content: center; font-weight: bold; color: #667085; font-size: 1.2rem;
            object-fit: cover;
        }
        .user-info h4 { margin: 0; font-size: 1rem; font-weight: 700; color: #101828; }
        .user-info p { margin: 0; font-size: 0.85rem; color: #667085; }

        .sidebar-nav { display: flex; flex-direction: column; gap: 4px; }
        .nav-item {
            display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 6px; 
            cursor: pointer; color: #344054; text-decoration: none; font-size: 0.95rem; font-weight: 500;
            transition: all 0.2s;
        }
        .nav-item:hover { background-color: #F9FAFB; }
        .nav-item.active { background-color: #FFF7ED; color: var(--primary-color); } /* Light Orange */

        .nav-icon { width: 20px; height: 20px; object-fit: contain; opacity: 0.7; }
        .nav-item.active .nav-icon { opacity: 1; filter: invert(57%) sepia(63%) saturate(462%) hue-rotate(359deg) brightness(102%) contrast(106%); } 
        .text-red { color: #D92D20; margin-top: 10px; }

        /* --- Dashboard Stats --- */
        .stat-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;
        }
        @media(max-width: 900px) { .stat-grid { grid-template-columns: 1fr; } }

        .stat-card {
            background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #EAECF0;
            display: flex; align-items: center; gap: 1rem; box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
        }
        .stat-icon-box {
            width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .bg-blue-light { background-color: #E3F2FD; }
        .bg-green-light { background-color: #E8F5E9; }
        .bg-orange-light { background-color: #FFF3E0; }

        .stat-info p { margin: 0 0 4px; font-size: 0.85rem; color: #667085; }
        .stat-info h3 { margin: 0; font-size: 1.25rem; font-weight: 700; color: #101828; }

        /* --- Activity & Tables --- */
        .section-title { font-size: 1.25rem; font-weight: 700; color: #101828; margin: 0 0 1.5rem; }
        
        .activity-list { display: flex; flex-direction: column; gap: 1rem; }
        .activity-item {
            display: flex; justify-content: space-between; align-items: center; padding: 1rem;
            border: 1px solid #F2F4F7; border-radius: 8px;
        }
        
        .table-responsive { overflow-x: auto; }
        .custom-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .custom-table th { text-align: left; padding: 12px; background-color: #F9FAFB; color: #667085; font-weight: 600; border-bottom: 1px solid #EAECF0; }
        .custom-table td { padding: 12px; border-bottom: 1px solid #EAECF0; color: #344054; }
        
        .status-badge { padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; }
        .status-completed { background: #ECFDF3; color: #027A48; }
        .status-pending { background: #FEF3F2; color: #B42318; }

        /* --- Empty State --- */
        .empty-state { text-align: center; padding: 3rem 1rem; }
        .empty-icon { width: 64px; height: 64px; background: #F2F4F7; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
        .empty-icon img { width: 32px; opacity: 0.5; }
        .empty-text { color: #667085; font-size: 0.95rem; margin-bottom: 1rem; }
        .link-orange { color: var(--primary-color); font-weight: 600; text-decoration: none; }
        .link-orange:hover { text-decoration: underline; }

        /* --- Profile Settings --- */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .link-btn { background: none; border: none; color: var(--primary-color); cursor: pointer; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 6px;}
        
        .profile-upload-area { display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; }
        .profile-preview-lg {
            width: 80px; height: 80px; border-radius: 50%; object-fit: cover; background: #F2F4F7; border: 1px solid #EAECF0;
            display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;
        }
        .upload-btn-wrapper { display: flex; gap: 10px; align-items: center; }
        .btn-upload-label { 
            background: #FFF3E0; color: #B54708; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; 
        }
        .no-file-text { font-size: 0.85rem; color: #667085; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .col-span-2 { grid-column: span 2; }
        @media(max-width: 600px) { .form-grid { grid-template-columns: 1fr; } .col-span-2 { grid-column: span 1; } }
        
        .btn-save {
            background-color: var(--primary-color); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; float: right;
        }
        .btn-save:hover { opacity: 0.9; }

        /* --- Modal --- */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-box { background: white; width: 100%; max-width: 400px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 24px -4px rgba(16, 24, 40, 0.1); }
        .modal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #EAECF0; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 1.5rem; }
        .close-modal { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #98A2B3; line-height: 1; }
        
        .btn-cancel { background-color: #F2F4F7; color: #344054; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; }

        /* --- Tabs --- */
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* --- Alerts --- */
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; font-weight: 500; }
        .alert-success { background-color: #ECFDF3; color: #027A48; border: 1px solid #D1FADF; }
        .alert-error { background-color: #FEF3F2; color: #B42318; border: 1px solid #FECDCA; }

    </style>
</head>
<body>

    <?php include '../include/header.php'; ?>

    <div class="dashboard-container">
        
        <!-- LEFT: SIDEBAR -->
        <aside>
            <div class="card-box">
                <!-- User Info -->
                <div class="user-brief">
                    <?php if (!empty($member['image']) && file_exists($member['image'])): ?>
                        <img src="<?php echo htmlspecialchars($member['image']); ?>" class="user-avatar">
                    <?php else: ?>
                        <div class="user-avatar">
                            <!-- FontAwesome removed, using generic char or image -->
                            <span style="font-size:1.5rem;">?</span>
                        </div>
                    <?php endif; ?>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($member['full_name'] ?? 'User'); ?></h4>
                        <p><?php echo htmlspecialchars($member['email'] ?? ''); ?></p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="sidebar-nav">
                    <div id="link-dashboard" class="nav-item active" onclick="switchTab('dashboard')">
                        <img src="../image/dashboard.png" class="nav-icon" alt="Dash"> Dashboard
                    </div>
                    <div id="link-orders" class="nav-item" onclick="switchTab('orders')">
                        <img src="../image/purchase-order.png" class="nav-icon" alt="Orders"> My Orders
                    </div>
                    <div id="link-profile" class="nav-item" onclick="switchTab('profile')">
                        <img src="../image/profileSetting.png" class="nav-icon" alt="Settings"> Settings
                    </div>
                    <a href="logout.php" class="nav-item text-red">
                        <img src="../image/exit.png" class="nav-icon" alt="Logout"> Logout
                    </a>
                </nav>
            </div>
        </aside>

        <!-- RIGHT: CONTENT -->
        <main>
            <div class="breadcrumb">
                <a href="home.php">Home</a> / <span>My Account</span>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $msg_type == 'success' ? 'alert-success' : 'alert-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- 1. DASHBOARD TAB -->
            <div id="dashboard" class="tab-content">
                
                <!-- Stats Grid -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-icon-box bg-blue-light">
                            <img src="../image/cart.png" style="width:24px;">
                        </div>
                        <div class="stat-info">
                            <p>Total Orders</p>
                            <h3><?php echo $stats['total_orders']; ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-box bg-green-light">
                            <img src="../image/wallet.png" style="width:24px;">
                        </div>
                        <div class="stat-info">
                            <p>Total Spent</p>
                            <h3>RM <?php echo number_format($stats['total_spent'], 2); ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-box bg-orange-light">
                            <img src="../image/voucher.png" style="width:24px;">
                        </div>
                        <div class="stat-info">
                            <p>Vouchers</p>
                            <h3>0</h3>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
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
                                        <span class="status-badge <?php echo $order['status']=='completed'?'status-completed':'status-pending'; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p class="empty-text">No recent activity.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. ORDERS TAB -->
            <div id="orders" class="tab-content">
                <div class="card-box" style="min-height: 400px;">
                    <h3 class="section-title">Order History</h3>
                    
                    <?php if (count($orders) > 0): ?>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            <td><?php echo $order['item_count']; ?> Items</td>
                                            <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $order['status']=='completed'?'status-completed':'status-pending'; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="margin-top: 4rem;">
                            <div class="empty-icon">
                                <img src="../image/package.png" alt="Box">
                            </div>
                            <p class="empty-text">You haven't placed any orders yet.</p>
                            <a href="home.php#shop" class="link-orange">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. SETTINGS TAB -->
            <div id="profile" class="tab-content">
                <div class="card-box">
                    <div class="section-header">
                        <h3 class="section-title">Account Settings</h3>
                        <button type="button" onclick="toggleModal()" class="link-btn">
                            <img src="../image/padlock.png" style="width:14px; opacity:0.6;"> Change Password
                        </button>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_type" value="update_profile">
                        
                        <!-- Profile Upload (Centered) -->
                        <div class="profile-upload-area">
                            <?php if (!empty($member['image']) && file_exists($member['image'])): ?>
                                <img id="previewImg" src="<?php echo htmlspecialchars($member['image']); ?>" class="profile-preview-lg">
                            <?php else: ?>
                                <div id="previewPlaceholder" class="profile-preview-lg">
                                    <img src="../image/user_placeholder.png" style="width:32px; opacity:0.3;">
                                </div>
                                <img id="previewImg" src="" class="profile-preview-lg" style="display:none;">
                            <?php endif; ?>
                            
                            <div class="upload-btn-wrapper">
                                <label for="fileInput" class="btn-upload-label">Choose file</label>
                                <span class="no-file-text" id="fileName">No file chosen</span>
                                <input type="file" name="profile_image" id="fileInput" style="display:none;" onchange="handleFileSelect(this)">
                            </div>
                        </div>

                        <!-- Form Grid -->
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
                            <div class="col-span-2">
                                <label class="form-label">Shipping Address</label>
                                <textarea name="address" rows="3" class="form-input" placeholder="Enter your full delivery address..."><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
                                <p style="font-size:0.75rem; color:#667085; margin-top:4px;">This address will be used for checkout.</p>
                            </div>
                        </div>

                        <div style="overflow:hidden;">
                            <button type="submit" class="btn-save">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <!-- Change Password Modal -->
    <div id="pwdModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="margin:0; font-size:1.1rem; font-weight:700;">Change Password</h3>
                <button class="close-modal" onclick="toggleModal()">&times;</button>
            </div>
            <form method="POST" action="" id="pwdForm" class="modal-body">
                <input type="hidden" name="form_type" value="change_password">
                
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" id="new_pwd" name="new_password" class="form-input" required>
                    <small id="pwd_msg" style="display:block; margin-top:4px; font-size:0.75rem;"></small>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_pwd" name="confirm_password" class="form-input" required>
                    <small id="match_msg" style="display:block; margin-top:4px; font-size:0.75rem;"></small>
                </div>

                <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                    <button type="button" onclick="toggleModal()" class="btn-cancel" style="flex:1;">Cancel</button>
                    <button type="submit" id="btnUpdatePwd" class="btn-save" style="flex:1; float:none;" disabled>Update</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>

    <script>
        // 1. Tab Switching
        function switchTab(tabId) {
            // Reset active class on contents
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            // Reset active class on links
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            
            // Set new active
            document.getElementById(tabId).classList.add('active');
            document.getElementById('link-' + tabId).classList.add('active');
        }

        // Initialize Tab based on PHP variable (persists after reload)
        document.addEventListener('DOMContentLoaded', function() {
            const initialTab = "<?php echo $active_tab; ?>";
            switchTab(initialTab);
        });

        // 2. Modal Toggle
        function toggleModal() {
            const modal = document.getElementById('pwdModal');
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }

        // 3. Image Preview
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

        // 4. Password Validation
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
                    // Regex: 6+ chars, Upper, Lower, Symbol
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