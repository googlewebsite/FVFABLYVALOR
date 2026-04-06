<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

$db = new Database();

// Handle product operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'add') {
        // Handle file uploads
        $images = [];
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $upload_result = uploadFile([
                        'name' => $name,
                        'tmp_name' => $_FILES['images']['tmp_name'][$key],
                        'error' => $_FILES['images']['error'][$key]
                    ], 'uploads/products/');
                    if ($upload_result) {
                        $images[] = $upload_result;
                    }
                }
            }
        }
        
        $video = '';
        if (!empty($_FILES['video']['name'])) {
            $video = uploadFile($_FILES['video'], 'uploads/videos/');
        }
        
        $sizes = json_encode($_POST['sizes'] ?? []);
        $images_json = json_encode($images);
        
        $stmt = $db->prepare("INSERT INTO products (title, description, short_description, sizes, images, video, mrp_price, selling_price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssddd", 
            $_POST['title'], 
            $_POST['description'], 
            $_POST['short_description'], 
            $sizes, 
            $images_json, 
            $video, 
            $_POST['mrp_price'], 
            $_POST['selling_price'], 
            $_POST['stock_quantity']
        );
        $stmt->execute();
        
        header('Location: admin_products.php?success=Product added successfully');
        exit();
    }
    
    if ($action == 'edit') {
        $product_id = $_POST['product_id'];
        
        // Handle file uploads
        $images = [];
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $key => $name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $upload_result = uploadFile([
                        'name' => $name,
                        'tmp_name' => $_FILES['images']['tmp_name'][$key],
                        'error' => $_FILES['images']['error'][$key]
                    ], 'uploads/products/');
                    if ($upload_result) {
                        $images[] = $upload_result;
                    }
                }
            }
        }
        
        $video = '';
        if (!empty($_FILES['video']['name'])) {
            $video = uploadFile($_FILES['video'], 'uploads/videos/');
        }
        
        $sizes = json_encode($_POST['sizes'] ?? []);
        
        if (!empty($images)) {
            $images_json = json_encode($images);
            $stmt = $db->prepare("UPDATE products SET title=?, description=?, short_description=?, sizes=?, images=?, video=?, mrp_price=?, selling_price=?, stock_quantity=? WHERE id=?");
            $stmt->bind_param("ssssssdddi", 
                $_POST['title'], 
                $_POST['description'], 
                $_POST['short_description'], 
                $sizes, 
                $images_json, 
                $video, 
                $_POST['mrp_price'], 
                $_POST['selling_price'], 
                $_POST['stock_quantity'],
                $product_id
            );
        } else {
            $stmt = $db->prepare("UPDATE products SET title=?, description=?, short_description=?, sizes=?, video=?, mrp_price=?, selling_price=?, stock_quantity=? WHERE id=?");
            $stmt->bind_param("ssssdddi", 
                $_POST['title'], 
                $_POST['description'], 
                $_POST['short_description'], 
                $sizes, 
                $video, 
                $_POST['mrp_price'], 
                $_POST['selling_price'], 
                $_POST['stock_quantity'],
                $product_id
            );
        }
        $stmt->execute();
        
        header('Location: admin_products.php?success=Product updated successfully');
        exit();
    }
    
    if ($action == 'delete') {
        $product_id = $_POST['product_id'];
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        header('Location: admin_products.php?success=Product deleted successfully');
        exit();
    }
}

// Get product for editing
$editing_product = null;
if (isset($_GET['edit'])) {
    $product_id = $_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editing_product = $result->fetch_assoc();
}

// Get all products
$result = $db->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - FV FABLY VALOR</title>
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
                    <li><a href="admin_products.php" class="active">📦 Products</a></li>
                    <li><a href="admin_inventory.php">📋 Inventory</a></li>
                    <li><a href="admin_orders.php">🧾 Orders</a></li>
                    <li><a href="admin_payments.php">💳 Payments</a></li>
                    <li><a href="admin_customers.php">👤 Customers</a></li>
                    <li><a href="admin_banners.php">🎨 Banners</a></li>
                    <li><a href="admin_settings.php">⚙️ Settings</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="admin-content" style="flex: 1;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Product Management</h2>
                    <button class="btn btn-primary" onclick="showAddForm()">+ Add Product</button>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Product Form -->
                <div id="productForm" class="card mb-4" style="display: <?php echo $editing_product ? 'block' : 'none'; ?>;">
                    <div class="card-content">
                        <h3><?php echo $editing_product ? 'Edit Product' : 'Add Product'; ?></h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="<?php echo $editing_product ? 'edit' : 'add'; ?>">
                            <?php if ($editing_product): ?>
                                <input type="hidden" name="product_id" value="<?php echo $editing_product['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">Product Title</label>
                                    <input type="text" name="title" class="form-control" required value="<?php echo $editing_product['title'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Short Description</label>
                                    <input type="text" name="short_description" class="form-control" value="<?php echo $editing_product['short_description'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?php echo $editing_product['description'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="grid grid-3">
                                <div class="form-group">
                                    <label class="form-label">MRP Price</label>
                                    <input type="number" name="mrp_price" class="form-control" step="0.01" required value="<?php echo $editing_product['mrp_price'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Selling Price</label>
                                    <input type="number" name="selling_price" class="form-control" step="0.01" required value="<?php echo $editing_product['selling_price'] ?? ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" name="stock_quantity" class="form-control" required value="<?php echo $editing_product['stock_quantity'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Sizes (Hold Ctrl/Cmd to select multiple)</label>
                                <select name="sizes[]" class="form-control" multiple style="height: 100px;">
                                    <option value="XS" <?php echo $editing_product && in_array('XS', json_decode($editing_product['sizes'], true)) ? 'selected' : ''; ?>>XS</option>
                                    <option value="S" <?php echo $editing_product && in_array('S', json_decode($editing_product['sizes'], true)) ? 'selected' : ''; ?>>S</option>
                                    <option value="M" <?php echo $editing_product && in_array('M', json_decode($editing_product['sizes'], true)) ? 'selected' : ''; ?>>M</option>
                                    <option value="L" <?php echo $editing_product && in_array('L', json_decode($editing_product['sizes'], true)) ? 'selected' : ''; ?>>L</option>
                                    <option value="XL" <?php echo $editing_product && in_array('XL', json_decode($editing_product['sizes'], true)) ? 'selected' : ''; ?>>XL</option>
                                    <option value="XXL" <?php echo $editing_product && in_array('XXL', json_decode($editing_product['sizes'], true)) ? 'selected' : ''; ?>>XXL</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Product Images (5 images max)</label>
                                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Product Video (optional)</label>
                                <input type="file" name="video" class="form-control" accept="video/*">
                            </div>
                            
                            <div class="d-flex" style="gap: 1rem;">
                                <button type="submit" class="btn btn-primary"><?php echo $editing_product ? 'Update Product' : 'Add Product'; ?></button>
                                <button type="button" class="btn btn-secondary" onclick="hideForm()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card">
                    <div class="card-content">
                        <h3>All Products</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $images = json_decode($product['images'], true);
                                        if (!empty($images)) {
                                            echo '<img src="' . htmlspecialchars($images[0]) . '" width="80" height="80" style="object-fit: contain; border-radius: 5px; border: 1px solid #ddd;">';
                                        } else {
                                            echo '<div width="80" height="80" style="background: #f0f0f0; border-radius: 5px; border: 1px solid #ddd;"></div>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['title']); ?></td>
                                    <td>
                                        <?php echo formatPrice($product['selling_price']); ?>
                                        <?php if ($product['mrp_price'] > $product['selling_price']): ?>
                                            <br><small style="text-decoration: line-through; color: #999;"><?php echo formatPrice($product['mrp_price']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="btn btn-<?php echo $product['stock_quantity'] > 10 ? 'success' : ($product['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php echo $product['stock_quantity']; ?> <?php echo $product['stock_quantity'] > 10 ? 'In Stock' : ($product['stock_quantity'] > 0 ? 'Low Stock' : 'Out of Stock'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="btn btn-<?php echo $product['status'] == 'active' ? 'success' : 'secondary'; ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex" style="gap: 0.5rem;">
                                            <a href="admin_products.php?edit=<?php echo $product['id']; ?>" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Edit</a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="return confirm('Are you sure?')">Delete</button>
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
            document.getElementById('productForm').style.display = 'block';
            window.scrollTo({ top: document.getElementById('productForm').offsetTop - 100, behavior: 'smooth' });
        }
        
        function hideForm() {
            document.getElementById('productForm').style.display = 'none';
            window.location.href = 'admin_products.php';
        }
        
        <?php if ($editing_product): ?>
            showAddForm();
        <?php endif; ?>
    </script>
</body>
</html>
