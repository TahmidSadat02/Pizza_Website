<?php
/**
 * Admin Banner Management
 */

// Include header
require_once '../../includes/header.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to access this page';
    redirect('/PizzaWebsite/pizza_delivery/public/index.php');
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $banner_id = (int)$_GET['delete'];
    
    try {
        // Get banner info first to delete file
        $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
        $stmt->execute([$banner_id]);
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($banner) {
            // Delete from database
            $delete_stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
            $delete_stmt->execute([$banner_id]);
            
            // Delete image file if it exists
            if (!empty($banner['image_path'])) {
                $image_path = $_SERVER['DOCUMENT_ROOT'] . $banner['image_path'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            $_SESSION['success_message'] = 'Banner deleted successfully!';
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error deleting banner: ' . $e->getMessage();
    }
    
    redirect('/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php');
}

// Handle toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $banner_id = (int)$_GET['toggle'];
    
    try {
        $stmt = $pdo->prepare("UPDATE banners SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$banner_id]);
        $_SESSION['success_message'] = 'Banner status updated successfully!';
    } catch (PDOException $e) {
        $_SESSION['error_message'] = 'Error updating banner status: ' . $e->getMessage();
    }
    
    redirect('/PizzaWebsite/pizza_delivery/public/admin/manage_banners.php');
}

// Get all banners
// Get all banners
try {
    $stmt = $pdo->query("SELECT * FROM banners ORDER BY position ASC, created_at DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $banners = [];
    $error_message = 'Error fetching banners: ' . $e->getMessage();
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
                <h1><i class="fas fa-images me-2"></i>Banner Management</h1>
                <a href="/PizzaWebsite/pizza_delivery/public/admin/add_banner.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Banner
                </a>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Banners Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Current Banners</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($banners)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-images fa-3x text-muted mb-3"></i>
                            <h5>No banners found</h5>
                            <p class="text-muted">Add your first banner to get started.</p>
                            <a href="/PizzaWebsite/pizza_delivery/public/admin/add_banner.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Banner
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($banners as $banner): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($banner['image_path'])): ?>
                                                    <img src="<?php echo htmlspecialchars($banner['image_path']); ?>" 
                                                         alt="Banner" 
                                                         class="img-thumbnail" 
                                                         style="width: 80px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="text-muted small">No image</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($banner['title'] ?: 'Untitled'); ?></strong>
                                                <?php if ($banner['link']): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fas fa-link"></i> 
                                                        <?php echo htmlspecialchars(substr($banner['link'], 0, 30)); ?>
                                                        <?php if (strlen($banner['link']) > 30) echo '...'; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $banner['position']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($banner['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($banner['start_date']): ?>
                                                    <small><?php echo date('M j, Y', strtotime($banner['start_date'])); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">No limit</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($banner['end_date']): ?>
                                                    <small><?php echo date('M j, Y', strtotime($banner['end_date'])); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">No limit</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($banner['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/PizzaWebsite/pizza_delivery/public/admin/edit_banner.php?id=<?php echo $banner['id']; ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?toggle=<?php echo $banner['id']; ?>" 
                                                       class="btn btn-outline-secondary" 
                                                       title="Toggle Status"
                                                       onclick="return confirm('Toggle banner status?')">
                                                        <i class="fas fa-power-off"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $banner['id']; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       title="Delete"
                                                       onclick="return confirm('Are you sure you want to delete this banner? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-lightbulb"></i> Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li><strong>Position:</strong> Lower numbers appear first in the carousel</li>
                        <li><strong>Date Range:</strong> Leave dates empty for permanent banners</li>
                        <li><strong>Image Size:</strong> Recommended 1200x300px for best display</li>
                        <li><strong>Active Status:</strong> Only active banners show on the website</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../../includes/footer.php';
?>
