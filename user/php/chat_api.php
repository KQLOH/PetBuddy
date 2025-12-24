<?php
session_start();
// Adjust this path to match your folder structure exactly
// Assuming file is in: user/php/chat_api.php
// And db is in:       user/include/db.php
require_once '../include/db.php'; 

date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json');

if (!isset($_SESSION['member_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$member_id = $_SESSION['member_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'fetch') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE member_id = ? ORDER BY created_at ASC");
        $stmt->execute([$member_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($messages as &$msg) {
            // Format time nicely
            $msg['time'] = date('h:i A', strtotime($msg['created_at']));
            if (date('Y-m-d', strtotime($msg['created_at'])) !== date('Y-m-d')) {
                $msg['time'] = date('d M h:i A', strtotime($msg['created_at']));
            }
        }
        echo json_encode($messages);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

elseif ($action === 'send') {
    $message = trim($_POST['message'] ?? '');
    if (!empty($message)) {
        try {
            // 1. Insert Member Message
            $stmt = $pdo->prepare("INSERT INTO chat_messages (member_id, sender, message) VALUES (?, 'member', ?)");
            $stmt->execute([$member_id, $message]);
            
            // 2. Auto-Reply Logic (Office Hours)
            $currentDay = date('N'); // 1=Mon, 7=Sun
            $currentHour = date('G'); // 0-23
            $isOfficeHours = ($currentDay >= 1 && $currentDay <= 5 && $currentHour >= 9 && $currentHour < 18);

            if (!$isOfficeHours) {
                // Check if we sent an auto-reply in the last 5 minutes to prevent spam
                $stmtCheck = $pdo->prepare("SELECT created_at FROM chat_messages WHERE member_id = ? AND sender = 'admin' ORDER BY created_at DESC LIMIT 1");
                $stmtCheck->execute([$member_id]);
                $lastReply = $stmtCheck->fetchColumn();

                $shouldReply = true;
                if ($lastReply) {
                    $minutes = (time() - strtotime($lastReply)) / 60;
                    if ($minutes < 5) $shouldReply = false;
                }

                if ($shouldReply) {
                    $autoMsg = "Thanks for contacting us. We are currently closed (Mon-Fri 9am-6pm). We will reply when we are back!";
                    $stmtReply = $pdo->prepare("INSERT INTO chat_messages (member_id, sender, message, is_read) VALUES (?, 'admin', ?, 1)");
                    $stmtReply->execute([$member_id, $autoMsg]);
                }
            }

            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Empty message']);
    }
}
?>