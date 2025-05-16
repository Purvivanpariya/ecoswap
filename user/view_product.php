<?php
session_start();
require_once '../includes/db.php';

// Check if product ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit;
}

// Get product details
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.email 
    FROM products p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = :id
");
$stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// If product doesn't exist, redirect to products page
if(!$product) {
    header("Location: products.php");
    exit;
}

include '../includes/header.php';
?>

<div class="view-product-page">
    <div class="view-product-container">
        <div class="product-header">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="product-meta">
                <span class="status <?php echo strtolower($product['status']); ?>">
                    <?php echo htmlspecialchars($product['status']); ?>
                </span>
                <span class="date">Listed on: <?php echo date('M d, Y', strtotime($product['created_at'])); ?></span>
            </div>
        </div>

        <div class="product-content">
            <div class="product-image-container">
                <?php if(!empty($product['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars('../' . $product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image">
                <?php else: ?>
                    <div class="no-image">No Image Available</div>
                <?php endif; ?>
            </div>

            <div class="product-details">
                <div class="detail-group">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <div class="detail-group">
                    <h3>Details</h3>
                    <ul class="details-list">
                        <li>
                            <span class="label">Category:</span>
                            <span class="value"><?php echo htmlspecialchars($product['category']); ?></span>
                        </li>
                        <li>
                            <span class="label">Condition:</span>
                            <span class="value"><?php echo htmlspecialchars($product['condition_status']); ?></span>
                        </li>
                        <li>
                            <span class="label">Listed by:</span>
                            <span class="value"><?php echo htmlspecialchars($product['username']); ?></span>
                        </li>
                    </ul>
                </div>

                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="product-actions">
                        <?php if($product['user_id'] != $_SESSION['user_id']): ?>
                            <?php if($product['status'] == 'Available'): ?>
                                <a href="request_swap.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary">Request Swap</a>
                                <a href="messages.php?to=<?php echo $product['user_id']; ?>" 
                                   class="btn btn-secondary">Contact Owner</a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Not Available</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-secondary">Edit Product</a>
                            <button class="btn btn-danger" 
                                    onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete Product</button>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="login-prompt">Please <a href="../login.php">login</a> to request a swap or contact the owner.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function deleteProduct(productId) {
    if(confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        window.location.href = 'delete_product.php?id=' + productId;
    }
}
</script>

<?php include '../includes/footer.php'; ?> 