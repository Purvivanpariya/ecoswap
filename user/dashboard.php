<?php
require_once '../includes/db.php';
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get user's products
$stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products WHERE user_id = :user_id");
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$products_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

// Get pending swap requests
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_swaps 
    FROM swap_requests 
    WHERE (requester_id = :user_id OR 
           requested_product_id IN (SELECT id FROM products WHERE user_id = :user_id))
    AND status = 'Pending'
");
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$pending_swaps = $stmt->fetch(PDO::FETCH_ASSOC)['pending_swaps'];

// Get unread messages
$stmt = $conn->prepare("SELECT COUNT(*) as unread_messages FROM messages WHERE receiver_id = :user_id AND is_read = 0");
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$unread_messages = $stmt->fetch(PDO::FETCH_ASSOC)['unread_messages'];

// Get recent products
$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5");
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="dashboard-container">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    
    <div class="dashboard-grid">
        <div class="dashboard-widget">
            <h3>Your Products</h3>
            <p class="stat"><?php echo $products_count; ?></p>
            <a href="products.php" class="btn btn-primary">View All</a>
        </div>
        
        <div class="dashboard-widget">
            <h3>Pending Swaps</h3>
            <p class="stat"><?php echo $pending_swaps; ?></p>
            <a href="swaps.php" class="btn btn-primary">View Requests</a>
        </div>
        
        <div class="dashboard-widget">
            <h3>Unread Messages</h3>
            <p class="stat"><?php echo $unread_messages; ?></p>
            <a href="messages.php" class="btn btn-primary">View Messages</a>
        </div>
    </div>
    
    <section class="recent-activity">
        <h2>Recent Products</h2>
        <div class="product-grid">
            <?php foreach($recent_products as $product): ?>
                <div class="product-card">
                    <?php if(!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars('../' . $product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                    <?php else: ?>
                        <div class="no-image">No Image Available</div>
                    <?php endif; ?>
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="category">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                        <p class="status">Status: <?php echo htmlspecialchars($product['status']); ?></p>
                        <div class="product-actions">
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-secondary">Edit</a>
                            <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if(empty($recent_products)): ?>
            <p class="no-items">You haven't listed any products yet. <a href="add_product.php">Add your first product</a></p>
        <?php endif; ?>
    </section>
    
    <div class="dashboard-actions">
        <a href="add_product.php" class="btn btn-primary">Add New Product</a>
        <a href="products.php" class="btn btn-secondary">Browse All Products</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 