<?php
/**
 * Reverse Migration Script: Convert DB BLOB banners back to filesystem
 * This script exports banner images stored as BLOBs in the database back to filesystem files
 * and updates the image_path column with the file path.
 */

// Include database configuration
require_once '../config/db.php';

try {
    echo "Starting reverse migration of banner images from DB to filesystem...\n";
    
    // Ensure uploads directory exists
    $uploadDir = '../public/uploads/banners/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "Created upload directory: $uploadDir\n";
    }
    
    // Get all banners that have BLOB data but no image_path
    $stmt = $pdo->prepare("SELECT id, title, image_mime, image_data FROM banners WHERE image_data IS NOT NULL AND (image_path IS NULL OR image_path = '')");
    $stmt->execute();
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($banners)) {
        echo "No banners found with BLOB data to migrate.\n";
        exit;
    }
    
    echo "Found " . count($banners) . " banners to migrate.\n";
    
    foreach ($banners as $banner) {
        echo "Processing banner ID: {$banner['id']} - {$banner['title']}\n";
        
        // Determine file extension from MIME type
        $extension = '';
        switch (strtolower($banner['image_mime'])) {
            case 'image/jpeg':
            case 'image/jpg':
                $extension = '.jpg';
                break;
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/gif':
                $extension = '.gif';
                break;
            case 'image/webp':
                $extension = '.webp';
                break;
            default:
                echo "  Warning: Unknown MIME type {$banner['image_mime']}, defaulting to .jpg\n";
                $extension = '.jpg';
                break;
        }
        
        // Generate unique filename
        $filename = 'banner_' . $banner['id'] . '_' . time() . $extension;
        $filePath = $uploadDir . $filename;
        $relativePath = 'uploads/banners/' . $filename;
        
        // Write BLOB data to file
        $result = file_put_contents($filePath, $banner['image_data']);
        
        if ($result === false) {
            echo "  ERROR: Failed to write file: $filePath\n";
            continue;
        }
        
        echo "  Created file: $filePath (" . number_format($result) . " bytes)\n";
        
        // Update database with image_path
        $updateStmt = $pdo->prepare("UPDATE banners SET image_path = ? WHERE id = ?");
        $updateResult = $updateStmt->execute([$relativePath, $banner['id']]);
        
        if ($updateResult) {
            echo "  Updated database with image_path: $relativePath\n";
        } else {
            echo "  ERROR: Failed to update database for banner ID: {$banner['id']}\n";
            // Remove the file we just created since DB update failed
            unlink($filePath);
            continue;
        }
        
        echo "  Successfully migrated banner ID: {$banner['id']}\n\n";
    }
    
    echo "Reverse migration completed successfully!\n";
    echo "You can now optionally remove the image_data and image_mime columns if no longer needed.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
