<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id   = (int)($_POST['category_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($id <= 0 || $name === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid ID or Name missing.']);
    exit;
}

try {
    $imagePath = null;
    $updateImage = false;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        if (in_array($_FILES['image']['type'], $allowed)) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newName = 'category_' . $id . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            $baseFolder = '../../user/images/category/';
            $dbPath = '../images/category/';
            
            if (!is_dir($baseFolder)) {
                mkdir($baseFolder, 0777, true);
            }
            
            $oldStmt = $pdo->prepare("SELECT image FROM product_categories WHERE category_id = ?");
            $oldStmt->execute([$id]);
            $oldImage = $oldStmt->fetchColumn();
            if ($oldImage && file_exists('../../user' . (strpos($oldImage, '/') === 0 ? '' : '/') . $oldImage)) {
                @unlink('../../user' . (strpos($oldImage, '/') === 0 ? '' : '/') . $oldImage);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $baseFolder . $newName)) {
                $imagePath = $dbPath . $newName;
                $updateImage = true;
            }
        }
    }

    if ($updateImage) {
        $stmt = $pdo->prepare("UPDATE product_categories SET name = ?, description = ?, image = ? WHERE category_id = ?");
        $stmt->execute([$name, $desc, $imagePath, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE product_categories SET name = ?, description = ? WHERE category_id = ?");
        $stmt->execute([$name, $desc, $id]);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
