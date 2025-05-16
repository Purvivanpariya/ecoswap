<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if search query is provided
if(!isset($_GET['query']) || empty(trim($_GET['query']))) {
    echo json_encode(['error' => 'No search query provided']);
    exit;
}

$search_query = '%' . trim($_GET['query']) . '%';
$current_user_id = $_SESSION['user_id'];

try {
    // Search for users by username, excluding the current user and admin users
    // Also get the last message between users if it exists
    $stmt = $conn->prepare("
        SELECT 
            u.id, 
            u.username,
            (SELECT COUNT(*) FROM products WHERE user_id = u.id AND status = 'Available') as product_count,
            (
                SELECT message 
                FROM messages 
                WHERE (sender_id = :current_user AND receiver_id = u.id) 
                   OR (sender_id = u.id AND receiver_id = :current_user)
                ORDER BY created_at DESC 
                LIMIT 1
            ) as last_message
        FROM users u
        WHERE u.username LIKE :query 
        AND u.id != :current_user 
        AND u.is_admin = 0 
        ORDER BY 
            CASE WHEN u.username LIKE :exact_query THEN 1 ELSE 2 END,
            username 
        LIMIT 10
    ");
    
    $exact_query = trim($_GET['query']) . '%';
    $stmt->bindParam(":query", $search_query, PDO::PARAM_STR);
    $stmt->bindParam(":exact_query", $exact_query, PDO::PARAM_STR);
    $stmt->bindParam(":current_user", $current_user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the results
    $formatted_users = array_map(function($user) {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'product_count' => $user['product_count'],
            'has_chat' => !empty($user['last_message'])
        ];
    }, $users);
    
    echo json_encode([
        'success' => true,
        'users' => $formatted_users
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'error' => 'Error searching users',
        'message' => $e->getMessage()
    ]);
}
?> 