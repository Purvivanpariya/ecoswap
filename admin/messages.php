<?php
require_once '../includes/db.php';
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Handle message deletion
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $message_id = $_GET['delete'];
    
    // Delete message
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = :id");
    $stmt->bindParam(":id", $message_id, PDO::PARAM_INT);
    
    if($stmt->execute()) {
        $success = "Message has been deleted successfully.";
    } else {
        $error = "Something went wrong. Please try again later.";
    }
}

// Get all messages with sender and receiver information
$stmt = $conn->prepare("
    SELECT m.*, 
           s.username as sender_username, s.email as sender_email,
           r.username as receiver_username, r.email as receiver_email
    FROM messages m 
    JOIN users s ON m.sender_id = s.id
    JOIN users r ON m.receiver_id = r.id
    ORDER BY m.created_at DESC
");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin_header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <h1>Monitor Messages</h1>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="messages-container">
            <?php if(count($messages) > 0): ?>
                <?php foreach($messages as $message): ?>
                    <div class="message-card">
                        <div class="message-header">
                            <div class="message-users">
                                <span class="sender">
                                    From: <?php echo htmlspecialchars($message['sender_username']); ?> 
                                    (<?php echo htmlspecialchars($message['sender_email']); ?>)
                                </span>
                                <span class="receiver">
                                    To: <?php echo htmlspecialchars($message['receiver_username']); ?> 
                                    (<?php echo htmlspecialchars($message['receiver_email']); ?>)
                                </span>
                            </div>
                            <div class="message-time">
                                <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?>
                            </div>
                        </div>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                        <div class="message-actions">
                            <a href="?delete=<?php echo $message['id']; ?>" class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this message?');">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">No messages found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 