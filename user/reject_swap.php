<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check if swap request ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No swap request specified.";
    header("Location: swaps.php");
    exit;
}

$request_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $conn->beginTransaction();

    // First, verify the swap request exists and belongs to the current user as the owner
    $stmt = $conn->prepare("
        SELECT sr.*, p1.user_id as owner_id 
        FROM swap_requests sr
        JOIN products p1 ON sr.requested_product_id = p1.id
        WHERE sr.id = ? AND p1.user_id = ? AND sr.status = 'Pending'
    ");
    $stmt->execute([$request_id, $user_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception("Invalid swap request or you don't have permission to reject it.");
    }

    // Update the swap request status to Rejected
    $stmt = $conn->prepare("
        UPDATE swap_requests 
        SET status = 'Rejected', 
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$request_id]);

    // Make sure both products are marked as Available
    $stmt = $conn->prepare("
        UPDATE products 
        SET status = 'Available' 
        WHERE id IN (?, ?)
    ");
    $stmt->execute([$request['requested_product_id'], $request['offered_product_id']]);

    $conn->commit();
    $_SESSION['success'] = "Swap request has been rejected. Both items remain available for swapping.";

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Failed to reject swap request: " . $e->getMessage();
}

// Redirect back to swaps page
header("Location: swaps.php");
exit;
?> 