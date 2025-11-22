<?php
session_start();
include '../include/db.php'; 

$message = "";
$msg_type = "";
$member = [];
$orders = [];

if (!isset($_SESSION['member_id'])) {
    $_SESSION['member_id'] = 1;
}
$member_id = $_SESSION['member_id'];

if (isset($pdo)) {
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // --- HANDLER 1: CHANGE PASSWORD ---
        if (isset($_POST['form_type']) && $_POST['form_type'] === 'change_password') {
            $current_pwd = $_POST['current_password'] ?? '';
            $new_pwd = $_POST['new_password'] ?? '';
            $confirm_pwd = $_POST['confirm_password'] ?? '';

            // 1. Verify Current Password
            $stmt = $pdo->prepare("SELECT password_hash FROM members WHERE member_id = ?");
            $stmt->execute([$member_id]);
            $userAuth = $stmt->fetch();

            if ($userAuth && password_verify($current_pwd, $userAuth['password_hash'])) {
                // 2. Check if new passwords match
                if ($new_pwd === $confirm_pwd) {
                    
                    // 3. ENFORCE COMPLEXITY (Updated Logic)
                    // Regex: At least 1 Lowercase, 1 Uppercase, 1 Symbol, Min 6 chars
                    if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}$/', $new_pwd)) {
                        
                        // 4. Update Password
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
                        $message = "Password must be at least 6 characters, with 1 uppercase, 1 lowercase, and 1 symbol.";
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
        
        // --- HANDLER 2: UPDATE PROFILE ---
        else {
            $full_name = $_POST['full_name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
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
                    $message = "Only JPG, PNG, and GIF files are allowed.";
                    $msg_type = "error";
                }
            }

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
                $message = "Profile updated successfully!";
                $msg_type = "success";
            } catch (PDOException $e) {
                $message = "Error updating profile: " . $e->getMessage();
                $msg_type = "error";
            }
        }
    }

    // --- FETCH DATA ---
    try {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        if (!$member) {
            $member = ['full_name' => 'New Member', 'email' => 'user@example.com', 'phone' => '', 'image' => '', 'role' => 'member'];
        }
        $orderSql = "SELECT o.*, COUNT(oi.order_item_id) as item_count 
                     FROM orders o 
                     LEFT JOIN order_items oi ON o.order_id = oi.order_id
                     WHERE o.member_id = ? 
                     GROUP BY o.order_id 
                     ORDER BY o.order_date DESC 
                     LIMIT 5";
        $stmtOrders = $pdo->prepare($orderSql);
        $stmtOrders->execute([$member_id]);
        $orders = $stmtOrders->fetchAll();
    } catch (PDOException $e) {
        $message = "Error loading data: " . $e->getMessage();
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - PetBuddy</title>
    <!-- jQuery (Required for Validation) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-gray-50 font-sans text-gray-700">

    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="#" class="flex items-center gap-2 text-2xl font-bold text-gray-800">
                    <i class="fas fa-paw text-pet-primary"></i> PetBuddy
                </a>
                <div class="hidden md:flex space-x-8 font-medium">
                    <a href="#" class="hover:text-pet-primary transition">Home</a>
                    <a href="#" class="hover:text-pet-primary transition">Shop</a>
                    <a href="#" class="text-pet-primary border-b-2 border-pet-primary">Profile</a>
                </div>
                <div class="flex items-center gap-4">
                    <span class="hidden md:block text-sm text-gray-500">Hello, <?php echo htmlspecialchars($member['full_name'] ?? 'Guest'); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 py-10">
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $msg_type == 'success' ? 'bg-green-100 text-green-700 border-l-4 border-green-500' : 'bg-red-100 text-red-700 border-l-4 border-red-500'; ?>">
                <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Sidebar: Profile Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
                    <div class="h-32 bg-gradient-to-r from-orange-300 to-orange-500 relative">
                        <div class="absolute -bottom-12 left-1/2 transform -translate-x-1/2">
                            <div class="w-24 h-24 bg-white rounded-full p-1 shadow-lg overflow-hidden">
                                <?php if (!empty($member['image']) && file_exists($member['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($member['image']); ?>" alt="Profile" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center text-gray-400 text-3xl">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="pt-16 pb-6 text-center">
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($member['full_name'] ?? 'User'); ?></h2>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($member['email'] ?? 'email@example.com'); ?></p>
                        <div class="mt-3">
                            <span class="px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-xs font-semibold capitalize">
                                <?php echo htmlspecialchars($member['role'] ?? 'Member'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Content: Edit Form & Orders -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Edit Profile Form -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <div class="flex justify-between items-center mb-6 border-b pb-4">
                        <h3 class="text-xl font-bold text-gray-800">Edit Personal Details</h3>
                        <!-- Trigger Modal -->
                        <button type="button" onclick="toggleModal()" class="text-sm text-pet-primary hover:underline font-semibold">
                            <i class="fas fa-key mr-1"></i> Change Password?
                        </button>
                    </div>

                    <form method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <!-- Hidden input to distinguish form -->
                        <input type="hidden" name="form_type" value="update_profile">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($member['full_name'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-200 focus:border-orange-400 outline-none transition" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-200 focus:border-orange-400 outline-none transition">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-xs text-gray-400">(Contact support to change)</span></label>
                                <input type="email" value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded-lg cursor-not-allowed" readonly>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                                <input type="file" name="profile_image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 transition cursor-pointer">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 rounded-lg bg-pet-primary text-white font-bold hover:bg-orange-600 shadow-md transition btn-hover">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Order History -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4">Recent Orders</h3>
                    <?php if (count($orders) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Order #</th>
                                        <th class="px-6 py-3">Date</th>
                                        <th class="px-6 py-3">Total</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3">Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="bg-white border-b hover:bg-gray-50">
                                            <td class="px-6 py-4 font-medium text-gray-900">#<?php echo $order['order_id']; ?></td>
                                            <td class="px-6 py-4"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            <td class="px-6 py-4">$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <a href="#" class="text-pet-primary hover:underline">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-shopping-basket text-4xl mb-3"></i>
                            <p>No orders found yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- PASSWORD CHANGE MODAL -->
    <div id="pwdModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50" style="display: none;">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-bold text-gray-900">Change Password</h3>
                <div class="mt-2 px-7 py-3">
                    <form method="POST" action="" id="pwdForm">
                        <input type="hidden" name="form_type" value="change_password">
                        
                        <div class="mb-4 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Current Password</label>
                            <input type="password" name="current_password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-300" required>
                        </div>

                        <div class="mb-4 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                            <input type="password" id="new_pwd" name="new_password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-300" required>
                            <p id="pwd_msg" class="text-xs mt-1"></p>
                        </div>

                        <div class="mb-4 text-left">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                            <input type="password" id="confirm_pwd" name="confirm_password" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-300" required>
                            <p id="match_msg" class="text-xs mt-1"></p>
                        </div>

                        <div class="items-center px-4 py-3">
                            <button type="submit" id="btnUpdatePwd" class="px-4 py-2 bg-pet-primary text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-300 disabled:opacity-50 disabled:cursor-not-allowed">
                                Update Password
                            </button>
                            <button type="button" onclick="toggleModal()" class="mt-3 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-gray-800 text-gray-400 py-8 mt-12 text-center">
        <p>&copy; <?php echo date("Y"); ?> PetBuddy Supplies. All rights reserved.</p>
    </footer>

    <!-- JAVASCRIPT & JQUERY -->
    <script>
        // Function to toggle modal visibility
        function toggleModal() {
            const modal = document.getElementById('pwdModal');
            if (modal.style.display === 'none' || modal.classList.contains('hidden')) {
                modal.style.display = 'block';
                modal.classList.remove('hidden');
            } else {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }

        // jQuery Real-Time Validation
        $(document).ready(function() {
            const $btn = $('#btnUpdatePwd');
            const $newPwd = $('#new_pwd');
            const $confirmPwd = $('#confirm_pwd');
            const $pwdMsg = $('#pwd_msg');
            const $matchMsg = $('#match_msg');

            function validateForm() {
                const pwd = $newPwd.val();
                const confirm = $confirmPwd.val();
                let isValid = true;

                // 1. Check Complexity (6+ chars, 1 Upper, 1 Lower, 1 Symbol)
                // Regex Breakdown:
                // (?=.*[a-z]) = Must contain at least one lowercase
                // (?=.*[A-Z]) = Must contain at least one uppercase
                // (?=.*[\W_]) = Must contain at least one symbol (non-alphanumeric)
                // .{6,}       = Must be at least 6 chars long
                const complexRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{6,}$/;

                if (!complexRegex.test(pwd)) {
                    $pwdMsg.text("Must be 6+ chars, include 1 Upper, 1 Lower & 1 Symbol").css("color", "red");
                    isValid = false;
                } else {
                    $pwdMsg.text("Password strength: Good").css("color", "green");
                }

                // 2. Check Match
                if (pwd !== confirm) {
                    $matchMsg.text("Passwords do not match").css("color", "red");
                    isValid = false;
                } else if (confirm.length > 0) {
                    $matchMsg.text("Passwords match").css("color", "green");
                } else {
                    $matchMsg.text("");
                }

                // 3. Toggle Button
                if (isValid && pwd.length > 0 && confirm.length > 0) {
                    $btn.prop('disabled', false);
                } else {
                    $btn.prop('disabled', true);
                }
            }

            // Trigger validation on keyup
            $newPwd.on('keyup', validateForm);
            $confirmPwd.on('keyup', validateForm);

            // Initial State
            $btn.prop('disabled', true);
        });
    </script>

</body>
</html>