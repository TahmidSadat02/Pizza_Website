<?php
/**
 * Edit Banner
 */

// Include header
require_once '../../includes/header.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to access this page';
    redirect('/PizzaWebsite/pizza_delivery/public/index.php');
}

// Get banner ID
$banner_id = (int)($_GET['id'] ?? 0);
if (!$banner_id) {
    $_SESSION['error_message'] = 'Invalid banner ID';
    redirect('/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php');
}

$errors = [];
$banner = null;

// Get existing banner data
try {
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$banner_id]);
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$banner) {
        $_SESSION['error_message'] = 'Banner not found';
        redirect('/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php');
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    redirect('/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php');
}

// Initialize form variables
$title = $banner['title'];
$link = $banner['link'];
$start_date = $banner['start_date'] ? date('Y-m-d\TH:i', strtotime($banner['start_date'])) : '';
$end_date = $banner['end_date'] ? date('Y-m-d\TH:i', strtotime($banner['end_date'])) : '';
$position = $banner['position'];
$is_active = $banner['is_active'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title'] ?? '');
    $link = clean_input($_POST['link'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $position = (int)($_POST['position'] ?? 1);
    $is_active = isset($_POST['is_active']);
    
    // Validate form
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if ($position < 1) {
        $errors[] = 'Position must be a positive number';
    }
    
    // Validate dates
    if (!empty($start_date) && !empty($end_date)) {
        if (strtotime($start_date) >= strtotime($end_date)) {
            $errors[] = 'End date must be after start date';
        }
    }
    
    $new_image_path = null;
    
    // Handle file upload if new image is provided
    if (!empty($_FILES['banner_image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        $file_size = $_FILES['banner_image']['size'];
        $file_extension = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));

        // Use getimagesize() on temporary file to validate and detect MIME type
        $img_info = @getimagesize($_FILES['banner_image']['tmp_name']);
        if ($img_info === false) {
            $errors[] = 'Uploaded file is not a valid image';
        } else {
            $file_type = $img_info['mime'];
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = 'Please upload a valid image file (JPEG, PNG, GIF, or WebP)';
            }
        }

        if ($file_size > $max_size) {
            $errors[] = 'Image file is too large. Maximum size is 5MB';
        }

        if (empty($errors)) {
            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file_extension;
            $upload_dir = __DIR__ . '/../uploads/banners/';
            $target_path = $upload_dir . $filename;
            $new_image_path = '/PizzaWebsite/pizza_delivery/public/uploads/banners/' . $filename;

            // Ensure upload directory exists
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $errors[] = 'Server error: cannot create upload directory';
                }
            }

            // Check if directory is writable
            if (empty($errors) && !is_writable($upload_dir)) {
                $errors[] = 'Server error: upload directory is not writable';
                debug_log(['upload_dir' => $upload_dir, 'perms' => substr(sprintf('%o', fileperms($upload_dir)), -4)], 'edit_banner upload dir not writable');
            }

            // Check PHP upload error first
            $uploadError = $_FILES['banner_image']['error'] ?? 0;
            if ($uploadError !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                ];
                $errors[] = $uploadErrors[$uploadError] ?? 'Unknown upload error: ' . $uploadError;
                debug_log($_FILES['banner_image'], 'edit_banner upload error');
            }

            // Ensure tmp file is real uploaded file
            if (empty($errors) && !is_uploaded_file($_FILES['banner_image']['tmp_name'])) {
                $errors[] = 'Upload failed: temporary file is not valid';
                debug_log($_FILES['banner_image'], 'edit_banner not is_uploaded_file');
            }

            $moved = false;
            if (empty($errors) && is_uploaded_file($_FILES['banner_image']['tmp_name'])) {
                $moved = move_uploaded_file($_FILES['banner_image']['tmp_name'], $target_path);
            }

            if (!$moved && empty($errors)) {
                $moved = @copy($_FILES['banner_image']['tmp_name'], $target_path);
            }

            if (empty($errors) && $moved) {
                // Prepare banner as 1400x480 (crop or pad)
                if (!enforce_banner_1400x480($target_path, 1400, 480)) {
                    if (file_exists($target_path)) unlink($target_path);
                    $errors[] = 'Failed to process the uploaded image. Please try a different image.';
                    $new_image_path = null;
                }
            } elseif (empty($errors)) {
                $dbg = [
                    'tmp_name' => $_FILES['banner_image']['tmp_name'] ?? null,
                    'target_path' => $target_path,
                    'is_uploaded_file' => is_uploaded_file($_FILES['banner_image']['tmp_name']) ? true : false,
                    'upload_error' => $_FILES['banner_image']['error'] ?? null,
                    'upload_dir_perms' => @substr(sprintf('%o', fileperms($upload_dir)), -4)
                ];
                debug_log($dbg, 'edit_banner upload move+copy failed');
                $errors[] = 'Failed to process the uploaded image. Please check server permissions and try again.';
                $new_image_path = null;
            }
        }
    }
    
    // Update database if no errors
    // Update database if no errors
    if (empty($errors)) {
        try {
            if ($new_image_path) {
                // Update with new image
                $stmt = $pdo->prepare(
                    "UPDATE banners SET title = ?, image_path = ?, link = ?, start_date = ?, end_date = ?, is_active = ?, position = ? WHERE id = ?"
                );
                $stmt->execute([
                    $title,
                    $new_image_path,
                    $link ?: null,
                    $start_date ?: null,
                    $end_date ?: null,
                    $is_active ? 1 : 0,
                    $position,
                    $banner_id
                ]);
                
                // Delete old image file
                if (!empty($banner['image_path'])) {
                    $old_image_path = $_SERVER['DOCUMENT_ROOT'] . $banner['image_path'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
            } else {
                // Update without changing image
                $stmt = $pdo->prepare(
                    "UPDATE banners SET title = ?, link = ?, start_date = ?, end_date = ?, is_active = ?, position = ? WHERE id = ?"
                );
                $stmt->execute([
                    $title,
                    $link ?: null,
                    $start_date ?: null,
                    $end_date ?: null,
                    $is_active ? 1 : 0,
                    $position,
                    $banner_id
                ]);
            }
            
            $_SESSION['success_message'] = 'Banner updated successfully!';
            redirect('/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php');
            
        } catch (PDOException $e) {
            // Delete uploaded file if database update fails
            if ($new_image_path) {
                $uploaded_file = $_SERVER['DOCUMENT_ROOT'] . $new_image_path;
                if (file_exists($uploaded_file)) {
                    unlink($uploaded_file);
                }
            }
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid admin-layout">
    <div class="row">
        <!-- Admin Sidebar -->
        <div class="col-md-3 col-lg-2 admin-sidebar">
            <nav class="nav flex-column">
                <a class="nav-link" href="/PizzaWebsite/pizza_delivery/public/admin/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="/PizzaWebsite/pizza_delivery/public/admin/manage_food.php">
                    <i class="fas fa-utensils"></i> Manage Food
                </a>
                <a class="nav-link" href="/PizzaWebsite/pizza_delivery/public/admin/manage_orders.php">
                    <i class="fas fa-shopping-cart"></i> Manage Orders
                </a>
                <a class="nav-link active" href="/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php">
                    <i class="fas fa-images"></i> Manage Banners
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-edit me-2"></i>Edit Banner</h1>
                <a href="/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Banners
                </a>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Edit Banner Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Banner Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Current Image Preview -->
                                <div class="mb-3">
                                    <label class="form-label">Current Image</label>
                                    <div>
                                        <?php if (!empty($banner['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($banner['image_path']); ?>" 
                                                 alt="Current Banner" 
                                                 class="img-thumbnail" 
                                                 style="max-width: 300px; height: auto;">
                                        <?php else: ?>
                                            <div class="text-muted">No image stored</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Title -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="title" 
                                           name="title" 
                                           value="<?php echo htmlspecialchars($title); ?>" 
                                           required
                                           maxlength="191">
                                    <div class="form-text">Banner title (required)</div>
                                </div>

                                <!-- Link -->
                                <div class="mb-3">
                                    <label for="link" class="form-label">Link URL</label>
                                    <input type="url" 
                                           class="form-control" 
                                           id="link" 
                                           name="link" 
                                           value="<?php echo htmlspecialchars($link); ?>"
                                           placeholder="https://example.com/promo">
                                    <div class="form-text">Optional link when banner is clicked</div>
                                </div>

                                <!-- New Image Upload -->
                                <div class="mb-3">
                                    <label for="banner_image" class="form-label">Replace Image</label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="banner_image" 
                                           name="banner_image" 
                                           accept="image/*">
                                    <div class="form-text">
                                        Leave empty to keep current image. Recommended size: 1400x480px. Uploaded images of any ratio are accepted — larger images will be center-cropped, smaller images will be centered and padded. Max file size: 5MB. Supported formats: JPEG, PNG, GIF, WebP
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- Position -->
                                <div class="mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="position" 
                                           name="position" 
                                           value="<?php echo $position; ?>" 
                                           min="1"
                                           required>
                                    <div class="form-text">Display order (lower = first)</div>
                                </div>

                                <!-- Active Status -->
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="is_active" 
                                               name="is_active" 
                                               <?php echo $is_active ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    <div class="form-text">Show banner on website</div>
                                </div>

                                <!-- Start Date -->
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="datetime-local" 
                                           class="form-control" 
                                           id="start_date" 
                                           name="start_date" 
                                           value="<?php echo $start_date; ?>">
                                    <div class="form-text">When to start showing (optional)</div>
                                </div>

                                <!-- End Date -->
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="datetime-local" 
                                           class="form-control" 
                                           id="end_date" 
                                           name="end_date" 
                                           value="<?php echo $end_date; ?>">
                                    <div class="form-text">When to stop showing (optional)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Banner
                            </button>
                            <a href="/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Option -->
            <div class="card mt-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Permanently delete this banner. This action cannot be undone.</p>
                    <a href="/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php?delete=<?php echo $banner_id; ?>" 
                       class="btn btn-danger"
                       onclick="return confirm('Are you sure you want to delete this banner? This action cannot be undone.')">
                        <i class="fas fa-trash"></i> Delete Banner
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../../includes/footer.php';
?>
