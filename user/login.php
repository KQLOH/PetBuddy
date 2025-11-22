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
            // password_verify checks the plain text password against the hash in DB
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .bg-pet-primary { background-color: #FF9F1C; }
        .text-pet-primary { color: #FF9F1C; }
    </style>
</head>
<body class="bg-gray-50 font-sans flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
        
        <!-- Header -->
        <div class="bg-pet-primary p-6 text-center">
            <h1 class="text-2xl font-bold text-white"><i class="fas fa-paw mr-2"></i> PetBuddy</h1>
            <p class="text-orange-100 text-sm">Welcome back!</p>
        </div>

        <!-- Form Container -->
        <div class="p-8">
            
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Member Login</h2>

            <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-envelope"></i></span>
                        <input type="email" name="email" id="email" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-transparent" placeholder="you@example.com" required>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-transparent" placeholder="••••••••" required>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-pet-primary hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-lg transition duration-200 shadow-md transform hover:-translate-y-1">
                    Sign In
                </button>

            </form>

            <div class="mt-6 text-center text-sm text-gray-500">
                Don't have an account? <a href="register.php" class="text-pet-primary font-bold hover:underline">Sign up here</a>
            </div>
            <div class="mt-2 text-center text-sm">
                <a href="../index.php" class="text-gray-400 hover:text-gray-600">Back to Home</a>
            </div>
        </div>
    </div>

</body>
</html>