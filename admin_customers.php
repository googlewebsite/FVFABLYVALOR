<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

$db = new Database();

// Handle customer operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'delete_customer') {
        $customer_id = $_POST['customer_id'];
        
        // Check if customer has orders
        $stmt = $db->prepare("SELECT COUNT(*) as order_count FROM orders WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_count = $result->fetch_assoc()['order_count'];
        
        if ($order_count > 0) {
            header('Location: admin_customers.php?error=Cannot delete customer with existing orders');
            exit();
        }
        
        // Delete customer
        $stmt = $db->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        
        header('Location: admin_customers.php?success=Customer deleted successfully');
        exit();
    }
    
    if ($action == 'update_status') {
        $customer_id = $_POST['customer_id'];
        $status = $_POST['status'];
        
        // Update customer status (you might want to add a status field to customers table)
        // For now, we'll just show a success message
        header('Location: admin_customers.php?success=Customer status updated');
        exit();
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';

if ($filter == 'recent') {
    $where_clause = "WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($filter == 'active') {
    $where_clause = "WHERE (SELECT COUNT(*) FROM orders WHERE customer_id = c.id) > 0";
} elseif ($filter == 'vip') {
    $where_clause = "WHERE (SELECT SUM(total_amount) FROM orders WHERE customer_id = c.id AND payment_status = 'verified') > 5000";
}

// Get customers with order statistics
$customers_query = "
    SELECT c.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COALESCE(SUM(CASE WHEN o.payment_status = 'verified' THEN o.total_amount ELSE 0 END), 0) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM customers c
    LEFT JOIN orders o ON c.id = o.customer_id
    $where_clause
    GROUP BY c.id
    ORDER BY c.created_at DESC
";
$result = $db->query($customers_query);
$customers = $result->fetch_all(MYSQLI_ASSOC);

// Get customer statistics
$total_customers = count($customers);
$active_customers = 0;
$vip_customers = 0;
$total_revenue = 0;

foreach ($customers as $customer) {
    if ($customer['total_orders'] > 0) {
        $active_customers++;
    }
    if ($customer['total_spent'] > 5000) {
        $vip_customers++;
    }
    $total_revenue += $customer['total_spent'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - FV FABLY VALOR</title>
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
                    <li><a href="admin_customers.php" class="active">👤 Customers</a></li>
                    <li><a href="admin_banners.php">🎨 Banners</a></li>
                    <li><a href="admin_settings.php">⚙️ Settings</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="admin-content" style="flex: 1;">
                <h2>Customer Management</h2>

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

                <!-- Customer Statistics -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $total_customers; ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $active_customers; ?></div>
                        <div class="stat-label">Active Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $vip_customers; ?></div>
                        <div class="stat-label">VIP Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo formatPrice($total_revenue); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="mb-4">
                    <div class="d-flex" style="gap: 1rem; flex-wrap: wrap;">
                        <a href="admin_customers.php?filter=all" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                            All Customers
                        </a>
                        <a href="admin_customers.php?filter=recent" class="btn <?php echo $filter == 'recent' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Recent (30 days)
                        </a>
                        <a href="admin_customers.php?filter=active" class="btn <?php echo $filter == 'active' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Active
                        </a>
                        <a href="admin_customers.php?filter=vip" class="btn <?php echo $filter == 'vip' ? 'btn-primary' : 'btn-secondary'; ?>">
                            VIP Customers
                        </a>
                    </div>
                </div>

                <!-- Customers Table -->
                <div class="card">
                    <div class="card-content">
                        <h3>Customers Overview</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Last Order</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center" style="gap: 1rem;">
                                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                                <?php if ($customer['total_spent'] > 5000): ?>
                                                    <span style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">VIP</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div>📧 <?php echo htmlspecialchars($customer['email']); ?></div>
                                            <div style="color: #666; font-size: 0.9rem;">📱 <?php echo htmlspecialchars($customer['phone']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo $customer['total_orders']; ?></strong>
                                        <?php if ($customer['total_orders'] > 0): ?>
                                            <div style="color: #28a745; font-size: 0.8rem;">Active</div>
                                        <?php else: ?>
                                            <div style="color: #6c757d; font-size: 0.8rem;">New</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo formatPrice($customer['total_spent']); ?></strong>
                                        <?php if ($customer['total_spent'] > 0): ?>
                                            <div style="color: #666; font-size: 0.8rem;">Avg: <?php echo formatPrice($customer['total_orders'] > 0 ? $customer['total_spent'] / $customer['total_orders'] : 0); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($customer['last_order_date']): ?>
                                            <div><?php echo date('M j, Y', strtotime($customer['last_order_date'])); ?></div>
                                            <div style="color: #666; font-size: 0.8rem;"><?php echo date('h:i A', strtotime($customer['last_order_date'])); ?></div>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">No orders</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></div>
                                        <div style="color: #666; font-size: 0.8rem;"><?php echo date('h:i A', strtotime($customer['created_at'])); ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex" style="gap: 0.5rem; flex-direction: column;">
                                            <a href="admin_customer_orders.php?id=<?php echo $customer['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">View Orders</a>
                                            <button class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="sendEmail(<?php echo $customer['id']; ?>)">Send Email</button>
                                            <?php if ($customer['total_orders'] == 0): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_customer">
                                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="return confirm('Are you sure you want to delete this customer?')">Delete</button>
                                                </form>
                                            <?php endif; ?>
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
        function sendEmail(customerId) {
            // This would typically open an email compose dialog or integrate with an email service
            const email = prompt('Enter email address to send message:');
            if (email) {
                // Here you would integrate with your email service
                alert('Email functionality would be implemented here');
            }
        }
        
        // Add search functionality
        function searchCustomers() {
            const searchValue = document.getElementById('customerSearch').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        }
        
        // Add search bar
        document.addEventListener('DOMContentLoaded', function() {
            const searchDiv = document.createElement('div');
            searchDiv.className = 'mb-4';
            searchDiv.innerHTML = `
                <input type="text" id="customerSearch" placeholder="Search customers..." class="form-control" style="max-width: 400px;" onkeyup="searchCustomers()">
            `;
            
            const cardContent = document.querySelector('.card-content');
            cardContent.insertBefore(searchDiv, cardContent.firstChild);
        });
    </script>
</body>
</html>
