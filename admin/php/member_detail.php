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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: member_list.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT member_id, full_name, email, phone, gender, dob, role, image
    FROM members
    WHERE member_id = ?
    LIMIT 1
");

$stmt->execute([$id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: member_list.php');
    exit;
}

$imagePath = !empty($member['image'])
    ? '../../user/' . ltrim($member['image'], '/')
    : 'https://via.placeholder.com/120?text=User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Member Detail</title>
    <link rel="stylesheet" href="../css/admin_member_detail.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" id="sidebarToggle">☰</button>
                <div class="topbar-title">Member Detail</div>
            </div>
        </header>

        <main class="content">

            <div class="member-detail-wrapper">
                <div class="member-detail-card">
                    <div class="member-header">
                        <img src="<?= htmlspecialchars($imagePath) ?>"
                            class="member-avatar-lg"
                            alt="Profile">

                        <div>
                            <h2><?= htmlspecialchars($member['full_name']) ?></h2>

                            <?php if ($member['role'] === 'super_admin'): ?>
                                <span class="role-super-admin">Super Admin</span>
                            <?php elseif ($member['role'] === 'admin'): ?>
                                <span class="role-admin">Admin</span>
                            <?php else: ?>
                                <span class="role-member">Member</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="member-info-grid">
                        <div>
                            <label>Email</label>
                            <p><?= htmlspecialchars($member['email']) ?></p>
                        </div>
                        <div>
                            <label>Phone</label>
                            <p><?= $member['phone'] ?: '-' ?></p>
                        </div>
                        <div>
                            <label>Gender</label>
                            <p><?= ucfirst($member['gender'] ?? '-') ?></p>
                        </div>
                        <div>
                            <label>Date of Birth</label>
                            <p><?= $member['dob'] ?: '-' ?></p>
                        </div>
                    </div>

                    <div class="member-actions">
                        <a href="member_list.php" class="btn-back">
                            <img src="../images/back.png" alt="Back" style="padding-top: 2px; width: 10px; height: 10px;">
                            Back to Members
                        </a>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').onclick = () =>
            document.body.classList.toggle('sidebar-collapsed');
    </script>

</body>

</html>