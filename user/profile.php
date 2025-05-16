<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$success = $error = '';

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if(empty($full_name) || empty($email)) {
        $error = "Full name and email are required.";
    } elseif(!empty($new_password) && strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif(!empty($new_password) && $new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        // Check if email exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $error = "This email is already registered to another account.";
        } else {
            // If changing password, verify current password
            if(!empty($new_password)) {
                if(!password_verify($current_password, $user['password'])) {
                    $error = "Current password is incorrect.";
                }
            }
            
            if(empty($error)) {
                // Update user information
                $sql = "UPDATE users SET full_name = :full_name, email = :email";
                $params = [
                    ":full_name" => $full_name,
                    ":email" => $email,
                    ":user_id" => $_SESSION['user_id']
                ];
                
                // Add password update if new password is provided
                if(!empty($new_password)) {
                    $sql .= ", password = :password";
                    $params[":password"] = password_hash($new_password, PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id = :user_id";
                
                $stmt = $conn->prepare($sql);
                if($stmt->execute($params)) {
                    $success = "Profile updated successfully!";
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
                    $stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Something went wrong. Please try again.";
                }
            }
        }
    }
}

// Get user statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products WHERE user_id = :user_id");
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

$stmt = $conn->prepare("SELECT COUNT(*) as total_swaps FROM swap_requests WHERE (requester_id = :user_id OR requested_product_id IN (SELECT id FROM products WHERE user_id = :user_id)) AND status = 'Completed'");
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$total_swaps = $stmt->fetch(PDO::FETCH_ASSOC)['total_swaps'];

include '../includes/header.php';
?>

<div class="profile-page">
    <h1>My Profile</h1>

    <div class="profile-container">
        <div class="profile-stats">
            <div class="stat-card">
                <h3>Total Products</h3>
                <p class="stat"><?php echo $total_products; ?></p>
            </div>
            <div class="stat-card">
                <h3>Successful Swaps</h3>
                <p class="stat"><?php echo $total_swaps; ?></p>
            </div>
            <div class="stat-card">
                <h3>Member Since</h3>
                <p class="stat"><?php echo date('M Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>

        <div class="profile-form">
            <h2>Edit Profile</h2>
            
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

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small class="form-text">Username cannot be changed</small>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="password-section">
                    <h3>Change Password</h3>
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control">
                        <small class="form-text">Leave blank to keep current password</small>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 