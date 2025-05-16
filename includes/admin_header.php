<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoSwap Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="logo">
                <a href="../index.php">
                    <i class="fas fa-leaf"></i>
                    EcoSwap
                </a>
            </div>
            <nav>
                <ul class="admin-nav">
                    <li class="admin-nav-item">
                        <a href="dashboard.php" class="admin-nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="manage_users.php" class="admin-nav-link">
                            <i class="fas fa-users"></i>
                            Users
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="manage_products.php" class="admin-nav-link">
                            <i class="fas fa-box"></i>
                            Products
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="manage_swaps.php" class="admin-nav-link">
                            <i class="fas fa-exchange-alt"></i>
                            Swaps
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="add_admin.php" class="admin-nav-link">
                            <i class="fas fa-user-plus"></i>
                            Add Admin
                        </a>
                    </li>
                    <li class="admin-nav-item">
                        <a href="../logout.php" class="admin-nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Admin Panel</h1>
                <div class="admin-header-actions">
                    <span class="admin-user">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                    </span>
                </div>
            </header>

            <!-- Page content will go here -->
            <div class="admin-content"> 