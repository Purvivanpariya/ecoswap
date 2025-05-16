<?php
session_start();
require_once 'includes/db.php';

// Fetch featured products
$stmt = $conn->prepare("SELECT * FROM products WHERE status = 'Available' ORDER BY created_at DESC LIMIT 6");
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Welcome to EcoSwap</h1>
        <p>Join our community of eco-conscious traders and give your items a second life!</p>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="hero-buttons">
                <a href="register.php" class="hero-btn hero-btn-primary">Join Now</a>
                <a href="login.php" class="hero-btn hero-btn-secondary">Login</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <section class="featured-products">
        <h2 class="text-center">Featured Items</h2>
        <div class="product-grid">
            <?php if(count($featured_products) > 0): ?>
                <?php foreach($featured_products as $product): ?>
                    <div class="product-card">
                        <?php if(!empty($product['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                                <span>No Image Available</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <p class="category">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                            <a href="user/products.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-items">No products available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="how-it-works">
        <h2>How It Works</h2>
        <div class="steps-grid">
            <div class="step">
                <div class="step-icon">
                    <i class="fas fa-upload"></i>
                </div>
                <h3>1. List Your Item</h3>
                <p>Upload photos and details of items you want to swap.</p>
            </div>
            <div class="step">
                <div class="step-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h3>2. Find Matches</h3>
                <p>Browse items and find potential swap matches.</p>
            </div>
            <div class="step">
                <div class="step-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h3>3. Make the Swap</h3>
                <p>Connect with other users and arrange your swap!</p>
            </div>
        </div>
    </section>

    <section class="go-green-tips">
        <h2 class="text-center mb-5">6 Tips for Going Green</h2>
        <div class="tips-grid">
            <div class="tip-item">
                <div class="tip-icon">
                    <i class="fas fa-tree"></i>
                </div>
                <p>Plant a Tree</p>
            </div>
            <div class="tip-item">
                <div class="tip-icon">
                    <i class="fas fa-recycle"></i>
                </div>
                <p>Ditch Single-Use Plastic</p>
            </div>
            <div class="tip-item">
                <div class="tip-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <p>Use Reusable Bags</p>
            </div>
            <div class="tip-item">
                <div class="tip-icon">
                    <i class="fas fa-bicycle"></i>
                </div>
                <p>Bike to Get Around</p>
            </div>
            <div class="tip-item">
                <div class="tip-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <p>Eat Plant-Based Meals</p>
            </div>
            <div class="tip-item">
                <div class="tip-icon">
                    <i class="fas fa-plug"></i>
                </div>
                <p>Reduce Energy Consumption</p>
            </div>
        </div>
    </section>

</div>

<?php include 'includes/footer.php'; ?> 