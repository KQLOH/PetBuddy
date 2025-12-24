<?php
session_start();
require_once '../../user/include/db.php';

if (
    empty($_SESSION['role']) ||
    $_SESSION['role'] !== 'super_admin'
) {
    header('Location: members_list.php');
    exit;
}

$adminId = $_SESSION['member_id'] ?? 0;

$memberId = $_POST['id'] ?? null;
if (!$memberId || !is_numeric($memberId)) {
    header('Location: members_list.php');
    exit;
}

$memberId = (int)$memberId;

if ($memberId === (int)$adminId) {
    header('Location: members_list.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    if (!$stmt->fetch()) {
        header('Location: members_list.php');
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
        header('Location: member_list.php');
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

    header('Location: member_list.php');
    exit;
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: member_list.php');
    exit;
}
