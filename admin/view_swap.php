<?php
require_once '../includes/db.php';
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Check if swap ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_swaps.php");
    exit;
}

$swap_id = $_GET['id'];

// Get swap details with related information
$stmt = $conn->prepare("
    SELECT sr.*,
           p1.name as requested_product_name,
           p1.description as requested_product_description,
           p1.category as requested_category,
           p1.condition_status as requested_condition,
           p1.image_url as requested_product_image,
           p2.name as offered_product_name,
           p2.description as offered_product_description,
           p2.category as offered_category,
           p2.condition_status as offered_condition,
           p2.image_url as offered_product_image,
           u1.username as owner_username,
           u1.email as owner_email,
           u2.username as requester_username,
           u2.email as requester_email
    FROM swap_requests sr
    LEFT JOIN products p1 ON sr.requested_product_id = p1.id
    LEFT JOIN products p2 ON sr.offered_product_id = p2.id
    LEFT JOIN users u1 ON p1.user_id = u1.id
    LEFT JOIN users u2 ON p2.user_id = u2.id
    WHERE sr.id = :swap_id
");

$stmt->bindParam(":swap_id", $swap_id, PDO::PARAM_INT);
$stmt->execute();
$swap = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$swap) {
    header("Location: manage_swaps.php");
    exit;
}

include '../includes/admin_header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Swap Details</h1>
            <a href="manage_swaps.php" class="btn btn-secondary">Back to Swaps</a>
        </div>

        <div class="row">
            <!-- Requested Product -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Requested Product</h5>
                    </div>
                    <div class="card-body">
                        <?php if($swap['requested_product_image']): ?>
                            <img src="../<?php echo htmlspecialchars($swap['requested_product_image']); ?>" 
                                 class="img-fluid mb-3" alt="Requested Product">
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($swap['requested_product_name']); ?></h4>
                        <p class="text-muted">Category: <?php echo htmlspecialchars($swap['requested_category']); ?></p>
                        <p class="text-muted">Condition: <?php echo htmlspecialchars($swap['requested_condition']); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($swap['requested_product_description'])); ?></p>
                        <hr>
                        <h5>Owner Details</h5>
                        <p>Username: <a href="view_user.php?id=<?php echo $swap['owner_id']; ?>">
                            <?php echo htmlspecialchars($swap['owner_username']); ?>
                        </a></p>
                        <p>Email: <?php echo htmlspecialchars($swap['owner_email']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Offered Product -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Offered Product</h5>
                    </div>
                    <div class="card-body">
                        <?php if($swap['offered_product_image']): ?>
                            <img src="../<?php echo htmlspecialchars($swap['offered_product_image']); ?>" 
                                 class="img-fluid mb-3" alt="Offered Product">
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($swap['offered_product_name']); ?></h4>
                        <p class="text-muted">Category: <?php echo htmlspecialchars($swap['offered_category']); ?></p>
                        <p class="text-muted">Condition: <?php echo htmlspecialchars($swap['offered_condition']); ?></p>
                        <p><?php echo nl2br(htmlspecialchars($swap['offered_product_description'])); ?></p>
                        <hr>
                        <h5>Requester Details</h5>
                        <p>Username: <a href="view_user.php?id=<?php echo $swap['requester_id']; ?>">
                            <?php echo htmlspecialchars($swap['requester_username']); ?>
                        </a></p>
                        <p>Email: <?php echo htmlspecialchars($swap['requester_email']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Swap Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Swap Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
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
                        </p>
                        <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($swap['created_at'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($swap['updated_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="manage_swaps.php" class="btn btn-secondary">Back to Swaps</a>
                    <a href="?delete=<?php echo $swap['id']; ?>" class="btn btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this swap request?');">
                        Delete Swap Request
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?> 