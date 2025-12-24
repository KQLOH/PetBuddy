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

$currentMember = $pdo->prepare("SELECT role FROM members WHERE member_id = ?");
$currentMember->execute([$member_id]);
$currentRole = $currentMember->fetchColumn();

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

if ($password !== '') {
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->prepare("
        UPDATE members SET password_hash = ?
        WHERE member_id = ?
    ")->execute([$hash, $member_id]);
}

echo json_encode(['success' => true, 'message' => 'Member updated successfully']);
?>

