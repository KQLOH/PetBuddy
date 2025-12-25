<?php
session_set_cookie_params(0, '/');
session_start();

include '../include/db.php'; 

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT member_id, full_name, password_hash, role, image FROM members WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['member_id'] = $user['member_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user_image'] = !empty($user['image']) ? $user['image'] : '../images/default-avatar.png';

                if (isset($_POST['remember_me'])) {
                    $token = bin2hex(random_bytes(32));
                    $updateStmt = $pdo->prepare("UPDATE members SET remember_token = ? WHERE member_id = ?");
                    $updateStmt->execute([$token, $user['member_id']]);
                    setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);
                } else {
                    $clearStmt = $pdo->prepare("UPDATE members SET remember_token = NULL WHERE member_id = ?");
                    $clearStmt->execute([$user['member_id']]);
                    
                    if (isset($_COOKIE['remember_token'])) {
                        setcookie('remember_token', '', time() - 3600, "/", "", false, true);
                        unset($_COOKIE['remember_token']);
                    }
                }

                header("Location: home.php");
                exit;
                
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "System error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PetBuddy</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .header-logo {
            height: 1.8rem;
            width: auto;
            margin-right: 0.5rem;
            vertical-align: bottom;
        }

        .input-group .input-icon {
            width: auto;
            height: 1.25rem;
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            pointer-events: none;
            opacity: 0.7;
        }

        .form-input {
            padding-left: 3rem !important;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            width: 1.25rem;
            height: auto;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.2s;
        }

        .password-toggle:hover {
            opacity: 1;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        .page-content-wrapper {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px 0;
            width: 100%;
        }

        .alert-success {
            background-color: rgba(244, 162, 97, 0.12);
            color: var(--primary-dark);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .form-footer-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .footer-link {
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .warning-link {
            color: #e67e22;
        }

        .warning-link:hover {
            color: #d35400;
            text-decoration: underline;
        }

        .muted-link {
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .muted-link:hover {
            color: #343a40;
        }

        .icon-arrow {
            font-size: 1.1em;
            line-height: 1;
            transition: transform 0.2s;
        }

        .muted-link:hover .icon-arrow {
            transform: translateX(-3px);
        }
    </style>
</head>
<?php include '../include/header.php'; ?>

<body>

    <div class="page-content-wrapper">
        <div class="card max-w-md">

            <div class="card-header">
                <h1>
                    <img src="../images/pawprint.png" alt="Logo" class="header-logo">
                    PetBuddy
                </h1>
                <p>Welcome back!</p>
            </div>

            <div class="card-body">

                <h2 class="card-title">Member Login</h2>

                <?php if (!empty($registration_success_message)): ?>
                    <div class="alert-success" role="alert">
                        <p><?php echo htmlspecialchars($registration_success_message); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert-error" role="alert">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="input-group">
                            <img src="../images/mail.png" alt="Email" class="input-icon">
                            <input type="text" name="email" id="email" class="form-input" placeholder="you@example.com" required>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <img src="../images/padlock.png" alt="Lock" class="input-icon">

                            <input type="password" name="password" id="password" class="form-input" placeholder="••••••••" style="padding-right: 3rem;" required>

                            <img src="../images/show.png" id="togglePassword" class="password-toggle" alt="Show Password">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">
                        Sign In
                    </button>

                                    <div class="mb-4" style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px;">
                    <input type="checkbox" name="remember_me" id="remember_me" style="cursor: pointer;">
                    <label for="remember_me" style="font-size: 0.9rem; color: #666; cursor: pointer;">Remember Me</label>
                </div>
                </form>

                <div class="mt-6 text-center link-muted">
                    Don't have an account? <a href="register.php" class="link-primary">Sign up here</a>
                </div>

                <div class="form-footer-actions">
                    <a href="forgot_password.php" class="footer-link warning-link">
                        Forgot Password?
                    </a>
                    <a href="home.php" class="footer-link muted-link">
                        <span class="icon-arrow"><</span> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function(e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            if (type === 'text') {
                this.src = '../images/hide.png';
            } else {
                this.src = '../images/show.png';
            }
        });
    </script>
    <?php include '../include/footer.php'; ?>
</body>

</html>