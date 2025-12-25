<?php
session_start();
require_once '../../user/include/db.php';

header('Content-Type: application/json');

if (
    empty($_SESSION['role']) ||
    $_SESSION['role'] !== 'super_admin'
) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$adminId = $_SESSION['member_id'] ?? 0;

$memberId = $_POST['id'] ?? null;
if (!$memberId || !is_numeric($memberId)) {
    echo json_encode(['success' => false, 'error' => 'Invalid member ID']);
    exit;
}

$memberId = (int)$memberId;

if ($memberId === (int)$adminId) {
    echo json_encode(['success' => false, 'error' => 'You cannot delete your own account']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Member not found']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM orders 
        WHERE member_id = ?
    ");
    $stmt->execute([$memberId]);
    $orderCount = (int)$stmt->fetchColumn();

    if ($orderCount > 0) {
        echo json_encode(['success' => false, 'error' => 'This member has existing orders and cannot be deleted']);
        exit;
    }

    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM chat_messages WHERE member_id = ?")
        ->execute([$memberId]);
    $pdo->prepare("DELETE FROM cart_items WHERE member_id = ?")
        ->execute([$memberId]);
    $pdo->prepare("DELETE FROM product_reviews WHERE member_id = ?")
        ->execute([$memberId]);
    $pdo->prepare("DELETE FROM member_addresses WHERE member_id = ?")
        ->execute([$memberId]);
    $pdo->prepare("DELETE FROM members WHERE member_id = ?")
        ->execute([$memberId]);
    $pdo->commit();

    echo json_encode(['success' => true]);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
