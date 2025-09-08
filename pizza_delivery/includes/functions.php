<?php
/**
 * Utility Functions
 * 
 * This file contains utility functions used throughout the Pizza Delivery Website.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Clean input data to prevent XSS attacks
 * 
 * @param string $data The input data to be cleaned
 * @return string The cleaned data
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Debug function to log messages to a file
 * 
 * @param mixed $data The data to log
 * @param string $label Optional label for the log entry
 */
function debug_log($data, $label = '') {
    $log_file = __DIR__ . '/../logs/debug.log';
    
    // Create logs directory if it doesn't exist
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0777, true);
    }
    
    // Format the log entry
    $log_entry = date('[Y-m-d H:i:s]') . ' ' . $label . ': ';
    
    if (is_array($data) || is_object($data)) {
        $log_entry .= print_r($data, true);
    } else {
        $log_entry .= $data;
    }
    
    // Append to log file
    file_put_contents($log_file, $log_entry . "\n", FILE_APPEND);
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin, false otherwise
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get current logged in user information
 * 
 * @return array|null User data if logged in, null otherwise
 */
function getCurrentUser() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        debug_log($e->getMessage(), 'getCurrentUser Error');
        return null;
    }
}

/**
 * Redirect to a specific page
 * 
 * @param string $location The location to redirect to
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Display error message
 * 
 * @param string $message The error message to display
 * @return string HTML for the error message
 */
function display_error($message) {
    return "<div class='alert alert-danger'>$message</div>";
}

/**
 * Display success message
 * 
 * @param string $message The success message to display
 * @return string HTML for the success message
 */
function display_success($message) {
    return "<div class='alert alert-success'>$message</div>";
}

/**
 * Get user by ID
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $user_id The user ID
 * @return array|false The user data or false if not found
 */
function get_user_by_id($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get food item by ID
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $food_id The food ID
 * @return array|false The food item data or false if not found
 */
function get_food_by_id($pdo, $food_id) {
    $stmt = $pdo->prepare("SELECT * FROM food_items WHERE food_id = ?");
    $stmt->execute([$food_id]);
    return $stmt->fetch();
}

/**
 * Get all food items
 * 
 * @param PDO $pdo The PDO connection object
 * @param string|null $category The category to filter by (optional)
 * @return array The food items
 */
function get_all_food_items($pdo, $category = null) {
    if ($category) {
        $stmt = $pdo->prepare("SELECT * FROM food_items WHERE category = ? AND available = 1 ORDER BY name");
        $stmt->execute([$category]);
    } else {
        $stmt = $pdo->query("SELECT * FROM food_items WHERE available = 1 ORDER BY category, name");
    }
    return $stmt->fetchAll();
}

/**
 * Get all food categories
 * 
 * @param PDO $pdo The PDO connection object
 * @return array The food categories
 */
function get_all_categories($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT category FROM food_items WHERE available = 1 ORDER BY category");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get cart items for a user
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $user_id The user ID
 * @return array The cart items
 */
function get_cart_items($pdo, $user_id) {
    $stmt = $pdo->prepare(
        "SELECT c.*, f.name, f.price, f.image_url, (c.quantity * f.price) as subtotal 
         FROM cart c 
         JOIN food_items f ON c.food_id = f.food_id 
         WHERE c.user_id = ?"
    );
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Get cart total for a user
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $user_id The user ID
 * @return float The cart total
 */
function get_cart_total($pdo, $user_id) {
    $stmt = $pdo->prepare(
        "SELECT SUM(c.quantity * f.price) as total 
         FROM cart c 
         JOIN food_items f ON c.food_id = f.food_id 
         WHERE c.user_id = ?"
    );
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

/**
 * Get order details
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $order_id The order ID
 * @return array The order details
 */
function get_order_details($pdo, $order_id) {
    $stmt = $pdo->prepare(
        "SELECT od.*, f.name, f.image_url 
         FROM order_details od 
         JOIN food_items f ON od.food_id = f.food_id 
         WHERE od.order_id = ?"
    );
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

/**
 * Get user orders
 * 
 * @param PDO $pdo The PDO connection object
 * @param int $user_id The user ID
 * @return array The user orders
 */
function get_user_orders($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_time DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Format price with currency symbol
 * 
 * @param float $price The price to format
 * @return string The formatted price
 */
function format_price($price) {
    return '$' . number_format($price, 2);
}

/**
 * Get color class for order status
 * 
 * @param string $status The order status
 * @return string The Bootstrap color class
 */
function get_status_color($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    
    return $colors[$status] ?? 'secondary';
}

/**
 * Ensure an image has a 16:9 aspect ratio by center-cropping and resizing.
 * Uses GD and preserves transparency for PNG/GIF/WebP where possible.
 *
 * @param string $sourcePath Absolute path to source image file
 * @param int $outWidth Desired output width (default 1600)
 * @param int $outHeight Desired output height (default 900)
 * @return bool True on success, false on failure
 */
function enforce_image_16_9($sourcePath, $outWidth = 1600, $outHeight = 900) {
    if (!file_exists($sourcePath)) {
        debug_log("Image file not found: $sourcePath", 'enforce_image_16_9');
        return false;
    }

    $info = getimagesize($sourcePath);
    if ($info === false) {
        debug_log("Failed to getimagesize() for: $sourcePath", 'enforce_image_16_9');
        return false;
    }

    list($srcW, $srcH, $type) = $info;
    if ($srcW <= 0 || $srcH <= 0) {
        debug_log("Invalid image dimensions for: $sourcePath", 'enforce_image_16_9');
        return false;
    }

    $srcRatio = $srcW / $srcH;
    $targetRatio = 16 / 9;

    // Determine crop area (centered)
    if ($srcRatio > $targetRatio) {
        // source is wider than target => crop width
        $cropH = $srcH;
        $cropW = (int)round($cropH * $targetRatio);
        $srcX = (int)round(($srcW - $cropW) / 2);
        $srcY = 0;
    } else {
        // source is taller than target => crop height
        $cropW = $srcW;
        $cropH = (int)round($cropW / $targetRatio);
        $srcX = 0;
        $srcY = (int)round(($srcH - $cropH) / 2);
    }

    // Create source image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $srcImg = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $srcImg = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $srcImg = imagecreatefromwebp($sourcePath);
            } else {
                debug_log('WebP not supported by GD on this PHP build', 'enforce_image_16_9');
                return false;
            }
            break;
        default:
            debug_log('Unsupported image type: ' . $type, 'enforce_image_16_9');
            return false;
    }

    if (!$srcImg) {
        debug_log('Failed to create image resource: ' . $sourcePath, 'enforce_image_16_9');
        return false;
    }

    // Create a true color image for the cropped area
    $cropped = imagecreatetruecolor($cropW, $cropH);

    // Preserve transparency for PNG/GIF/WebP
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $transparent);
    }

    // Copy cropped region
    if (!imagecopy($cropped, $srcImg, 0, 0, $srcX, $srcY, $cropW, $cropH)) {
        debug_log('Failed during imagecopy for: ' . $sourcePath, 'enforce_image_16_9');
        imagedestroy($srcImg);
        imagedestroy($cropped);
        return false;
    }

    // Create final resized image
    $dst = imagecreatetruecolor($outWidth, $outHeight);
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP])) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $outWidth, $outHeight, $transparent);
    } else {
        // Fill with white for JPEG
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $outWidth, $outHeight, $white);
    }

    if (!imagecopyresampled($dst, $cropped, 0, 0, 0, 0, $outWidth, $outHeight, $cropW, $cropH)) {
        debug_log('Failed during imagecopyresampled for: ' . $sourcePath, 'enforce_image_16_9');
        imagedestroy($srcImg);
        imagedestroy($cropped);
        imagedestroy($dst);
        return false;
    }

    // Overwrite original file with the resized image according to type
    $saved = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $saved = imagejpeg($dst, $sourcePath, 85);
            break;
        case IMAGETYPE_PNG:
            $saved = imagepng($dst, $sourcePath);
            break;
        case IMAGETYPE_GIF:
            $saved = imagegif($dst, $sourcePath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $saved = imagewebp($dst, $sourcePath, 85);
            } else {
                debug_log('imagewebp not available', 'enforce_image_16_9');
                $saved = false;
            }
            break;
    }

    // Free resources
    imagedestroy($srcImg);
    imagedestroy($cropped);
    imagedestroy($dst);

    if (!$saved) {
        debug_log('Failed to save resized image: ' . $sourcePath, 'enforce_image_16_9');
    }

    return (bool)$saved;
}

/**
 * Prepare a banner image sized to exactly 1200x300px.
 * - If source is larger than target, center-crop and resize down to 1200x300.
 * - If source is smaller than target, center the image on a 1200x300 canvas (no upscaling), leaving background space.
 * - Preserves transparency for PNG/GIF/WebP where possible.
 *
 * @param string $sourcePath Absolute path to source image
 * @param int $targetW Target width (default 1200)
 * @param int $targetH Target height (default 300)
 * @return bool True on success, false on failure
 */
function enforce_banner_1400x480($sourcePath, $targetW = 1400, $targetH = 480) {
    if (!file_exists($sourcePath)) {
        debug_log("Image file not found: $sourcePath", 'enforce_banner_1400x480');
        return false;
    }

    $info = getimagesize($sourcePath);
    if ($info === false) {
        debug_log("Failed to getimagesize() for: $sourcePath", 'enforce_banner_1400x480');
        return false;
    }

    list($srcW, $srcH, $type) = $info;
    if ($srcW <= 0 || $srcH <= 0) {
        debug_log("Invalid image dimensions for: $sourcePath", 'enforce_banner_1400x480');
        return false;
    }

    // Create source image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $srcImg = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $srcImg = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $srcImg = imagecreatefromwebp($sourcePath);
            } else {
                debug_log('WebP not supported by GD on this PHP build', 'enforce_banner_1400x480');
                return false;
            }
            break;
        default:
            debug_log('Unsupported image type: ' . $type, 'enforce_banner_1400x480');
            return false;
    }

    if (!$srcImg) {
        debug_log('Failed to create image resource: ' . $sourcePath, 'enforce_banner_1400x480');
        return false;
    }

    $dst = imagecreatetruecolor($targetW, $targetH);

    // Preserve transparency for PNG/GIF/WebP
    $hasTransparency = in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP]);
    if ($hasTransparency) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);
    } else {
        // white background for JPEG and others
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $white);
    }

    // If source is larger than target in either dimension, crop to target ratio then resize
    $targetRatio = $targetW / $targetH;
    $srcRatio = $srcW / $srcH;

    if ($srcW >= $targetW && $srcH >= $targetH) {
        // Center-crop to target ratio
        if ($srcRatio > $targetRatio) {
            $cropH = $srcH;
            $cropW = (int)round($cropH * $targetRatio);
            $srcX = (int)round(($srcW - $cropW) / 2);
            $srcY = 0;
        } else {
            $cropW = $srcW;
            $cropH = (int)round($cropW / $targetRatio);
            $srcX = 0;
            $srcY = (int)round(($srcH - $cropH) / 2);
        }

        $tmp = imagecreatetruecolor($cropW, $cropH);
        if ($hasTransparency) {
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
            $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
            imagefilledrectangle($tmp, 0, 0, $cropW, $cropH, $transparent);
        }

        if (!imagecopy($tmp, $srcImg, 0, 0, $srcX, $srcY, $cropW, $cropH)) {
            debug_log('Failed during imagecopy in crop phase: ' . $sourcePath, 'enforce_banner_1400x480');
            imagedestroy($srcImg);
            imagedestroy($tmp);
            imagedestroy($dst);
            return false;
        }

        if (!imagecopyresampled($dst, $tmp, 0, 0, 0, 0, $targetW, $targetH, $cropW, $cropH)) {
            debug_log('Failed during imagecopyresampled in resize phase: ' . $sourcePath, 'enforce_banner_1400x480');
            imagedestroy($srcImg);
            imagedestroy($tmp);
            imagedestroy($dst);
            return false;
        }
        imagedestroy($tmp);
    } else {
        // Source is smaller in at least one dimension: center it on the canvas without upscaling
        $dstX = (int)round(($targetW - $srcW) / 2);
        $dstY = (int)round(($targetH - $srcH) / 2);

        if (!imagecopy($dst, $srcImg, $dstX, $dstY, 0, 0, $srcW, $srcH)) {
            debug_log('Failed to copy smaller image onto canvas: ' . $sourcePath, 'enforce_banner_1400x480');
            imagedestroy($srcImg);
            imagedestroy($dst);
            return false;
        }
    }

    // Save according to type
    $saved = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $saved = imagejpeg($dst, $sourcePath, 90);
            break;
        case IMAGETYPE_PNG:
            $saved = imagepng($dst, $sourcePath);
            break;
        case IMAGETYPE_GIF:
            $saved = imagegif($dst, $sourcePath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                $saved = imagewebp($dst, $sourcePath, 90);
            } else {
                debug_log('imagewebp not available', 'enforce_banner_1400x480');
                $saved = false;
            }
            break;
    }

    imagedestroy($srcImg);
    imagedestroy($dst);

    if (!$saved) {
        debug_log('Failed to save banner image: ' . $sourcePath, 'enforce_banner_1400x480');
    }

    return (bool)$saved;
}