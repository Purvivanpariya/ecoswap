<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if message and receiver_id are provided
if (!isset($_POST['message']) || !isset($_POST['receiver_id'])) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$message = trim($_POST['message']);
$receiver_id = $_POST['receiver_id'];
$sender_id = $_SESSION['user_id'];

// Validate message
if (empty($message)) {
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

// Validate receiver exists and is not the sender
try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND id != ? AND is_admin = 0");
    $stmt->execute([$receiver_id, $sender_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => 'Invalid receiver']);
        exit;
    }

    // Insert the message
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$sender_id, $receiver_id, $message]);

    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully'
    ]);

} catch(Exception $e) {
    echo json_encode([
        'error' => 'Error sending message',
        'message' => $e->getMessage()
    ]);
}
?> 