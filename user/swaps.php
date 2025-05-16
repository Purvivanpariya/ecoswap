<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Get active tab from URL parameter, default to 'incoming'
$active_tab = isset($_GET['tab']) && $_GET['tab'] === 'outgoing' ? 'outgoing' : 'incoming';

include '../includes/header.php';
?>

<div class="swap-requests-page">
    <div class="swap-requests-header">
        <h1>Swap Requests</h1>
    </div>

    <div class="container">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="swap-navigation">
            <a href="?tab=incoming" class="<?php echo $active_tab === 'incoming' ? 'active' : ''; ?>">
                Incoming Requests
            </a>
            <a href="?tab=outgoing" class="<?php echo $active_tab === 'outgoing' ? 'active' : ''; ?>">
                Outgoing Requests
            </a>
        </div>

        <?php if($active_tab === 'incoming'): ?>
            <?php
            // Get incoming swap requests
            $stmt = $conn->prepare("
                SELECT sr.*, 
                       rp.name as requested_product_name, rp.image_url as requested_product_image,
                       op.name as offered_product_name, op.image_url as offered_product_image,
                       u.username as requester_username
                FROM swap_requests sr
                JOIN products rp ON sr.requested_product_id = rp.id
                JOIN products op ON sr.offered_product_id = op.id
                JOIN users u ON sr.requester_id = u.id
                WHERE sr.owner_id = ?
                ORDER BY 
                    CASE 
                        WHEN sr.status = 'Pending' THEN 1
                        WHEN sr.status = 'Accepted' THEN 2
                        ELSE 3
                    END,
                    sr.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $requests = $stmt->fetchAll();
            ?>

            <?php if(count($requests) > 0): ?>
                <?php foreach($requests as $request): ?>
                    <div class="swap-request-card">
                        <div class="swap-request-header">
                            <h3>Swap Request from <?php echo htmlspecialchars($request['requester_username']); ?></h3>
                            <span class="swap-status status-<?php echo strtolower($request['status']); ?>">
                                <?php echo htmlspecialchars($request['status']); ?>
                            </span>
                        </div>
                        <div class="swap-content">
                            <div class="swap-items">
                                <div class="swap-item">
                                    <img src="<?php echo htmlspecialchars('../' . $request['requested_product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($request['requested_product_name']); ?>">
                                    <h4>They Want</h4>
                                    <p><?php echo htmlspecialchars($request['requested_product_name']); ?></p>
                                </div>
                                <div class="swap-arrow">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div class="swap-item">
                                    <img src="<?php echo htmlspecialchars('../' . $request['offered_product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($request['offered_product_name']); ?>">
                                    <h4>They Offer</h4>
                                    <p><?php echo htmlspecialchars($request['offered_product_name']); ?></p>
                                </div>
                            </div>
                            <?php if($request['status'] === 'Pending'): ?>
                                <div class="swap-actions">
                                    <a href="accept_swap.php?id=<?php echo $request['id']; ?>" class="btn btn-primary">Accept</a>
                                    <a href="reject_swap.php?id=<?php echo $request['id']; ?>" class="btn btn-danger">Reject</a>
                                    <a href="cancel_swap.php?id=<?php echo $request['id']; ?>" class="btn btn-warning">Cancel</a>
                                </div>
                            <?php endif; ?>
                            <div class="swap-meta">
                                Requested on: <?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-items">No incoming swap requests at the moment.</div>
            <?php endif; ?>

        <?php else: ?>
            <?php
            // Get outgoing swap requests
            $stmt = $conn->prepare("
                SELECT sr.*, 
                       rp.name as requested_product_name, rp.image_url as requested_product_image,
                       op.name as offered_product_name, op.image_url as offered_product_image,
                       u.username as owner_username
                FROM swap_requests sr
                JOIN products rp ON sr.requested_product_id = rp.id
                JOIN products op ON sr.offered_product_id = op.id
                JOIN users u ON sr.owner_id = u.id
                WHERE sr.requester_id = ?
                ORDER BY 
                    CASE 
                        WHEN sr.status = 'Pending' THEN 1
                        WHEN sr.status = 'Accepted' THEN 2
                        ELSE 3
                    END,
                    sr.created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $requests = $stmt->fetchAll();
            ?>

            <?php if(count($requests) > 0): ?>
                <?php foreach($requests as $request): ?>
                    <div class="swap-request-card">
                        <div class="swap-request-header">
                            <h3>Swap Request to <?php echo htmlspecialchars($request['owner_username']); ?></h3>
                            <span class="swap-status status-<?php echo strtolower($request['status']); ?>">
                                <?php echo htmlspecialchars($request['status']); ?>
                            </span>
                        </div>
                        <div class="swap-content">
                            <div class="swap-items">
                                <div class="swap-item">
                                    <img src="<?php echo htmlspecialchars('../' . $request['requested_product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($request['requested_product_name']); ?>">
                                    <h4>You Want</h4>
                                    <p><?php echo htmlspecialchars($request['requested_product_name']); ?></p>
                                </div>
                                <div class="swap-arrow">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <div class="swap-item">
                                    <img src="<?php echo htmlspecialchars('../' . $request['offered_product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($request['offered_product_name']); ?>">
                                    <h4>You Offer</h4>
                                    <p><?php echo htmlspecialchars($request['offered_product_name']); ?></p>
                                </div>
                            </div>
                            <?php if($request['status'] === 'Pending'): ?>
                                <div class="swap-actions">
                                    <a href="cancel_swap.php?id=<?php echo $request['id']; ?>" class="btn btn-warning">Cancel Request</a>
                                </div>
                            <?php endif; ?>
                            <div class="swap-meta">
                                Requested on: <?php echo date('F j, Y g:i A', strtotime($request['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-items">No outgoing swap requests at the moment.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 