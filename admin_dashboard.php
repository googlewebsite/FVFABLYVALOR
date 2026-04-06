<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

// Get dashboard statistics
$db = new Database();

// Daily revenue (today)
$today = date('Y-m-d');
$result = $db->query("SELECT COALESCE(SUM(total_amount), 0) as daily_revenue FROM orders WHERE DATE(created_at) = '$today' AND payment_status = 'verified'");
$daily_revenue = $result->fetch_assoc()['daily_revenue'];

// Monthly revenue
$month = date('Y-m');
$result = $db->query("SELECT COALESCE(SUM(total_amount), 0) as monthly_revenue FROM orders WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month' AND payment_status = 'verified'");
$monthly_revenue = $result->fetch_assoc()['monthly_revenue'];

// Total orders
$result = $db->query("SELECT COUNT(*) as total_orders FROM orders");
$total_orders = $result->fetch_assoc()['total_orders'];

// Total products
$products_result = $db->query("SELECT COUNT(*) as total_products FROM products");
if ($products_result) {
    $total_products = $products_result->fetch_assoc()['total_products'];
} else {
    $total_products = 0;
}

// Top selling products
$top_products_result = $db->query("
    SELECT p.title, SUM(oi.quantity) as total_sold 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    GROUP BY p.id, p.title 
    ORDER BY total_sold DESC 
    LIMIT 5
");
if ($top_products_result) {
    $top_products = $top_products_result->fetch_all(MYSQLI_ASSOC);
} else {
    $top_products = [];
}

// Sales data for chart (last 7 days)
$sales_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $result = $db->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE DATE(created_at) = '$date' AND payment_status = 'verified'");
    $revenue = $result->fetch_assoc()['revenue'];
    $sales_data[] = [
        'date' => date('M d', strtotime($date)),
        'revenue' => $revenue
    ];
}

// Recent orders
$result = $db->query("
    SELECT o.order_id, o.customer_name, o.total_amount, o.status, o.created_at 
    FROM orders o 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$recent_orders = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Jemimah Fashion Admin</h1>
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
                    <li><a href="index.php" class="logo">Jemimah Fashion</a></li>
                    <li><a href="admin_dashboard.php" class="active">📊 Dashboard</a></li>
                    <li><a href="admin_products.php">📦 Products</a></li>
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
                <p style="color: #ccc;">&copy; 2026 Jemimah Fashion. All rights reserved.</p>
                <h2>Dashboard Overview</h2>
                
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo formatPrice($daily_revenue); ?></div>
                        <div class="stat-label">Daily Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo formatPrice($monthly_revenue); ?></div>
                        <div class="stat-label">Monthly Revenue</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_products; ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                </div>

                <!-- Sales Chart -->
                <div class="card mb-4">
                    <div class="card-content">
                        <h3>Sales Analytics (Last 7 Days)</h3>
                        <canvas id="salesChart" width="400" height="100"></canvas>
                    </div>
                </div>

                <div class="grid grid-2">
                    <!-- Top Selling Products -->
                    <div class="card">
                        <div class="card-content">
                            <h3>Top Selling Products</h3>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Units Sold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['title']); ?></td>
                                        <td><?php echo $product['total_sold']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="card">
                        <div class="card-content">
                            <h3>Recent Orders</h3>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo formatPrice($order['total_amount']); ?></td>
                                        <td>
                                            <span class="btn btn-<?php echo $order['status'] == 'delivered' ? 'success' : 'warning'; ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
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
    </div>

    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($sales_data, 'date')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($sales_data, 'revenue')); ?>,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    borderColor: 'rgba(0, 0, 0, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>
