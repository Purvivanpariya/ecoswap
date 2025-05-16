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
        
        // Redirect back to manage products with success message
        header("Location: manage_products.php?success=Product deleted successfully");
        exit;
        
    } catch(PDOException $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Check if product ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_products.php");
    exit;
}

$product_id = $_GET['id'];

// Get product information with user details
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.email 
    FROM products p 
    LEFT JOIN users u ON p.user_id = u.id 
    WHERE p.id = :id
");
$stmt->bindParam(":id", $product_id, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// If product doesn't exist, redirect back
if(!$product) {
    header("Location: manage_products.php");
    exit;
}

// Get swap requests for this product
$stmt = $conn->prepare("
    SELECT sr.*, 
           p2.name as offered_product_name,
           u.username as requester_username,
           u.id as requester_id
    FROM swap_requests sr
    LEFT JOIN products p2 ON sr.offered_product_id = p2.id
    LEFT JOIN users u ON sr.requester_id = u.id
    WHERE sr.requested_product_id = :product_id
    ORDER BY sr.created_at DESC
");
$stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
$stmt->execute();
$swap_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin_header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Product Details</h1>
            <div>
                <a href="manage_products.php" class="btn btn-secondary">Back to Products</a>
                <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this product? This will also delete all related swap requests.');">
                    Delete Product
                </a>
            </div>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Product Information</h2>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th width="150">Name:</th>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td><?php echo nl2br(htmlspecialchars($product['description'])); ?></td>
                            </tr>
                            <tr>
                                <th>Category:</th>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td><?php echo htmlspecialchars($product['status']); ?></td>
                            </tr>
                            <tr>
                                <th>Owner:</th>
                                <td>
                                    <a href="view_user.php?id=<?php echo $product['user_id']; ?>">
                                        <?php echo htmlspecialchars($product['username']); ?>
                                    </a>
                                    (<?php echo htmlspecialchars($product['email']); ?>)
                                </td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td><?php echo date('M d, Y H:i', strtotime($product['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td><?php echo date('M d, Y H:i', strtotime($product['updated_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Swap Requests</h2>
                    </div>
                    <div class="card-body">
                        <?php if(count($swap_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Requested By</th>
                                            <th>Offering Product</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($swap_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <a href="view_user.php?id=<?php echo $request['requester_id']; ?>">
                                                        <?php echo htmlspecialchars($request['requester_username']); ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="view_product.php?id=<?php echo $request['offered_product_id']; ?>">
                                                        <?php echo htmlspecialchars($request['offered_product_name']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['status']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mb-0">No swap requests found for this product.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?> 