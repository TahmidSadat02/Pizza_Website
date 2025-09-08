<?php
// Include functions file
require_once __DIR__ . '/functions.php';

// Include database connection
require_once __DIR__ . '/../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);

// Check if we're in admin area
$is_admin_area = strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PizzaBurg - Delicious Food Delivered Fast</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/PizzaWebsite/pizza_delivery/assets/css/style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark <?php echo $is_admin_area ? 'bg-warning' : 'bg-dark'; ?> sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/PizzaWebsite/pizza_delivery/public/index.php">
                <i class="fas fa-pizza-slice me-2"></i>PizzaBurg
                <?php if ($is_admin_area): ?>
                    <span class="badge bg-dark ms-2">Admin Area</span>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <?php if ($is_admin_area): ?>
                            <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" 
                               href="/PizzaWebsite/pizza_delivery/public/admin/dashboard.php">
                               <i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                        <?php else: ?>
                            <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" 
                               href="/PizzaWebsite/pizza_delivery/public/index.php">Menu</a>
                        <?php endif; ?>
                    </li>
                    <?php if (!$is_admin_area && is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'order_history.php') ? 'active' : ''; ?>" 
                           href="/PizzaWebsite/pizza_delivery/public/order_history.php">My Orders</a>
                    </li>
                    <?php endif; ?>
                    <?php if ($is_admin_area): ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm me-2" 
                           href="/PizzaWebsite/pizza_delivery/public/index.php"
                           style="border-radius: 20px; padding: 0.375rem 0.75rem;">
                            <i class="fas fa-store me-1"></i>Back to Store
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (is_admin()): ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-warning btn-sm me-2" 
                           href="/PizzaWebsite/pizza_delivery/public/admin/dashboard.php"
                           style="border-radius: 20px; padding: 0.375rem 0.75rem; <?php echo $is_admin_area ? 'display: none;' : ''; ?>">
                            <i class="fas fa-cogs me-1"></i>Admin Panel
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/PizzaWebsite/pizza_delivery/public/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="/PizzaWebsite/pizza_delivery/public/admin/add_food.php">
                                <i class="fas fa-plus-circle me-2"></i>Add Food Item</a></li>
                            <li><a class="dropdown-item" href="/PizzaWebsite/pizza_delivery/public/admin/manage_food.php">
                                <i class="fas fa-utensils me-2"></i>Manage Food</a></li>
                            <li><a class="dropdown-item" href="/PizzaWebsite/pizza_delivery/public/admin/add_banner.php">
                                <i class="fas fa-image me-2"></i>Add Banner</a></li>
                            <li><a class="dropdown-item" href="/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php">
                                <i class="fas fa-images me-2"></i>Manage Banners</a></li>
                            <li><a class="dropdown-item" href="/PizzaWebsite/pizza_delivery/public/admin/manage_orders.php">
                                <i class="fas fa-shopping-bag me-2"></i>Manage Orders</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (is_logged_in() && !$is_admin_area): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>" 
                           href="/PizzaWebsite/pizza_delivery/public/cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <span class="cart-count" id="cart-count">0</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['name'] ?? 'Account'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/PizzaWebsite/pizza_delivery/public/order_history.php">My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/PizzaWebsite/pizza_delivery/public/logout.php">Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>" 
                           href="/PizzaWebsite/pizza_delivery/public/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'register.php') ? 'active' : ''; ?>" 
                           href="/PizzaWebsite/pizza_delivery/public/register.php">Register</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container my-4">
        <?php
        // Display flash messages if any
        if (isset($_SESSION['success_message'])) {
            echo display_success($_SESSION['success_message']);
            unset($_SESSION['success_message']);
        }
        
        if (isset($_SESSION['error_message'])) {
            echo display_error($_SESSION['error_message']);
            unset($_SESSION['error_message']);
        }
        ?>