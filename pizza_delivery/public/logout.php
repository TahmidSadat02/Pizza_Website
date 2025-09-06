<?php
/**
 * User Logout Page
 */

// Include functions file to access session functions
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set logout message if user was logged in
if (isset($_SESSION['user_id'])) {
    $_SESSION['success_message'] = 'You have been successfully logged out!';
}

// Unset all session variables
$_SESSION = [];

// If a session cookie is used, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: /PizzaWebsite/pizza_delivery/public/login.php');
exit;