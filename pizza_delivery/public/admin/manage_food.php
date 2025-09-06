<?php
/**
 * Manage Food Items Page
 */

// Include header
require_once '../../includes/header.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to access this page';
    redirect('../index.php');
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $food_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if ($food_id) {
        try {
            // Check if the food item is used in any orders
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_details WHERE food_id = ?");
            $stmt->execute([$food_id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                // Food item is used in orders, just mark as unavailable
                $stmt = $pdo->prepare("UPDATE food_items SET available = 0 WHERE food_id = ?");
                $stmt->execute([$food_id]);
                $_SESSION['success_message'] = 'Food item marked as unavailable because it is used in orders';
            } else {
                // Food item is not used in orders, delete it
                $stmt = $pdo->prepare("DELETE FROM food_items WHERE food_id = ?");
                $stmt->execute([$food_id]);
                $_SESSION['success_message'] = 'Food item deleted successfully';
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        }
    }
    
    redirect('manage_food.php');
}

// Handle toggle availability action
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $food_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    if ($food_id) {
        try {
            // Get current availability status
            $stmt = $pdo->prepare("SELECT available FROM food_items WHERE food_id = ?");
            $stmt->execute([$food_id]);
            $food = $stmt->fetch();
            
            if ($food) {
                // Toggle availability
                $new_status = $food['available'] ? 0 : 1;
                $stmt = $pdo->prepare("UPDATE food_items SET available = ? WHERE food_id = ?");
                $stmt->execute([$new_status, $food_id]);
                
                $status_text = $new_status ? 'available' : 'unavailable';
                $_SESSION['success_message'] = "Food item marked as {$status_text}";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        }
    }
    
    redirect('manage_food.php');
}

// Get all food items
$category_filter = isset($_GET['category']) ? clean_input($_GET['category']) : '';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

$query = "SELECT * FROM food_items WHERE 1=1";
$params = [];

if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$query .= " ORDER BY category, name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$food_items = $stmt->fetchAll();

// Get all categories for filter
$stmt = $pdo->query("SELECT DISTINCT category FROM food_items ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Food Items</h2>
        <a href="add_food.php" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Add New Food Item
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="category" class="form-label">Filter by Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, description or category">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Food Items Table -->
    <div class="card">
        <div class="card-body">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($food_items)): ?>
                <div class="alert alert-info">No food items found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($food_items as $item): ?>
                                <tr>
                                    <td><?php echo $item['food_id']; ?></td>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="img-thumbnail" 
                                             style="max-width: 50px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $item['available'] ? 'success' : 'danger'; ?>">
                                            <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="edit_food.php?id=<?php echo $item['food_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_food.php?action=toggle&id=<?php echo $item['food_id']; ?>" 
                                               class="btn btn-sm btn-<?php echo $item['available'] ? 'warning' : 'success'; ?>" 
                                               title="<?php echo $item['available'] ? 'Mark as Unavailable' : 'Mark as Available'; ?>">
                                                <i class="fas <?php echo $item['available'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                            </a>
                                            <a href="manage_food.php?action=delete&id=<?php echo $item['food_id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure you want to delete this item?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../../includes/footer.php';
?>