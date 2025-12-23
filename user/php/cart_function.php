<?php



function getCartItems($pdo, $member_id) {
    
    $sql = "SELECT ci.quantity, p.product_id, p.name, p.price, p.image 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.product_id 
            WHERE ci.member_id = :member_id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['member_id' => $member_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        
        return [];
    }
}

function getCartTotal($cart_items) {
    $total = 0;
    foreach ($cart_items as $item) {
        
        $price = (float)($item['price'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        $total += $price * $quantity;
    }
    return $total;
}


function addToCart($pdo, $member_id, $product_id, $quantity = 1) { 
 
    $sql = "SELECT quantity FROM cart_items WHERE member_id = :member_id AND product_id = :product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'member_id' => $member_id,
        'product_id' => $product_id
    ]);

    if ($stmt->rowCount() > 0) {
       
        $sql = "UPDATE cart_items SET quantity = quantity + :quantity 
                WHERE member_id = :member_id AND product_id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'quantity' => $quantity, 
            'member_id' => $member_id,
            'product_id' => $product_id
        ]);

        return "quantity increased";
    }

   
    $sql = "INSERT INTO cart_items (member_id, product_id, quantity)
            VALUES (:member_id, :product_id, :quantity)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'member_id' => $member_id,
        'product_id' => $product_id,
        'quantity' => $quantity 
    ]);

    return "added";
}
?>