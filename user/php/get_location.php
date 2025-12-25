<?php
require_once '../include/db.php'; 

header('Content-Type: application/json');

if (isset($_GET['postcode'])) {
    $postcode = trim($_GET['postcode']);

    
    $stmt = $pdo->prepare("SELECT city, state FROM malaysia_locations WHERE postcode = ?");
    $stmt->execute([$postcode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['success' => true, 'city' => $result['city'], 'state' => $result['state']]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>