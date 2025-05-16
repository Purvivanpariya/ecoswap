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
            OR requesting_product_id IN (SELECT id FROM products WHERE user_id = :user_id)
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
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :user_id");
        $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect back to manage users with success message
        header("Location: manage_users.php?success=User and all related data deleted successfully");
        exit;
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Error deleting user: " . $e->getMessage();
    }
}

// Check if user ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$user_id = $_GET['id'];

// Get user information
$stmt = $conn->prepare("
    SELECT * FROM users 
    WHERE id = :id AND is_admin = 0
");
$stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user doesn't exist or is an admin, redirect back
if(!$user) {
    header("Location: manage_users.php");
    exit;
}

// Get user's products
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC
");
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's messages count
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_messages 
    FROM messages 
    WHERE sender_id = :user_id OR receiver_id = :user_id
");
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$messages_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_messages'];

// Get user's swap requests count
$stmt = $conn->prepare("
    SELECT COUNT(*) as total_swaps 
    FROM swap_requests 
    WHERE requester_id = :user_id OR owner_id = :user_id
");
$stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$stmt->execute();
$swaps_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_swaps'];

include '../includes/admin_header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>User Details</h1>
            <div>
                <a href="manage_users.php" class="btn btn-secondary">Back to Users</a>
                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this user? This will also delete all their products and messages.');">
                    Delete User
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">User Information</h2>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th width="150">Username:</th>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                            </tr>
                            <tr>
                                <th>Full Name:</th>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Joined:</th>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td><?php echo date('M d, Y', strtotime($user['updated_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Activity Summary</h2>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <h3 class="h2 mb-1"><?php echo count($products); ?></h3>
                                <p class="text-muted">Products</p>
                            </div>
                            <div class="col-md-4">
                                <h3 class="h2 mb-1"><?php echo $messages_count; ?></h3>
                                <p class="text-muted">Messages</p>
                            </div>
                            <div class="col-md-4">
                                <h3 class="h2 mb-1"><?php echo $swaps_count; ?></h3>
                                <p class="text-muted">Swaps</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">User's Products</h2>
                    </div>
                    <div class="card-body">
                        <?php if(count($products) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                                <td><?php echo htmlspecialchars($product['status']); ?></td>
                                                <td>
                                                    <a href="view_product.php?id=<?php echo $product['id']; ?>" 
                                                       class="btn btn-sm btn-secondary">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">No products found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?> 