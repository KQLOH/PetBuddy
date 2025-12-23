<?php
// admin/product_add.php
session_start();
require_once '../user/include/db.php';

// 1. 权限检查
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = "";
$msg_type = "";

// 2. 获取分类 (用于下拉菜单)
$categories = [];
try {
    $stmt = $pdo->query("SELECT category_id, name FROM product_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }

// 3. 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock_qty'];
    $category_id = $_POST['category_id'];
    
    // 图片上传逻辑
    $imageName = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // 生成唯一文件名防止覆盖
            $imageName = uniqid("prod_") . "." . $ext;
            $uploadDir = "../uploads/products/";
            
            // 确保文件夹存在
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        } else {
            $message = "Invalid image format (JPG, PNG, WEBP only).";
            $msg_type = "error";
        }
    }

    if (empty($message)) {
        try {
            $sql = "INSERT INTO products (name, description, price, stock_qty, category_id, image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $description, $price, $stock, $category_id, $imageName]);
            
            // 成功后跳转回列表
            header("Location: products_list.php");
            exit;
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 复用之前的 CSS 风格 */
        body { font-family: 'Segoe UI', sans-serif; background: #f9f9f9; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-submit { background: #F4A261; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; width: 100%; font-size: 16px; font-weight: 600; }
        .btn-submit:hover { background: #E68E3F; }
        .btn-back { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #666; font-size: 14px; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 6px; background: #ffebee; color: #c62828; }
    </style>
</head>
<body>

<div class="container">
    <a href="products_list.php" class="btn-back">← Back to List</a>
    <h2>Add New Product</h2>

    <?php if ($message): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Category</label>
            <select name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Price (RM)</label>
            <input type="number" step="0.01" name="price" required>
        </div>

        <div class="form-group">
            <label>Stock Quantity</label>
            <input type="number" name="stock_qty" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4"></textarea>
        </div>

        <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="image" accept="image/*">
        </div>

        <button type="submit" class="btn-submit">Add Product</button>
    </form>
</div>

</body>
</html>