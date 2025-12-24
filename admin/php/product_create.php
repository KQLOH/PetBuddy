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
$success = null;

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

    /* ===== IMAGE UPLOAD ===== */
    $imagePath = null;

    if (!$error && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array($_FILES['image']['type'], $allowed, true)) {
            $error = 'Invalid image format.';
        } else {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $newName = 'product_' . time() . '_' . rand(1000,9999) . '.' . $ext;

            $uploadDir = '../../user/php/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newName);
            $imagePath = 'uploads/' . $newName;
        }
    }

    /* ===== INSERT ===== */
    if (!$error) {
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

        header('Location: product_list.php?created=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Product</title>
    <link rel="stylesheet" href="../css/admin_product_create.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">

    <header class="topbar">
        <div class="topbar-left">
            <button id="sidebarToggle" class="sidebar-toggle">☰</button>
            <div class="topbar-title">New Product</div>
        </div>
    </header>

    <main class="content">

        <div class="panel product-create-panel">

            <?php if ($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">

                <!-- IMAGE -->
                <div class="image-section">
                    <img id="preview" src="https://via.placeholder.com/160?text=Preview" alt="Preview">
                    <label class="upload-btn">
                        Upload Image
                        <input type="file" name="image" accept="image/*" hidden onchange="previewImage(event)">
                    </label>
                </div>

                <!-- FORM -->
                <div class="form-grid">

                    <div>
                        <label>Product Name</label>
                        <input type="text" name="name" required>
                    </div>

                    <div>
                        <label>Price (RM)</label>
                        <input type="number" step="0.01" name="price" required>
                    </div>

                    <div>
                        <label>Stock Quantity</label>
                        <input type="number" name="stock_qty" value="0" required>
                    </div>

                    <div>
                        <label>Category</label>
                        <select name="category_id">
                            <option value="">-- None --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['category_id'] ?>">
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label>Sub Category</label>
                        <select name="sub_category_id">
                            <option value="">-- None --</option>
                            <?php foreach ($subCategories as $s): ?>
                                <option value="<?= $s['sub_category_id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="full">
                        <label>Description</label>
                        <textarea name="description"></textarea>
                    </div>

                </div>

                <div class="actions">
                    <a href="product_list.php" class="btn-secondary">← Back</a>
                    <button class="btn-primary">Create Product</button>
                </div>

            </form>

        </div>
    </main>
</div>

<script>
document.getElementById('sidebarToggle').onclick = () =>
    document.body.classList.toggle('sidebar-collapsed');

function previewImage(e) {
    document.getElementById('preview').src =
        URL.createObjectURL(e.target.files[0]);
}
</script>

</body>
</html>
