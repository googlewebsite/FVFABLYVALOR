<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

$db = new Database();

// Handle order status updates
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'update_status') {
        $order_id = $_POST['order_id'];
        $status = $_POST['status'];
        
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $order_id);
        $stmt->execute();
        
        // If status was changed to delivered, redirect to pending filter
        if ($status == 'delivered') {
            header('Location: admin_orders.php?filter=pending&success=Order marked as delivered and removed from pending list');
        } else {
            header('Location: admin_orders.php?success=Order status updated successfully');
        }
        exit();
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';

if ($filter == 'pending') {
    $where_clause = "WHERE o.status = 'pending'";
} elseif ($filter == 'confirmed') {
    $where_clause = "WHERE o.status = 'confirmed'";
} elseif ($filter == 'processing') {
    $where_clause = "WHERE o.status = 'processing'";
} elseif ($filter == 'shipped') {
    $where_clause = "WHERE o.status = 'shipped'";
} elseif ($filter == 'delivered') {
    $where_clause = "WHERE o.status = 'delivered'";
} elseif ($filter == 'cancelled') {
    $where_clause = "WHERE o.status = 'cancelled'";
}

// Get orders with customer information
$orders_query = "
    SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.id 
    $where_clause 
    ORDER BY o.created_at DESC
";
$result = $db->query($orders_query);
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Get order statistics
$stats_query = "
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(total_amount), 0) as total_amount
    FROM orders 
    GROUP BY status
";
$stats_result = $db->query($stats_query);
$stats = [];
$total_revenue = 0;

while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['status']] = [
        'count' => $row['count'],
        'amount' => $row['total_amount']
    ];
    if (in_array($row['status'], ['delivered', 'shipped'])) {
        $total_revenue += $row['total_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - FV FABLY VALOR</title>
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
                <h2>Order Management</h2>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Order Statistics -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo array_sum(array_column($stats, 'count')); ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending']['count'] ?? 0; ?></div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #17a2b8;"><?php echo $stats['processing']['count'] ?? 0; ?></div>
                        <div class="stat-label">Processing</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #28a745;"><?php echo $stats['delivered']['count'] ?? 0; ?></div>
                        <div class="stat-label">Delivered</div>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="mb-4">
                    <div class="d-flex" style="gap: 1rem; flex-wrap: wrap;">
                        <a href="admin_orders.php?filter=all" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                            All Orders
                        </a>
                        <a href="admin_orders.php?filter=pending" class="btn <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Pending (<?php echo $stats['pending']['count'] ?? 0; ?>)
                        </a>
                        <a href="admin_orders.php?filter=confirmed" class="btn <?php echo $filter == 'confirmed' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Confirmed (<?php echo $stats['confirmed']['count'] ?? 0; ?>)
                        </a>
                        <a href="admin_orders.php?filter=processing" class="btn <?php echo $filter == 'processing' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Processing (<?php echo $stats['processing']['count'] ?? 0; ?>)
                        </a>
                        <a href="admin_orders.php?filter=shipped" class="btn <?php echo $filter == 'shipped' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Shipped (<?php echo $stats['shipped']['count'] ?? 0; ?>)
                        </a>
                        <a href="admin_orders.php?filter=delivered" class="btn <?php echo $filter == 'delivered' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Delivered (<?php echo $stats['delivered']['count'] ?? 0; ?>)
                        </a>
                        <a href="admin_orders.php?filter=cancelled" class="btn <?php echo $filter == 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Cancelled (<?php echo $stats['cancelled']['count'] ?? 0; ?>)
                        </a>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-content">
                        <h3>Orders Overview</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Products</th>
                                    <th>Amount</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['order_id']); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                            <small style="color: #666;">
                                                📱 <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                                                📧 <?php echo htmlspecialchars($order['customer_email']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $product_details = json_decode($order['product_details'], true);
                                        if ($product_details) {
                                            foreach ($product_details as $item) {
                                                echo '<div style="margin-bottom: 0.5rem;">';
                                                echo '<strong>' . htmlspecialchars($item['name']) . '</strong><br>';
                                                echo '<small>Size: ' . htmlspecialchars($item['size']) . ' | Qty: ' . $item['quantity'] . '</small>';
                                                echo '</div>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="btn btn-<?php echo $order['payment_status'] == 'verified' ? 'success' : ($order['payment_status'] == 'failed' ? 'danger' : 'warning'); ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" class="form-control" style="width: auto; display: inline-block;" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?><br>
                                        <small><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex" style="gap: 0.5rem; flex-direction: column;">
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">View Details</a>
                                            <a href="shipping_label.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">📄 Shipping Label</a>
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
</body>
</html>
