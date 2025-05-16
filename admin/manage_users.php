<?php
require_once '../includes/db.php';
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Handle user deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        $user_id = $_GET['delete'];
        
        // 1. Delete swap requests related to user's products
        $stmt = $conn->prepare("
            DELETE FROM swap_requests 
            WHERE requested_product_id IN (SELECT id FROM products WHERE user_id = :user_id)
            OR offered_product_id IN (SELECT id FROM products WHERE user_id = :user_id)
        ");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // 2. Delete messages
        $stmt = $conn->prepare("DELETE FROM messages WHERE sender_id = :user_id OR receiver_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // 3. Delete products
        $stmt = $conn->prepare("DELETE FROM products WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // 4. Finally delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :user_id AND is_admin = 0");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "User and all related data deleted successfully";
        header("Location: manage_users.php");
        exit;
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Get all non-admin users
$stmt = $conn->prepare("
    SELECT u.*, 
           COUNT(DISTINCT p.id) as product_count,
           COUNT(DISTINCT m.id) as message_count
    FROM users u
    LEFT JOIN products p ON u.id = p.user_id
    LEFT JOIN messages m ON u.id = m.sender_id OR u.id = m.receiver_id
    WHERE u.is_admin = 0
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin_header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Manage Users</h1>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Products</th>
                                <th>Messages</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['product_count']; ?></td>
                                    <td><?php echo $user['message_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                        <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this user? This will also delete all their products and messages.');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?> 