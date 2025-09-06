<?php
/**
 * Manage Orders Page for Admin
 */

// Include header
require_once '../../includes/header.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to access this page';
    redirect('/PizzaWebsite/pizza_delivery/public/index.php');
}

// Initialize variables
$errors = [];
$success_message = '';

// Process status update if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = filter_var($_POST['order_id'] ?? 0, FILTER_VALIDATE_INT);
    $status = clean_input($_POST['status'] ?? '');
    
    // Validate input
    if (!$order_id) {
        $errors[] = 'Invalid order ID';
    }
    
    if (!in_array($status, ['pending', 'preparing', 'on the way', 'delivered', 'cancelled'])) {
        $errors[] = 'Invalid status';
    }
    
    // Update order status if no errors
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$status, $order_id]);
            
            $success_message = 'Order status updated successfully';
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? clean_input($_GET['date']) : '';

// Build query based on filters
$query = "SELECT o.*, u.name as customer_name, u.email, u.phone 
          FROM orders o 
          JOIN users u ON o.user_id = u.user_id";
$params = [];

$where_clauses = [];
if (!empty($status_filter)) {
    $where_clauses[] = "o.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_clauses[] = "DATE(o.order_date) = ?";
    $params[] = $date_filter;
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY o.order_date DESC";

// Get orders
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
    $orders = [];
}
?>

<div class="container py-5">
    <h2 class="mb-4">Manage Orders</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filter Orders</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-5">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                        <option value="on the way" <?php echo $status_filter === 'on the way' ? 'selected' : ''; ?>>On the Way</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="date" class="form-label">Order Date</label>
                    <input type="date" class="form-control" id="date" name="date" value="<?php echo $date_filter; ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Orders</h5>
        </div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="alert alert-info">No orders found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>
                                        <small>
                                            <div><?php echo htmlspecialchars($order['email']); ?></div>
                                            <div><?php echo htmlspecialchars($order['phone']); ?></div>
                                        </small>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo get_status_color($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['order_id']; ?>">
                                            View Details
                                        </button>
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

<!-- Order Detail Modals -->
<?php foreach ($orders as $order): ?>
    <div class="modal fade" id="orderModal<?php echo $order['order_id']; ?>" tabindex="-1" aria-labelledby="orderModalLabel<?php echo $order['order_id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel<?php echo $order['order_id']; ?>">
                        Order #<?php echo $order['order_id']; ?> Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p>
                                <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?><br>
                                <strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p>
                                <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?><br>
                                <strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?><br>
                                <strong>Status:</strong> 
                                <span class="badge bg-<?php echo get_status_color($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h6>Delivery Address</h6>
                            <p><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                        </div>
                    </div>
                    
                    <h6>Order Items</h6>
                    <?php
                    // Get order items
                    try {
                        $stmt = $pdo->prepare(
                            "SELECT od.*, f.name, f.image_url 
                             FROM order_details od 
                             JOIN food_items f ON od.food_id = f.food_id 
                             WHERE od.order_id = ?"
                        );
                        $stmt->execute([$order['order_id']]);
                        $order_items = $stmt->fetchAll();
                    } catch (PDOException $e) {
                        echo '<div class="alert alert-danger">Error loading order items</div>';
                        $order_items = [];
                    }
                    ?>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </div>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th>$<?php echo number_format($order['total_amount'], 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <hr>
                    
                    <h6>Update Order Status</h6>
                    <form method="POST" action="">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <select class="form-select" name="status" required>
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="preparing" <?php echo $order['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="on the way" <?php echo $order['status'] === 'on the way' ? 'selected' : ''; ?>>On the Way</option>
                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php
// Include footer
require_once '../../includes/footer.php';
?>