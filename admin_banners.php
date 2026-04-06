<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

$db = new Database();

// Handle banner operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'add_banner') {
        // Handle image upload
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $image = uploadFile($_FILES['image'], 'uploads/banners/');
        }
        
        if ($image) {
            $stmt = $db->prepare("INSERT INTO banners (title, image, link, position, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", 
                $_POST['title'], 
                $image, 
                $_POST['link'], 
                $_POST['position'], 
                $_POST['status']
            );
            $stmt->execute();
            
            header('Location: admin_banners.php?success=Banner added successfully');
            exit();
        } else {
            header('Location: admin_banners.php?error=Failed to upload image');
            exit();
        }
    }
    
    if ($action == 'edit_banner') {
        $banner_id = $_POST['banner_id'];
        
        // Handle image upload if new image provided
        $image = $_POST['existing_image'];
        if (!empty($_FILES['image']['name'])) {
            $new_image = uploadFile($_FILES['image'], 'uploads/banners/');
            if ($new_image) {
                $image = $new_image;
            }
        }
        
        $stmt = $db->prepare("UPDATE banners SET title=?, image=?, link=?, position=?, status=? WHERE id=?");
        $stmt->bind_param("sssssi", 
            $_POST['title'], 
            $image, 
            $_POST['link'], 
            $_POST['position'], 
            $_POST['status'],
            $banner_id
        );
        $stmt->execute();
        
        header('Location: admin_banners.php?success=Banner updated successfully');
        exit();
    }
    
    if ($action == 'delete_banner') {
        $banner_id = $_POST['banner_id'];
        
        // Get banner image to delete file
        $stmt = $db->prepare("SELECT image FROM banners WHERE id = ?");
        $stmt->bind_param("i", $banner_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $banner = $result->fetch_assoc();
        
        // Delete banner from database
        $stmt = $db->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->bind_param("i", $banner_id);
        $stmt->execute();
        
        // Delete image file
        if ($banner && file_exists($banner['image'])) {
            unlink($banner['image']);
        }
        
        header('Location: admin_banners.php?success=Banner deleted successfully');
        exit();
    }
    
    if ($action == 'toggle_status') {
        $banner_id = $_POST['banner_id'];
        $status = $_POST['status'];
        
        $stmt = $db->prepare("UPDATE banners SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $banner_id);
        $stmt->execute();
        
        header('Location: admin_banners.php?success=Banner status updated');
        exit();
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';

if ($filter == 'active') {
    $where_clause = "WHERE status = 'active'";
} elseif ($filter == 'inactive') {
    $where_clause = "WHERE status = 'inactive'";
} elseif ($filter == 'home') {
    $where_clause = "WHERE position = 'home'";
} elseif ($filter == 'product') {
    $where_clause = "WHERE position = 'product'";
}

// Get all banners
$banners_query = "SELECT * FROM banners $where_clause ORDER BY created_at DESC";
$result = $db->query($banners_query);
$banners = $result->fetch_all(MYSQLI_ASSOC);

// Get banner statistics
$total_banners = count($banners);
$active_banners = 0;
$home_banners = 0;
$product_banners = 0;

foreach ($banners as $banner) {
    if ($banner['status'] == 'active') {
        $active_banners++;
    }
    if ($banner['position'] == 'home') {
        $home_banners++;
    }
    if ($banner['position'] == 'product') {
        $product_banners++;
    }
}

// Get banner for editing
$editing_banner = null;
if (isset($_GET['edit'])) {
    $banner_id = $_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->bind_param("i", $banner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editing_banner = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banner Management - FV FABLY VALOR</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>FV FABLY VALOR Admin</h1>
                <div>
                    <span>Welcome, Admin</span>
                    <a href="admin_logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="d-flex" style="gap: 2rem;">
            <!-- Sidebar -->
            <div class="admin-sidebar" style="width: 250px;">
                <ul class="admin-menu">
                    <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
                    <li><a href="admin_products.php">📦 Products</a></li>
                    <li><a href="admin_inventory.php">📋 Inventory</a></li>
                    <li><a href="admin_orders.php">🧾 Orders</a></li>
                    <li><a href="admin_payments.php">💳 Payments</a></li>
                    <li><a href="admin_customers.php">👤 Customers</a></li>
                    <li><a href="admin_banners.php" class="active">🎨 Banners</a></li>
                    <li><a href="admin_settings.php">⚙️ Settings</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="admin-content" style="flex: 1;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Banner Management</h2>
                    <button class="btn btn-primary" onclick="showAddForm()">+ Add Banner</button>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Banner Statistics -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_banners; ?></div>
                        <div class="stat-label">Total Banners</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $active_banners; ?></div>
                        <div class="stat-label">Active Banners</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $home_banners; ?></div>
                        <div class="stat-label">Home Page</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $product_banners; ?></div>
                        <div class="stat-label">Product Page</div>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="mb-4">
                    <div class="d-flex" style="gap: 1rem; flex-wrap: wrap;">
                        <a href="admin_banners.php?filter=all" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                            All Banners
                        </a>
                        <a href="admin_banners.php?filter=active" class="btn <?php echo $filter == 'active' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Active
                        </a>
                        <a href="admin_banners.php?filter=inactive" class="btn <?php echo $filter == 'inactive' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Inactive
                        </a>
                        <a href="admin_banners.php?filter=home" class="btn <?php echo $filter == 'home' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Home Page
                        </a>
                        <a href="admin_banners.php?filter=product" class="btn <?php echo $filter == 'product' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Product Page
                        </a>
                    </div>
                </div>

                <!-- Banner Form -->
                <div id="bannerForm" class="card mb-4" style="display: <?php echo $editing_banner ? 'block' : 'none'; ?>;">
                    <div class="card-content">
                        <h3><?php echo $editing_banner ? 'Edit Banner' : 'Add Banner'; ?></h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $editing_banner ? 'edit_banner' : 'add_banner'; ?>">
                            <?php if ($editing_banner): ?>
                                <input type="hidden" name="banner_id" value="<?php echo $editing_banner['id']; ?>">
                                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editing_banner['image']); ?>">
                            <?php endif; ?>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">Banner Title</label>
                                    <input type="text" name="title" class="form-control" required value="<?php echo $editing_banner['title'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Position</label>
                                    <select name="position" class="form-control" required>
                                        <option value="home" <?php echo ($editing_banner['position'] ?? '') == 'home' ? 'selected' : ''; ?>>Home Page</option>
                                        <option value="product" <?php echo ($editing_banner['position'] ?? '') == 'product' ? 'selected' : ''; ?>>Product Page</option>
                                        <option value="all" <?php echo ($editing_banner['position'] ?? '') == 'all' ? 'selected' : ''; ?>>All Pages</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Link (Optional)</label>
                                <input type="text" name="link" class="form-control" placeholder="https://example.com/product" value="<?php echo $editing_banner['link'] ?? ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Banner Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*" <?php echo !$editing_banner ? 'required' : ''; ?>>
                                <?php if ($editing_banner): ?>
                                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                                        Current: <img src="<?php echo htmlspecialchars($editing_banner['image']); ?>" width="100" style="vertical-align: middle; margin-left: 0.5rem;">
                                    </small>
                                <?php endif; ?>
                                <small style="color: #666; display: block; margin-top: 0.5rem;">
                                    Recommended size: 1400x500px. Supported formats: JPG, PNG, GIF
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="active" <?php echo ($editing_banner['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($editing_banner['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="d-flex" style="gap: 1rem;">
                                <button type="submit" class="btn btn-primary"><?php echo $editing_banner ? 'Update Banner' : 'Add Banner'; ?></button>
                                <button type="button" class="btn btn-secondary" onclick="hideForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Banners Table -->
                <div class="card">
                    <div class="card-content">
                        <h3>All Banners</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Title</th>
                                    <th>Position</th>
                                    <th>Link</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banners as $banner): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($banner['image']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>" width="120" height="60" style="object-fit: cover; border-radius: 5px;">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($banner['title']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php echo ucfirst($banner['position']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($banner['link']): ?>
                                            <a href="<?php echo htmlspecialchars($banner['link']); ?>" target="_blank" style="color: #007bff; text-decoration: none;">
                                                <?php echo strlen($banner['link']) > 30 ? substr($banner['link'], 0, 30) . '...' : $banner['link']; ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">No link</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="btn btn-<?php echo $banner['status'] == 'active' ? 'success' : 'secondary'; ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php echo ucfirst($banner['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($banner['created_at'])); ?></div>
                                        <div style="color: #666; font-size: 0.8rem;"><?php echo date('h:i A', strtotime($banner['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex" style="gap: 0.5rem; flex-direction: column;">
                                            <a href="admin_banners.php?edit=<?php echo $banner['id']; ?>" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Edit</a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $banner['status'] == 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn btn-<?php echo $banner['status'] == 'active' ? 'secondary' : 'success'; ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                    <?php echo $banner['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_banner">
                                                <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="return confirm('Are you sure you want to delete this banner?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddForm() {
            document.getElementById('bannerForm').style.display = 'block';
            window.scrollTo({ top: document.getElementById('bannerForm').offsetTop - 100, behavior: 'smooth' });
        }
        
        function hideForm() {
            document.getElementById('bannerForm').style.display = 'none';
            window.location.href = 'admin_banners.php';
        }
        
        <?php if ($editing_banner): ?>
            showAddForm();
        <?php endif; ?>
        
        // Preview image on upload
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.querySelector('input[name="image"]');
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            // Create preview
                            let preview = document.querySelector('#imagePreview');
                            if (!preview) {
                                preview = document.createElement('div');
                                preview.id = 'imagePreview';
                                preview.style.cssText = 'margin-top: 1rem; padding: 1rem; border: 2px dashed #ddd; border-radius: 8px; text-align: center;';
                                imageInput.parentNode.appendChild(preview);
                            }
                            preview.innerHTML = `
                                <img src="${e.target.result}" style="max-width: 300px; max-height: 150px; border-radius: 5px;">
                                <p style="margin-top: 0.5rem; color: #666; font-size: 0.9rem;">New image preview</p>
                            `;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>
