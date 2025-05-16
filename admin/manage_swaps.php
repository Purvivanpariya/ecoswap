<?php
require_once '../includes/db.php';
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Handle swap request deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $swap_id = $_GET['delete'];
        
        // Delete the swap request
        $stmt = $conn->prepare("DELETE FROM swap_requests WHERE id = :swap_id");
        $stmt->bindParam(":swap_id", $swap_id, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            $_SESSION['success'] = "Swap request deleted successfully";
        } else {
            $error = "Error deleting swap request";
        }
        
        header("Location: manage_swaps.php");
        exit;
        
    } catch(PDOException $e) {
        $error = "Error deleting swap request: " . $e->getMessage();
    }
}

// Get all swap requests with related information
$stmt = $conn->prepare("
    SELECT sr.*,
           p1.name as requested_product_name,
           p2.name as offered_product_name,
           u1.username as owner_username,
           u2.username as requester_username,
           p1.category as requested_category,
           p2.category as offered_category
    FROM swap_requests sr
    LEFT JOIN products p1 ON sr.requested_product_id = p1.id
    LEFT JOIN products p2 ON sr.offered_product_id = p2.id
    LEFT JOIN users u1 ON p1.user_id = u1.id
    LEFT JOIN users u2 ON p2.user_id = u2.id
    ORDER BY sr.created_at DESC
");
$stmt->execute();
$swaps = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin_header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Manage Swaps</h1>
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
                                <th>ID</th>
                                <th>Requested Product</th>
                                <th>Owner</th>
                                <th>Offered Product</th>
                                <th>Requester</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($swaps as $swap): ?>
                                <tr>
                                    <td><?php echo $swap['id']; ?></td>
                                    <td>
                                        <a href="view_product.php?id=<?php echo $swap['requested_product_id']; ?>">
                                            <?php echo htmlspecialchars($swap['requested_product_name']); ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($swap['requested_category']); ?></small>
                                    </td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $swap['owner_id']; ?>">
                                            <?php echo htmlspecialchars($swap['owner_username']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="view_product.php?id=<?php echo $swap['offered_product_id']; ?>">
                                            <?php echo htmlspecialchars($swap['offered_product_name']); ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($swap['offered_category']); ?></small>
                                    </td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $swap['requester_id']; ?>">
                                            <?php echo htmlspecialchars($swap['requester_username']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($swap['status']) {
                                                'pending' => 'bg-warning',
                                                'accepted' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'completed' => 'bg-info',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst(htmlspecialchars($swap['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($swap['created_at'])); ?></td>
                                    <td>
                                        <a href="view_swap.php?id=<?php echo $swap['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                        <a href="?delete=<?php echo $swap['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this swap request?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if(empty($swaps)): ?>
                    <p class="text-center text-muted my-4">No swap requests found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?> 