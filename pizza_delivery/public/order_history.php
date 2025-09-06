<?php
/**
 * Order History Page
 */

// Include header
require_once '../includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['error_message'] = 'Please log in to view your order history';
    redirect('login.php');
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get all orders for the user
$stmt = $pdo->prepare(
    "SELECT * FROM orders 
     WHERE user_id = ? 
     ORDER BY order_time DESC"
);
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <h2 class="mb-4">Your Order History</h2>
    
    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            <p>You haven't placed any orders yet. <a href="index.php">Start shopping</a></p>
        </div>
    <?php else: ?>
        <div class="accordion" id="orderAccordion">
            <?php foreach ($orders as $index => $order): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?php echo $order['order_id']; ?>">
                        <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?php echo $order['order_id']; ?>" 
                                aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                aria-controls="collapse<?php echo $order['order_id']; ?>">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <span>
                                    <strong>Order #<?php echo $order['order_id']; ?></strong> - 
                                    <?php echo date('F j, Y, g:i a', strtotime($order['order_time'])); ?>
                                </span>
                                <span>
                                    <span class="badge bg-<?php echo get_status_color($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                    <span class="ms-2">$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $order['order_id']; ?>" 
                         class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                         aria-labelledby="heading<?php echo $order['order_id']; ?>" 
                         data-bs-parent="#orderAccordion">
                        <div class="accordion-body">
                            <?php
                            // Get order details
                            $stmt = $pdo->prepare(
                                "SELECT od.*, f.name, f.category 
                                 FROM order_details od 
                                 JOIN food_items f ON od.food_id = f.food_id 
                                 WHERE od.order_id = ?"
                            );
                            $stmt->execute([$order['order_id']]);
                            $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_details as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                            <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <h6>Order Status</h6>
                                <div class="progress">
                                    <?php
                                    $status_progress = [
                                        'pending' => 25,
                                        'confirmed' => 50,
                                        'delivered' => 100,
                                        'cancelled' => 100
                                    ];
                                    $progress = $status_progress[$order['status']] ?? 0;
                                    $color = ($order['status'] === 'cancelled') ? 'danger' : 'success';
                                    ?>
                                    <div class="progress-bar bg-<?php echo $color; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $progress; ?>%" 
                                         aria-valuenow="<?php echo $progress; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo ucfirst($order['status']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="mt-4">
        <a href="index.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>