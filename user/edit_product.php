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
    header("Location: products.php");
    exit;
}

$success = $error = '';

// Get product details
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
    header("Location: products.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $condition = trim($_POST['condition']);
    $status = trim($_POST['status']);
    
    // Validate input
    if(empty($name) || empty($description) || empty($category) || empty($condition)) {
        $error = "Please fill in all required fields.";
    } else {
        $image_url = $product['image_url']; // Keep existing image by default
        
        // Handle new image upload if provided
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if(!in_array(strtolower($filetype), $allowed)) {
                $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = "../uploads/products/";
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate a secure random filename
                $random_bytes = bin2hex(random_bytes(16));
                $new_filename = $random_bytes . '.' . $filetype;
                $upload_path = $upload_dir . $new_filename;
                
                if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Set secure file permissions
                    chmod($upload_path, 0640);
                    // Delete old image if exists
                    if(!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
                        unlink('../' . $product['image_url']);
                    }
                    $image_url = 'uploads/products/' . $new_filename;
                } else {
                    $error = "Failed to upload image. Please try again.";
                }
            }
        }
        
        if(empty($error)) {
            // Update product in database
            $sql = "UPDATE products SET 
                    name = :name, 
                    description = :description, 
                    category = :category, 
                    condition_status = :condition, 
                    status = :status,
                    image_url = :image_url 
                    WHERE id = :id AND user_id = :user_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":name", $name, PDO::PARAM_STR);
            $stmt->bindParam(":description", $description, PDO::PARAM_STR);
            $stmt->bindParam(":category", $category, PDO::PARAM_STR);
            $stmt->bindParam(":condition", $condition, PDO::PARAM_STR);
            $stmt->bindParam(":status", $status, PDO::PARAM_STR);
            $stmt->bindParam(":image_url", $image_url, PDO::PARAM_STR);
            $stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $success = "Product updated successfully!";
                // Refresh product data
                $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
                $stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
                $stmt->execute();
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

// Get list of categories
$categories = [
    'Electronics',
    'Clothing',
    'Books',
    'Home & Garden',
    'Sports & Outdoors',
    'Toys & Games',
    'Art & Collectibles',
    'Other'
];

// Product conditions
$conditions = [
    'New',
    'Like New',
    'Very Good',
    'Good',
    'Fair',
    'Poor'
];

// Product statuses
$statuses = [
    'Available',
    'Pending',
    'Swapped',
    'Not Available'
];

include '../includes/header.php';
?>

<div class="edit-product-page">
    <h1>Edit Product</h1>

    <div class="edit-product-container">
        <?php if(!empty($error)): ?>
            <div class="message message-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="message message-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $_GET['id']); ?>" 
              method="post" 
              enctype="multipart/form-data">
            
            <div class="form-group">
                <label>Product Name *</label>
                <input type="text" name="name" class="form-control" 
                       value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>

            <div class="form-group">
                <label>Description *</label>
                <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Category *</label>
                <select name="category" class="form-control" required>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" 
                            <?php echo ($product['category'] === $cat) ? 'selected' : ''; ?>>
                            <?php echo $cat; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Condition *</label>
                <select name="condition" class="form-control" required>
                    <?php foreach($conditions as $cond): ?>
                        <option value="<?php echo $cond; ?>" 
                            <?php echo ($product['condition_status'] === $cond) ? 'selected' : ''; ?>>
                            <?php echo $cond; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Status *</label>
                <select name="status" class="form-control" required>
                    <?php foreach($statuses as $stat): ?>
                        <option value="<?php echo $stat; ?>" 
                            <?php echo ($product['status'] === $stat) ? 'selected' : ''; ?>>
                            <?php echo $stat; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Current Image</label>
                <?php if(!empty($product['image_url'])): ?>
                    <div class="current-image">
                        <img src="<?php echo htmlspecialchars('../' . $product['image_url']); ?>" 
                             alt="Current product image">
                    </div>
                <?php else: ?>
                    <p>No image uploaded</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>New Image (optional)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <small class="form-text">Leave empty to keep current image. Accepted formats: JPG, JPEG, PNG, GIF</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="view_product.php?id=<?php echo $_GET['id']; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 