<?php
session_start();
require_once '../../user/include/db.php';

/* =======================
   AUTH
======================= */
if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin','super_admin'], true)
) {
    header('Location: admin_login.php');
    exit;
}

/* =======================
   LOAD CATEGORIES
======================= */
$categories = $pdo->query("
    SELECT category_id, name 
    FROM product_categories 
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$subCategories = $pdo->query("
    SELECT sub_category_id, name 
    FROM sub_categories 
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$error = null;

/* =======================
   HANDLE POST
======================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock_qty'] ?? 0);
    $cat   = $_POST['category_id'] ?: null;
    $sub   = $_POST['sub_category_id'] ?: null;

    if ($name === '') {
        $error = 'Product name is required.';
    } elseif ($price <= 0) {
        $error = 'Invalid price.';
    } elseif ($stock < 0) {
        $error = 'Invalid stock quantity.';
    }

    /* ================= IMAGE UPLOAD ================= */
    $imagePath = null;

    if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $allowed = ['image/jpeg','image/png','image/webp', 'image/jpg'];
        if (!in_array($_FILES['image']['type'], $allowed, true)) {
            $error = 'Invalid image format.';
        } else {

            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newName = 'product_' . time() . '_' . rand(1000,9999) . '.' . $ext;

            /* ===== PHYSICAL PATH (真实文件) ===== */
            $baseFolder = '../../user/images/product/';

            /* ===== DB PATH（你要的） ===== */
            $dbPath = 'images/product/';

            if ($cat) {
                $catStmt = $pdo->prepare("SELECT name FROM product_categories WHERE category_id = ?");
                $catStmt->execute([$cat]);
                $catName = $catStmt->fetchColumn();

                if ($catName) {
                    $catFolder = trim($catName);($catName);
                    $baseFolder .= $catFolder . '/';
                    $dbPath .= $catFolder . '/';

                    if ($sub) {
                        $subStmt = $pdo->prepare("SELECT name FROM sub_categories WHERE sub_category_id = ?");
                        $subStmt->execute([$sub]);
                        $subName = $subStmt->fetchColumn();

                        if ($subName) {
                            $subFolder = trim($subName);
                            $baseFolder .= $subFolder . '/';
                            $dbPath .= $subFolder . '/';
                        }
                    }
                }
            }

            if (!is_dir($baseFolder)) {
                mkdir($baseFolder, 0777, true);
            }

            move_uploaded_file($_FILES['image']['tmp_name'], $baseFolder . $newName);

            /* 最终存进 DB 的值 */
            $imagePath = $dbPath . $newName;
        }
    }

    /* ================= INSERT ================= */
    if (!$error) {
        try {
            $pdo->prepare("
                INSERT INTO products
                (name, description, price, stock_qty, category_id, sub_category_id, image)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $name,
                $desc,
                $price,
                $stock,
                $cat,
                $sub,
                $imagePath
            ]);

            /* AJAX support */
            if (
                !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            ) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }

            header('Location: product_list.php');
            exit;

        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => $error]);
        exit;
    }
}
?>
