<?php
session_start();
require_once '../../user/include/db.php';

/* ===== AUTH ===== */
if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'], true)
) {
    header('Location: admin_login.php');
    exit;
}

/* ===== INPUT ===== */
$productId = (int)($_POST['id'] ?? 0);

if ($productId <= 0) {
    header('Location: product_list.php?error=invalid_id');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$productId]);

    header('Location: product_list.php?deleted=1');
    exit;

} catch (PDOException $e) {
    header('Location: product_list.php?error=delete_failed');
    exit;
}
