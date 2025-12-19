<?php
// 1. 启动 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. 连接数据库
include "../include/db.php"; 

include_once __DIR__ . "/cart_function.php";

// 3. 检查用户是否登录
$is_logged_in = isset($_SESSION['member_id']);

// 4. 获取商品列表 (PDO写法)
$stmt = $pdo->query("SELECT * FROM products ORDER BY product_id DESC LIMIT 12");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PetBuddy Online Pet Shop | Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --primary-color: #FFB774;
            --primary-dark: #E89C55;
            --text-dark: #2F2F2F;
            --border-color: #e8e8e8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Inter", sans-serif; }
        body { background: #fff; overflow-x: hidden; }

        /* BANNER */
        .banner { width: 100%; height: 350px; background: #FFB774; display: flex; align-items: center; justify-content: center; color: white; text-shadow: 0 2px 10px rgba(0,0,0,0.1); font-size: 40px; font-weight: bold; }

        /* PRODUCTS */
        .products-section { max-width: 1150px; margin: 40px auto; padding: 0 20px; }
        .products-section h2 { text-align: center; margin-bottom: 30px; font-size: 28px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; }
        .product-card { background: white; padding: 15px; border-radius: 12px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #eee; transition: 0.3s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .product-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 8px; }
        .product-card h3 { margin-top: 15px; font-size: 18px; }
        .add-btn { margin-top: 15px; background: var(--primary-color); border: none; padding: 10px; color: white; width: 100%; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .add-btn:hover { background: var(--primary-dark); }

        /* ⚠️ 注意：Sidebar 的样式和 HTML 已经从这里移除了，因为 header.php 里已经有了。 */
    </style>
</head>
<body>

<?php include '../include/header.php'; ?>

<div class="banner">
    Welcome to Pet Shop
</div>

<div class="products-section">
    <h2>Latest Pets</h2>
    <div class="product-grid">
        <?php foreach ($products as $row): ?>
            <div class="product-card">
                <img src="<?= htmlspecialchars($row['image']) ?>" alt="pet">
                <h3><?= htmlspecialchars($row['name']) ?></h3>
                <p>RM <?= number_format($row['price'], 2) ?></p>
                
                <button class="add-btn" data-id="<?= $row['product_id'] ?>">
                    Add to Cart
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../include/footer.php'; ?>



</body>
</html>