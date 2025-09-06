<?php
/**
 * User Registration Page
 */

// Include header
require_once '../includes/header.php';

// Initialize variables
$name = $email = $phone = $address = '';
$errors = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data and sanitize
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $address = clean_input($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Debug log
    debug_log([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'password_length' => strlen($password),
        'confirm_password_length' => strlen($confirm_password)
    ], 'Registration Attempt');
    
    // Validate form data
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            debug_log($email, 'Email already exists');
            $errors[] = 'Email already exists. Please use a different email or login';
        }
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    debug_log($errors, 'Validation Errors');
    
    // If no errors, register the user
    if (empty($errors)) {
        try {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            debug_log(strlen($hashed_password), 'Hashed Password Length');
            
            // Insert user into database
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, phone, address) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $hashed_password, $phone, $address]);
            
            // Get the user ID
            $user_id = $pdo->lastInsertId();
            debug_log($user_id, 'New User ID');
            
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'customer';
            
            debug_log($_SESSION, 'Session after registration');
            
            // Set success message
            $_SESSION['success_message'] = 'Registration successful! Welcome to Pizza Delivery!';
            
            // Redirect to homepage
            redirect('/PizzaWebsite/pizza_delivery/public/index.php');
        } catch (PDOException $e) {
            debug_log($e->getMessage(), 'Registration Database Error');
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="form-container">
            <h2>Create an Account</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registration-form">
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Delivery Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="form-text">Password must be at least 6 characters long.</div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <div id="password-error" class="text-danger" style="display: none;"></div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
                
                <div class="mt-3 text-center">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>