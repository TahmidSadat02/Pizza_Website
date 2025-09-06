<?php
/**
 * Add Food Item Page
 */

// Include header
require_once '../../includes/header.php';

# Check if user is admin
if (!is_admin()) {
    redirect('/PizzaWebsite/pizza_delivery/public/index.php');
}

// Initialize variables
$name = $description = $price = $category = '';
$available = 1;
$errors = [];

// Create uploads directory if it doesn't exist
$upload_dir = __DIR__ . '/../uploads/food/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = clean_input($_POST['name'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $category = clean_input($_POST['category'] ?? '');
    $available = isset($_POST['available']) ? 1 : 0;
    
    // Validate input
    if (empty($name)) {
        $errors[] = 'Food name is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if ($price <= 0) {
        $errors[] = 'Price must be greater than zero';
    }
    
    if (empty($category)) {
        $errors[] = 'Category is required';
    }
    
    // Handle image upload
    $image_url = '/PizzaWebsite/pizza_delivery/public/uploads/food/default-food.jpg'; // Default image
    
    if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['food_image']['tmp_name'];
        $file_name = $_FILES['food_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Debug logging
        debug_log([
            'file_name' => $file_name,
            'file_tmp' => $file_tmp,
            'file_size' => $_FILES['food_image']['size'],
            'file_ext' => $file_ext,
            'upload_dir' => $upload_dir
        ], 'Image Upload Debug');
        
        // Check if file is an actual image
        $check = getimagesize($file_tmp);
        if ($check === false) {
            $errors[] = 'File is not an image';
            debug_log('File is not an image', 'Upload Error');
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['food_image']['size'] > 5000000) {
            $errors[] = 'File is too large (max 5MB)';
            debug_log('File too large: ' . $_FILES['food_image']['size'], 'Upload Error');
        }
        
        // Allow certain file formats
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_extensions)) {
            $errors[] = 'Only JPG, JPEG, PNG & GIF files are allowed';
            debug_log('Invalid file extension: ' . $file_ext, 'Upload Error');
        }
        
        // If no errors, upload the file
        if (empty($errors)) {
            // Generate unique filename
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            debug_log('Attempting upload to: ' . $upload_path, 'Upload Debug');
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $image_url = '/PizzaWebsite/pizza_delivery/public/uploads/food/' . $new_filename;
                debug_log('Upload successful: ' . $image_url, 'Upload Success');
            } else {
                $errors[] = 'Failed to upload image';
                debug_log('move_uploaded_file failed from ' . $file_tmp . ' to ' . $upload_path, 'Upload Error');
            }
        }
    } else if ($_FILES['food_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // If there was an error uploading the file
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $error_message = $upload_errors[$_FILES['food_image']['error']] ?? 'Unknown error';
        $errors[] = 'Image upload error: ' . $error_message;
        debug_log('Upload error code: ' . $_FILES['food_image']['error'] . ' - ' . $error_message, 'Upload Error');
    }
    
    // If no errors, insert food item
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO food_items (name, description, price, category, image_url, available) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $description, $price, $category, $image_url, $available]);
            
            $_SESSION['success_message'] = 'Food item added successfully';
            redirect('/PizzaWebsite/pizza_delivery/public/admin/manage_food.php');
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get existing categories for dropdown
$stmt = $pdo->query("SELECT DISTINCT category FROM food_items ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Add New Food Item</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Food Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($description); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="price" class="form-label">Price ($)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <div class="input-group">
                                <select class="form-select" id="category" name="category">
                                    <option value="" disabled <?php echo empty($category) ? 'selected' : ''; ?>>Select a category or type a new one</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" class="form-control" id="new_category" placeholder="Or type a new category">
                                <button class="btn btn-outline-secondary" type="button" id="use_new_category">Use This</button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="food_image" class="form-label">Food Image</label>
                            <input type="file" class="form-control" id="food_image" name="food_image">
                            <small class="form-text text-muted">Upload an image of the food item (JPG, PNG, GIF, max 5MB)</small>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="available" name="available" <?php echo $available ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="available">Available for ordering</label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="manage_food.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Food Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Script to handle new category input
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category');
        const newCategoryInput = document.getElementById('new_category');
        const useNewCategoryBtn = document.getElementById('use_new_category');
        
        useNewCategoryBtn.addEventListener('click', function() {
            const newCategory = newCategoryInput.value.trim();
            if (newCategory) {
                // Check if category already exists in dropdown
                let exists = false;
                for (let i = 0; i < categorySelect.options.length; i++) {
                    if (categorySelect.options[i].value === newCategory) {
                        exists = true;
                        categorySelect.value = newCategory;
                        break;
                    }
                }
                
                // If not exists, add it
                if (!exists) {
                    const option = new Option(newCategory, newCategory);
                    categorySelect.add(option);
                    categorySelect.value = newCategory;
                }
                
                newCategoryInput.value = '';
            }
        });
    });
</script>

<?php
// Include footer
require_once '../../includes/footer.php';
?>