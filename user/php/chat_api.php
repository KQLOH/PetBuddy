<?php
session_start();
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
            $ts = strtotime($msg['created_at']);
            if (date('Y-m-d', $ts) === date('Y-m-d')) {
                $msg['time'] = date('h:i A', $ts);
            } else {
                $msg['time'] = date('d/m h:i A', $ts);
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
            $stmt = $pdo->prepare("INSERT INTO chat_messages (member_id, sender, message, is_read) VALUES (?, 'member', ?, 0)");
            $stmt->execute([$member_id, $message]);
            
            $d = date('N'); 
            $h = date('G');
            $isOfficeHours = ($d >= 1 && $d <= 5 && $h >= 9 && $h < 18);

            if (!$isOfficeHours) {
                $stmtCheck = $pdo->prepare("SELECT created_at FROM chat_messages WHERE member_id = ? AND sender = 'admin' ORDER BY created_at DESC LIMIT 1");
                $stmtCheck->execute([$member_id]);
                $lastReply = $stmtCheck->fetchColumn();

                $shouldReply = true;
                if ($lastReply && (time() - strtotime($lastReply)) < 300) {
                    $shouldReply = false;
                }

                if ($shouldReply) {
                    $autoMsg = "Thanks for your message. We are currently closed (Mon-Fri 9am-6pm). We will get back to you soon!";
                    $stmtReply = $pdo->prepare("INSERT INTO chat_messages (member_id, sender, message, is_read) VALUES (?, 'admin', ?, 0)");
                    $stmtReply->execute([$member_id, $autoMsg]);
                }
            }

            echo json_encode(['status' => 'success']);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

elseif ($action === 'get_unread') {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE member_id = ? AND sender = 'admin' AND is_read = 0");
        $stmt->execute([$member_id]);
        $count = $stmt->fetchColumn();
        echo json_encode(['unread' => $count]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

elseif ($action === 'mark_read') {
    try {
        $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE member_id = ? AND sender = 'admin'");
        $stmt->execute([$member_id]);
        echo json_encode(['status' => 'success']);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>