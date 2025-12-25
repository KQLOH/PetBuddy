<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Category Name is required.']);
    exit;
}

try {
    $check = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Category already exists.']);
        exit;
    }

    $imagePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (in_array($_FILES['image']['type'], $allowed)) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newName = 'category_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            $baseFolder = '../../user/images/category/';
            $dbPath = '../images/category/';
            
            if (!is_dir($baseFolder)) {
                mkdir($baseFolder, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $baseFolder . $newName)) {
                $imagePath = $dbPath . $newName;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO product_categories (name, description, image) VALUES (?, ?, ?)");
    $stmt->execute([$name, $desc, $imagePath]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
