<?php
session_start();
require_once '../../user/include/db.php';

if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'], true)
) {
    header('Location: admin_login.php');
    exit;
}

$adminRole = $_SESSION['role'];
$adminName = $_SESSION['full_name'] ?? 'Admin';

$member_id = $_GET['id'] ?? $_POST['id'] ?? null;
if (!$member_id || !is_numeric($member_id)) {
    header('Location: members_list.php');
    exit;
}
$member_id = (int)$member_id;

$stmt = $pdo->prepare("
    SELECT member_id, full_name, email, phone, address,
           gender, dob, role, image
    FROM members
    WHERE member_id = ?
    LIMIT 1
");
$stmt->execute([$member_id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: members_list.php');
    exit;
}

$memberImg = !empty($member['image'])
    ? '../../user/php/' . ltrim($member['image'], '/')
    : 'https://via.placeholder.com/120?text=User';

$error_message = null;
$success_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $gender    = $_POST['gender'] ?: null;
    $dob       = $_POST['dob'] ?: null;
    $role      = $_POST['role'] ?? $member['role'];
    $password  = $_POST['password'] ?? '';

    if ($full_name === '') {
        $error_message = 'Full name is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format.';
    } elseif ($gender && !in_array($gender, ['male', 'female'], true)) {
        $error_message = 'Invalid gender selection.';
    }

    if (!$error_message && $email !== $member['email']) {
        $check = $pdo->prepare("
            SELECT member_id FROM members
            WHERE email = ? AND member_id != ?
        ");
        $check->execute([$email, $member_id]);
        if ($check->fetch()) {
            $error_message = 'Email already exists.';
        }
    }

    if (!$error_message) {

        $pdo->prepare("
            UPDATE members SET
                full_name = ?, email = ?, phone = ?, address = ?,
                gender = ?, dob = ?, role = ?
            WHERE member_id = ?
        ")->execute([
            $full_name,
            $email,
            $phone,
            $address,
            $gender,
            $dob,
            ($adminRole === 'super_admin' ? $role : $member['role']),
            $member_id
        ]);

        if ($password !== '') {
            if (strlen($password) < 6) {
                $error_message = 'Password must be at least 6 characters.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("
                    UPDATE members SET password_hash = ?
                    WHERE member_id = ?
                ")->execute([$hash, $member_id]);
            }
        }

        if (!$error_message) {
            $success_message = 'Member updated successfully.';

            $stmt->execute([$member_id]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Member</title>
    <link rel="stylesheet" href="../css/admin_member_edit.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                <div class="topbar-title">Members</div>
            </div>
        </header>
        <main class="content">
            <div class="member-edit-panel">
                <?php if ($success_message): ?>
                    <div class="alert-success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert-error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <div class="member-edit-header">
                    <img src="<?= htmlspecialchars($memberImg) ?>" class="member-edit-avatar" alt="Avatar">

                    <div class="member-edit-title">
                        <h2><?= htmlspecialchars($member['full_name']) ?></h2>

                        <?php if ($member['role'] === 'super_admin'): ?>
                            <span class="role-badge super-admin">Super Admin</span>
                        <?php elseif ($member['role'] === 'admin'): ?>
                            <span class="role-badge admin">Admin</span>
                        <?php else: ?>
                            <span class="role-badge member">Member</span>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="post" class="member-edit-form">

                    <input type="hidden" name="id" value="<?= $member_id ?>">

                    <div>
                        <label>Full Name</label>
                        <input type="text" name="full_name"
                            value="<?= htmlspecialchars($member['full_name']) ?>" required>
                    </div>

                    <div>
                        <label>Email</label>
                        <input type="email" name="email"
                            value="<?= htmlspecialchars($member['email']) ?>" required>
                    </div>

                    <div>
                        <label>Phone</label>
                        <input type="text" name="phone"
                            value="<?= htmlspecialchars($member['phone']) ?>">
                    </div>

                    <div>
                        <label>Date of Birth</label>
                        <input type="date" name="dob"
                            value="<?= htmlspecialchars($member['dob']) ?>">
                    </div>

                    <div class="full">
                        <label>Address</label>
                        <textarea name="address"><?= htmlspecialchars($member['address']) ?></textarea>
                    </div>

                    <div>
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">-- Select --</option>
                            <option value="male" <?= $member['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= $member['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>

                    <?php if ($adminRole === 'super_admin'): ?>
                        <div>
                            <label>Role</label>
                            <select name="role">
                                <option value="member" <?= $member['role'] === 'member' ? 'selected' : '' ?>>Member</option>
                                <option value="admin" <?= $member['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="full form-section">Security</div>

                    <div class="full">
                        <label>Reset Password (optional)</label>
                        <input type="password" name="password" placeholder="Min 6 characters">
                    </div>

                    <div class="member-edit-actions">
                        <div class="left">
                            <a href="member_list.php" class="btn-secondary">← Back</a>
                        </div>
                        <div class="right">
                            <button class="btn-primary">Save Changes</button>
                        </div>
                    </div>

                </form>

            </div>

        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').onclick = () =>
            document.body.classList.toggle('sidebar-collapsed');
    </script>

</body>

</html>