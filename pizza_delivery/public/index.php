<?php
/**
 * Homepage - Menu Display
 */

// Include header
require_once '../includes/header.php';

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
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1>Delicious Pizza Delivered To Your Door</h1>
                <p class="lead">Fresh ingredients, authentic recipes, and fast delivery!</p>
                <a href="#menu" class="btn btn-primary btn-lg">Order Now</a>
            </div>
            <div class="col-md-6 text-center">
                <img src="../assets/images/pizza-hero.jpg" alt="Delicious Pizza" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<!-- Menu Section -->
<section id="menu" class="menu-section py-5">
    <div class="container">
        <h2 class="text-center mb-5">Our Menu</h2>
        
        <!-- Category Tabs -->
        <ul class="nav nav-tabs mb-4 justify-content-center" id="menuTabs" role="tablist">
            <?php $first = true; foreach (array_keys($categories) as $index => $category): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $first ? 'active' : ''; ?>" 
                            id="<?php echo strtolower($category); ?>-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#<?php echo strtolower($category); ?>" 
                            type="button" 
                            role="tab" 
                            aria-controls="<?php echo strtolower($category); ?>" 
                            aria-selected="<?php echo $first ? 'true' : 'false'; ?>">
                        <?php echo ucfirst($category); ?>
                    </button>
                </li>
            <?php $first = false; endforeach; ?>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content" id="menuTabsContent">
            <?php $first = true; foreach ($categories as $category => $items): ?>
                <div class="tab-pane fade <?php echo $first ? 'show active' : ''; ?>" 
                     id="<?php echo strtolower($category); ?>" 
                     role="tabpanel" 
                     aria-labelledby="<?php echo strtolower($category); ?>-tab">
                    
                    <div class="row">
                        <?php foreach ($items as $item): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card food-item">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             class="card-img-top" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <?php else: ?>
                                        <div class="card-img-top placeholder-img">
                                            <i class="fas fa-utensils fa-3x"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="card-text description"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <p class="price">$<?php echo number_format($item['price'], 2); ?></p>
                                        
                                        <button class="btn btn-primary add-to-cart-btn" 
                                                data-food-id="<?php echo $item['food_id']; ?>" 
                                                data-food-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                                data-food-price="<?php echo $item['price']; ?>">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php $first = false; endforeach; ?>
        </div>
    </div>
</section>

<?php
// Include footer
require_once '../includes/footer.php';
?>