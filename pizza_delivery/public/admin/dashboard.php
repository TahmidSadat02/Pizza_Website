<?php
/**
 * Admin Dashboard
 */

// Include header
require_once '../../includes/header.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to access this page';
    redirect('/PizzaWebsite/pizza_delivery/public/index.php');
}

// Get counts for dashboard
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$customer_count = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM food_items");
$food_count = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
$order_count = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$pending_count = $stmt->fetch()['count'];

// Get recent orders
$stmt = $pdo->query(
    "SELECT o.*, u.name as customer_name 
     FROM orders o 
     JOIN users u ON o.user_id = u.user_id 
     ORDER BY o.order_time DESC 
     LIMIT 5"
);
$recent_orders = $stmt->fetchAll();
?>

<div class="container py-5">
    <h2 class="mb-4">Admin Dashboard</h2>
    
    <!-- Dashboard Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Customers</h5>
                    <h2><?php echo $customer_count; ?></h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="#" class="text-white">View Details</a>
                    <div><i class="fas fa-users fa-2x"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Food Items</h5>
                    <h2><?php echo $food_count; ?></h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="manage_food.php" class="text-white">View Details</a>
                    <div><i class="fas fa-pizza-slice fa-2x"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h2><?php echo $order_count; ?></h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="manage_orders.php" class="text-white">View Details</a>
                    <div><i class="fas fa-shopping-bag fa-2x"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Pending Orders</h5>
                    <h2><?php echo $pending_count; ?></h2>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a href="manage_orders.php?status=pending" class="text-white">View Details</a>
                    <div><i class="fas fa-clock fa-2x"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Admin Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="add_food.php" class="btn btn-success w-100">
                                <i class="fas fa-plus-circle"></i> Add New Food Item
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="manage_food.php" class="btn btn-primary w-100">
                                <i class="fas fa-edit"></i> Manage Food Items
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="manage_orders.php" class="btn btn-info w-100">
                                <i class="fas fa-list"></i> Manage Orders
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="manage_banners.php" class="btn btn-warning w-100">
                                <i class="fas fa-images"></i> Manage Banners
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="../index.php" class="btn btn-secondary w-100">
                                <i class="fas fa-home"></i> View Website
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No orders found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo get_status_color($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($order['order_time'])); ?></td>
                                            <td>
                                                <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="update_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="manage_orders.php" class="btn btn-primary">View All Orders</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../../includes/footer.php';
?>