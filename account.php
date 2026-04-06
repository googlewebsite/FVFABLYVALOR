<?php
require_once 'config.php';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Get current page
$current_page = $_GET['page'] ?? 'dashboard';

// Handle login/logout
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $db = new Database();
        $stmt = $db->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            if (password_verify($password, $customer['password'])) {
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['customer_name'] = $customer['name'];
                header('Location: account.php');
                exit();
            }
        }
        
        $error = "Invalid email or password";
    }
    
    if ($action == 'register') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters";
        } else {
            $db = new Database();
            
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email already exists";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO customers (name, email, phone, password) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
                $stmt->execute();
                
                $_SESSION['customer_id'] = $db->insert_id();
                $_SESSION['customer_email'] = $email;
                $_SESSION['customer_name'] = $name;
                
                header('Location: account.php');
                exit();
            }
        }
    }
    
    if ($action == 'logout') {
        session_destroy();
        header('Location: index.php');
        exit();
    }
}

// Check if user is logged in
$is_logged_in = isLoggedIn();
$db = new Database();
$customer_data = null;

if ($is_logged_in) {
    $customer_id = $_SESSION['customer_id'];
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $customer_data = $stmt->get_result()->fetch_assoc();
    
    // Get customer statistics
    $stmt = $db->prepare("SELECT COUNT(*) as total_orders, SUM(total_amount) as total_spent FROM orders WHERE customer_id = ? AND payment_status = 'verified'");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    
    // Ensure stats values are not null
    $total_orders = $stats['total_orders'] ?? 0;
    $total_spent = $stats['total_spent'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .account-container {
            max-width: 1200px;
            margin: 2rem auto;
        }
        
        .account-sidebar {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .account-menu {
            list-style: none;
            padding: 0;
        }
        
        .account-menu li {
            margin-bottom: 8px;
        }
        
        .account-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #4b5563;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .account-menu a:hover,
        .account-menu a.active {
            background: #000000;
            color: #ffffff;
            transform: translateX(4px);
        }
        
        .account-content {
            background: #ffffff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
        }
        
        .auth-form {
            max-width: 400px;
            margin: 0 auto;
        }
        
        .auth-tabs {
            display: flex;
            margin-bottom: 32px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .auth-tab {
            flex: 1;
            padding: 16px;
            text-align: center;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .auth-tab.active {
            color: #000000;
            border-bottom: 3px solid #000000;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
            border-left: 4px solid #667eea;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .order-id {
            font-weight: 600;
            color: #000000;
        }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="logo">Jemimah Fashion</a>
                
                <div class="search-bar">
                    <form method="GET" action="products.php">
                        <input type="text" name="search" placeholder="Search for products...">
                        <button type="submit" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
                
                <div class="nav-icons">
                    <a href="wishlist.php" title="Wishlist">
                        <i class="fas fa-heart"></i>
                    </a>
                    <a href="cart.php" title="Cart">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                    <a href="account.php" title="My Account" class="active">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Account Section -->
    <section style="padding: 2rem 0;">
        <div class="account-container">
            <?php if ($is_logged_in): ?>
                <!-- Logged In User Dashboard -->
                <div class="d-flex" style="gap: 2rem;">
                    <!-- Sidebar -->
                    <div class="account-sidebar">
                        <div style="text-align: center; margin-bottom: 24px;">
                            <div style="position: relative; display: inline-block;">
                                <div id="profilePhotoContainer" style="width: 100px; height: 100px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem; font-weight: bold; cursor: pointer; overflow: hidden; transition: all 0.3s ease;" onclick="document.getElementById('profilePhotoInput').click()">
                                    <?php if ($customer_data && $customer_data['profile_photo']): ?>
                                        <img src="<?php echo htmlspecialchars($customer_data['profile_photo']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                        <?php echo $customer_data ? strtoupper(substr($customer_data['name'], 0, 1)) : '?'; ?>
                                    <?php endif; ?>
                                </div>
                                <input type="file" id="profilePhotoInput" accept="image/*" style="display: none;" onchange="uploadProfilePhoto(this)">
                                <button onclick="document.getElementById('profilePhotoInput').click()" style="position: absolute; bottom: 0; right: 0; background: #667eea; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <h4 style="margin: 8px 0;"><?php echo $customer_data ? htmlspecialchars($customer_data['name']) : 'Customer'; ?></h4>
                            <p style="color: #666; margin: 0;"><?php echo $customer_data ? htmlspecialchars($customer_data['email']) : 'No email'; ?></p>
                        </div>
                        
                        <ul class="account-menu">
                            <li><a href="account.php?page=dashboard" <?php echo $current_page == 'dashboard' ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="account.php?page=orders" <?php echo $current_page == 'orders' ? 'class="active"' : ''; ?>><i class="fas fa-shopping-bag"></i> My Orders</a></li>
                            <li><a href="account.php?page=profile" <?php echo $current_page == 'profile' ? 'class="active"' : ''; ?>><i class="fas fa-user-edit"></i> Profile</a></li>
                            <li><a href="account.php?page=addresses" <?php echo $current_page == 'addresses' ? 'class="active"' : ''; ?>><i class="fas fa-map-marker-alt"></i> Addresses</a></li>
                            <li><a href="account.php?page=wishlist" <?php echo $current_page == 'wishlist' ? 'class="active"' : ''; ?>><i class="fas fa-heart"></i> Wishlist</a></li>
                            <li><a href="account.php?page=settings" <?php echo $current_page == 'settings' ? 'class="active"' : ''; ?>><i class="fas fa-cog"></i> Settings</a></li>
                            <li><a href="account.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                    
                    <!-- Main Content -->
                    <div style="flex: 1;">
                        <!-- Dashboard Section -->
                        <div id="dashboard" class="account-content" <?php echo $current_page == 'dashboard' ? '' : 'style="display: none;"'; ?>>
                            <h2 style="margin-bottom: 32px;">My Dashboard</h2>
                            
                            <!-- Statistics -->
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $total_orders; ?></div>
                                    <div class="stat-label">Total Orders</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo formatPrice($total_spent); ?></div>
                                    <div class="stat-label">Total Spent</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">🎉</div>
                                    <div class="stat-label">Member Since</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number"><?php echo $customer_data ? date('M j, Y', strtotime($customer_data['created_at'])) : 'N/A'; ?></div>
                                    <div class="stat-label">Join Date</div>
                                </div>
                            </div>
                            
                            <!-- Profile Section -->
                        <div id="profile" class="account-content" <?php echo $current_page == 'profile' ? '' : 'style="display: none;"'; ?>>
                            <h2 style="margin-bottom: 32px;">Edit Profile</h2>
                            
                            <form id="profileForm" onsubmit="updateProfile(event)">
                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" id="profileName" class="form-control" value="<?php echo htmlspecialchars($customer_data['name'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" id="profilePhone" class="form-control" value="<?php echo htmlspecialchars($customer_data['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($customer_data['email'] ?? ''); ?>" readonly style="background: #f8f9fa; cursor: not-allowed;">
                                    <small style="color: #666;">Email cannot be changed. Contact support for email changes.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Member Since</label>
                                    <input type="text" class="form-control" value="<?php echo $customer_data ? date('M j, Y', strtotime($customer_data['created_at'])) : 'N/A'; ?>" readonly style="background: #f8f9fa; cursor: not-allowed;">
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </form>
                        </div>
                        
                        <!-- Orders Section -->
                        <div id="orders" class="account-content" <?php echo $current_page == 'orders' ? '' : 'style="display: none;"'; ?>>
                            <h2 style="margin-bottom: 32px;">My Orders</h2>
                            <p style="color: #666; margin-bottom: 24px;">View your complete order history and track shipments.</p>
                            <a href="order_history.php" class="btn btn-primary">
                                <i class="fas fa-list"></i> View All Orders
                            </a>
                        </div>
                        
                        <!-- Addresses Section -->
                        <div id="addresses" class="account-content" <?php echo $current_page == 'addresses' ? '' : 'style="display: none;"'; ?>>
                            <h2 style="margin-bottom: 32px;">Shipping Addresses</h2>
                            <p style="color: #666; margin-bottom: 24px;">Manage your shipping addresses for faster checkout.</p>
                            <button class="btn btn-primary" onclick="addNewAddress()">
                                <i class="fas fa-plus"></i> Add New Address
                            </button>
                        </div>
                        
                        <!-- Wishlist Section -->
                        <div id="wishlist" class="account-content" <?php echo $current_page == 'wishlist' ? '' : 'style="display: none;"'; ?>>
                            <h2 style="margin-bottom: 32px;">My Wishlist</h2>
                            <p style="color: #666; margin-bottom: 24px;">Items you've saved for later.</p>
                            <a href="wishlist.php" class="btn btn-primary">
                                <i class="fas fa-heart"></i> View Wishlist
                            </a>
                        </div>
                        
                        <!-- Settings Section -->
                        <div id="settings" class="account-content" <?php echo $current_page == 'settings' ? '' : 'style="display: none;"'; ?>>
                            <h2 style="margin-bottom: 32px;">Account Settings</h2>
                            <p style="color: #666; margin-bottom: 24px;">Manage your account preferences and security.</p>
                            
                            <div class="form-group">
                                <label class="form-label">Change Password</label>
                                <input type="password" class="form-control" placeholder="Current Password">
                            </div>
                            
                            <div class="form-group">
                                <input type="password" class="form-control" placeholder="New Password">
                            </div>
                            
                            <div class="form-group">
                                <input type="password" class="form-control" placeholder="Confirm New Password">
                            </div>
                            
                            <button class="btn btn-primary" style="margin-top: 16px;">
                                <i class="fas fa-lock"></i> Update Password
                            </button>
                        </div>
                            <?php
                            $stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5");
                            $stmt->bind_param("i", $customer_id);
                            $stmt->execute();
                            $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            
                            if (!empty($orders)):
                                foreach ($orders as $order):
                            ?>
                                <div class="order-item">
                                    <div class="order-header">
                                        <div>
                                            <div class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                                            <div style="color: #666; font-size: 0.9rem;"><?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?></div>
                                        </div>
                                        <div>
                                            <span class="order-status status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <div style="color: #666; font-size: 0.9rem;">Amount: <strong><?php echo formatPrice($order['total_amount']); ?></strong></div>
                                            <div style="color: #666; font-size: 0.9rem;">Payment: <strong><?php echo ucfirst($order['payment_status']); ?></strong></div>
                                        </div>
                                        <a href="order_confirmation.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.9rem;">View Details</a>
                                    </div>
                                </div>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <p style="text-align: center; color: #666; padding: 40px;">You haven't placed any orders yet.</p>
                                <div style="text-align: center;">
                                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Login/Register Form -->
                <div class="auth-form">
                    <h2 style="text-align: center; margin-bottom: 32px;">My Account</h2>
                    
                    <?php if (isset($error)): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 24px; text-align: center;">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="auth-tabs">
                        <button class="auth-tab active" onclick="showTab('login')">Login</button>
                        <button class="auth-tab" onclick="showTab('register')">Register</button>
                    </div>
                    
                    <!-- Login Form -->
                    <div id="loginForm" class="auth-form-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 16px;">Login</button>
                        </form>
                        
                        <div style="text-align: center; margin-top: 16px;">
                            <a href="#" style="color: #667eea; text-decoration: none;">Forgot Password?</a>
                        </div>
                    </div>
                    
                    <!-- Register Form -->
                    <div id="registerForm" class="auth-form-content" style="display: none;">
                        <form method="POST">
                            <input type="hidden" name="action" value="register">
                            
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 16px;">Register</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: #000000; color: white; padding: 3rem 0 1rem;">
        <div class="container">
            <div class="grid grid-4">
                <div>
                    <h3 style="margin-bottom: 1rem;">Jemimah Fashion</h3>
                    <p style="color: #ccc;">Your destination for premium fashion collection with quality and style.</p>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li><a href="products.php" style="color: #ccc; text-decoration: none;">Products</a></li>
                        <li><a href="about.php" style="color: #ccc; text-decoration: none;">About Us</a></li>
                        <li><a href="contact.php" style="color: #ccc; text-decoration: none;">Contact</a></li>
                        <li><a href="account.php" style="color: #ccc; text-decoration: none;">My Account</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem;">Customer Service</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li><a href="#" style="color: #ccc; text-decoration: none;">Shipping Info</a></li>
                        <li><a href="#" style="color: #ccc; text-decoration: none;">Returns</a></li>
                        <li><a href="#" style="color: #ccc; text-decoration: none;">Size Guide</a></li>
                        <li><a href="#" style="color: #ccc; text-decoration: none;">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem;">Contact Info</h4>
                    <p style="color: #ccc;">
                        📞 +91 98765 43210<br>
                        📧 info@fvfablyvalor.com<br>
                        📍 123 Fashion Street, Mumbai
                    </p>
                </div>
            </div>
            
            <hr style="border-color: #333; margin: 2rem 0;">
            
            <div class="text-center">
                <p style="color: #ccc;">&copy; 2026 Jemimah Fashion. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function showTab(tabName) {
            // Hide all forms
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'none';
            
            // Remove active class from all tabs
            document.querySelectorAll('.auth-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected form and tab
            document.getElementById(tabName + 'Form').style.display = 'block';
            event.target.classList.add('active');
        }
        
        // Handle account menu navigation
        document.querySelectorAll('.account-menu a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    document.querySelectorAll('.account-menu a').forEach(a => {
                        a.classList.remove('active');
                    });
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Show corresponding section
                    const sectionId = this.getAttribute('href').substring(1);
                    document.querySelectorAll('.account-content').forEach(section => {
                        section.style.display = 'none';
                    });
                    
                    const targetSection = document.getElementById(sectionId);
                    if (targetSection) {
                        targetSection.style.display = 'block';
                    }
                }
            });
        });
        
        // Profile photo upload
        function uploadProfilePhoto(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('action', 'upload_profile_photo');
                formData.append('profile_photo', input.files[0]);
                
                fetch('ajax_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update profile photo display
                        const photoContainer = document.getElementById('profilePhotoContainer');
                        photoContainer.innerHTML = `<img src="${data.photo_url}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                        
                        showNotification('Profile photo updated successfully!', 'success');
                    } else {
                        showNotification(data.message || 'Failed to upload photo', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to upload photo', 'error');
                });
            }
        }
        
        // Update profile
        function updateProfile(event) {
            event.preventDefault();
            
            const name = document.getElementById('profileName').value;
            const phone = document.getElementById('profilePhone').value;
            
            const formData = new FormData();
            formData.append('action', 'update_profile');
            formData.append('name', name);
            formData.append('phone', phone);
            
            fetch('ajax_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Profile updated successfully!', 'success');
                    
                    // Update displayed name
                    document.querySelector('h4').textContent = name;
                } else {
                    showNotification(data.message || 'Failed to update profile', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to update profile', 'error');
            });
        }
        
        // Add new address (placeholder function)
        function addNewAddress() {
            showNotification('Address management coming soon!', 'info');
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                padding: 16px 20px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 10000;
                box-shadow: 0 8px 24px rgba(0,0,0,0.15);
                transform: translateX(400px);
                transition: transform 0.3s ease;
                font-weight: 500;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Add hover effect to profile photo
        document.addEventListener('DOMContentLoaded', function() {
            const photoContainer = document.getElementById('profilePhotoContainer');
            if (photoContainer) {
                photoContainer.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                    this.style.boxShadow = '0 8px 24px rgba(0,0,0,0.2)';
                });
                
                photoContainer.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                });
            }
        });
    </script>
</body>
</html>
