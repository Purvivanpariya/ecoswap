<?php
require_once '../includes/db.php';
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Handle product deletion
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Start transaction
        $conn->beginTransaction();
        
        $product_id = $_GET['delete'];
        
        // 1. Delete swap requests related to this product
        $stmt = $conn->prepare("
            DELETE FROM swap_requests 
            WHERE requested_product_id = :product_id 
            OR offered_product_id = :product_id
        ");
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // 2. Delete the product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = :product_id");
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Product deleted successfully";
        header("Location: manage_products.php");
        exit;
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Get all products with user information
$stmt = $conn->prepare("
    SELECT p.*, 
           u.username,
           COUNT(DISTINCT sr.id) as swap_request_count
    FROM products p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN swap_requests sr ON p.id = sr.requested_product_id OR p.id = sr.offered_product_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin_header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Manage Products</h1>
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
                                <th>Name</th>
                                <th>Category</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Swap Requests</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td>
                                        <a href="view_user.php?id=<?php echo $product['user_id']; ?>">
                                            <?php echo htmlspecialchars($product['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['status']); ?></td>
                                    <td><?php echo $product['swap_request_count']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                    <td>
                                        <a href="view_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                        <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this product? This will also delete all related swap requests.');">
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