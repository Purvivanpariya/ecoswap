<?php
// Database connection parameters
$host = 'localhost';
$dbname = 'ecoswap';
$username = 'root';
$password = '';

try {
    // Create database connection
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Default admin credentials
    $admin_username = 'admin1';
    $admin_email = 'admin1@ecoswap.com';
    $admin_password = 'admin123';
    $admin_full_name = 'Default Admin';
    
    // Check if admin account already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
    $stmt->bindParam(":email", $admin_email);
    $stmt->bindParam(":username", $admin_username);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        echo "Admin account already exists!\n";
    } else {
        // Create admin account
        $sql = "INSERT INTO users (username, email, password, full_name, is_admin) VALUES (:username, :email, :password, :full_name, TRUE)";
        $stmt = $conn->prepare($sql);
        
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        $stmt->bindParam(":username", $admin_username);
        $stmt->bindParam(":email", $admin_email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":full_name", $admin_full_name);
        
        if($stmt->execute()) {
            echo "Default admin account created successfully!\n";
            echo "Email: $admin_email\n";
            echo "Password: $admin_password\n";
        } else {
            echo "Error creating admin account.\n";
        }
    }
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
?> 