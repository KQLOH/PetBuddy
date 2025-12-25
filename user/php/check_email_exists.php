<?php


session_start();
require '../include/db.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE email = ?");
    $stmt->execute([$email]);
    $exists = $stmt->fetch() !== false;
    
    echo json_encode(['exists' => $exists]);
} catch (PDOException $e) {
    
    echo json_encode(['exists' => false]);
}
?>

