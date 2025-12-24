<?php
session_start();
include '../include/db.php'; 

if (isset($_SESSION['member_id']) && isset($pdo)) {
    $stmt = $pdo->prepare("UPDATE members SET remember_token = NULL WHERE member_id = ?");
    $stmt->execute([$_SESSION['member_id']]);
}

session_unset();
session_destroy();

if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, "/");
}

header("Location: login.php");
exit;
?>