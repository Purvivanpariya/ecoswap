<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $condition = trim($_POST['condition']);
    
    // Validate input
    if(empty($name) || empty($description) || empty($category) || empty($condition)) {
        $error = "Please fill in all required fields.";
    } else {
        $image_url = '';
        
        // Handle image upload
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
                    $image_url = 'uploads/products/' . $new_filename;
                } else {
                    // Debug information
                    $error = "Failed to upload image. Error details: " . error_get_last()['message'];
                }
            }
        }
        
        if(empty($error)) {
            // Insert product into database
            $sql = "INSERT INTO products (name, description, category, condition_status, image_url, user_id, status, created_at) 
                    VALUES (:name, :description, :category, :condition, :image_url, :user_id, 'Available', NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":name", $name, PDO::PARAM_STR);
            $stmt->bindParam(":description", $description, PDO::PARAM_STR);
            $stmt->bindParam(":category", $category, PDO::PARAM_STR);
            $stmt->bindParam(":condition", $condition, PDO::PARAM_STR);
            $stmt->bindParam(":image_url", $image_url, PDO::PARAM_STR);
            $stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $success = "Product added successfully!";
                // Clear form data
                $name = $description = $category = $condition = '';
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

// Get list of categories (you can expand this list)
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

include '../includes/header.php';
?>

<div class="container">
    <h2>Add New Product</h2>
    
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if(!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="category">Category</label>
            <select class="form-control" id="category" name="category" required>
                <option value="">Select a category</option>
                <option value="Electronics" <?php echo (isset($category) && $category == 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                <option value="Clothing" <?php echo (isset($category) && $category == 'Clothing') ? 'selected' : ''; ?>>Clothing</option>
                <option value="Books" <?php echo (isset($category) && $category == 'Books') ? 'selected' : ''; ?>>Books</option>
                <option value="Home" <?php echo (isset($category) && $category == 'Home') ? 'selected' : ''; ?>>Home</option>
                <option value="Sports" <?php echo (isset($category) && $category == 'Sports') ? 'selected' : ''; ?>>Sports</option>
                <option value="Other" <?php echo (isset($category) && $category == 'Other') ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="condition">Condition</label>
            <select class="form-control" id="condition" name="condition" required>
                <option value="">Select condition</option>
                <option value="New" <?php echo (isset($condition) && $condition == 'New') ? 'selected' : ''; ?>>New</option>
                <option value="Like New" <?php echo (isset($condition) && $condition == 'Like New') ? 'selected' : ''; ?>>Like New</option>
                <option value="Good" <?php echo (isset($condition) && $condition == 'Good') ? 'selected' : ''; ?>>Good</option>
                <option value="Fair" <?php echo (isset($condition) && $condition == 'Fair') ? 'selected' : ''; ?>>Fair</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="image">Product Image</label>
            <input type="file" class="form-control-file" id="image" name="image" accept="image/*">
            <small class="form-text text-muted">Accepted formats: JPG, JPEG, PNG, GIF</small>
        </div>
        
        <button type="submit" class="btn btn-primary">Add Product</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?> 