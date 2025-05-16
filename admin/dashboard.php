<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

// Get total users count
$stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE is_admin = 0");
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// Get total products count
$stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products");
$stmt->execute();
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

// Get total messages count
$stmt = $conn->prepare("SELECT COUNT(*) as total_messages FROM messages");
$stmt->execute();
$total_messages = $stmt->fetch(PDO::FETCH_ASSOC)['total_messages'];

// Get recent users
$stmt = $conn->prepare("SELECT id, username, email, full_name, created_at FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent products
$stmt = $conn->prepare("SELECT p.*, u.username FROM products p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5");
$stmt->execute();
$recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/admin_header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <h1 class="mb-4">Admin Dashboard</h1>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3 class="card-title">Total Users</h3>
                        <p class="display-4"><?php echo $total_users; ?></p>
                        <a href="manage_users.php" class="btn btn-light">Manage Users</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h3 class="card-title">Total Products</h3>
                        <p class="display-4"><?php echo $total_products; ?></p>
                        <a href="manage_products.php" class="btn btn-light">Manage Products</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h3 class="card-title">Total Messages</h3>
                        <p class="display-4"><?php echo $total_messages; ?></p>
                        <a href="messages.php" class="btn btn-light">View Messages</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Recent Users</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <a href="manage_users.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="manage_users.php" class="btn btn-primary">View All Users</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h2 class="h5 mb-0">Recent Products</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td><?php echo htmlspecialchars($product['status']); ?></td>
                                            <td>
                                                <a href="manage_products.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="manage_products.php" class="btn btn-primary">View All Products</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?> 