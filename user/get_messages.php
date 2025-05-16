<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if user_id is provided
if(!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

$other_user_id = $_GET['user_id'];

try {
    // Get messages between the two users
    $stmt = $conn->prepare("
        SELECT m.*, 
               s.username as sender_username,
               r.username as receiver_username
        FROM messages m
        JOIN users s ON m.sender_id = s.id
        JOIN users r ON m.receiver_id = r.id
        WHERE (m.sender_id = :user_id1 AND m.receiver_id = :user_id2)
           OR (m.sender_id = :user_id2 AND m.receiver_id = :user_id1)
        ORDER BY m.created_at ASC
    ");
    
    $stmt->bindParam(":user_id1", $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(":user_id2", $other_user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read
    $update = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = :other_user_id AND receiver_id = :current_user_id AND is_read = 0");
    $update->bindParam(":other_user_id", $other_user_id, PDO::PARAM_INT);
    $update->bindParam(":current_user_id", $_SESSION['user_id'], PDO::PARAM_INT);
    $update->execute();

    echo json_encode(['messages' => $messages]);
    
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 