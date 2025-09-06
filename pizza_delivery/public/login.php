<?php
/**
 *# Check if user is already logged in
if (is_logged_in()) {
    redirect('/PizzaWebsite/pizza_delivery/public/index.php');
}r Login Page
 */

// Include header
require_once '../includes/header.php';

// Initialize variables
$email = '';
$errors = [];

// Check if user is already logged in
if (is_logged_in()) {
    redirect('/pizza_delivery/public/index.php');
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Debug log
    debug_log(['email' => $email, 'password_length' => strlen($password)], 'Login Attempt');
    
    // Validate form data
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If no errors, attempt to login
    if (empty($errors)) {
        try {
            // Get user from database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // Debug log
            debug_log($user, 'User from database');
            
            // Check if user exists and password is correct
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Debug log
                debug_log($_SESSION, 'Session after login');
                
                // Set success message
                $_SESSION['success_message'] = 'Login successful! Welcome back, ' . $user['name'] . '!';
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('/PizzaWebsite/pizza_delivery/public/admin/dashboard.php');
                } else {
                    redirect('/PizzaWebsite/pizza_delivery/public/index.php');
                }
            } else {
                if (!$user) {
                    debug_log('User not found', 'Login Error');
                    $errors[] = 'Invalid email or password';
                } else {
                    debug_log('Password verification failed', 'Login Error');
                    $errors[] = 'Invalid email or password';
                }
            }
        } catch (PDOException $e) {
            debug_log($e->getMessage(), 'Database Error');
            $errors[] = 'Login failed: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-container">
            <h2>Login to Your Account</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
                
                <div class="mt-3 text-center">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>