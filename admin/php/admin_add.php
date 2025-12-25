<?php
session_start();
require_once '../../user/include/db.php';

// 安全检查：只有 Super Admin 可以进入
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: member_list.php');
    exit;
}

$adminName = $_SESSION['full_name'] ?? 'Admin';
$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role']; // 通常默认为 'admin'

    // 简单验证
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            // 检查 Email 是否已存在
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Email already registered.";
            } else {
                // 插入新管理员
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO members (full_name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$fullName, $email, $hashedPassword, $role]);
                $success = "New admin added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Admin</title>
    <link rel="stylesheet" href="../css/admin_member.css">
    <link rel="stylesheet" href="../css/admin_btn.css">
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <div class="topbar-title">Add New Admin</div>
            </div>
            <span class="tag-pill">Super Admin: <?= htmlspecialchars($adminName) ?></span>
        </header>

        <main class="content">
            <div class="page-header">
                <a href="member_list.php" style="text-decoration: none; color: #666;">← Back to List</a>
                <h2 class="page-title">Register Administrator</h2>
            </div>

            <div class="form-container">
                <?php if ($error): ?> <div class="msg msg-error"><?= $error ?></div> <?php endif; ?>
                <?php if ($success): ?> <div class="msg msg-success"><?= $success ?></div> <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" required placeholder="Enter full name">
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required placeholder="admin@petbuddy.com">
                        </div>
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required placeholder="Min 6 characters">
                        </div>
                        <div class="form-group">
                            <label>Assigned Role</label>
                            <select name="role">
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="member_list.php" class="btn-reset" style="padding: 10px 25px;">Cancel</a>
                        <button type="submit" class="btn-pill-add">
                             Save Administrator
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>