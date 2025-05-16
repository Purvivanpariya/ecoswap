<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db.php';
session_start();

// Check if user is logged in and is an admin
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    
    // Validate input
    if(empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $error = "Please fill in all fields.";
    } elseif(strlen($password) < 6) {
        $error = "Password must have at least 6 characters.";
    } elseif($password != $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username exists
        $stmt_username = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt_username->bindParam(":username", $username, PDO::PARAM_STR);
        if (!$stmt_username->execute()) {
            $error = "Error checking username: " . implode(' | ', $stmt_username->errorInfo());
        } elseif($stmt_username->rowCount() > 0) {
            $error = "This username is already taken.";
        } else {
            // Check if email exists
            $stmt_email = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt_email->bindParam(":email", $email, PDO::PARAM_STR);
            if (!$stmt_email->execute()) {
                $error = "Error checking email: " . implode(' | ', $stmt_email->errorInfo());
            } elseif($stmt_email->rowCount() > 0) {
                $error = "This email is already registered.";
            } else {
                // Insert new admin user
                $sql = "INSERT INTO users (username, email, password, full_name, is_admin) VALUES (:username, :email, :password, :full_name, TRUE)";
                $stmt_insert = $conn->prepare($sql);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert->bindParam(":username", $username, PDO::PARAM_STR);
                $stmt_insert->bindParam(":email", $email, PDO::PARAM_STR);
                $stmt_insert->bindParam(":password", $hashed_password, PDO::PARAM_STR);
                $stmt_insert->bindParam(":full_name", $full_name, PDO::PARAM_STR);
                if (!$stmt_insert->execute()) {
                    $error = "Error inserting admin: " . implode(' | ', $stmt_insert->errorInfo());
                } else {
                    $success = "Admin account created successfully!";
                    // Clear form data
                    $username = $email = $full_name = '';
                }
            }
        }
    }
}

include '../includes/admin_header.php';
?>

<div class="container">
    <div class="admin-dashboard">
        <h1 class="mb-4">Add New Admin</h1>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary">Create Admin Account</button>
                        <a href="dashboard.php" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?> 