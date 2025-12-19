<?php
session_start();
include '../include/db.php';

// Set Timezone to Malaysia/Singapore (Adjust if needed)
date_default_timezone_set('Asia/Kuala_Lumpur');

// Ensure user is logged in
if (!isset($_SESSION['member_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$member_id = $_SESSION['member_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (isset($pdo)) {
    
    // --- FETCH MESSAGES ---
    if ($action === 'fetch') {
        try {
            $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE member_id = ? ORDER BY created_at ASC");
            $stmt->execute([$member_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($messages as &$msg) {
                $msg['time'] = date('h:i A', strtotime($msg['created_at']));
                // Add date if it's not today
                if (date('Y-m-d', strtotime($msg['created_at'])) !== date('Y-m-d')) {
                    $msg['time'] = date('d M h:i A', strtotime($msg['created_at']));
                }
            }
            
            echo json_encode($messages);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // --- SEND MESSAGE ---
    elseif ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = trim($_POST['message'] ?? '');
        
        if (!empty($message)) {
            try {
                // 1. Save User's Message
                $stmt = $pdo->prepare("INSERT INTO chat_messages (member_id, sender, message) VALUES (?, 'member', ?)");
                $stmt->execute([$member_id, $message]);
                
                // 2. CHECK OFFICE HOURS & AUTO-REPLY
                // If it is NOT office hours, send an automated "Away" message
                if (!isOfficeHours()) {
                    $autoReply = "Thank you for your message. We are currently closed. Our office hours are Mon-Fri, 9:00 AM to 6:00 PM. We will get back to you as soon as we open!";
                    
                    // Check if we already sent an auto-reply recently (last 5 mins) to avoid spamming
                    // This is optional, but good practice. For now, we just send it.
                    $stmtReply = $pdo->prepare("INSERT INTO chat_messages (member_id, sender, message, is_read) VALUES (?, 'admin', ?, 1)");
                    $stmtReply->execute([$member_id, $autoReply]);
                }
                
                echo json_encode(['status' => 'success']);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    }
}

// --- HELPER: CHECK OFFICE HOURS ---
function isOfficeHours() {
    // Current Day (1 = Mon, 7 = Sun)
    $currentDay = date('N');
    // Current Hour (0 - 23)
    $currentHour = date('G');

    // Logic: Monday(1) to Friday(5) AND 9am to 18pm (6pm)
    if ($currentDay >= 1 && $currentDay <= 5) {
        if ($currentHour >= 9 && $currentHour < 18) {
            return true; // It is office hours
        }
    }
    return false; // It is closed
}
?>