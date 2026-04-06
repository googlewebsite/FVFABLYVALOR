<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

$db = new Database();

// Handle payment verification
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'verify_payment') {
        $payment_id = $_POST['payment_id'];
        $utr_number = $_POST['utr_number'];
        $status = $_POST['status'];
        
        // Update payment status
        $stmt = $db->prepare("UPDATE payments SET status = ?, utr_number = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $utr_number, $payment_id);
        $stmt->execute();
        
        // Update corresponding order payment status
        $stmt = $db->prepare("UPDATE orders SET payment_status = ?, utr_number = ? WHERE id = (SELECT order_id FROM payments WHERE id = ?)");
        $stmt->bind_param("ssi", $status, $utr_number, $payment_id);
        $stmt->execute();
        
        header('Location: admin_payments.php?success=Payment status updated successfully');
        exit();
    }
    
    if ($action == 'bulk_verify') {
        $payment_ids = $_POST['payment_ids'];
        $status = $_POST['bulk_status'];
        
        foreach ($payment_ids as $payment_id) {
            $stmt = $db->prepare("UPDATE payments SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $payment_id);
            $stmt->execute();
            
            // Update corresponding order payment status
            $stmt = $db->prepare("UPDATE orders SET payment_status = ? WHERE id = (SELECT order_id FROM payments WHERE id = ?)");
            $stmt->bind_param("si", $status, $payment_id);
            $stmt->execute();
        }
        
        header('Location: admin_payments.php?success=Bulk payment verification completed');
        exit();
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$where_clause = '';

if ($filter == 'pending') {
    $where_clause = "WHERE p.status = 'pending'";
} elseif ($filter == 'verified') {
    $where_clause = "WHERE p.status = 'verified'";
} elseif ($filter == 'failed') {
    $where_clause = "WHERE p.status = 'failed'";
}

// Get payments with order information
$payments_query = "
    SELECT p.*, o.order_id, o.customer_name, o.customer_phone, o.created_at as order_date 
    FROM payments p 
    JOIN orders o ON p.order_id = o.id 
    $where_clause 
    ORDER BY p.created_at DESC
";
$result = $db->query($payments_query);
$payments = $result->fetch_all(MYSQLI_ASSOC);

// Get payment statistics
$stats_query = "
    SELECT 
        status,
        COUNT(*) as count,
        COALESCE(SUM(amount), 0) as total_amount
    FROM payments 
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
    if ($row['status'] == 'verified') {
        $total_revenue += $row['total_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - FV FABLY VALOR</title>
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
                    <li><a href="admin_payments.php" class="active">💳 Payments</a></li>
                    <li><a href="admin_customers.php">👤 Customers</a></li>
                    <li><a href="admin_banners.php">🎨 Banners</a></li>
                    <li><a href="admin_settings.php">⚙️ Settings</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="admin-content" style="flex: 1;">
                <h2>Payment Management</h2>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Payment Statistics -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo array_sum(array_column($stats, 'count')); ?></div>
                        <div class="stat-label">Total Payments</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #ffc107;"><?php echo $stats['pending']['count'] ?? 0; ?></div>
                        <div class="stat-label">Pending Verification</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #28a745;"><?php echo $stats['verified']['count'] ?? 0; ?></div>
                        <div class="stat-label">Verified</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo formatPrice($total_revenue); ?></div>
                        <div class="stat-label">Verified Revenue</div>
                    </div>
                </div>

                <!-- Filter Buttons -->
                <div class="mb-4">
                    <div class="d-flex" style="gap: 1rem; flex-wrap: wrap;">
                        <a href="admin_payments.php?filter=all" class="btn <?php echo $filter == 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                            All Payments
                        </a>
                        <a href="admin_payments.php?filter=pending" class="btn <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Pending (<?php echo $stats['pending']['count'] ?? 0; ?>)
                        </a>
                        <a href="admin_payments.php?filter=verified" class="btn <?php echo $filter == 'verified' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Verified (<?php echo $stats['verified']['count'] ?? 0; ?>)
                        </a>
                        <a href="admin_payments.php?filter=failed" class="btn <?php echo $filter == 'failed' ? 'btn-primary' : 'btn-secondary'; ?>">
                            Failed (<?php echo $stats['failed']['count'] ?? 0; ?>)
                        </a>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="card mb-4">
                    <div class="card-content">
                        <h3>Bulk Payment Verification</h3>
                        <form method="POST" id="bulkVerifyForm">
                            <input type="hidden" name="action" value="bulk_verify">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <button type="submit" class="btn btn-success">Verify Selected</button>
                                    <button type="button" class="btn btn-danger" onclick="bulkReject()">Reject Selected</button>
                                    <button type="button" class="btn btn-secondary" onclick="selectAll()">Select All</button>
                                    <button type="button" class="btn btn-secondary" onclick="deselectAll()">Deselect All</button>
                                </div>
                                <span id="selectedCount">0 payments selected</span>
                            </div>
                            <input type="hidden" name="bulk_status" id="bulkStatus" value="verified">
                        </form>
                    </div>
                </div>

                <!-- Payments Table -->
                <div class="card">
                    <div class="card-content">
                        <h3>Payment Overview</h3>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                    </th>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>UTR Number</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="payment_ids[]" value="<?php echo $payment['id']; ?>" class="payment-checkbox" onchange="updateSelectedCount()">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($payment['order_id']); ?></strong>
                                        <br><small>Order Date: <?php echo date('M j, Y', strtotime($payment['order_date'])); ?></small>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($payment['customer_name']); ?></strong><br>
                                            <small style="color: #666;">
                                                📱 <?php echo htmlspecialchars($payment['customer_phone']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo formatPrice($payment['amount']); ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($payment['utr_number']): ?>
                                            <code><?php echo htmlspecialchars($payment['utr_number']); ?></code>
                                        <?php else: ?>
                                            <span style="color: #999;">Not provided</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="btn btn-<?php echo $payment['status'] == 'verified' ? 'success' : ($payment['status'] == 'failed' ? 'danger' : 'warning'); ?>" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($payment['created_at'])); ?><br>
                                        <small><?php echo date('h:i A', strtotime($payment['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex" style="gap: 0.5rem; flex-direction: column;">
                                            <?php if ($payment['status'] == 'pending'): ?>
                                                <button class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="verifyPayment(<?php echo $payment['id']; ?>, '<?php echo htmlspecialchars($payment['utr_number'] ?? ''); ?>')">
                                                    ✓ Verify
                                                </button>
                                                <button class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="rejectPayment(<?php echo $payment['id']; ?>)">
                                                    ✗ Reject
                                                </button>
                                            <?php endif; ?>
                                            <a href="order_details.php?id=<?php echo $payment['order_id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">View Order</a>
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

    <!-- Payment Verification Modal -->
    <div id="verifyModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 10px; width: 90%; max-width: 500px;">
            <h3>Verify Payment</h3>
            <form method="POST" id="verifyForm">
                <input type="hidden" name="action" value="verify_payment">
                <input type="hidden" name="payment_id" id="modalPaymentId">
                
                <div class="form-group">
                    <label class="form-label">UTR Number</label>
                    <input type="text" name="utr_number" id="modalUtrNumber" class="form-control" placeholder="Enter UTR number">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Payment Status</label>
                    <select name="status" class="form-control">
                        <option value="verified">Verified</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                
                <div class="d-flex" style="gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Update Status</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const paymentCheckboxes = document.querySelectorAll('.payment-checkbox');
            
            paymentCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checkedBoxes = document.querySelectorAll('.payment-checkbox:checked');
            document.getElementById('selectedCount').textContent = checkedBoxes.length + ' payments selected';
        }
        
        function selectAll() {
            document.getElementById('selectAllCheckbox').checked = true;
            toggleSelectAll();
        }
        
        function deselectAll() {
            document.getElementById('selectAllCheckbox').checked = false;
            toggleSelectAll();
        }
        
        function verifyPayment(paymentId, utrNumber) {
            document.getElementById('modalPaymentId').value = paymentId;
            document.getElementById('modalUtrNumber').value = utrNumber;
            document.getElementById('verifyModal').style.display = 'block';
        }
        
        function rejectPayment(paymentId) {
            if (confirm('Are you sure you want to reject this payment?')) {
                document.getElementById('modalPaymentId').value = paymentId;
                document.getElementById('modalUtrNumber').value = '';
                document.querySelector('#verifyForm select[name="status"]').value = 'failed';
                document.getElementById('verifyForm').submit();
            }
        }
        
        function closeModal() {
            document.getElementById('verifyModal').style.display = 'none';
        }
        
        function bulkReject() {
            document.getElementById('bulkStatus').value = 'failed';
            document.getElementById('bulkVerifyForm').submit();
        }
        
        // Initialize
        updateSelectedCount();
    </script>
</body>
</html>
