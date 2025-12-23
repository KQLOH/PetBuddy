<?php
// admin/member_edit.php
session_start();

require_once '../user/include/db.php';

$error_message = null;
$success_message = null;
$member = null;

// --- Security & Authorization Check ---
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin.php');
    exit;
}
$adminName = $_SESSION['full_name'] ?? 'Admin';

// Get member ID from URL or POST data
$member_id = $_REQUEST['id'] ?? null;

if (empty($member_id) || !is_numeric($member_id)) {
    $_SESSION['error_message'] = "Error: Invalid member ID specified for editing.";
    header('Location: members_list.php');
    exit;
}
$member_id = (int)$member_id;

// --- 1. Fetch Existing Member Data ---
try {
    $stmt = $pdo->prepare("SELECT member_id, full_name, email, phone, address, gender, dob, role, image FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();

    if (!$member) {
        $_SESSION['error_message'] = "Error: Member ID " . sprintf("M%04d", $member_id) . " not found.";
        header('Location: members_list.php');
        exit;
    }

} catch (PDOException $e) {
    $error_message = "Error fetching member data.";
    // Log error for debugging
}

// --- 2. Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $member) {
    // Basic Input Sanitization and Validation
    $input_full_name = trim($_POST['full_name'] ?? '');
    $input_email     = trim($_POST['email'] ?? '');
    $input_phone     = trim($_POST['phone'] ?? '');
    $input_address   = trim($_POST['address'] ?? '');
    $input_gender    = $_POST['gender'] ?? null;
    $input_dob       = trim($_POST['dob'] ?? '');
    $input_role      = $_POST['role'] ?? 'member';

    // Validation rules
    if (empty($input_full_name)) {
        $error_message = "Full Name cannot be empty.";
    } elseif (!filter_var($input_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif ($input_gender && !in_array($input_gender, ['male', 'female'])) {
        $error_message = "Invalid gender selection.";
    } elseif ($input_role && !in_array($input_role, ['admin', 'member'])) {
        $error_message = "Invalid role selection.";
    } else {
        // --- A. Handle Basic Member Data Update ---
        try {
            // Check for email conflict (unless the email hasn't changed)
            if ($input_email !== $member['email']) {
                $stmt = $pdo->prepare("SELECT member_id FROM members WHERE email = ? AND member_id != ?");
                $stmt->execute([$input_email, $member_id]);
                if ($stmt->fetch()) {
                    $error_message = "Error: Email address already registered by another member.";
                }
            }

            if (!$error_message) {
                $sql = "UPDATE members SET 
                        full_name = ?, email = ?, phone = ?, address = ?, 
                        gender = ?, dob = ?, role = ?
                        WHERE member_id = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $input_full_name, $input_email, $input_phone, $input_address, 
                    $input_gender, ($input_dob ?: null), $input_role, $member_id
                ]);

                $success_message = "Member details updated successfully.";

                // Re-fetch member data to populate form with new values
                $stmt = $pdo->prepare("SELECT member_id, full_name, email, phone, address, gender, dob, role, image FROM members WHERE member_id = ?");
                $stmt->execute([$member_id]);
                $member = $stmt->fetch();
            }

        } catch (PDOException $e) {
            $error_message = "Database Error: Failed to update member.";
            // Log the error
        }

        // --- B. Handle Password Update (Optional) ---
        $input_password = $_POST['password'] ?? '';
        if (!empty($input_password)) {
            if (strlen($input_password) < 6) { // Example: Minimum length check
                $error_message = ($error_message ? $error_message . " " : "") . "Password must be at least 6 characters long.";
            } else {
                try {
                    $password_hash = password_hash($input_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE members SET password_hash = ? WHERE member_id = ?");
                    $stmt->execute([$password_hash, $member_id]);
                    
                    $success_message .= " Password updated.";

                } catch (PDOException $e) {
                    $error_message .= " Failed to update password.";
                }
            }
        }
        
        // --- C. Handle Image Upload (Placeholder Logic - Requires more complex file handling) ---
        // For now, we will skip file upload as it's complex, but here's where it would go.
        // If you need file upload functionality, we can add it later.
    }
}

$currentPageTitle = "Edit Member " . sprintf("M%04d", $member_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - <?php echo htmlspecialchars($currentPageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* --- Base Layout Styles (Copy the CSS from member_list.php here for consistency) --- */
        :root {
            --primary-color: #F4A261;
            --primary-dark: #E68E3F;
            --bg-light: #f5f5f7;
            --bg-sidebar: #fff7ec;
            --text-dark: #333333;
            --border-color: #e0e0e0;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { min-height: 100vh; background-color: #ffffff; color: var(--text-dark); transition: padding-left 0.25s ease; padding-left: 220px; }
        .main { min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { height: 56px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 18px; background-color: #ffffff; position: sticky; top: 0; z-index: 10; }
        .topbar-left { display: flex; align-items: center; gap: 10px; }
        .topbar-title { font-size: 18px; font-weight: 600; }
        .sidebar-toggle { width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--border-color); background-color: #ffffff; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 18px; }
        .sidebar-toggle:hover { background: rgba(244,162,97,0.12); border-color: var(--primary-color); }
        .topbar-right { display: flex; align-items: center; gap: 10px; font-size: 13px; }
        .tag-pill { font-size: 11px; padding: 3px 8px; border-radius: 999px; background-color: rgba(244, 162, 97, 0.12); color: var(--primary-dark); }
        .admin-avatar { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 5px; border: 1px solid var(--primary-color); }
        .content { padding: 18px; background-color: var(--bg-light); min-height: calc(100vh - 56px); }

        /* --- Form Specific Styles --- */
        .form-panel {
            background-color: #ffffff;
            border-radius: 14px;
            border: 1px solid #e5e5e5;
            padding: 25px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group input[type="password"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-actions {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-primary {
            padding: 10px 20px;
            background-color: var(--primary-dark);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #D67A30;
        }
        
        .btn-cancel {
            text-decoration: none;
            color: #555;
            font-size: 14px;
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: background-color 0.2s;
        }
        .btn-cancel:hover {
            background-color: #f0f0f0;
        }

        .alert-success { padding: 12px; background: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px; margin-bottom: 15px; color: #1b5e20; font-size: 14px;}
        .alert-error { padding: 12px; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; margin-bottom: 15px; color: #b91c1c; font-size: 14px;}

        /* Member Info Panel */
        .member-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .member-info img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid var(--primary-color);
        }
        .member-info h3 {
            font-size: 18px;
            margin: 0;
        }
        .member-info p {
            font-size: 12px;
            color: #777;
            margin: 0;
        }

        @media (max-width: 768px) {
            body { padding-left: 0; }
            .form-panel { padding: 15px; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button id="sidebarToggle" class="sidebar-toggle">☰</button>
            <div class="topbar-title">Edit Member</div>
        </div>
        <div class="topbar-right">
            <?php // Topbar Admin Avatar Display (Assumed from member_list.php) ?>
            <span class="tag-pill">Admin: <?php echo htmlspecialchars($adminName); ?></span>
        </div>
    </header>

    <main class="content">
        <div class="form-panel">
            <div class="page-header">
                <div class="page-title">Editing Member: <?php echo sprintf("M%04d", $member_id); ?></div>
            </div>

            <?php if ($success_message): ?>
                <div class="alert-success">✅ <strong>Success:</strong> <?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert-error">❌ <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if ($member): ?>
                <div class="member-info">
                    <?php 
                        $member_img_src = !empty($member['image']) ? "../" . htmlspecialchars($member['image']) : "https://via.placeholder.com/60/cccccc/ffffff?text=U";
                    ?>
                    <img src="<?php echo $member_img_src; ?>" alt="Member Profile">
                    <div>
                        <h3><?php echo htmlspecialchars($member['full_name']); ?></h3>
                        <p>Role: <?php echo ucfirst(htmlspecialchars($member['role'])); ?></p>
                    </div>
                </div>

                <form method="POST" action="member_edit.php?id=<?php echo $member_id; ?>">
                    <input type="hidden" name="id" value="<?php echo $member_id; ?>">

                    <fieldset>
                        <legend style="font-size: 16px; font-weight: 700; color: var(--primary-dark); margin-bottom: 10px;">User Details</legend>

                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($member['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($member['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="" <?php if (empty($member['gender'])) echo 'selected'; ?>>-- Select --</option>
                                <option value="male" <?php if ($member['gender'] === 'male') echo 'selected'; ?>>Male</option>
                                <option value="female" <?php if ($member['gender'] === 'female') echo 'selected'; ?>>Female</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" 
                                   value="<?php echo htmlspecialchars($member['dob'] ?? ''); ?>">
                        </div>
                    </fieldset>

                    <fieldset style="margin-top: 20px;">
                        <legend style="font-size: 16px; font-weight: 700; color: var(--primary-dark); margin-bottom: 10px;">Admin Settings</legend>

                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="member" <?php if ($member['role'] === 'member') echo 'selected'; ?>>Member</option>
                                <option value="admin" <?php if ($member['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="password">Reset Password (Leave blank if unchanged)</label>
                            <input type="password" id="password" name="password" 
                                   placeholder="New password (Min 6 characters)">
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Save Changes</button>
                        <a href="member_list.php" class="btn-cancel">Cancel / Back to List</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
    // Sidebar Toggle
    document.getElementById('sidebarToggle').addEventListener('click', function () {
        document.body.classList.toggle('sidebar-collapsed');
    });
</script>

</body>
</html>