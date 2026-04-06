<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

$db = new Database();

// Get order details
$order_id = $_GET['id'];
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header('Location: admin_orders.php');
    exit();
}

// Get customer details if customer_id exists
$customer = null;
if ($order['customer_id']) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $order['customer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
}

// Parse product details
$product_details = json_decode($order['product_details'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - FV FABLY VALOR</title>
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
                    <li><a href="admin_orders.php" class="active">🧾 Orders</a></li>
                    <li><a href="admin_payments.php">💳 Payments</a></li>
                    <li><a href="admin_customers.php">👤 Customers</a></li>
                    <li><a href="admin_banners.php">🎨 Banners</a></li>
                    <li><a href="admin_settings.php">⚙️ Settings</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="admin-content" style="flex: 1;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Order Details</h2>
                    <div>
                        <a href="admin_orders.php" class="btn btn-secondary">← Back to Orders</a>
                        <a href="shipping_label.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-primary">📄 Shipping Label</a>
                    </div>
                </div>

                <div class="grid grid-2">
                    <!-- Order Information -->
                    <div class="card">
                        <div class="card-content">
                            <h3>Order Information</h3>
                            <table class="table" style="margin-top: 1rem;">
                                <tr>
                                    <th>Order ID:</th>
                                    <td><strong><?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Order Date:</th>
                                    <td><?php echo date('F j, Y h:i A', strtotime($order['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Amount:</th>
                                    <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Order Status:</th>
                                    <td>
                                        <span class="btn btn-<?php echo $order['status'] == 'delivered' ? 'success' : ($order['status'] == 'cancelled' ? 'danger' : 'warning'); ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Payment Status:</th>
                                    <td>
                                        <span class="btn btn-<?php echo $order['payment_status'] == 'verified' ? 'success' : ($order['payment_status'] == 'failed' ? 'danger' : 'warning'); ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Customer Information -->
                    <div class="card">
                        <div class="card-content">
                            <h3>Customer Information</h3>
                            <table class="table" style="margin-top: 1rem;">
                                <tr>
                                    <th>Name:</th>
                                    <td><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                                </tr>
                                <?php if ($customer): ?>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Address:</th>
                                    <td><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="card mt-4">
                    <div class="card-content">
                        <h3>Product Details</h3>
                        <table class="table" style="margin-top: 1rem;">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Size</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                foreach ($product_details as $item): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        <?php if (!empty($item['short_description'])): ?>
                                            <br><small style="color: #666;"><?php echo htmlspecialchars($item['short_description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['size']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatPrice($item['price']); ?></td>
                                    <td><strong><?php echo formatPrice($item_total); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th colspan="4">Subtotal:</th>
                                    <td><?php echo formatPrice($subtotal); ?></td>
                                </tr>
                                <tr>
                                    <th colspan="4">Total:</th>
                                    <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Actions -->
                <div class="card mt-4">
                    <div class="card-content">
                        <h3>Order Actions</h3>
                        <form method="POST" action="admin_orders.php">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Update Order Status:</label>
                                <select name="status" class="form-control" style="width: auto;">
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
