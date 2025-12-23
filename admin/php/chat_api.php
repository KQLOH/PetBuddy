<?php
session_start();
include '../user/include/db.php';

// Verify Admin Login
// if (!isset($_SESSION['admin_id'])) { die(json_encode(['error' => 'Unauthorized'])); }

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (isset($pdo)) {

    // --- 1. GET LIST OF MEMBERS WITH UNREAD COUNT ---
    if ($action === 'get_contacts') {
        try {
            // Modified query to count unread messages (is_read = 0) from 'member'
            $sql = "SELECT 
                        m.member_id, 
                        m.full_name, 
                        m.image, 
                        MAX(c.created_at) as last_msg_time,
                        SUM(CASE WHEN c.sender = 'member' AND c.is_read = 0 THEN 1 ELSE 0 END) as unread_count
                    FROM members m
                    JOIN chat_messages c ON m.member_id = c.member_id
                    GROUP BY m.member_id
                    ORDER BY last_msg_time DESC";
            
            $stmt = $pdo->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // --- 2. FETCH MESSAGES & MARK AS READ ---
    elseif ($action === 'fetch_messages') {
        $member_id = $_GET['member_id'] ?? 0;
        
        try {
            // A. Mark messages from this user as READ
            $update = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE member_id = ? AND sender = 'member'");
            $update->execute([$member_id]);

            // B. Fetch the conversation
            $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE member_id = ? ORDER BY created_at ASC");
            $stmt->execute([$member_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($messages as &$msg) {
                $msg['time'] = date('d M h:i A', strtotime($msg['created_at']));
            }
            echo json_encode($messages);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // --- 3. SEND REPLY AS ADMIN ---
    elseif ($action === 'send_reply') {
        $member_id = $_POST['member_id'] ?? 0;
        $message = trim($_POST['message'] ?? '');

        if (!empty($message) && $member_id > 0) {
            try {
                // Admin messages are read by default (or unread for user side if you implement that later)
                $stmt = $pdo->prepare("INSERT INTO chat_messages (member_id, sender, message, is_read) VALUES (?, 'admin', ?, 1)");
                $stmt->execute([$member_id, $message]);
                echo json_encode(['status' => 'success']);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    }
}
?>