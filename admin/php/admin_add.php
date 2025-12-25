<?php
session_start();
require_once '../../user/include/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Only Super Admin can add admins.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$address   = trim($_POST['address'] ?? '');
$gender    = $_POST['gender'] ?? null;
$dob       = $_POST['dob'] ?: null;
$password  = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');
$role      = trim($_POST['role'] ?? 'admin');

if ($full_name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Full name is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if ($password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password is required']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long']);
    exit;
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Password must contain: uppercase letter, lowercase letter, 1 number, and 1 special character']);
    exit;
}

if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Passwords do not match']);
    exit;
}

if (!in_array($role, ['admin', 'super_admin'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid role']);
    exit;
}

if ($gender && !in_array($gender, ['male', 'female'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid gender selection']);
    exit;
}

if ($dob && !strtotime($dob)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date of birth format']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetchColumn() > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit;
    }

    $image_path = null;
    $target_dir = "../images/admin/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $file_ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check !== false && in_array($file_ext, $allowed_exts)) {
            if ($_FILES["profile_image"]["size"] <= 5000000) {
                $email_hash = substr(md5($email), 0, 8);
                $new_filename = "admin_" . $email_hash . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                    $image_path = "admin/images/admin/" . $new_filename;
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
                    exit;
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Image file is too large. Maximum size is 5MB']);
                exit;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid image file. Please upload JPG, JPEG, PNG, or GIF files only']);
            exit;
        }
    }

    if ($image_path === null) {
        $default_image_name = 'boy.png';
        if ($gender === 'female') {
            $default_image_name = 'woman.png';
        }
        
        $source_file = "../../user/images/" . $default_image_name;
        $dest_file = $target_dir . $default_image_name;
        
        if (file_exists($source_file)) {
            if (copy($source_file, $dest_file)) {
                $image_path = "admin/images/admin/" . $default_image_name;
            } else {
                $image_path = "admin/images/admin/" . $default_image_name;
            }
        } else {
            $image_path = "admin/images/admin/" . $default_image_name;
        }
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO members (full_name, email, password_hash, role, image, phone, address, gender, dob) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$full_name, $email, $hashedPassword, $role, $image_path, $phone ?: null, $address ?: null, $gender, $dob]);

    echo json_encode([
        'success' => true,
        'message' => 'Admin added successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

