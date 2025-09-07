</div> <!-- End of Main Content Container -->

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>PizzaBurg</h5>
                    <p>Delicious food delivered fast to your doorstep. Order online for the best pizza experience!</p>
                </div>
                <div class="col-md-4 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="/PizzaWebsite/pizza_delivery/public/index.php" class="text-white">Menu</a></li>
                        <li><a href="/PizzaWebsite/pizza_delivery/public/cart.php" class="text-white">Cart</a></li>
                        <?php if (is_logged_in()): ?>
                        <li><a href="/PizzaWebsite/pizza_delivery/public/order_history.php" class="text-white">Order History</a></li>
                        <li><a href="/PizzaWebsite/pizza_delivery/public/logout.php" class="text-white">Logout</a></li>
                        <?php else: ?>
                        <li><a href="/PizzaWebsite/pizza_delivery/public/login.php" class="text-white">Login</a></li>
                        <li><a href="/PizzaWebsite/pizza_delivery/public/register.php" class="text-white">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <i class="fas fa-map-marker-alt me-2"></i> 123 Pizza Street, Food City<br>
                        <i class="fas fa-phone me-2"></i> (123) 456-7890<br>
                        <i class="fas fa-envelope me-2"></i> info@pizzaburg.com
                    </address>
                    <div class="social-icons mt-2">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> PizzaBurg. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (required for AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="/PizzaWebsite/pizza_delivery/assets/js/script.js"></script>
    
    <?php if (is_logged_in()): ?>
    <script>
    // Update cart count on page load
    $(document).ready(function() {
        updateCartCount();
    });
    
    // Function to update cart count
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
    </script>
    <?php endif; ?>
</body>
</html>