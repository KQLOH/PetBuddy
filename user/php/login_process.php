<?php
session_start();
require "../include/db.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $_POST['email'];
    $password = $_POST['password'];

    
    $sql = "SELECT * FROM members WHERE email = :email";
    
    try {
        
        $stmt = $pdo->prepare($sql);
        
        
        $stmt->execute(['email' => $email]);
        
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

       
        if ($user) {
            
            if (password_verify($password, $user['password_hash'])) {
                
                
                $_SESSION['member_id'] = $user['member_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                echo "<p class='success'>Login Successful! Redirecting...</p>";
                header("refresh:1; url=../php/home.php");
                exit;

            } else {
                echo "<p class='error'>Wrong password!</p>";
            }
        } else {
            echo "<p class='error'>Email not found!</p>";
        }

    } catch (PDOException $e) {
        
        echo "Error: " . $e->getMessage();
    }
}
?>