<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check if product ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../products.php");
    exit;
}

$product_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$error = $success = '';

// Get the product details
$stmt = $conn->prepare("SELECT p.*, u.username FROM products p 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.id = ? AND p.status = 'Available'");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if product exists and user is not requesting their own product
if(!$product || $product['user_id'] == $user_id) {
    header("Location: ../products.php");
    exit;
}

// Check if there's already a pending request for this product
$stmt = $conn->prepare("SELECT id FROM swap_requests 
                       WHERE requested_product_id = ? 
                       AND status = 'Pending'");
$stmt->execute([$product_id]);
if($stmt->fetch()) {
    $error = "This product already has a pending swap request.";
} else {
    // Get user's products for offering in swap
    $stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ? AND status = 'Available'");
    $stmt->execute([$user_id]);
    $my_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {
    if(!isset($_POST['offered_product']) || !is_numeric($_POST['offered_product'])) {
        $error = "Please select a product to offer for swap.";
    } else {
        $offered_product_id = $_POST['offered_product'];
        
        // Verify the offered product belongs to the user and is available
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND user_id = ? AND status = 'Available'");
        $stmt->execute([$offered_product_id, $user_id]);
        if(!$stmt->fetch()) {
            $error = "Invalid product selection or product no longer available.";
        } else {
            // Create swap request
            try {
                $conn->beginTransaction();
                
                // Double check if product is still available
                $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND status = 'Available'");
                $stmt->execute([$product_id]);
                if(!$stmt->fetch()) {
                    throw new Exception("The requested product is no longer available.");
                }
                
                // Insert swap request
                $stmt = $conn->prepare("INSERT INTO swap_requests (requested_product_id, offered_product_id, requester_id, owner_id, status, created_at) 
                                      VALUES (?, ?, ?, ?, 'Pending', NOW())");
                if(!$stmt->execute([$product_id, $offered_product_id, $user_id, $product['user_id']])) {
                    throw new Exception("Failed to create swap request.");
                }
                
                $conn->commit();
                $success = "Swap request sent successfully! Waiting for owner's approval.";
                
                // Redirect to swap requests page after short delay
                header("refresh:2;url=swaps.php");
            } catch(Exception $e) {
                $conn->rollBack();
                $error = $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2>Request Swap</h2>
    
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if(!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if(empty($error) || !empty($my_products)): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Product You Want</h3>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text">
                        <strong>Owner:</strong> <?php echo htmlspecialchars($product['username']); ?><br>
                        <strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?><br>
                        <strong>Condition:</strong> <?php echo htmlspecialchars($product['condition_status']); ?>
                    </p>
                    <?php if(!empty($product['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars('../' . $product['image_url']); ?>" class="img-fluid mb-3" alt="Product Image">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Choose Your Product to Offer</h3>
                </div>
                <div class="card-body">
                    <?php if(empty($my_products)): ?>
                        <p class="text-muted">You don't have any available products to offer.</p>
                        <a href="add_product.php" class="btn btn-primary">Add a Product</a>
                    <?php else: ?>
                        <form method="POST" action="">
                            <div class="form-group">
                                <select name="offered_product" class="form-control" required>
                                    <option value="">Select a product to offer</option>
                                    <?php foreach($my_products as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>">
                                            <?php echo htmlspecialchars($prod['name']); ?> 
                                            (<?php echo htmlspecialchars($prod['condition_status']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Swap Request</button>
                            <a href="../products.php" class="btn btn-secondary">Cancel</a>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?> 