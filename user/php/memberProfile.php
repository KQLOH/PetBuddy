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
                    // Regex: Min 6 chars, 1 Upper, 1 Lower, 1 Symbol
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
    <title>My Account - PetBuddy</title>
    <!-- Only Local CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/memberProfileStyle.css">
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
                            <?php echo strtoupper(substr($member['full_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($member['full_name'] ?? 'User'); ?></h4>
                        <p><?php echo htmlspecialchars($member['email'] ?? ''); ?></p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="sidebar-nav">
                    <div id="link-dashboard" class="sidebar-link active" onclick="switchTab('dashboard')">
                        <img src="../images/dashboard.png" alt="Dashboard"> Dashboard
                    </div>
                    <div id="link-orders" class="sidebar-link" onclick="switchTab('orders')">
                        <img src="../images/purchase-order.png" alt="Orders"> My Orders
                    </div>
                    <div id="link-profile" class="sidebar-link" onclick="switchTab('profile')">
                        <img src="../images/profileSetting.png" alt="Settings"> Settings
                    </div>
                    <a href="logout.php" class="sidebar-link text-red">
                        <img src="../images/exit.png" alt="Logout"> Logout
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
                            <img src="../images/cart.png" style="width:24px;">
                        </div>
                        <div class="stat-info">
                            <p>Total Orders</p>
                            <h3><?php echo $stats['total_orders']; ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-box bg-green-light">
                            <img src="../images/wallet.png" style="width:24px;">
                        </div>
                        <div class="stat-info">
                            <p>Total Spent</p>
                            <h3>RM <?php echo number_format($stats['total_spent'], 2); ?></h3>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon-box bg-orange-light">
                            <img src="../images/voucher.png" style="width:24px;">
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
                                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
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
                                <img src="../images/purchase-order.png" alt="Box">
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
                            <img src="../images/padlock.png" style="width:14px; opacity:0.6;"> Change Password
                        </button>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="form_type" value="update_profile">
                        
                        <!-- Profile Upload -->
                        <div class="profile-upload-area">
                            <?php if (!empty($member['image']) && file_exists($member['image'])): ?>
                                <img id="previewImg" src="<?php echo htmlspecialchars($member['image']); ?>" class="profile-preview-lg">
                            <?php else: ?>
                                <div id="previewPlaceholder" class="profile-preview-lg">
                                    <img src="../images/user_placeholder.png" style="width:32px; opacity:0.3;">
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

    <!-- Change Password Modal with Show/Hide Icons -->
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

    <?php include '../include/footer.php'; ?>

    <script>
        // 1. Tab Switching
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.sidebar-link').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            document.getElementById('link-' + tabId).classList.add('active');
        }

        // Initialize Tab
        document.addEventListener('DOMContentLoaded', function() {
            const initialTab = "<?php echo $active_tab; ?>";
            switchTab(initialTab);
        });

        // 2. Modal Toggle
        function toggleModal() {
            const modal = document.getElementById('pwdModal');
            modal.style.display = (modal.style.display === 'flex') ? 'none' : 'flex';
        }

        // 3. Password Visibility Toggle (Global)
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

        // 4. Image Preview
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

        // 5. Password Validation
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