<?php
session_start();
require '../include/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['member_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$member_id = $_SESSION['member_id'];
$product_id = $_POST['product_id'] ?? 0;
$rating = intval($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating or product.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, member_id, rating, comment, review_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$product_id, $member_id, $rating, $comment]);

    $msg = "Review submitted!";
    echo json_encode(['success' => true, 'message' => $msg]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
