<?php
session_start();
require_once 'includes/db.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: user/dashboard.php");
    exit;
}

$error = '';
$success = '';

// Handle registration form submission
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
        $sql = "SELECT id FROM users WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $error = "This username is already taken.";
        } else {
            // Check if email exists
            $sql = "SELECT id FROM users WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $error = "This email is already registered.";
            } else {
                // Insert new user
                $sql = "INSERT INTO users (username, email, password, full_name) VALUES (:username, :email, :password, :full_name)";
                
                if($stmt = $conn->prepare($sql)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt->bindParam(":username", $username, PDO::PARAM_STR);
                    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                    $stmt->bindParam(":password", $hashed_password, PDO::PARAM_STR);
                    $stmt->bindParam(":full_name", $full_name, PDO::PARAM_STR);
                    
                    if($stmt->execute()) {
                        $_SESSION['registration_success'] = "Registration successful! You can now login.";
                        header("Location: login.php");
                        exit;
                    } else {
                        $error = "Something went wrong. Please try again later.";
                    }
                }
            }
        }
    }
}

include 'includes/header.php';
?>

<head>
    <style>
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
    </style>
</head>

<div class="auth-container">
    <div class="auth-card">
        <h2>Create an Account</h2>
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
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>    
            <div class="form-group">
                <label>Password</label>
                <div class="password-field">
                    <input type="password" name="password" id="password" class="form-control" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
                </div>
                <small class="form-text">Password must be at least 6 characters long.</small>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="password-field">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
            <p class="text-center">Already have an account? <a href="login.php">Login here</a></p>
        </form>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = passwordInput.parentElement.querySelector('.toggle-password');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.classList.remove('fa-eye');
        toggleButton.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleButton.classList.remove('fa-eye-slash');
        toggleButton.classList.add('fa-eye');
    }
}
</script>

<?php include 'includes/footer.php'; ?> 