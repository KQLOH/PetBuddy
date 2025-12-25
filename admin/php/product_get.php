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
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        p.product_id,
        p.name,
        p.description,
        p.price,
        p.stock_qty,
        p.image,
        p.category_id,
        p.sub_category_id,
        pc.name AS category_name,
        sc.name AS sub_category_name
    FROM products p
    LEFT JOIN product_categories pc ON pc.category_id = p.category_id
    LEFT JOIN sub_categories sc ON sc.sub_category_id = p.sub_category_id
    WHERE p.product_id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

$image = trim((string)$product['image']);

$product['image_path'] = $product['image']
    ? '../../user/php/' . ltrim($product['image'], '/')
    : 'https://via.placeholder.com/140?text=No+Image';

echo json_encode([
    'success' => true,
    'product' => $product
]);
