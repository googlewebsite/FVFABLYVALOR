<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

$db = new Database();

// Handle stock updates
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'update_stock') {
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        $stmt = $db->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $product_id);
        $stmt->execute();
        
        header('Location: admin_inventory.php?success=Stock updated successfully');
        exit();
    }
    
    if ($action == 'bulk_update') {
        $product_ids = $_POST['product_ids'];
        $quantities = $_POST['quantities'];
        
        foreach ($product_ids as $index => $product_id) {
            $quantity = $quantities[$index];
            $stmt = $db->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $stmt->bind_param("ii", $quantity, $product_id);
            $stmt->execute();
        }
        
        header('Location: admin_inventory.php?success=Bulk stock updated successfully');
        exit();
    }
}

// Get all products with stock information
$result = $db->query("SELECT * FROM products ORDER BY stock_quantity ASC, title ASC");
$products = $result->fetch_all(MYSQLI_ASSOC);

// Calculate inventory statistics
$total_products = count($products);
$in_stock = 0;
$low_stock = 0;
$out_of_stock = 0;

foreach ($products as $product) {
    if ($product['stock_quantity'] > 10) {
        $in_stock++;
    } elseif ($product['stock_quantity'] > 0) {
        $low_stock++;
    } else {
        $out_of_stock++;
    }
}

// Filter products
$filter = $_GET['filter'] ?? 'all';
$filtered_products = $products;

if ($filter == 'low_stock') {
    $filtered_products = array_filter($products, function($product) {
        return $product['stock_quantity'] > 0 && $product['stock_quantity'] <= 10;
    });
} elseif ($filter == 'out_of_stock') {
    $filtered_products = array_filter($products, function($product) {
        return $product['stock_quantity'] == 0;
    });
} elseif ($filter == 'in_stock') {
    $filtered_products = array_filter($products, function($product) {
        return $product['stock_quantity'] > 10;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - FV FABLY VALOR</title>
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
                    <li><a href="admin_inventory.php" class="active">📋 Inventory</a></li>
                    <li><a href="admin_orders.php">🧾 Orders</a></li>
                    <li><a href="admin_payments.php">💳 Payments</a></li>
                    <li><a href="admin_customers.php">👤 Customers</a></li>
                    <li><a href="admin_banners.php">🎨 Banners</a></li>
                    <li><a href="admin_settings.php">⚙️ Settings</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="admin-content" style="flex: 1;">
                <h2>Inventory Management</h2>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Inventory Statistics -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_products; ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #28a745;"><?php echo $in_stock; ?></div>
                        <div class="stat-label">In Stock (>10)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #ffc107;"><?php echo $low_stock; ?></div>
                        <div class="stat-label">Low Stock (1-10)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #dc3545;"><?php echo $out_of_stock; ?></div>
                        <div class="stat-label">Out of Stock</div>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="mb-4">
                    <div class="d-flex" style="gap: 1rem; flex-wrap: wrap;">
                        <a href="admin_inventory.php?filter=all" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                            All Products (<?php echo $total_products; ?>)
                        </a>
                        <a href="admin_inventory.php?filter=in_stock" class="btn <?php echo $filter == 'in_stock' ? 'btn-primary' : 'btn-secondary'; ?>">
                            In Stock (<?php echo $in_stock; ?>)
                        </a>
                        <a href="admin_inventory.php?filter=low_stock" class="btn <?php echo $filter == 'low_stock' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Low Stock (<?php echo $low_stock; ?>)
                        </a>
                        <a href="admin_inventory.php?filter=out_of_stock" class="btn <?php echo $filter == 'out_of_stock' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Out of Stock (<?php echo $out_of_stock; ?>)
                        </a>
                    </div>
                </div>

                <!-- Bulk Update Form -->
                <div class="card mb-4">
                    <div class="card-content">
                        <h3>Bulk Stock Update</h3>
                        <form method="POST" id="bulkUpdateForm">
                            <input type="hidden" name="action" value="bulk_update">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="submit" class="btn btn-success">Update Selected Items</button>
                                    <button type="button" class="btn btn-secondary" onclick="selectAll()">Select All</button>
                                    <button type="button" class="btn btn-secondary" onclick="deselectAll()">Deselect All</button>
                                </div>
                                <span id="selectedCount">0 items selected</span>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="card">
                    <div class="card-content">
                        <h3>Inventory Overview</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                    </th>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filtered_products as $product): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_products[]" value="<?php echo $product['id']; ?>" class="product-checkbox" onchange="updateSelectedCount()">
                                        <input type="hidden" name="product_ids[]" value="<?php echo $product['id']; ?>" form="bulkUpdateForm" style="display: none;" class="bulk-product-id" disabled>
                                        <input type="number" name="quantities[]" value="<?php echo $product['stock_quantity']; ?>" form="bulkUpdateForm" style="display: none;" class="bulk-quantity" disabled>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center" style="gap: 1rem;">
                                            <?php 
                                            $images = json_decode($product['images'], true);
                                            if (!empty($images)) {
                                                echo '<img src="' . htmlspecialchars($images[0]) . '" width="40" height="40" style="object-fit: cover; border-radius: 5px;">';
                                            } else {
                                                echo '<div width="40" height="40" style="background: #f0f0f0; border-radius: 5px;"></div>';
                                            }
                                            ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($product['title']); ?></strong><br>
                                                <small style="color: #666;"><?php echo htmlspecialchars($product['short_description']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_stock">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $product['stock_quantity']; ?>" class="form-control" style="width: 80px; display: inline-block;" min="0">
                                            <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; margin-left: 0.5rem;">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="btn btn-<?php echo $product['stock_quantity'] > 10 ? 'success' : ($product['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php 
                                            if ($product['stock_quantity'] > 10) {
                                                echo '✓ In Stock';
                                            } elseif ($product['stock_quantity'] > 0) {
                                                echo '⚠ Low Stock';
                                            } else {
                                                echo '✗ Out of Stock';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo formatPrice($product['selling_price']); ?>
                                        <?php if ($product['mrp_price'] > $product['selling_price']): ?>
                                            <br><small style="text-decoration: line-through; color: #999;"><?php echo formatPrice($product['mrp_price']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($product['updated_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex" style="gap: 0.5rem;">
                                            <a href="admin_products.php?edit=<?php echo $product['id']; ?>" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Edit</a>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" target="_blank" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">View</a>
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
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const productCheckboxes = document.querySelectorAll('.product-checkbox');
            const bulkProductIds = document.querySelectorAll('.bulk-product-id');
            const bulkQuantities = document.querySelectorAll('.bulk-quantity');
            
            productCheckboxes.forEach((checkbox, index) => {
                checkbox.checked = selectAllCheckbox.checked;
                bulkProductIds[index].disabled = !selectAllCheckbox.checked;
                bulkQuantities[index].disabled = !selectAllCheckbox.checked;
            });
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            const bulkProductIds = document.querySelectorAll('.bulk-product-id');
            const bulkQuantities = document.querySelectorAll('.bulk-quantity');
            
            document.getElementById('selectedCount').textContent = checkedBoxes.length + ' items selected';
            
            // Enable/disable bulk form inputs based on selection
            document.querySelectorAll('.product-checkbox').forEach((checkbox, index) => {
                bulkProductIds[index].disabled = !checkbox.checked;
                bulkQuantities[index].disabled = !checkbox.checked;
            });
        }
        
        function selectAll() {
            document.getElementById('selectAllCheckbox').checked = true;
            toggleSelectAll();
        }
        
        function deselectAll() {
            document.getElementById('selectAllCheckbox').checked = false;
            toggleSelectAll();
        }
        
        // Initialize
        updateSelectedCount();
    </script>
</body>
</html>
