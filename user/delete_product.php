<?php
session_start();
require_once '../includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to delete a product.";
    header("Location: ../login.php");
    exit;
}

// Check if product ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid product ID provided.";
    header("Location: products.php");
    exit;
}

// Get product details to verify ownership and get image path
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE id = :id AND user_id = :user_id
");
$stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// If product doesn't exist or doesn't belong to user, redirect
if(!$product) {
    $_SESSION['error_message'] = "Product not found or you don't have permission to delete it.";
    header("Location: products.php");
    exit;
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Delete any associated swap requests
    $stmt = $conn->prepare("
        DELETE FROM swap_requests 
        WHERE requested_product_id = :id OR offered_product_id = :id
    ");
    $stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
    $stmt->execute();

    // Delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
    $stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
    
    if($stmt->execute()) {
        // Delete product image if exists
        if(!empty($product['image_url'])) {
            $image_path = '../' . $product['image_url'];
            if(file_exists($image_path)) {
                if(!unlink($image_path)) {
                    error_log("Failed to delete image file: " . $image_path);
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = "Product deleted successfully.";
    } else {
        throw new Exception("Failed to delete product from database.");
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    error_log("Error deleting product: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to delete product: " . $e->getMessage();
}

// Redirect to products page
header("Location: products.php");
exit;
?> 