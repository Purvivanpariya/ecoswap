<?php
// Determine if we're in a subdirectory
$isSubdir = strpos($_SERVER['PHP_SELF'], '/admin/') !== false || 
            strpos($_SERVER['PHP_SELF'], '/user/') !== false;
$baseUrl = $isSubdir ? '../' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoSwap - Sustainable Trading Platform</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script>
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</head>
<body>
    <header>
        <nav class="main-nav">
            <div class="container">
                <div class="nav-wrapper">
                    <div class="logo">
                        <a href="<?php echo $baseUrl; ?>index.php">
                            EcoSwap
                            <span class="logo-tagline">Connecting Communities Through Sustainable Exchange</span>
                        </a>
                    </div>
                    <ul class="nav-links">
                        <li><a href="<?php echo $baseUrl; ?>index.php">Home</a></li>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <li><a href="<?php echo $baseUrl; ?>user/products.php">Products</a></li>
                            <li><a href="<?php echo $baseUrl; ?>user/swaps.php">Swaps</a></li>
                            <li><a href="<?php echo $baseUrl; ?>user/dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo $baseUrl; ?>user/messages.php">Messages</a></li>
                            <li><a href="<?php echo $baseUrl; ?>user/profile.php">Profile</a></li>
                            <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <li><a href="<?php echo $baseUrl; ?>admin/dashboard.php">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo $baseUrl; ?>logout.php" onclick="return confirmLogout();">Logout</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo $baseUrl; ?>user/products.php">Products</a></li>
                            <li><a href="<?php echo $baseUrl; ?>login.php">Login</a></li>
                            <li><a href="<?php echo $baseUrl; ?>register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <main class="container">