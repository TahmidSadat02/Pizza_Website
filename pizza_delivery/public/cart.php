<?php
/**
 * Shopping Cart Page
 */

// Include header
require_once '../includes/header.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['error_message'] = 'Please log in to view your cart';
    redirect('login.php');
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

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
?>

<div class="container py-5">
    <h2 class="mb-4">Your Shopping Cart</h2>
    
    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info">
            <p>Your cart is empty. <a href="index.php">Continue shopping</a></p>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr data-cart-id="<?php echo $item['cart_id']; ?>">
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
                                                <h5 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="btn btn-sm btn-outline-secondary decrease-qty" 
                                                    data-cart-id="<?php echo $item['cart_id']; ?>">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="quantity mx-2"><?php echo $item['quantity']; ?></span>
                                            <button class="btn btn-sm btn-outline-secondary increase-qty" 
                                                    data-cart-id="<?php echo $item['cart_id']; ?>">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="subtotal">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-danger remove-item" 
                                                data-cart-id="<?php echo $item['cart_id']; ?>">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td id="cart-total">$<?php echo number_format($total, 2); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <a href="checkout.php" class="btn btn-success">
                        <i class="fas fa-shopping-cart"></i> Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- AJAX Handlers for Cart Updates -->
<script>
    // Update cart item quantity
    function updateCartItem(cartId, quantity) {
        $.ajax({
            url: 'update_cart.php',
            type: 'POST',
            data: {
                cart_id: cartId,
                quantity: quantity
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update cart display
                    updateCartDisplay();
                    
                    // Show success message
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message, 'danger');
                }
            },
            error: function() {
                showNotification('Error updating cart', 'danger');
            }
        });
    }
    
    // Remove item from cart
    function removeCartItem(cartId) {
        $.ajax({
            url: 'remove_from_cart.php',
            type: 'POST',
            data: {
                cart_id: cartId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove item from display
                    $('tr[data-cart-id="' + cartId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        updateCartDisplay();
                        
                        // If cart is empty, reload page
                        if ($('tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                    
                    // Update cart count in navbar
                    updateCartCount(response.cart_count);
                    
                    // Show success message
                    showNotification(response.message, 'success');
                } else {
                    showNotification(response.message, 'danger');
                }
            },
            error: function() {
                showNotification('Error removing item from cart', 'danger');
            }
        });
    }
    
    // Update cart display (recalculate totals)
    function updateCartDisplay() {
        let total = 0;
        
        // Update each row
        $('tbody tr').each(function() {
            const cartId = $(this).data('cart-id');
            const price = parseFloat($(this).find('td:nth-child(2)').text().replace('$', ''));
            const quantity = parseInt($(this).find('.quantity').text());
            const subtotal = price * quantity;
            
            // Update subtotal display
            $(this).find('.subtotal').text('$' + subtotal.toFixed(2));
            
            // Add to total
            total += subtotal;
        });
        
        // Update total display
        $('#cart-total').text('$' + total.toFixed(2));
    }
    
    // Event handlers
    $(document).ready(function() {
        // Increase quantity
        $('.increase-qty').click(function() {
            const cartId = $(this).data('cart-id');
            const quantityElement = $(this).siblings('.quantity');
            const currentQty = parseInt(quantityElement.text());
            const newQty = currentQty + 1;
            
            quantityElement.text(newQty);
            updateCartItem(cartId, newQty);
        });
        
        // Decrease quantity
        $('.decrease-qty').click(function() {
            const cartId = $(this).data('cart-id');
            const quantityElement = $(this).siblings('.quantity');
            const currentQty = parseInt(quantityElement.text());
            
            if (currentQty > 1) {
                const newQty = currentQty - 1;
                quantityElement.text(newQty);
                updateCartItem(cartId, newQty);
            } else {
                // If quantity would be 0, remove the item
                if (confirm('Remove this item from your cart?')) {
                    removeCartItem(cartId);
                }
            }
        });
        
        // Remove item
        $('.remove-item').click(function() {
            const cartId = $(this).data('cart-id');
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                removeCartItem(cartId);
            }
        });
    });
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>