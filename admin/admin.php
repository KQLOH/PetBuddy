<?php
session_start();

$errors = [];
$email = '';

$fixed_admin_email = "kwc@petbuddy.com";
$fixed_admin_password = "1234";

if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        if ($email === $fixed_admin_email && $password === $fixed_admin_password) {
            
            $_SESSION['member_id'] = 1;
            $_SESSION['full_name'] = "Admin KWC";
            $_SESSION['email'] = $email;
            $_SESSION['role'] = "admin";

            header('Location: admin/dashboard.php');
            exit;

        } else {
            $errors[] = 'Invalid admin email or password.';
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

    <style>
        :root {
            --primary-color: #F4A261;
            --primary-dark: #E68E3F;
            --bg-light: #f9f9f9;
            --text-dark: #333333;
            --border-color: #e0e0e0;
            --danger-color: #e53935;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fff7ec, #ffffff);
            color: var(--text-dark);
        }

        .admin-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .admin-card {
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            padding: 24px 22px 22px;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .logo-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #ffffff;
        }

        .admin-logo-text {
            font-size: 20px;
            font-weight: 700;
        }

        .admin-subtitle {
            font-size: 13px;
            color: #777;
            margin-bottom: 18px;
        }

        .badge-admin {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 999px;
            background-color: rgba(244, 162, 97, 0.12);
            color: var(--primary-dark);
            margin-bottom: 14px;
        }

        form {
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        label {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 14px;
        }

        .btn {
            border: none;
            border-radius: 999px;
            padding: 8px 14px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #ffffff;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .errors {
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            color: var(--danger-color);
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 12px;
            margin-bottom: 10px;
        }

        .errors ul {
            padding-left: 18px;
        }

        .back-to-site {
            margin-top: 12px;
            text-align: center;
            font-size: 12px;
        }

        .back-to-site a {
            color: var(--primary-dark);
        }
    </style>
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
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form id="adminLoginForm" method="post" action="admin.php">
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email"
                       value="<?php echo htmlspecialchars($email); ?>"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="back-to-site">
            ← <a href="home.php">Back to PetBuddy Shop</a>
        </div>
    </div>
</div>

</body>
</html>
