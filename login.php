<?php
session_start();
require_once 'includes/db.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    header("Location: user/dashboard.php");
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if(empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id, username, password, is_admin FROM users WHERE email = :email";
        
        if($stmt = $conn->prepare($sql)) {
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            
            if($stmt->execute()) {
                if($stmt->rowCount() == 1) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $id = $row['id'];
                    $username = $row['username'];
                    $hashed_password = $row['password'];
                    $is_admin = $row['is_admin'];
                    
                    if(password_verify($password, $hashed_password)) {
                        // Store data in session variables
                        $_SESSION['user_id'] = $id;
                        $_SESSION['username'] = $username;
                        $_SESSION['is_admin'] = $is_admin;
                        
                        // Redirect user to appropriate dashboard
                        if($is_admin) {
                            header("Location: admin/dashboard.php");
                        } else {
                            header("Location: user/dashboard.php");
                        }
                        exit;
                    } else {
                        $error = "Invalid email or password.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }
        }
    }
}

// Add debugging
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("Login attempt for email: " . $email);
    if (isset($row)) {
        error_log("User found in database. Hashed password: " . $hashed_password);
        error_log("Password verification result: " . (password_verify($password, $hashed_password) ? "true" : "false"));
    } else {
        error_log("No user found with this email");
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
        <h2>Login to EcoSwap</h2>
        <?php if(isset($_SESSION['registration_success'])): ?>
            <div class="message message-success">
                <?php 
                echo $_SESSION['registration_success'];
                unset($_SESSION['registration_success']); 
                ?>
            </div>
        <?php endif; ?>
        <?php if(!empty($error)): ?>
            <div class="message message-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
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
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
            <p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
        </form>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = document.querySelector('.toggle-password');
    
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