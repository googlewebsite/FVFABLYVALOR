<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$db = new Database();

// Get order details
$order_id = $_GET['order_id'];
$stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ? AND customer_id = ?");
$stmt->bind_param("si", $order_id, $_SESSION['customer_id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header('Location: account.php');
    exit();
}

// Parse product details
$product_details = json_decode($order['product_details'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 2rem auto;
        }
        
        .success-animation {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .success-circle {
            width: 100px;
            height: 100px;
            background: #28a745;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            animation: scaleIn 0.5s ease-out;
        }
        
        .success-circle i {
            color: white;
            font-size: 3rem;
        }
        
        @keyframes scaleIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .confirmation-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .confirmation-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .confirmation-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .confirmation-header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .confirmation-body {
            padding: 2rem;
        }
        
        .order-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .order-info h3 {
            margin: 0 0 1rem 0;
            color: #333;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
        }
        
        .info-value {
            color: #333;
            text-align: right;
        }
        
        .payment-status {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .payment-status i {
            color: #f39c12;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .product-list {
            margin-bottom: 2rem;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-icon {
            width: 40px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: #666;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .product-meta {
            font-size: 0.9rem;
            color: #666;
        }
        
        .product-price {
            font-weight: bold;
            color: #333;
        }
        
        .next-steps {
            background: #e3f2fd;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .next-steps h3 {
            margin: 0 0 1rem 0;
            color: #1976d2;
        }
        
        .next-steps ol {
            margin: 0;
            padding-left: 1.5rem;
            color: #666;
        }
        
        .next-steps li {
            margin-bottom: 0.5rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-buttons .btn {
            flex: 1;
            padding: 1rem;
            font-size: 1rem;
        }
        
        .contact-info {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            margin-top: 1rem;
        }
        
        .contact-info p {
            margin: 0.25rem 0;
            color: #666;
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
                    <a href="account.php" title="My Account">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Confirmation Section -->
    <section style="padding: 2rem 0;">
        <div class="confirmation-container">
            <!-- Success Animation -->
            <div class="success-animation">
                <div class="success-circle">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            
            <div class="confirmation-box">
                <!-- Confirmation Header -->
                <div class="confirmation-header">
                    <h1>Order Successful!</h1>
                    <p>Thank you for your purchase. Your order has been received.</p>
                </div>
                
                <div class="confirmation-body">
                    <!-- Order Information -->
                    <div class="order-info">
                        <h3><i class="fas fa-receipt"></i> Order Details</h3>
                        <div class="info-row">
                            <span class="info-label">Order ID:</span>
                            <span class="info-value"><strong><?php echo htmlspecialchars($order_id); ?></strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Order Date:</span>
                            <span class="info-value"><?php echo date('M j, Y h:i A', strtotime($order['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Total Amount:</span>
                            <span class="info-value"><strong><?php echo formatPrice($order['total_amount']); ?></strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Status:</span>
                            <span class="info-value">
                                <span style="color: #f39c12;">
                                    <i class="fas fa-clock"></i> Pending Verification
                                </span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Payment Status -->
                    <div class="payment-status">
                        <i class="fas fa-hourglass-half"></i>
                        <h4>Payment Verification in Progress</h4>
                        <p>We are verifying your payment. You will receive a confirmation once it's processed.</p>
                        <p><small>Usually takes 5-10 minutes during business hours.</small></p>
                    </div>
                    
                    <!-- Product List -->
                    <div class="product-list">
                        <h3><i class="fas fa-box"></i> Ordered Items</h3>
                        <?php foreach ($product_details as $item): ?>
                            <div class="product-item">
                                <div class="product-icon">
                                    <i class="fas fa-tshirt"></i>
                                </div>
                                <div class="product-details">
                                    <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="product-meta">
                                        Size: <?php echo htmlspecialchars($item['size']); ?> | Qty: <?php echo $item['quantity']; ?>
                                    </div>
                                </div>
                                <div class="product-price">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Next Steps -->
                    <div class="next-steps">
                        <h3><i class="fas fa-list-ol"></i> What's Next?</h3>
                        <ol>
                            <li>Payment verification (usually 5-10 minutes)</li>
                            <li>Order confirmation via WhatsApp/SMS</li>
                            <li>Order processing (1-2 business days)</li>
                            <li>Shipping with tracking details</li>
                            <li>Delivery to your address</li>
                        </ol>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="account.php" class="btn btn-primary">
                            <i class="fas fa-user"></i> My Account
                        </a>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                        </a>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="contact-info">
                        <p><strong>Need Help?</strong></p>
                        <p>📞 +91 98765 43210</p>
                        <p>📧 support@fvfablyvalor.com</p>
                        <p>Order ID: <strong><?php echo htmlspecialchars($order_id); ?></strong></p>
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
