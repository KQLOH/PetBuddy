<?php
session_start();
include '../include/db.php';

$error = '';

// Handle Login Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            // 1. Find user by email
            $stmt = $pdo->prepare("SELECT member_id, full_name, password_hash, role FROM members WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 2. Verify password
            if ($user && password_verify($password, $user['password_hash'])) {
                
                // 3. Set Session Variables
                $_SESSION['member_id'] = $user['member_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // 4. Redirect to Profile
                header("Location: memberProfile.php");
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
    <!-- ONLY Local CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* --- ICON ADJUSTMENTS --- */

        /* 1. Header Logo */
        .header-logo {
            height: 1.8rem; 
            width: auto; 
            margin-right: 0.5rem; 
            vertical-align: bottom; /* Aligns nicely with text baseline */
        }

        /* 2. Input Icons (Mail & Lock) */
        .input-group .input-icon {
            width: auto; 
            height: 1.25rem; /* Approx 20px - standard icon size */
            position: absolute;
            top: 50%;
            left: 1rem; /* Space from the left edge */
            transform: translateY(-50%); /* Perfect vertical centering */
            pointer-events: none; /* Lets clicks pass through to the input */
            opacity: 0.7;
        }

        /* 3. Input Field Spacing */
        /* Overriding style.css to ensure text doesn't overlap the image */
        .form-input {
            padding-left: 3rem !important; /* Room for the icon */
        }

        /* 4. Show/Hide Password Toggle */
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
    </style>
</head>
<body class="flex-center-screen">

    <div class="card max-w-md">
        
        <!-- Header -->
        <div class="card-header">
            <h1>
                <img src="../image/pawprint.png" alt="Logo" class="header-logo">
                PetBuddy
            </h1>
            <p>Welcome back!</p>
        </div>

        <!-- Form Container -->
        <div class="card-body">
            
            <h2 class="card-title">Member Login</h2>

            <?php if ($error): ?>
                <div class="alert-error" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Email -->
                <div class="mb-4">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-group">
                        <!-- Left Icon -->
                        <img src="../image/mail.png" alt="Email" class="input-icon">
                        <input type="email" name="email" id="email" class="form-input" placeholder="you@example.com" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <!-- Left Icon (Padlock) -->
                        <img src="../image/padlock.png" alt="Lock" class="input-icon">
                        
                        <!-- Password Input (Added padding-right to prevent text hitting the eye icon) -->
                        <input type="password" name="password" id="password" class="form-input" placeholder="••••••••" style="padding-right: 3rem;" required>
                        
                        <!-- Right Icon (Show/Hide Toggle) -->
                        <img src="../image/show.png" id="togglePassword" class="password-toggle" alt="Show Password">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-primary">
                    Sign In
                </button>

            </form>

            <div class="mt-6 text-center link-muted">
                Don't have an account? <a href="register.php" class="link-primary">Sign up here</a>
            </div>
            <div class="mt-2 text-center">
                <a href="../user/index.php" class="link-muted">Back to Home</a>
            </div>
        </div>
    </div>

    <!-- Script to toggle Password Visibility -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // 1. Toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // 2. Toggle the eye icon image
            if (type === 'text') {
                this.src = '../image/hide.png';
            } else {
                this.src = '../image/show.png';
            }
        });
    </script>

</body>
</html>