<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

$loggedIn = false;
$userAvatar = "";

if (isset($_SESSION['user_id'])) {
    $loggedIn = true;
    $userAvatar = 'images/default-avatar.png';

    $stmt = $pdo->prepare("SELECT image FROM members WHERE member_id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row && $row['image']) {
        $userAvatar = $row['image'];
    }
}

$login_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_email'])) {
    $email = trim($_POST['login_email']);
    $password = $_POST['login_password'];

    $stmt = $pdo->prepare("SELECT member_id, password_hash FROM members WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['member_id'];
        echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
        exit;
    } else {
        $login_error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy Online Pet Shop | Header</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --primary-color: #FFB774;
            --primary-dark: #E89C55;
            --text-dark: #2F2F2F;
            --border-color: #e8e8e8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Inter", system-ui, sans-serif;
        }

        body {
            background: #fff;
            padding-top: 80px;
        }

        .navbar {
            width: 100%;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000; 
        }

        .navbar-inner {
            max-width: 1150px;
            margin: auto;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .logo-circle {
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-links {
            display: flex;
            gap: 25px;
        }

        .nav-links a {
            font-size: 17px;
            font-weight: 500;
            color: var(--text-dark);
            text-decoration: none;
            transition: 0.2s;
        }

        .nav-links a.active {
            color: var(--primary-color) !important;
            font-weight: 600;
        }

        .nav-links a:hover {
            color: var(--primary-dark);
        }

        .nav-icon-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: transparent;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .nav-icon-btn:hover {
            background: rgba(0,0,0,0.06);
        }

        svg {
            stroke: #444;
            width: 26px;
            height: 26px;
        }

        .search-container {
            width: 100%;
            background: #fff;
            border-bottom: 1px solid var(--border-color);
            max-height: 0;
            overflow: hidden;
            transition: 0.35s ease;
        }

        .search-container.active {
            max-height: 170px;
            padding: 18px 0;
        }

        .search-box {
            max-width: 620px;
            margin: auto;
            display: flex;
            border: 1px solid var(--primary-color);
            border-radius: 50px;
            overflow: hidden;
        }

        .search-box input {
            flex: 1;
            padding: 13px 20px;
            border: none;
            outline: none;
            font-size: 16px;
        }

        .search-box button {
            padding: 0 28px;
            border: none;
            background: var(--primary-color);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .search-box button:hover {
            background: var(--primary-dark);
        }

        .search-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 12px 20px;
            border: none;
            background-color: #F4A261;
            color: #fff;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: 0.3s ease;
            z-index: 1050;
        }

        .login-sidebar {
            position: fixed;
            right: -400px;
            top: 0;
            width: 380px;
            height: 100vh;
            background: #fff;
            box-shadow: -4px 0 12px rgba(0,0,0,0.15);
            padding: 30px;
            transition: 0.35s ease;
            z-index: 1100;
            overflow-y: auto;
            font-family: "Inter", sans-serif;
        }

        .login-sidebar.active {
            right: 0;
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .login-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 26px;
            cursor: pointer;
        }

        .login-form label {
            font-weight: 500;
            font-size: 15px;
            margin-top: 15px;
            display: block;
        }

        .login-form input {
            width: 100%;
            padding: 12px;
            margin-top: 6px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            margin-top: 25px;
            background: #000;
            color: #fff;
            border: none;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .lost-password {
            display: block;
            margin-top: 18px;
            text-align: center;
            font-size: 14px;
            text-decoration: none;
        }

        .no-account {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .primary-link {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
        }

        .primary-link:hover {
            color: var(--primary-dark);
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <div class="logo">
            <div class="logo-circle">🐾</div>
            <span>PetBuddy</span>
        </div>

        <div class="nav-links">
            <a href="index.php" class="<?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>">Home</a>
            <a href="about.php" class="<?= basename($_SERVER['PHP_SELF'])==='about.php'?'active':'' ?>">About</a>
            <a href="products.php" class="<?= basename($_SERVER['PHP_SELF'])==='products.php'?'active':'' ?>">Products</a>
            <a href="contact.php" class="<?= basename($_SERVER['PHP_SELF'])==='contact.php'?'active':'' ?>">Contact</a>
        </div>

        <div style="display:flex;gap:10px;align-items:center;">
            <button class="nav-icon-btn" onclick="toggleSearchBar()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="8"></circle>
                    <line x1="22" y1="22" x2="17" y2="17"></line>
                </svg>
            </button>

            <?php if($loggedIn): ?>
                <div class="user-avatar-dropdown" style="position:relative;">
                    <img src="<?= $userAvatar ?>" style="width:26px;height:26px;border-radius:50%;cursor:pointer;" onclick="toggleUserDropdown()">
                    <div id="userDropdown" style="display:none;position:absolute;top:36px;right:0;background:#fff;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.15);min-width:120px;z-index:1000;">
                        <a href="profile.php" style="display:block;padding:10px;text-decoration:none;color:#333;">Profile</a>
                        <a href="logout.php" style="display:block;padding:10px;text-decoration:none;color:#333;">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <button class="nav-icon-btn" onclick="openLogin()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </button>
            <?php endif; ?>

            <a href="cart.php" class="nav-icon-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2l1 5h10l1-5z"/>
                    <path d="M3 7h18l-2 13H5L3 7z"/>
                </svg>
            </a>
        </div>
    </div>
</nav>

<div class="search-container" id="searchBar">
    <form action="products.php" method="get" class="search-box">
        <input type="text" name="search" placeholder="Search pet food, toys, grooming...">
        <button type="submit" class="search-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span>Search</span>
        </button>
    </form>
</div>

<div id="loginOverlay" class="overlay" onclick="closeLogin()"></div>

<div id="loginSidebar" class="login-sidebar">
    <div class="login-header">
        <span>Sign in</span>
        <button class="close-btn" onclick="closeLogin()">&times;</button>
    </div>
    <form class="login-form" method="POST" action="">
        <label>Email *</label>
        <input type="text" name="login_email" placeholder="Enter your email" required>

        <label>Password *</label>
        <input type="password" name="login_password" placeholder="Enter your password" required>

        <button type="submit" class="login-btn">Login</button>

        <?php if($login_error): ?>
            <div style="color:red;text-align:center;margin-top:10px;"><?= $login_error ?></div>
        <?php endif; ?>

        <a href="#" class="lost-password">Lost your password?</a>

        <div class="no-account">
            No account yet? <a href="register.php" class="primary-link">Create an Account</a>
        </div>
    </form>
</div>

<script>
function toggleSearchBar() {
    document.getElementById("searchBar").classList.toggle("active");
}
function openLogin() {
    document.getElementById("loginSidebar").classList.add("active");
    document.getElementById("loginOverlay").classList.add("active");
}
function closeLogin() {
    document.getElementById("loginSidebar").classList.remove("active");
    document.getElementById("loginOverlay").classList.remove("active");
}
function toggleUserDropdown() {
    const dropdown = document.getElementById("userDropdown");
    dropdown.style.display = dropdown.style.display==="block"?"none":"block";
}
document.addEventListener('click', function(e){
    const avatar = document.querySelector('.user-avatar-dropdown img');
    const dropdown = document.getElementById("userDropdown");
    if(avatar && !avatar.contains(e.target) && !dropdown.contains(e.target)){
        dropdown.style.display="none";
    }
});
</script>

</body>
</html>
