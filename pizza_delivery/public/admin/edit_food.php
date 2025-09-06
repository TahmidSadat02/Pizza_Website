<?php
/**
 * Edit Food Item Page
 */

// Include header
require_once '../../includes/header.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to access this page';
    redirect('../index.php');
}

// Check if food ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = 'Food ID is required';
    redirect('manage_food.php');
}

$food_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$food_id) {
    $_SESSION['error_message'] = 'Invalid Food ID';
    redirect('manage_food.php');
}

// Initialize variables
$errors = [];

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/food/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Get food item details
try {
    $stmt = $pdo->prepare("SELECT * FROM food_items WHERE food_id = ?");
    $stmt->execute([$food_id]);
    $food = $stmt->fetch();
    
    if (!$food) {
        $_SESSION['error_message'] = 'Food item not found';
        redirect('manage_food.php');
    }
    
    // Set variables from database
    $name = $food['name'];
    $description = $food['description'];
    $price = $food['price'];
    $category = $food['category'];
    $image_url = $food['image_url'];
    $available = $food['available'];
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
    redirect('manage_food.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = clean_input($_POST['name'] ?? '');
    $description = clean_input($_POST['description'] ?? '');
    $price = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    $category = clean_input($_POST['category'] ?? '');
    $available = isset($_POST['available']) ? 1 : 0;
    
    // Keep existing image URL by default
    $image_url = $food['image_url'];
    
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
    
    // Handle image upload if a new image is provided
    if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['food_image']['tmp_name'];
        $file_name = $_FILES['food_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check if file is an actual image
        $check = getimagesize($file_tmp);
        if ($check === false) {
            $errors[] = 'File is not an image';
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['food_image']['size'] > 5000000) {
            $errors[] = 'File is too large (max 5MB)';
        }
        
        // Allow certain file formats
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_ext, $allowed_extensions)) {
            $errors[] = 'Only JPG, JPEG, PNG & GIF files are allowed';
        }
        
        // If no errors, upload the file
        if (empty($errors)) {
            // Generate unique filename
            $new_filename = uniqid() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Delete old image if it's not the default image
                if ($image_url !== 'default-food.jpg' && file_exists('../' . $image_url)) {
                    unlink('../' . $image_url);
                }
                
                $image_url = 'uploads/food/' . $new_filename;
            } else {
                $errors[] = 'Failed to upload image';
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
        $errors[] = 'Image upload error: ' . ($upload_errors[$_FILES['food_image']['error']] ?? 'Unknown error');
    }
    
    // If no errors, update food item
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE food_items 
                 SET name = ?, description = ?, price = ?, category = ?, image_url = ?, available = ? 
                 WHERE food_id = ?"
            );
            $stmt->execute([$name, $description, $price, $category, $image_url, $available, $food_id]);
            
            $_SESSION['success_message'] = 'Food item updated successfully';
            redirect('manage_food.php');
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
                    <h4 class="mb-0">Edit Food Item</h4>
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
                                    <option value="" disabled>Select a category or type a new one</option>
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
                            <small class="form-text text-muted">Upload a new image (JPG, PNG, GIF, max 5MB) or leave blank to keep current image</small>
                            <?php if ($image_url): ?>
                                <div class="mt-2">
                                    <p>Current image:</p>
                                    <img src="../<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($name); ?>" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="available" name="available" <?php echo $available ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="available">Available for ordering</label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="manage_food.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Food Item</button>
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