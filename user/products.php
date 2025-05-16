<?php
session_start();
require_once '../includes/db.php';

// Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug: Print the current working directory and upload path
echo "<!-- Debug Info:
Current Directory: " . getcwd() . "
Upload Directory: " . realpath('../uploads/products') . "
-->";

// First, let's check what's in the products table
$check_query = $conn->query("SELECT COUNT(*) as total FROM products");
$total_products = $check_query->fetch(PDO::FETCH_ASSOC)['total'];
echo "<!-- Total products in database: " . $total_products . " -->";

// Get all products (removed the user exclusion for testing)
$stmt = $conn->prepare("
    SELECT p.*, u.username, u.email 
    FROM products p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.status = 'Available' 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Print all products data
echo "<!-- Debug: Products Data
Number of products found: " . count($products) . "
Current user ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not logged in') . "
Products data:
";
foreach ($products as $product) {
    echo "
    Product ID: " . $product['id'] . "
    Name: " . $product['name'] . "
    User ID: " . $product['user_id'] . "
    Status: " . $product['status'] . "
    Image URL: " . $product['image_url'] . "
    ";
}
echo "-->";

// Get categories for filter
$stmt = $conn->prepare("SELECT DISTINCT category FROM products ORDER BY category");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>

<div class="products-header">
    <div class="products-header-content">
        <h1>Available Products</h1>
        <div class="modern-search-container">
            <select id="categoryFilter" class="modern-select">
                <option value="">Category</option>
                <?php foreach($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="search-input-container">
                <input type="text" id="searchInput" placeholder="Enter Keywords?" class="modern-search">
                <button type="button" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="message message-success">
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="message message-error">
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['user_id'])): ?>
        <div class="add-product">
            <a href="add_product.php" class="btn btn-primary">Add New Product</a>
        </div>
    <?php endif; ?>

    <div class="product-grid">
        <?php if(count($products) > 0): ?>
            <?php foreach($products as $product): ?>
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
                        <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="category">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                        <p class="condition">Condition: <?php echo htmlspecialchars($product['condition_status']); ?></p>
                        <p class="owner">Listed by: <?php echo htmlspecialchars($product['username']); ?></p>
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="product-actions">
                                <a href="view_product.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-secondary">View Details</a>
                                <?php if($product['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="request_swap.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-primary">Request Swap</a>
                                <?php else: ?>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-secondary">Edit</a>
                                    <a href="delete_product.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this product?');">Delete</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="login-prompt">Please <a href="../login.php">login</a> to request a swap.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-items">No products available at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const products = document.querySelectorAll('.product-card');

    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value.toLowerCase();

        products.forEach(product => {
            const name = product.querySelector('h3').textContent.toLowerCase();
            const description = product.querySelector('.description').textContent.toLowerCase();
            const category = product.querySelector('.category').textContent.toLowerCase();
            
            const matchesSearch = name.includes(searchTerm) || description.includes(searchTerm);
            const matchesCategory = !selectedCategory || category.includes(selectedCategory);

            product.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
        });
    }

    searchInput.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);
});
</script>

<?php include '../includes/footer.php'; ?> 