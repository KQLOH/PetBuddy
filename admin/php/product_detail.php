<?php
session_start();
require_once '../../user/include/db.php';

/* =======================
   AUTH
======================= */
if (
    empty($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'super_admin'], true)
) {
    header('Location: admin_login.php');
    exit;
}

/* =======================
   VALIDATE ID
======================= */
$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    header('Location: product_list.php');
    exit;
}

/* =======================
   FETCH PRODUCT
======================= */
$sql = "
    SELECT
        p.product_id,
        p.name,
        p.description,
        p.price,
        p.stock_qty,
        p.image,
        pc.name AS category_name,
        sc.name AS sub_category_name
    FROM products p
    LEFT JOIN product_categories pc ON pc.category_id = p.category_id
    LEFT JOIN sub_categories sc ON sc.sub_category_id = p.sub_category_id
    WHERE p.product_id = ?
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: product_list.php');
    exit;
}

/* =======================
   IMAGE PATH
======================= */
function productImageUrl(?string $dbPath): string {
    if (!$dbPath) {
        return 'https://via.placeholder.com/300?text=No+Image';
    }
    return '../../user/php/' . ltrim($dbPath, '/');
}

/* =======================
   STOCK STATUS
======================= */
$qty = (int)$product['stock_qty'];
$stockClass = $qty <= 0 ? 'stock-out' : ($qty <= 5 ? 'stock-low' : 'stock-ok');
$stockLabel = $qty <= 0 ? 'Out of stock' : $qty;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Detail</title>
    <link rel="stylesheet" href="../css/admin_product.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-left">
            <button id="sidebarToggle" class="sidebar-toggle">☰</button>
            <div class="topbar-title">Product Detail</div>
        </div>
    </header>

    <main class="content">

        <div class="page-header">
            <div class="page-title"><?= htmlspecialchars($product['name']) ?></div>
            <div class="page-subtitle">View product information</div>
        </div>

        <div class="panel">

            <table>
                <tbody>
                <tr>
                    <th style="width:180px;">Product ID</th>
                    <td><?= (int)$product['product_id'] ?></td>
                </tr>

                <tr>
                    <th>Photo</th>
                    <td>
                        <img
                            src="<?= htmlspecialchars(productImageUrl($product['image'])) ?>"
                            alt="Product Image"
                            class="product-thumb"
                            style="width:140px;height:140px;">
                    </td>
                </tr>

                <tr>
                    <th>Name</th>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                </tr>

                <tr>
                    <th>Category</th>
                    <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                </tr>

                <tr>
                    <th>Sub Category</th>
                    <td><?= htmlspecialchars($product['sub_category_name'] ?? '-') ?></td>
                </tr>

                <tr>
                    <th>Price (RM)</th>
                    <td><?= number_format((float)$product['price'], 2) ?></td>
                </tr>

                <tr>
                    <th>Stock</th>
                    <td>
                        <span class="stock-pill <?= $stockClass ?>">
                            <?= $stockLabel ?>
                        </span>
                    </td>
                </tr>

                <tr>
                    <th>Description</th>
                    <td>
                        <?= nl2br(htmlspecialchars($product['description'] ?: '-')) ?>
                    </td>
                </tr>
                </tbody>
            </table>

            <!-- ACTIONS -->
            <div class="actions" style="margin-top:18px;">
                <a href="product_list.php" class="btn-action btn-view">
                    Back
                </a>
            </div>

        </div>

    </main>
</div>

<script>
document.getElementById('sidebarToggle').onclick = () =>
    document.body.classList.toggle('sidebar-collapsed');
</script>

</body>
</html>
