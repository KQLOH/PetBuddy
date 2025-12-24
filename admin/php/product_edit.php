<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (!in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$stock = (int)($_POST['stock_qty'] ?? 0);
$cat = $_POST['category_id'] ?: null;
$sub = $_POST['sub_category_id'] ?: null;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

if ($name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Name required']);
    exit;
}

if ($price <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid price']);
    exit;
}

if ($stock < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid stock quantity']);
    exit;
}

$imagePath = null;
$updateImage = false;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!in_array($_FILES['image']['type'], $allowed, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid image format']);
        exit;
    }

    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $newName = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $baseFolder = '../../user/images/product/';
    $dbPath = '../images/product/';

    if ($cat) {
        $catStmt = $pdo->prepare("SELECT name FROM product_categories WHERE category_id=?");
        $catStmt->execute([$cat]);
        $catName = $catStmt->fetchColumn();

        if ($catName) {
            $baseFolder .= $catName . '/';
            $dbPath     .= $catName . '/';

            if ($sub) {
                $subStmt = $pdo->prepare("SELECT name FROM sub_categories WHERE sub_category_id=?");
                $subStmt->execute([$sub]);
                $subName = $subStmt->fetchColumn();

                if ($subName) {
                    $baseFolder .= $subName . '/';
                    $dbPath     .= $subName . '/';
                }
            }
        }
    }

    if (!is_dir($baseFolder)) {
        mkdir($baseFolder, 0777, true);
    }

    move_uploaded_file($_FILES['image']['tmp_name'], $baseFolder . $newName);

    $imagePath = $dbPath . $newName;
    $updateImage = true;
}

try {
    if ($updateImage) {
        $pdo->prepare("
            UPDATE products SET
            name=?, price=?, stock_qty=?, description=?, category_id=?, sub_category_id=?, image=?
            WHERE product_id=?
        ")->execute([$name, $price, $stock, $desc, $cat, $sub, $imagePath, $id]);
    } else {
        $pdo->prepare("
            UPDATE products SET
            name=?, price=?, stock_qty=?, description=?, category_id=?, sub_category_id=?
            WHERE product_id=?
        ")->execute([$name, $price, $stock, $desc, $cat, $sub, $id]);
    }

    echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
