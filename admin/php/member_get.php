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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid member ID']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT member_id, full_name, email, phone, address, gender, dob, role, image
    FROM members
    WHERE member_id = ?
    LIMIT 1
");

$stmt->execute([$id]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    http_response_code(404);
    echo json_encode(['error' => 'Member not found']);
    exit;
}

$imagePath = 'https://via.placeholder.com/120?text=User';
if (!empty($member['image'])) {
    $image = ltrim($member['image'], '/');
    if (strpos($image, 'admin/images/') === 0) {
        $imagePath = '../../' . $image;
    } else {
        $imagePath = '../../user/' . $image;
    }
}

$member['image_path'] = $imagePath;

echo json_encode(['success' => true, 'member' => $member]);
