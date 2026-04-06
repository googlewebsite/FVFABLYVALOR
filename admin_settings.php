<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

$db = new Database();

// Handle settings update
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'update_settings') {
        $settings = [
            'gst_number' => $_POST['gst_number'],
            'upi_id' => $_POST['upi_id'],
            'business_name' => $_POST['business_name'],
            'instagram_url' => $_POST['instagram_url'],
            'facebook_url' => $_POST['facebook_url'],
            'bank_details' => $_POST['bank_details'],
            'contact_phone' => $_POST['contact_phone'],
            'contact_email' => $_POST['contact_email'],
            'contact_address' => $_POST['contact_address'],
            'shipping_charge' => $_POST['shipping_charge'],
            'free_shipping_above' => $_POST['free_shipping_above']
        ];
        
        foreach ($settings as $key => $value) {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
        
        // Handle QR code upload
        if (!empty($_FILES['qr_code']['name'])) {
            $qr_code = uploadFile($_FILES['qr_code'], 'uploads/settings/');
            if ($qr_code) {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('qr_code', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->bind_param("ss", $qr_code, $qr_code);
                $stmt->execute();
            }
        }
        
        header('Location: admin_settings.php?success=Settings updated successfully');
        exit();
    }
}

// Get all settings
$result = $db->query("SELECT * FROM settings ORDER BY setting_key");
$settings_data = [];
while ($row = $result->fetch_assoc()) {
    $settings_data[$row['setting_key']] = $row['setting_value'];
}

// Default values
$settings = array_merge([
    'gst_number' => '',
    'upi_id' => 'jesuslifemylife@okaxis',
    'business_name' => 'FV FABLY VALOR',
    'instagram_url' => '',
    'facebook_url' => '',
    'bank_details' => '',
    'qr_code' => '',
    'contact_phone' => '+91 98765 43210',
    'contact_email' => 'info@fvfablyvalor.com',
    'contact_address' => '123 Fashion Street, Mumbai, Maharashtra 400001',
    'shipping_charge' => '50',
    'free_shipping_above' => '999'
], $settings_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Jemimah Fashion Admin</title>
    <link rel="stylesheet" href="style.css">
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
                    <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
                    <li><a href="admin_products.php">📦 Products</a></li>
                    <li><a href="admin_inventory.php">📋 Inventory</a></li>
                    <li><a href="admin_orders.php">🧾 Orders</a></li>
                    <li><a href="admin_payments.php">💳 Payments</a></li>
                    <li><a href="admin_customers.php">👤 Customers</a></li>
                    <li><a href="admin_banners.php">🎨 Banners</a></li>
                    <li><a href="admin_settings.php" class="active">⚙️ Settings</a></li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="admin-content" style="flex: 1;">
                <h2>System Settings</h2>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <!-- Business Settings -->
                    <div class="card mb-4">
                        <div class="card-content">
                            <h3>🏢 Business Information</h3>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">Business Name</label>
                                    <input type="text" name="business_name" class="form-control" value="<?php echo htmlspecialchars($settings['business_name']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">GST Number</label>
                                    <input type="text" name="gst_number" class="form-control" placeholder="27AAAPL1234C1Z" value="<?php echo htmlspecialchars($settings['gst_number']); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Bank Details</label>
                                <textarea name="bank_details" class="form-control" rows="3" placeholder="Bank Name, Account Number, IFSC Code"><?php echo htmlspecialchars($settings['bank_details']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Settings -->
                    <div class="card mb-4">
                        <div class="card-content">
                            <h3>💳 Payment Settings</h3>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">UPI ID</label>
                                    <input type="text" name="upi_id" class="form-control" placeholder="yourupi@paytm" value="<?php echo htmlspecialchars($settings['upi_id']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">UPI QR Code</label>
                                    <input type="file" name="qr_code" class="form-control" accept="image/*">
                                    <?php if ($settings['qr_code']): ?>
                                        <small style="color: #666; display: block; margin-top: 0.5rem;">
                                            Current: <img src="<?php echo htmlspecialchars($settings['qr_code']); ?>" width="60" style="vertical-align: middle; margin-left: 0.5rem;">
                                        </small>
                                    <?php endif; ?>
                                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                                        Upload QR code image for UPI payments
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Settings -->
                    <div class="card mb-4">
                        <div class="card-content">
                            <h3>🚚 Shipping Settings</h3>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">Shipping Charge (₹)</label>
                                    <input type="number" name="shipping_charge" class="form-control" value="<?php echo htmlspecialchars($settings['shipping_charge']); ?>" min="0" step="0.01">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Free Shipping Above (₹)</label>
                                    <input type="number" name="free_shipping_above" class="form-control" value="<?php echo htmlspecialchars($settings['free_shipping_above']); ?>" min="0" step="0.01">
                                </div>
                            </div>
                            
                            <small style="color: #666; display: block; margin-top: 0.5rem;">
                                Customers get free shipping when order amount exceeds the specified amount
                            </small>
                        </div>
                    </div>

                    <!-- Contact Settings -->
                    <div class="card mb-4">
                        <div class="card-content">
                            <h3>📞 Contact Information</h3>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">Contact Phone</label>
                                    <input type="tel" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email']); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Business Address</label>
                                <textarea name="contact_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings['contact_address']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media Settings -->
                    <div class="card mb-4">
                        <div class="card-content">
                            <h3>🌐 Social Media</h3>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">Instagram URL</label>
                                    <input type="url" name="instagram_url" class="form-control" placeholder="https://instagram.com/yourprofile" value="<?php echo htmlspecialchars($settings['instagram_url']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Facebook URL</label>
                                    <input type="url" name="facebook_url" class="form-control" placeholder="https://facebook.com/yourpage" value="<?php echo htmlspecialchars($settings['facebook_url']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="card mb-4">
                        <div class="card-content">
                            <h3>🔧 System Information</h3>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">PHP Version</label>
                                    <input type="text" class="form-control" value="<?php echo phpversion(); ?>" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">MySQL Version</label>
                                    <input type="text" class="form-control" value="<?php echo $db->conn->server_info; ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="grid grid-2">
                                <div class="form-group">
                                    <label class="form-label">Server Time</label>
                                    <input type="text" class="form-control" value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Timezone</label>
                                    <input type="text" class="form-control" value="<?php echo date_default_timezone_get(); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Website URL</label>
                                <input type="text" class="form-control" value="<?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex" style="gap: 1rem;">
                        <button type="submit" class="btn btn-primary">💾 Save Settings</button>
                        <button type="button" class="btn btn-secondary" onclick="confirmReset()">🔄 Reset to Defaults</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmReset() {
            if (confirm('Are you sure you want to reset all settings to default values? This action cannot be undone.')) {
                // Here you would implement the reset functionality
                window.location.href = 'admin_settings.php?reset=true';
            }
        }
        
        // Auto-save functionality (optional enhancement)
        let autoSaveTimer;
        const inputs = document.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    // Show auto-save indicator
                    const indicator = document.getElementById('autoSaveIndicator');
                    if (!indicator) {
                        const div = document.createElement('div');
                        div.id = 'autoSaveIndicator';
                        div.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 8px 16px; border-radius: 5px; font-size: 14px; z-index: 1000;';
                        div.textContent = 'Auto-saving...';
                        document.body.appendChild(div);
                    }
                    
                    // Hide indicator after 2 seconds
                    setTimeout(() => {
                        const el = document.getElementById('autoSaveIndicator');
                        if (el) {
                            el.style.background = '#28a745';
                            el.textContent = 'Saved';
                            setTimeout(() => {
                                el.remove();
                            }, 1000);
                        }
                    }, 2000);
                }, 2000);
            });
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const upiId = document.querySelector('input[name="upi_id"]').value;
            const email = document.querySelector('input[name="contact_email"]').value;
            
            // Basic validation
            if (upiId && !upiId.includes('@')) {
                alert('Please enter a valid UPI ID (format: username@upi)');
                e.preventDefault();
                return;
            }
            
            if (email && !email.includes('@')) {
                alert('Please enter a valid email address');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
