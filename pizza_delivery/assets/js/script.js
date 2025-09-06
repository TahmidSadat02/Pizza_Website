/**
 * Pizza Delivery Website - Main JavaScript
 * 
 * This file contains JavaScript functionality for the Pizza Delivery Website,
 * including cart operations with AJAX, form validation, and UI interactions.
 */

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    if (addToCartButtons.length > 0) {
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const foodId = this.getAttribute('data-id');
                addToCart(foodId, 1);
            });
        });
    }
    
    // Quantity control in cart page
    const quantityControls = document.querySelectorAll('.quantity-control');
    if (quantityControls.length > 0) {
        quantityControls.forEach(control => {
            const decreaseBtn = control.querySelector('.decrease-qty');
            const increaseBtn = control.querySelector('.increase-qty');
            const qtyInput = control.querySelector('.qty-input');
            const foodId = qtyInput.getAttribute('data-id');
            
            decreaseBtn.addEventListener('click', function() {
                let currentQty = parseInt(qtyInput.value);
                if (currentQty > 1) {
                    currentQty--;
                    qtyInput.value = currentQty;
                    updateCartItem(foodId, currentQty);
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                let currentQty = parseInt(qtyInput.value);
                currentQty++;
                qtyInput.value = currentQty;
                updateCartItem(foodId, currentQty);
            });
            
            qtyInput.addEventListener('change', function() {
                let currentQty = parseInt(qtyInput.value);
                if (currentQty < 1) {
                    currentQty = 1;
                    qtyInput.value = currentQty;
                }
                updateCartItem(foodId, currentQty);
            });
        });
    }
    
    // Remove from cart functionality
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    if (removeButtons.length > 0) {
        removeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const foodId = this.getAttribute('data-id');
                removeFromCart(foodId);
            });
        });
    }
    
    // Form validation for registration
    const registrationForm = document.getElementById('registration-form');
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                const errorDiv = document.getElementById('password-error');
                errorDiv.textContent = 'Passwords do not match';
                errorDiv.style.display = 'block';
            }
        });
    }
});

/**
 * Add an item to the cart
 * 
 * @param {number} foodId - The ID of the food item
 * @param {number} quantity - The quantity to add
 */
function addToCart(foodId, quantity) {
    $.ajax({
        url: '/PizzaWebsite/pizza_delivery/public/add_to_cart.php',
        type: 'POST',
        data: {
            food_id: foodId,
            quantity: quantity
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                showNotification('Item added to cart!', 'success');
                
                // Update cart count
                updateCartCount();
            } else {
                // Show error message
                showNotification(response.message || 'Error adding item to cart', 'danger');
            }
        },
        error: function() {
            showNotification('Error adding item to cart', 'danger');
        }
    });
}

/**
 * Update cart item quantity
 * 
 * @param {number} foodId - The ID of the food item
 * @param {number} quantity - The new quantity
 */
function updateCartItem(foodId, quantity) {
    $.ajax({
        url: '/PizzaWebsite/pizza_delivery/public/update_cart.php',
        type: 'POST',
        data: {
            food_id: foodId,
            quantity: quantity
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update subtotal and total
                $('#subtotal-' + foodId).text(response.subtotal);
                $('#cart-total').text(response.total);
                
                // Update cart count
                updateCartCount();
            } else {
                showNotification(response.message || 'Error updating cart', 'danger');
            }
        },
        error: function() {
            showNotification('Error updating cart', 'danger');
        }
    });
}

/**
 * Remove an item from the cart
 * 
 * @param {number} foodId - The ID of the food item
 */
function removeFromCart(foodId) {
    $.ajax({
        url: '/PizzaWebsite/pizza_delivery/public/remove_from_cart.php',
        type: 'POST',
        data: {
            food_id: foodId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Remove the row from the table
                $('#cart-row-' + foodId).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Update cart total
                    $('#cart-total').text(response.total);
                    
                    // Check if cart is empty
                    if (response.count === 0) {
                        $('.cart-table').replaceWith('<div class="alert alert-info">Your cart is empty</div>');
                        $('.checkout-btn').hide();
                    }
                    
                    // Update cart count
                    updateCartCount();
                });
                
                showNotification('Item removed from cart', 'success');
            } else {
                showNotification(response.message || 'Error removing item from cart', 'danger');
            }
        },
        error: function() {
            showNotification('Error removing item from cart', 'danger');
        }
    });
}

/**
 * Update the cart count in the navbar
 */
function updateCartCount() {
    $.ajax({
        url: '/PizzaWebsite/pizza_delivery/public/get_cart_count.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#cart-count').text(response.count);
            }
        }
    });
}

/**
 * Show a notification message
 * 
 * @param {string} message - The message to display
 * @param {string} type - The type of notification (success, danger, etc.)
 */
function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification`;
    notification.innerHTML = message;
    
    // Add to the document
    document.body.appendChild(notification);
    
    // Style the notification
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '200px';
    notification.style.padding = '10px 15px';
    notification.style.borderRadius = '4px';
    notification.style.opacity = '0';
    notification.style.transform = 'translateY(-20px)';
    notification.style.transition = 'opacity 0.3s, transform 0.3s';
    
    // Show the notification
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
    }, 10);
    
    // Hide and remove after 3 seconds
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}