<?php
/**
 * Checkout Page
 */

// Include header
require_once '../includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['error_message'] = 'Please log in to checkout';
    redirect('/PizzaWebsite/pizza_delivery/public/login.php');
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get cart items for the user with food details
$stmt = $pdo->prepare(
    "SELECT c.cart_id, c.quantity, f.* 
     FROM cart c 
     JOIN food_items f ON c.food_id = f.food_id 
     WHERE c.user_id = ?"
);
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Check if cart is empty
if (empty($cart_items)) {
    $_SESSION['error_message'] = 'Your cart is empty. Please add items before checkout.';
    redirect('/PizzaWebsite/pizza_delivery/public/index.php');
}

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Create order
        $stmt = $pdo->prepare(
            "INSERT INTO orders (user_id, status, total_amount) 
             VALUES (?, 'pending', ?)"
        );
        $stmt->execute([$user_id, $total]);
        $order_id = $pdo->lastInsertId();
        
        // Add order details
        $stmt = $pdo->prepare(
            "INSERT INTO order_details (order_id, food_id, quantity, price) 
             VALUES (?, ?, ?, ?)"
        );
        
        foreach ($cart_items as $item) {
            $stmt->execute([
                $order_id,
                $item['food_id'],
                $item['quantity'],
                $item['price']
            ]);
        }
        
        // Clear cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Set success message
        $_SESSION['success_message'] = 'Your order has been placed successfully! Order #' . $order_id;
        
        // Redirect to order history
        redirect('/PizzaWebsite/pizza_delivery/public/order_history.php');
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error_message'] = 'Error placing order: ' . $e->getMessage();
    }
}
?>

<div class="container py-5">
    <h2 class="mb-4">Checkout</h2>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                         class="cart-item-img me-3">
                                                <?php else: ?>
                                                    <div class="cart-item-img-placeholder me-3">
                                                        <i class="fas fa-utensils"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small>
                                                </div>
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
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Delivery Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Delivery Address</label>
                            <textarea class="form-control" id="address" rows="3" readonly><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment" required>
                                <option value="cash">Cash on Delivery</option>
                                <option value="card" disabled>Credit Card (Coming Soon)</option>
                                <option value="paypal" disabled>PayPal (Coming Soon)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Delivery Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any special instructions for delivery"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="/PizzaWebsite/pizza_delivery/public/cart.php" class="btn btn-outline-secondary">Back to Cart</a>
                            <button type="submit" class="btn btn-success">Place Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>