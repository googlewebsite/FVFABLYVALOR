<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Debug: Check if user is logged in
echo "<!-- Debug: Checking login status -->";

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=cart.php');
    exit();
}

// Debug: Check if config file is loaded
if (!class_exists('Database')) {
    die('Database class not found. Please check config.php');
}

// Debug: Check if Database class can be instantiated
try {
    $db = new Database();
} catch (Exception $e) {
    die('Database instantiation failed: ' . $e->getMessage());
}

// Debug: Check if database connection is successful
if (!isset($db) || !is_object($db)) {
    die('Database object not created properly');
}

if (!isset($db->conn) || $db->conn->connect_error) {
    die('Database connection failed: ' . $db->conn->connect_error);
}

echo "<!-- Debug: Database connection status: " . ($db->conn ? 'OK' : 'FAILED') . " -->";

$customer_id = $_SESSION['customer_id'];

// Handle cart operations
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action == 'update_quantity') {
        $cart_id = $_POST['cart_id'];
        $quantity = $_POST['quantity'];
        
        $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
        $stmt->bind_param("iii", $quantity, $cart_id, $customer_id);
        $stmt->execute();
        
        header('Location: cart.php');
        exit();
    }
    
    if ($action == 'remove_item') {
        $cart_id = $_POST['cart_id'];
        
        $stmt = $db->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
        $stmt->bind_param("ii", $cart_id, $customer_id);
        $stmt->execute();
        
        header('Location: cart.php');
        exit();
    }
    
    if ($action == 'clear_cart') {
        $stmt = $db->prepare("DELETE FROM cart WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        
        header('Location: cart.php');
        exit();
    }
}

// Get cart items with product details
$stmt = $db->prepare("
    SELECT c.*, p.title, p.selling_price, p.mrp_price, p.stock_quantity, p.images
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.customer_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $item_total = $item['selling_price'] * $item['quantity'];
    $subtotal += $item_total;
    $total_items += $item['quantity'];
}

$shipping = $subtotal > 999 ? 0 : 50; // Free shipping above ₹999
$total_amount = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Jemimah Fashion</title>
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
                        <?php if ($total_items > 0): ?>
                            <span style="position: absolute; top: -8px; right: -8px; background: red; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo $total_items; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="account.php" title="My Account">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Cart Section -->
    <section style="padding: 2rem 0;">
        <div class="container">
            <h1 style="margin-bottom: 2rem;">Shopping Cart</h1>
            
            <?php if (!empty($cart_items)): ?>
                <div class="d-flex" style="gap: 2rem;">
                    <!-- Cart Items -->
                    <div style="flex: 2;">
                        <div class="card">
                            <div class="card-content">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h3>Cart Items (<?php echo $total_items; ?>)</h3>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="clear_cart">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to clear your cart?')" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Clear Cart</button>
                                    </form>
                                </div>
                                
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="d-flex align-items-center" style="padding: 1rem 0; border-bottom: 1px solid #eee;">
                                        <!-- Product Image -->
                                        <div style="margin-right: 1rem;">
                                            <?php 
                                            $images = json_decode($item['images'], true);
                                            if (!empty($images)): 
                                            ?>
                                                <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px;">
                                            <?php else: ?>
                                                <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999;">No Image</div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Product Details -->
                                        <div style="flex: 1;">
                                            <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($item['title']); ?></h4>
                                            <?php if ($item['size']): ?>
                                                <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;">Size: <?php echo htmlspecialchars($item['size']); ?></p>
                                            <?php endif; ?>
                                            <div style="color: #000; font-weight: bold;">
                                                <?php echo formatPrice($item['selling_price']); ?>
                                                <?php if ($item['mrp_price'] > $item['selling_price']): ?>
                                                    <span style="text-decoration: line-through; color: #999; font-size: 0.9rem;"><?php echo formatPrice($item['mrp_price']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Quantity -->
                                        <div style="margin-right: 1rem;">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_quantity">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <div class="quantity-controls">
                                                    <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity']; ?>, -1)">-</button>
                                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" class="quantity-input" id="quantity-<?php echo $item['id']; ?>" onchange="this.form.submit()">
                                                    <button type="button" class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity']; ?>, 1)">+</button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <!-- Item Total -->
                                        <div style="margin-right: 1rem; text-align: right;">
                                            <div style="font-weight: bold; font-size: 1.1rem;">
                                                <?php echo formatPrice($item['selling_price'] * $item['quantity']); ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Remove Button -->
                                        <div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 0.5rem; background: none; border: none; color: #dc3545;" title="Remove item">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
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
                                
                                <div style="margin-top: 2rem;">
                                    <a href="checkout.php" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-bottom: 1rem;">
                                        Proceed to Checkout
                                    </a>
                                    <a href="products.php" class="btn btn-secondary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                                        Continue Shopping
                                    </a>
                                </div>
                                
                                <!-- Security Badge -->
                                <div style="text-align: center; margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                                    <div style="color: #666; font-size: 0.9rem; margin-bottom: 0.5rem;">Secure Checkout</div>
                                    <div style="color: #28a745; font-size: 0.8rem;">
                                        <i class="fas fa-lock"></i> 100% Secure UPI Payment
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center" style="padding: 4rem 2rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">🛒</div>
                    <h2>Your cart is empty</h2>
                    <p style="color: #666; margin-bottom: 2rem;">Looks like you haven't added any products to your cart yet.</p>
                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
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
                <p style="color: #ccc;">&copy; <?php echo date('Y'); ?> Jemimah Fashion. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function updateQuantity(cartId, currentQuantity, change) {
            const newQuantity = currentQuantity + change;
            if (newQuantity >= 1) {
                const quantityInput = document.getElementById('quantity-' + cartId);
                quantityInput.value = newQuantity;
                quantityInput.form.submit();
            }
        }
    </script>
</body>
</html>
