<?php
/**
 * Add New Banner
 */

// Include header
require_once '../../includes/header.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to access this page';
    redirect('/PizzaWebsite/pizza_delivery/public/index.php');
}

$errors = [];
$title = '';
$link = '';
$start_date = '';
$end_date = '';
$position = 1;
$is_active = true;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_input($_POST['title'] ?? '');
    $link = clean_input($_POST['link'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $position = (int)($_POST['position'] ?? 1);
    $is_active = isset($_POST['is_active']);
    
    // Validate form
    if (empty($_FILES['banner_image']['name'])) {
        $errors[] = 'Please select an image file';
    }
    
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
    
    // Validate and handle file upload
    if (empty($errors) && !empty($_FILES['banner_image']['name'])) {
        $upload_dir = __DIR__ . '/../uploads/banners/';
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
            $web_path = '/PizzaWebsite/pizza_delivery/public/uploads/banners/' . $filename;

            // Ensure upload directory exists
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $errors[] = 'Server error: cannot create upload directory';
                }
            }

            // Check if directory is writable
            if (empty($errors) && !is_writable($upload_dir)) {
                $errors[] = 'Server error: upload directory is not writable';
                debug_log(['upload_dir' => $upload_dir, 'perms' => substr(sprintf('%o', fileperms($upload_dir)), -4)], 'add_banner upload dir not writable');
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
                debug_log($_FILES['banner_image'], 'add_banner upload error');
            }

            // Ensure tmp file is real uploaded file
            if (empty($errors) && !is_uploaded_file($_FILES['banner_image']['tmp_name'])) {
                $errors[] = 'Upload failed: temporary file is not valid';
                debug_log($_FILES['banner_image'], 'add_banner not is_uploaded_file');
            }

            // Attempt move, then fallback to copy
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
                }

                // Insert into database
                if (empty($errors)) {
                    try {
                        $stmt = $pdo->prepare(
                            "INSERT INTO banners (title, image_path, link, start_date, end_date, is_active, position) VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );

                        $stmt->execute([
                            $title,
                            $web_path,
                            $link ?: null,
                            $start_date ?: null,
                            $end_date ?: null,
                            $is_active ? 1 : 0,
                            $position
                        ]);

                        $_SESSION['success_message'] = 'Banner added successfully!';
                        redirect('/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php');

                    } catch (PDOException $e) {
                        if (file_exists($target_path)) {
                            unlink($target_path);
                        }
                        $errors[] = 'Database error: ' . $e->getMessage();
                    }
                }
            } elseif (empty($errors)) {
                // Both move and copy failed — log details and inform user
                $dbg = [
                    'tmp_name' => $_FILES['banner_image']['tmp_name'] ?? null,
                    'target_path' => $target_path,
                    'is_uploaded_file' => is_uploaded_file($_FILES['banner_image']['tmp_name']) ? true : false,
                    'upload_error' => $_FILES['banner_image']['error'] ?? null,
                    'upload_dir_perms' => @substr(sprintf('%o', fileperms($upload_dir)), -4)
                ];
                debug_log($dbg, 'add_banner upload move+copy failed');
                $errors[] = 'Failed to process the uploaded image. Please check server permissions and try again.';
            }
        }
    }
}

// Get highest position for default
try {
    $stmt = $pdo->query("SELECT MAX(position) as max_pos FROM banners");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $default_position = ($result['max_pos'] ?? 0) + 1;
    if ($position == 1) $position = $default_position;
} catch (PDOException $e) {
    $default_position = 1;
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
                <h1><i class="fas fa-plus me-2"></i>Add New Banner</h1>
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

            <!-- Add Banner Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Banner Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
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

                                <!-- Image Upload -->
                                <div class="mb-3">
                                    <label for="banner_image" class="form-label">Banner Image *</label>
                                    <input type="file" 
                                           class="form-control" 
                                           id="banner_image" 
                                           name="banner_image" 
                                           accept="image/*"
                                           required>
                                    <div class="form-text">
                                        Recommended size: 1400x480px. Uploaded images of any ratio are accepted — larger images will be center-cropped, smaller images will be centered and padded. Max file size: 5MB. Supported formats: JPEG, PNG, GIF, WebP
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
                                <i class="fas fa-save"></i> Add Banner
                            </button>
                            <a href="/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Note -->
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle"></i> 
                <strong>Note:</strong> After adding the banner, it will appear in the carousel on the homepage 
                if it's set to active and within the date range (if specified).
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../../includes/footer.php';
?>
