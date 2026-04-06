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
    header('Location: cart.php');
    exit();
}

// Get UPI settings from settings table
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key IN ('upi_id', 'business_name')");
$stmt->execute();
$settings_result = $stmt->get_result();
$settings = [];

while ($row = $settings_result->fetch_assoc()) {
    $settings[] = $row['setting_value'];
}

$upi_id = $settings[0] ?? 'jesuslifemylife@okaxis';
$business_name = $settings[1] ?? 'FV FABLY VALOR';

// Generate UPI link
$upi_link = "upi://pay?pa=$upi_id&pn=$business_name&am=$order[total_amount]&cu=INR&tn=FV$order_id";

// Generate QR code
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($upi_link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-container {
            max-width: 500px;
            margin: 2rem auto;
        }
        
        .payment-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .payment-header h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }
        
        .payment-header .amount {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .payment-body {
            padding: 2rem;
        }
        
        .qr-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .qr-code {
            display: inline-block;
            padding: 1rem;
            background: white;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .qr-code:hover {
            transform: scale(1.05);
        }
        
        .qr-code img {
            width: 180px;
            height: 180px;
        }
        
        .payment-apps {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .payment-app {
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .payment-app:hover {
            transform: translateY(-5px);
        }
        
        .payment-app img {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }
        
        .payment-app span {
            display: block;
            font-size: 0.8rem;
            color: #666;
        }
        
        .instructions {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        
        .instructions h4 {
            margin: 0 0 1rem 0;
            color: #333;
        }
        
        
        .instructions h4 {
            margin: 0 0 0.5rem 0;
            color: #1976d2;
        }
        
        .instructions ol {
            margin: 0;
            padding-left: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .instructions li {
            margin-bottom: 0.25rem;
        }
        
        .order-summary {
            background: #fff3e0;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .order-summary h4 {
            margin: 0 0 0.5rem 0;
            color: #f57c00;
        }
        
        .order-summary p {
            margin: 0.25rem 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .security-badge {
            text-align: center;
            padding: 1rem;
            background: #e8f5e8;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }
        
        .security-badge i {
            color: #28a745;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .security-badge p {
            margin: 0;
            color: #28a745;
            font-size: 0.9rem;
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

    <!-- Payment Section -->
    <section style="padding: 2rem 0;">
        <div class="payment-container">
            <div class="payment-box">
                <!-- Payment Header -->
                <div class="payment-header">
                    <h2>Complete Your Payment</h2>
                    <div class="amount"><?php echo formatPrice($order['total_amount']); ?></div>
                    <div style="font-size: 0.9rem; opacity: 0.8;">Order ID: <?php echo htmlspecialchars($order_id); ?></div>
                </div>
                
                <div class="payment-body">
                    <!-- QR Code Section -->
                    <div class="qr-section">
                        <div class="qr-code" onclick="openUPIApp()">
                            <img src="<?php echo $qr_code_url; ?>" alt="UPI QR Code">
                        </div>
                        <p style="color: #666; margin-bottom: 1rem;">Scan QR code or click to pay</p>
                        
                        <!-- Payment Apps -->
                        <div class="payment-apps">
                            <div class="payment-app" onclick="openUPIApp()" style="background-color: #4285F4; color: white; padding: 15px 20px; border-radius: 10px; text-align: center; cursor: pointer; transition: transform 0.3s; margin: 5px; display: inline-block; font-weight: bold;">
                                Google Pay
                            </div>
                            <div class="payment-app" onclick="openUPIApp()" style="background-color: #673AB7; color: white; padding: 15px 20px; border-radius: 10px; text-align: center; cursor: pointer; transition: transform 0.3s; margin: 5px; display: inline-block; font-weight: bold;">
                                PhonePe
                            </div>
                            <div class="payment-app" onclick="openUPIApp()" style="background-color: #00B0FF; color: white; padding: 15px 20px; border-radius: 10px; text-align: center; cursor: pointer; transition: transform 0.3s; margin: 5px; display: inline-block; font-weight: bold;">
                                Paytm
                            </div>
                        </div>
                        
                        
                    </div>
                    
                    <!-- Payment Instructions -->
                    <div class="instructions">
                        <h4><i class="fas fa-info-circle"></i> How to Pay</h4>
                        <ol>
                            <li>Scan the QR code with any UPI app</li>
                            <li>Or click on any payment app button above</li>
                            <li>Enter the amount: <?php echo formatPrice($order['total_amount']); ?></li>
                            <li>Complete the payment in your UPI app</li>
                            <li>Your payment will be automatically verified</li>
                        </ol>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="order_confirmation.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary" style="font-size: 1.1rem; padding: 12px 24px;">
                            View Order Confirmation
                        </a>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <h4><i class="fas fa-receipt"></i> Order Summary</h4>
                        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?></p>
                        <p><strong>Amount:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
                        <p><strong>Payment Method:</strong> UPI</p>
                        <p><strong>Merchant:</strong> <?php echo htmlspecialchars($business_name); ?></p>
                    </div>
                    
                    <!-- Security Badge -->
                    <div class="security-badge">
                        <i class="fas fa-shield-alt"></i>
                        <p><strong>100% Secure Payment</strong></p>
                        <p>Your payment information is encrypted and secure</p>
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

    <script>
        // UPI Details from PHP
        const upiLink = "<?php echo $upi_link; ?>";
        
        function openUPIApp() {
            // Try to open UPI app
            window.location.href = upiLink;
            
            // Fallback: Copy UPI link to clipboard
            setTimeout(() => {
                if (confirm('UPI app not opening? Click OK to copy payment link to clipboard.')) {
                    navigator.clipboard.writeText(upiLink).then(() => {
                        alert('Payment link copied! Paste it in your UPI app.');
                    });
                }
            }, 1000);
        }
        
        // Payment app click handlers
        document.querySelectorAll('.payment-app').forEach(app => {
            app.addEventListener('click', function() {
                const paymentMethod = this.dataset.app;
                console.log('Opening payment app:', paymentMethod);
            });
        });
    </script>
</body>
</html>
