<?php
/**
 * Get Cart Count
 * 
 * This file handles AJAX requests to get the current cart count for a user.
 */

// Include functions file
require_once '../includes/functions.php';

// Include database connection
require_once '../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in',
        'count' => 0
    ]);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Get cart count from database
    $stmt = $pdo->prepare(
        "SELECT SUM(quantity) as count 
         FROM cart 
         WHERE user_id = ?"
    );
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get count or default to 0
    $count = $result['count'] ?? 0;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'count' => (int)$count
    ]);
} catch (PDOException $e) {
    // Log error
    debug_log($e->getMessage(), 'Get Cart Count Error');
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error getting cart count',
        'count' => 0
    ]);
}