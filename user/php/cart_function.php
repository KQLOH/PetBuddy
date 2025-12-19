<?php
/**
 * 购物车功能函数库
 * 包含：获取商品、计算总价、添加/更新购物车
 */

/**
 * 获取指定用户的购物车商品列表
 *
 * @param PDO $pdo 数据库连接对象
 * @param int $member_id 用户ID
 * @return array 返回包含商品详情的数组
 */
function getCartItems($pdo, $member_id) {
    // 关联查询：同时获取购物车数量和商品详细信息
    $sql = "SELECT ci.quantity, p.product_id, p.name, p.price, p.image 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.product_id 
            WHERE ci.member_id = :member_id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['member_id' => $member_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // 发生错误时返回空数组，防止页面报错
        return [];
    }
}

/**
 * 计算购物车商品总金额
 *
 * @param array $cart_items getCartItems 返回的数组
 * @return float 总金额
 */
function getCartTotal($cart_items) {
    $total = 0;
    foreach ($cart_items as $item) {
        // 强制类型转换，确保计算准确
        $price = (float)($item['price'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        $total += $price * $quantity;
    }
    return $total;
}

/**
 * 将商品添加到购物车（如果已存在则增加数量）
 *
 * @param PDO $pdo 数据库连接对象
 * @param int $member_id 用户ID
 * @param int $product_id 商品ID
 * @return string 操作结果状态 ('quantity increased' | 'added')
 */
function addToCart($pdo, $member_id, $product_id) {
    // 1. 检查该用户购物车中是否已存在该商品
    $sql = "SELECT quantity FROM cart_items WHERE member_id = :member_id AND product_id = :product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'member_id' => $member_id,
        'product_id' => $product_id
    ]);

    if ($stmt->rowCount() > 0) {
        // 2. 如果已存在，则更新数量 (+1)
        $sql = "UPDATE cart_items SET quantity = quantity + 1 
                WHERE member_id = :member_id AND product_id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'member_id' => $member_id,
            'product_id' => $product_id
        ]);

        return "quantity increased";
    }

    // 3. 如果不存在，则插入新记录
    $sql = "INSERT INTO cart_items (member_id, product_id, quantity)
            VALUES (:member_id, :product_id, 1)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'member_id' => $member_id,
        'product_id' => $product_id
    ]);

    return "added";
}
?>