<?php
// admin/product_edit.php
session_start();
require_once '../user/include/db.php';

// 1. 权限检查
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: products_list.php");
    exit;
}

// 2. 获取当前商品信息
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "Product not found.";
    exit;
}

// 3. 获取分类
$categories = [];
try {
    $stmt = $pdo->query("SELECT category_id, name FROM product_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }

// 4. 处理更新提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock_qty'];
    $category_id = $_POST['category_id'];
    
    // 默认使用旧图片
    $imageName = $product['image']; 

    // 如果上传了新图片
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newFileName = uniqid("prod_") . "." . $ext;
            $uploadDir = "../uploads/products/";
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFileName)) {
                // 删除旧图片 (可选，为了节省空间)
                if (!empty($product['image']) && file_exists($uploadDir . $product['image'])) {
                    unlink($uploadDir . $product['image']);
                }
                $imageName = $newFileName;
            }
        }
    }

    try {
        $sql = "UPDATE products SET name=?, description=?, price=?, stock_qty=?, category_id=?, image=? WHERE product_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $price, $stock, $category_id, $imageName, $id]);
        
        header("Location: products_list.php");
        exit;
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 复用样式 */
        body { font-family: 'Segoe UI', sans-serif; background: #f9f9f9; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-submit { background: #2196F3; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; width: 100%; font-size: 16px; font-weight: 600; }
        .btn-submit:hover { background: #1976D2; }
        .btn-back { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #666; font-size: 14px; }
        .current-img { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; margin-top: 5px; border: 1px solid #eee; }
    </style>
</head>
<body>

<div class="container">
    <a href="products_list.php" class="btn-back">← Back to List</a>
    <h2>Edit Product</h2>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>

        <div class="form-group">
            <label>Category</label>
            <select name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php if($product['category_id'] == $cat['category_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Price (RM)</label>
            <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required>
        </div>

        <div class="form-group">
            <label>Stock Quantity</label>
            <input type="number" name="stock_qty" value="<?php echo htmlspecialchars($product['stock_qty']); ?>" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Current Image</label>
            <?php if ($product['image']): ?>
                <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" class="current-img">
            <?php else: ?>
                <p style="color:#999; font-size:12px;">No image uploaded</p>
            <?php endif; ?>
            
            <label style="margin-top:10px;">Change Image (Optional)</label>
            <input type="file" name="image" accept="image/*">
        </div>

        <button type="submit" class="btn-submit">Update Product</button>
    </form>
</div>

</body>
</html>