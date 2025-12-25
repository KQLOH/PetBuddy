<?php
session_start();
require_once '../../user/include/db.php';

header('Content-Type: application/json');

if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'], true)
) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$adminRole = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$member_id = (int)($_POST['id'] ?? 0);
if ($member_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid member ID']);
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');
$address   = trim($_POST['address'] ?? '');
$gender    = $_POST['gender'] ?: null;
$dob       = $_POST['dob'] ?: null;
$role      = $_POST['role'] ?? null;
$password  = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

if ($full_name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Full name is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

if ($gender && !in_array($gender, ['male', 'female'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid gender selection']);
    exit;
}

$check = $pdo->prepare("
    SELECT member_id FROM members
    WHERE email = ? AND member_id != ?
");
$check->execute([$email, $member_id]);
if ($check->fetch()) {
    http_response_code(400);
    echo json_encode(['error' => 'Email already exists']);
    exit;
}

$currentMember = $pdo->prepare("SELECT role, image FROM members WHERE member_id = ?");
$currentMember->execute([$member_id]);
$currentMemberData = $currentMember->fetch(PDO::FETCH_ASSOC);
$currentRole = $currentMemberData['role'];
$currentImage = $currentMemberData['image'];

$image_path = $currentImage;
$updateImage = false;

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
    $file_ext = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if ($check !== false && in_array($file_ext, $allowed_exts)) {
        if ($_FILES["profile_image"]["size"] <= 5000000) {
            $target_dir = "";
            $db_path_prefix = "";
            
            if ($currentImage && strpos($currentImage, 'admin/images/admin/') === 0) {
                $target_dir = "../../admin/images/admin/";
                $db_path_prefix = "admin/images/admin/";
            } else {
                $target_dir = "../../user/uploads/";
                $db_path_prefix = "uploads/";
            }
            
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $new_filename = "mem_" . $member_id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                if ($currentImage && 
                    !in_array(basename($currentImage), ['boy.png', 'woman.png', 'default_product.jpg']) &&
                    strpos($currentImage, 'admin/images/admin/') === 0) {
                    $old_file = "../../admin/images/admin/" . basename($currentImage);
                    if (file_exists($old_file)) {
                        @unlink($old_file);
                    }
                } elseif ($currentImage && 
                          !in_array(basename($currentImage), ['boy.png', 'woman.png']) &&
                          strpos($currentImage, 'uploads/') !== false) {
                    $old_file = "../../user/" . $currentImage;
                    if (file_exists($old_file)) {
                        @unlink($old_file);
                    }
                }
                
                $image_path = $db_path_prefix . $new_filename;
                $updateImage = true;
            }
        }
    }
}

if ($updateImage) {
    $pdo->prepare("
        UPDATE members SET
            full_name = ?, email = ?, phone = ?, address = ?,
            gender = ?, dob = ?, role = ?, image = ?
        WHERE member_id = ?
    ")->execute([
        $full_name,
        $email,
        $phone,
        $address,
        $gender,
        $dob,
        $currentRole,
        $image_path,
        $member_id
    ]);
} else {
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
        $currentRole,
        $member_id
    ]);
}

if ($password !== '') {
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

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("
        UPDATE members SET password_hash = ?
        WHERE member_id = ?
    ")->execute([$hash, $member_id]);
}

echo json_encode(['success' => true, 'message' => 'Member updated successfully']);
