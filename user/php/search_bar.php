<?php

require_once '../include/db.php';

header('Content-Type: application/json; charset=utf-8');


function searchCatalog(PDO $pdo, string $term, int $limit = 10): array {
    $likeTerm = '%' . $term . '%';
    $results = [];

   
    $productSql = "SELECT p.product_id, p.name, p.description, p.price, p.image AS photo, c.name AS category_name
                   FROM products p
                   LEFT JOIN product_categories c ON p.category_id = c.category_id
                   WHERE p.name LIKE :term OR p.description LIKE :term2
                   ORDER BY p.product_id DESC
                   LIMIT :limit";

    try {
        $stmt = $pdo->prepare($productSql);
        
        $stmt->bindValue(':term', $likeTerm, PDO::PARAM_STR);
        $stmt->bindValue(':term2', $likeTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $row) {
            $results[] = [
                'type' => 'product',
                'id' => (int)$row['product_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'category' => $row['category_name'],
                'price' => $row['price'],
                'photo' => $row['photo'], 
                'url' => 'product_detail.php?id=' . (int)$row['product_id'],
            ];
        }
    } catch (PDOException $e) {
        
    }

    
    $categorySql = "SELECT category_id, name, description
                    FROM product_categories
                    WHERE name LIKE :term OR description LIKE :term2
                    ORDER BY name
                    LIMIT :limit";

    try {
        $stmt = $pdo->prepare($categorySql);
        $stmt->bindValue(':term', $likeTerm, PDO::PARAM_STR);
        $stmt->bindValue(':term2', $likeTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categories as $row) {
            $results[] = [
                'type' => 'category',
                'id' => (int)$row['category_id'],
                'name' => $row['name'],
                'description' => $row['description'],
                'url' => 'product_listing.php?category=' . (int)$row['category_id'],
            ];
        }
    } catch (PDOException $e) {
        
    }

    return $results;
}


$term = trim($_GET['term'] ?? '');

if ($term === '') {
    echo json_encode([
        'success' => true,
        'results' => [],
        'message' => 'Please provide a search term.',
    ]);
    exit;
}

try {
    
    $data = searchCatalog($pdo, $term);
    echo json_encode([
        'success' => true,
        'results' => $data,
        'count' => count($data),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Search failed.',
        'error' => $e->getMessage(),
    ]);
}
?>