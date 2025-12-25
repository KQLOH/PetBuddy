<?php
session_start();
require_once '../../user/include/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock_qty'] ?? 0);
    $cat   = $_POST['category_id'] ?: null;
    $sub   = $_POST['sub_category_id'] ?: null;

    if ($name === '') {
        echo json_encode(['success' => false, 'error' => 'Product name is required.']);
        exit;
    }
    if ($price <= 0) {
        echo json_encode(['success' => false, 'error' => 'Price must be greater than 0.']);
        exit;
    }

    try {
        $imagePath = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            if (in_array($_FILES['image']['type'], $allowed)) {

                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $newName = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $ext;

                $baseFolder = '../../user/images/product/';
                $dbPath = '../images/product/';

                if ($cat) {
                    $catStmt = $pdo->prepare("SELECT name FROM product_categories WHERE category_id = ?");
                    $catStmt->execute([$cat]);
                    $catName = $catStmt->fetchColumn();

                    if ($catName) {
                        $safeCatName = trim($catName);
                        $baseFolder .= $safeCatName . '/';
                        $dbPath .= $safeCatName . '/';

                        if ($sub) {
                            $subStmt = $pdo->prepare("SELECT name FROM sub_categories WHERE sub_category_id = ?");
                            $subStmt->execute([$sub]);
                            $subName = $subStmt->fetchColumn();
                            if ($subName) {
                                $safeSubName = trim($subName);
                                $baseFolder .= $safeSubName . '/';
                                $dbPath .= $safeSubName . '/';
                            }
                        }
                    }
                }

                if (!is_dir($baseFolder)) {
                    mkdir($baseFolder, 0777, true);
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $baseFolder . $newName)) {
                    $imagePath = $dbPath . $newName;
                }
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO products 
            (name, description, price, stock_qty, category_id, sub_category_id, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $success = $stmt->execute([$name, $desc, $price, $stock, $cat, $sub, $imagePath]);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database insertion failed.']);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}
