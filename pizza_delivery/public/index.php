<?php
/**
 * GoMeal-Style Homepage
 */

// Include required files
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
$user = getCurrentUser();

// Get all available food items from database
$stmt = $pdo->prepare(
    "SELECT * FROM food_items 
     WHERE available = 1 
     ORDER BY category, name"
);
$stmt->execute();
$food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group food items by category
$categories = [];
foreach ($food_items as $item) {
    $categories[$item['category']][] = $item;
}

// Get cart count for current user
$cart_count = 0;
if ($user) {
    $cart_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $cart_stmt->execute([$user['user_id']]);
    $cart_count = $cart_stmt->fetchColumn() ?: 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PizzaBurg - Food Delivery</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="main-layout">
        <!-- Left Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-pizza-slice"></i>
                    <span>PizzaBurg</span>
                </div>
            </div>
            
            <div class="user-profile">
                <?php if ($user): ?>
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="profile-info">
                        <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p>Welcome back!</p>
                    </div>
                <?php else: ?>
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-info">
                        <h4>Guest User</h4>
                        <p><a href="login.php" class="text-orange">Login</a> to order</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#menu" class="nav-item">
                    <i class="fas fa-utensils"></i>
                    <span>Food Order</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-heart"></i>
                    <span>Favorite</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-comments"></i>
                    <span>Message</span>
                </a>
                <a href="order_history.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>Order History</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-receipt"></i>
                    <span>Bills</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <?php if ($user): ?>
                    <a href="logout.php" class="nav-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search for food, restaurant...">
                </div>
                <div class="top-actions">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                </div>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                <!-- Discount Banner -->
                <div class="discount-banner">
                    <div class="discount-content">
                        <h2>20% Discount</h2>
                        <p>On your first order with PizzaBurg!</p>
                        <button class="btn btn-light">Order Now</button>
                    </div>
                    <div class="discount-girl">
                        <i class="fas fa-pizza-slice fa-7x" style="opacity: 0.3;"></i>
                    </div>
                </div>

                <!-- Category Section -->
                <div class="category-section" id="menu">
                    <div class="section-header">
                        <h3>Category</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="category-grid">
                        <?php 
                        $category_icons = [
                            'pizza' => 'fas fa-pizza-slice',
                            'burger' => 'fas fa-hamburger',
                            'pasta' => 'fas fa-bread-slice',
                            'drinks' => 'fas fa-coffee',
                            'dessert' => 'fas fa-ice-cream',
                            'salad' => 'fas fa-leaf'
                        ];
                        
                        $active_category = array_keys($categories)[0] ?? '';
                        
                        foreach (array_keys($categories) as $index => $category): 
                            $icon = $category_icons[strtolower($category)] ?? 'fas fa-utensils';
                            $is_active = $index === 0;
                        ?>
                            <div class="category-item <?php echo $is_active ? 'active' : ''; ?>" 
                                 data-category="<?php echo strtolower($category); ?>">
                                <div class="category-icon">
                                    <i class="<?php echo $icon; ?>"></i>
                                </div>
                                <div class="category-name"><?php echo ucfirst($category); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Popular Dishes -->
                <div class="dishes-section">
                    <div class="section-header">
                        <h3>Popular Dishes</h3>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <div class="dishes-grid" id="dishes-container">
                        <?php 
                        // Show first category items by default
                        $display_items = $categories[$active_category] ?? [];
                        foreach ($display_items as $item): 
                        ?>
                            <div class="dish-card">
                                <div class="dish-image">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div style="height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-utensils fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <button class="favorite-btn">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                                <div class="dish-info">
                                    <div class="dish-rating">
                                        <div class="stars">
                                            <i class="fas fa-star star"></i>
                                            <i class="fas fa-star star"></i>
                                            <i class="fas fa-star star"></i>
                                            <i class="fas fa-star star"></i>
                                            <i class="far fa-star star"></i>
                                        </div>
                                        <span class="rating-text">4.0 (100+)</span>
                                    </div>
                                    <h4 class="dish-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="dish-footer">
                                        <span class="dish-price">$<?php echo number_format($item['price'], 2); ?></span>
                                        <button class="add-btn add-to-cart-btn" 
                                                data-food-id="<?php echo $item['food_id']; ?>" 
                                                data-food-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                                data-food-price="<?php echo $item['price']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="recent-orders">
                    <div class="section-header">
                        <h3>Recent Orders</h3>
                        <a href="order_history.php" class="view-all">View All</a>
                    </div>
                    <div class="orders-grid">
                        <?php 
                        // Show some sample recent orders or actual user orders
                        $recent_items = array_slice($food_items, 0, 4);
                        foreach ($recent_items as $item): 
                        ?>
                            <div class="order-card">
                                <i class="favorite-order far fa-heart"></i>
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.5rem;">
                                        <i class="fas fa-utensils text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p>$<?php echo number_format($item['price'], 2); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="right-sidebar">
            <div class="balance-section">
                <h3>My Balance</h3>
                <div class="balance-amount">$128.40</div>
                <div class="balance-actions">
                    <button class="btn btn-primary btn-sm">Add Money</button>
                    <button class="btn btn-outline-primary btn-sm">Withdraw</button>
                </div>
            </div>

            <div class="address-section">
                <div class="address-header">
                    <h3>Address</h3>
                    <button class="btn btn-sm btn-outline-primary">Change</button>
                </div>
                <div class="address-card">
                    <i class="fas fa-map-marker-alt text-orange me-2"></i>
                    123 Main Street, Downtown<br>
                    New York, NY 10001
                </div>
            </div>

            <div class="order-menu">
                <h3>Order Menu</h3>
                <div id="cart-items-display">
                    <!-- Cart items will be loaded here via JavaScript -->
                    <p class="text-muted">Your cart is empty</p>
                </div>
                <div class="order-total">
                    <div class="d-flex justify-content-between">
                        <span>Total:</span>
                        <span class="fw-bold" id="cart-total">$0.00</span>
                    </div>
                </div>
                <button class="checkout-btn" onclick="window.location.href='cart.php'">
                    Checkout
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden data for categories -->
    <script type="application/json" id="categories-data">
        <?php echo json_encode($categories); ?>
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>
    
    <script>
        // Category switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const categoryItems = document.querySelectorAll('.category-item');
            const dishesContainer = document.getElementById('dishes-container');
            const categoriesData = JSON.parse(document.getElementById('categories-data').textContent);
            
            categoryItems.forEach(item => {
                item.addEventListener('click', function() {
                    // Remove active class from all categories
                    categoryItems.forEach(cat => cat.classList.remove('active'));
                    // Add active class to clicked category
                    this.classList.add('active');
                    
                    // Get selected category
                    const category = this.dataset.category;
                    const categoryKey = Object.keys(categoriesData).find(key => 
                        key.toLowerCase() === category
                    );
                    
                    if (categoryKey && categoriesData[categoryKey]) {
                        updateDishesDisplay(categoriesData[categoryKey]);
                    }
                });
            });
            
            function updateDishesDisplay(items) {
                dishesContainer.innerHTML = '';
                
                items.forEach(item => {
                    const dishCard = createDishCard(item);
                    dishesContainer.appendChild(dishCard);
                });
            }
            
            function createDishCard(item) {
                const card = document.createElement('div');
                card.className = 'dish-card';
                
                const imageUrl = item.image_url || '';
                const imageHtml = imageUrl ? 
                    `<img src="${imageUrl}" alt="${item.name}">` :
                    `<div style="height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-utensils fa-3x text-muted"></i>
                    </div>`;
                
                card.innerHTML = `
                    <div class="dish-image">
                        ${imageHtml}
                        <button class="favorite-btn">
                            <i class="far fa-heart"></i>
                        </button>
                    </div>
                    <div class="dish-info">
                        <div class="dish-rating">
                            <div class="stars">
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="far fa-star star"></i>
                            </div>
                            <span class="rating-text">4.0 (100+)</span>
                        </div>
                        <h4 class="dish-name">${item.name}</h4>
                        <div class="dish-footer">
                            <span class="dish-price">$${parseFloat(item.price).toFixed(2)}</span>
                            <button class="add-btn add-to-cart-btn" 
                                    data-food-id="${item.food_id}" 
                                    data-food-name="${item.name}" 
                                    data-food-price="${item.price}">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                `;
                
                return card;
            }
            
            // Load cart on page load
            loadCart();
        });
    </script>
</body>
</html>