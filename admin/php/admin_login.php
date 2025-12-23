<?php
session_start();

require_once '../../user/include/db.php';

$errors = [];
$email = '';

if (!empty($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT member_id, full_name, password_hash, role
                FROM members
                WHERE email = ?
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (
                $user &&
                in_array($user['role'], ['admin', 'super_admin']) &&
                password_verify($password, $user['password_hash'])
            ) {
                $_SESSION['member_id'] = $user['member_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $email;
                $_SESSION['role']      = $user['role'];

                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid admin email or password.';
            }
        } catch (PDOException $e) {
            $errors[] = 'System error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PetBuddy Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin_login.css">
</head>

<body>

    <div class="admin-wrapper">
        <div class="admin-card">

            <div class="admin-logo">
                <div class="logo-circle">🐾</div>
                <div class="admin-logo-text">PetBuddy Admin</div>
            </div>

            <div class="badge-admin">Admin Panel Login</div>
            <p class="admin-subtitle">
                Please login with your administrator account to manage the backend.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Admin Email</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($email) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    Login
                </button>
            </form>

            <div class="back-to-site">
                ← <a href="../../home.php">Back to PetBuddy Shop</a>
            </div>

        </div>
    </div>

</body>

</html>