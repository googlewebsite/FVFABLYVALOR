<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=checkout.php');
    exit();
}

$db = new Database();
$customer_id = $_SESSION['customer_id'];

// Handle Buy Now flow
$buy_now = false;
$cart_items = [];

if (isset($_GET['buy_now']) && $_GET['buy_now'] == '1') {
    // Buy Now flow - single product
    $buy_now = true;
    $product_id = $_GET['product_id'];
    $quantity = $_GET['quantity'] ?? 1;
    $size = $_GET['size'] ?? '';
    
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product) {
        $cart_items[] = [
            'id' => 'buy_now_' . $product_id,
            'product_id' => $product_id,
            'title' => $product['title'],
            'selling_price' => $product['selling_price'],
            'mrp_price' => $product['mrp_price'],
            'quantity' => $quantity,
            'size' => $size,
            'images' => $product['images']
        ];
    }
} else {
    // Regular checkout from cart
    $stmt = $db->prepare("
        SELECT c.*, p.title, p.selling_price, p.mrp_price, p.images
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.customer_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
}

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['selling_price'] * $item['quantity'];
}

$shipping = $subtotal > 999 ? 0 : 50;
$total_amount = $subtotal + $shipping;

// Get customer ID from session
$customer_id = $_SESSION['customer_id'];

// Handle form submission
if ($_POST) {
    $customer_name = $_POST['customer_name'];
    $customer_phone = $_POST['customer_phone'];
    $customer_address = $_POST['customer_address'];
    
    // Create order
    $order_id = generateOrderId();
    $product_details = [];
    
    foreach ($cart_items as $item) {
        $product_details[] = [
            'name' => $item['title'],
            'price' => $item['selling_price'],
            'quantity' => $item['quantity'],
            'size' => $item['size']
        ];
    }
    
    $product_details_json = json_encode($product_details);
    
    // Store direct values in variables for bind_param
    $order_status = 'pending';
    $payment_status = 'pending';
    
    $stmt = $db->prepare("
        INSERT INTO orders (order_id, customer_id, customer_name, customer_phone, customer_address, product_details, total_amount, status, payment_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sissssdss", $order_id, $customer_id, $customer_name, $customer_phone, $customer_address, $product_details_json, $total_amount, $order_status, $payment_status);
    $stmt->execute();
    
    $order_db_id = $db->insert_id();
    
    // Create payment record
    // Store direct values in variables for bind_param
    $payment_status = 'pending';
    $customer_email = $_SESSION['customer_email'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO payments (order_id, customer_name, email, phone, amount, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssss", $order_db_id, $customer_name, $customer_email, $customer_phone, $total_amount, $payment_status);
    $stmt->execute();
    
    // Clear cart if not Buy Now
    if (!$buy_now) {
        $stmt = $db->prepare("DELETE FROM cart WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
    }
    
    // Redirect to payment page
    header('Location: payment.php?order_id=' . $order_id);
    exit();
}

// Get customer details
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <span id="wishlist-count" style="position: absolute; top: -8px; right: -8px; background: red; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center;">0</span>
                    </a>
                    <a href="cart.php" title="Cart">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                    <a href="account.php" title="My Account">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Checkout Section -->
    <section style="padding: 2rem 0;">
        <div class="container">
            <h1 style="margin-bottom: 2rem;">Checkout</h1>
            
            <div class="d-flex" style="gap: 2rem;">
                <!-- Checkout Form -->
                <div style="flex: 2;">
                    <form method="POST" id="checkoutForm">
                        <div class="card">
                            <div class="card-content">
                                <h3>Shipping Information</h3>
                                
                                <div class="grid grid-2">
                                    <div class="form-group">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" name="customer_name" class="form-control" required value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Phone Number *</label>
                                        <input type="tel" name="customer_phone" class="form-control" required value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" pattern="[6-9][0-9]{9}" maxlength="10">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Delivery Address *</label>
                                    <textarea name="customer_address" class="form-control" rows="4" required placeholder="Enter your complete delivery address with landmark"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="customer_email" class="form-control" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Items Summary -->
                        <div class="card mt-4">
                            <div class="card-content">
                                <h3>Order Items</h3>
                                
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="d-flex align-items-center" style="padding: 1rem 0; border-bottom: 1px solid #eee;">
                                        <div style="margin-right: 1rem;">
                                            <?php 
                                            $images = json_decode($item['images'], true);
                                            if (!empty($images)): 
                                            ?>
                                                <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.8rem;">No Image</div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="flex: 1;">
                                            <h4 style="margin-bottom: 0.25rem; font-size: 1rem;"><?php echo htmlspecialchars($item['title']); ?></h4>
                                            <?php if ($item['size']): ?>
                                                <p style="color: #666; font-size: 0.9rem; margin: 0;">Size: <?php echo htmlspecialchars($item['size']); ?></p>
                                            <?php endif; ?>
                                            <p style="color: #666; font-size: 0.9rem; margin: 0;">Qty: <?php echo $item['quantity']; ?></p>
                                        </div>
                                        
                                        <div style="text-align: right;">
                                            <div style="font-weight: bold;">
                                                <?php echo formatPrice($item['selling_price'] * $item['quantity']); ?>
                                            </div>
                                            <?php if ($item['mrp_price'] > $item['selling_price']): ?>
                                                <div style="text-decoration: line-through; color: #999; font-size: 0.9rem;">
                                                    <?php echo formatPrice($item['mrp_price'] * $item['quantity']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 2rem;">
                            Proceed to Payment
                        </button>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div style="flex: 1;">
                    <div class="card">
                        <div class="card-content">
                            <h3>Order Summary</h3>
                            
                            <table style="width: 100%; margin-top: 1rem;">
                                <tr>
                                    <td style="padding: 0.5rem 0;">Subtotal:</td>
                                    <td style="text-align: right; padding: 0.5rem 0;"><?php echo formatPrice($subtotal); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0;">Shipping:</td>
                                    <td style="text-align: right; padding: 0.5rem 0;">
                                        <?php echo $shipping == 0 ? 'FREE' : formatPrice($shipping); ?>
                                    </td>
                                </tr>
                                <?php if ($shipping == 0): ?>
                                <tr>
                                    <td colspan="2" style="padding: 0.5rem 0; color: #28a745; font-size: 0.9rem;">
                                        🎉 You've qualified for free shipping!
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr style="border-top: 2px solid #000;">
                                    <td style="padding: 0.5rem 0; font-weight: bold;">Total:</td>
                                    <td style="text-align: right; padding: 0.5rem 0; font-weight: bold; font-size: 1.2rem;"><?php echo formatPrice($total_amount); ?></td>
                                </tr>
                            </table>
                            
                            <!-- Payment Methods -->
                            <div style="margin-top: 2rem;">
                                <h4 style="margin-bottom: 1rem;">Payment Method</h4>
                                <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; text-align: center;">
                                    <div style="color: #666; margin-bottom: 0.5rem;">Secure UPI Payment</div>
                                    <div style="display: flex; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="font-size: 1.5rem;">📱</div>
                                        <div style="font-size: 1.5rem;">💳</div>
                                        <div style="font-size: 1.5rem;">🔒</div>
                                    </div>
                                    <div style="color: #28a745; font-size: 0.9rem;">
                                        <i class="fas fa-shield-alt"></i> 100% Secure Payment
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Expected Delivery -->
                            <div style="margin-top: 2rem; padding: 1rem; background: #e3f2fd; border-radius: 5px;">
                                <div style="color: #1976d2; font-weight: bold; margin-bottom: 0.5rem;">
                                    <i class="fas fa-truck"></i> Expected Delivery
                                </div>
                                <div style="color: #666; font-size: 0.9rem;">
                                    <?php echo date('M j, Y', strtotime('+3 days')); ?> - <?php echo date('M j, Y', strtotime('+5 days')); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                <p style="color: #ccc;">&copy; <?php echo date('Y'); ?> FV FABLY VALOR. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
