<?php
session_start();

// 路径可能需要根据你的实际文件夹结构微调
require_once '../../user/include/db.php'; 

date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json');

// 安全检查 (建议开启)
if (empty($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (isset($pdo)) {

    // 1. 获取联系人列表
    if ($action === 'get_contacts') {
        try {
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

    // 2. 获取消息 (并标记为已读)
    elseif ($action === 'fetch_messages') {
        $member_id = $_GET['member_id'] ?? 0;
        try {
            $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE member_id = ? AND sender = 'member'")->execute([$member_id]);
            
            $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE member_id = ? ORDER BY created_at ASC");
            $stmt->execute([$member_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($messages as &$msg) {
                $ts = strtotime($msg['created_at']);
                // 如果是今天，只显示时间，否则显示日期+时间
                if(date('Y-m-d') == date('Y-m-d', $ts)) {
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

    // 3. 发送回复
    elseif ($action === 'send_reply') {
        $member_id = $_POST['member_id'] ?? 0;
        $message = trim($_POST['message'] ?? '');

        if (!empty($message) && $member_id > 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO chat_messages (member_id, sender, message, is_read) VALUES (?, 'admin', ?, 1)");
                $stmt->execute([$member_id, $message]);
                echo json_encode(['status' => 'success']);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
        }
    }

    // --- 新增：删除对话 ---
    elseif ($action === 'delete_conversation') {
        $member_id = $_POST['member_id'] ?? 0;
        
        if ($member_id > 0) {
            try {
                // 删除该用户的所有聊天记录
                $stmt = $pdo->prepare("DELETE FROM chat_messages WHERE member_id = ?");
                $stmt->execute([$member_id]);
                echo json_encode(['status' => 'success']);
            } catch (PDOException $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['error' => 'Invalid Member ID']);
        }
    }
}
?>