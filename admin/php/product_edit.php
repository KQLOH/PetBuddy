<?php
session_start();
require_once '../../user/include/db.php';
header('Content-Type: application/json');

if (!in_array($_SESSION['role'] ?? '', ['admin','super_admin'])) {
    http_response_code(401);
    exit(json_encode(['error'=>'Unauthorized']));
}

$id=(int)$_POST['id'];
$name=trim($_POST['name']);
$price=(float)$_POST['price'];
$stock=(int)$_POST['stock_qty'];
$desc=trim($_POST['description']??'');

if($name==='') exit(json_encode(['error'=>'Name required']));

$pdo->prepare("
    UPDATE products SET
    name=?, price=?, stock_qty=?, description=?
    WHERE product_id=?
")->execute([$name,$price,$stock,$desc,$id]);

echo json_encode(['success'=>true]);
