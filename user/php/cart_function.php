<?php
function getCartItems($pdo, $member_id)
{

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

function getCartTotal($cart_items)
{
    $total = 0;
    foreach ($cart_items as $item) {

        $price = (float)($item['price'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        $total += $price * $quantity;
    }
    return $total;
}

function addToCart($pdo, $member_id, $product_id, $quantity = 1)
{
    $stmt = $pdo->prepare("SELECT stock_qty, name FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        return ['status' => 'error', 'message' => 'Product not found.'];
    }

    $stock_qty = (int)$product['stock_qty'];
    $product_name = $product['name'];

    $stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE member_id = :member_id AND product_id = :product_id");
    $stmt->execute([
        'member_id' => $member_id,
        'product_id' => $product_id
    ]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_cart_qty = $existing ? (int)$existing['quantity'] : 0;

    $new_total_qty = $current_cart_qty + $quantity;

    if ($new_total_qty > $stock_qty) {
        $remaining = max(0, $stock_qty - $current_cart_qty);
        if ($remaining <= 0) {
            return ['status' => 'error', 'message' => "Sorry, '{$product_name}' is out of stock or you reached the limit."];
        } else {
            return ['status' => 'error', 'message' => "You already have {$current_cart_qty} in cart. Only {$remaining} more available."];
        }
    }

    if ($existing) {
        $sql = "UPDATE cart_items SET quantity = quantity + :quantity 
                WHERE member_id = :member_id AND product_id = :product_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'quantity' => $quantity,
            'member_id' => $member_id,
            'product_id' => $product_id
        ]);
        return ['status' => 'success', 'message' => 'Quantity increased!'];
    } else {
        $sql = "INSERT INTO cart_items (member_id, product_id, quantity)
                VALUES (:member_id, :product_id, :quantity)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'member_id' => $member_id,
            'product_id' => $product_id,
            'quantity' => $quantity
        ]);
        return ['status' => 'success', 'message' => 'Added to cart!'];
    }
}

function updateCartQuantity($pdo, $member_id, $product_id, $new_quantity)
{
    if ($new_quantity < 1) {
        return ['status' => 'error', 'message' => 'Quantity cannot be less than 1.'];
    }

    $stmt = $pdo->prepare("SELECT stock_qty, name FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        return ['status' => 'error', 'message' => 'Product not found.'];
    }

    $stock_qty = (int)$product['stock_qty'];

    if ($new_quantity > $stock_qty) {
        return ['status' => 'error', 'message' => "Sorry, only {$stock_qty} units available for '{$product['name']}'."];
    }

    $sql = "UPDATE cart_items SET quantity = :quantity 
            WHERE member_id = :member_id AND product_id = :product_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'quantity' => $new_quantity,
        'member_id' => $member_id,
        'product_id' => $product_id
    ]);

    return ['status' => 'success', 'message' => 'Cart updated.'];
}
