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
        throw new Exception("Invalid swap request or you don't have permission to accept it.");
    }

    // Verify both products are still available
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM products 
        WHERE id IN (?, ?) 
        AND status = 'Available'
    ");
    $stmt->execute([$request['requested_product_id'], $request['offered_product_id']]);
    if ($stmt->fetchColumn() != 2) {
        throw new Exception("One or both products are no longer available for swapping.");
    }

    // Update the swap request status to Accepted
    $stmt = $conn->prepare("
        UPDATE swap_requests 
        SET status = 'Accepted', 
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$request_id]);

    // Mark both products as Swapped
    $stmt = $conn->prepare("
        UPDATE products 
        SET status = 'Swapped' 
        WHERE id IN (?, ?)
    ");
    $stmt->execute([$request['requested_product_id'], $request['offered_product_id']]);

    // Reject all other pending requests for these products
    $stmt = $conn->prepare("
        UPDATE swap_requests 
        SET status = 'Rejected', 
            updated_at = NOW() 
        WHERE (requested_product_id IN (?, ?) OR offered_product_id IN (?, ?))
        AND id != ? AND status = 'Pending'
    ");
    $stmt->execute([
        $request['requested_product_id'], 
        $request['offered_product_id'],
        $request['requested_product_id'], 
        $request['offered_product_id'],
        $request_id
    ]);

    $conn->commit();
    $_SESSION['success'] = "Swap request has been accepted successfully! The items have been marked as swapped.";

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Failed to accept swap request: " . $e->getMessage();
}

// Redirect back to swaps page
header("Location: swaps.php");
exit;
?> 