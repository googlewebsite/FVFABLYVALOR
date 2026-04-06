<?php
require_once 'config.php';

// Check if admin is logged in
if (!isAdmin()) {
    header('Location: admin_login.php');
    exit();
}

$db = new Database();

// Get order details
$order_id = $_GET['id'];
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header('Location: admin_orders.php');
    exit();
}

// Parse product details
$product_details = json_decode($order['product_details'], true);

// Generate shipping label
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Label - <?php echo htmlspecialchars($order['order_id']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .shipping-label {
            width: 800px;
            height: 600px;
            background: white;
            margin: 0 auto;
            padding: 40px;
            border: 2px solid #333;
            position: relative;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            font-size: 24px;
            margin: 0;
            color: #000;
        }
        
        .logo p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }
        
        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .address-box {
            width: 45%;
            padding: 20px;
            border: 2px dashed #333;
            min-height: 150px;
        }
        
        .address-box h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #000;
        }
        
        .address-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .order-info h3 {
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        
        .product-list {
            margin-bottom: 20px;
        }
        
        .product-item {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .product-details {
            font-size: 12px;
            color: #666;
        }
        
        .barcode {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            border: 2px solid #333;
            background: #f8f9fa;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-top: 10px;
        }
        
        .footer {
            position: absolute;
            bottom: 20px;
            left: 40px;
            right: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .shipping-label {
                border: none;
                box-shadow: none;
                margin: 0;
            }
        }
        
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #000;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .print-btn:hover {
            background: #333;
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print Label</button>
    
    <div class="shipping-label">
        <!-- Logo and Company Info -->
        <div class="logo">
            <h1>Jemimah Fashion</h1>
            <p>Premium Fashion Collection</p>
            <p>📞 +91 98765 43210 | 📧 info@fvfablyvalor.com</p>
        </div>

        <!-- Addresses -->
        <div class="addresses">
            <!-- From Address -->
            <div class="address-box">
                <h3>FROM:</h3>
                <p><strong>Jemimah Fashion</strong></p>
                <p>123 Fashion Street</p>
                <p>Shopping District, Mumbai</p>
                <p>Maharashtra, 400001</p>
                <p>📞 +91 98765 43210</p>
            </div>

            <!-- To Address -->
            <div class="address-box">
                <h3>TO:</h3>
                <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                <p><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></p>
                <p>📞 <?php echo htmlspecialchars($order['customer_phone']); ?></p>
            </div>
        </div>

        <!-- Order Information -->
        <div class="order-info">
            <h3>Order Information</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px; border: 1px solid #ddd;"><strong>Order ID:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ddd;"><?php echo htmlspecialchars($order['order_id']); ?></td>
                    <td style="padding: 5px; border: 1px solid #ddd;"><strong>Order Date:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ddd;"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                </tr>
                <tr>
                    <td style="padding: 5px; border: 1px solid #ddd;"><strong>Total Amount:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ddd;"><?php echo formatPrice($order['total_amount']); ?></td>
                    <td style="padding: 5px; border: 1px solid #ddd;"><strong>Payment Status:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ddd;"><?php echo ucfirst($order['payment_status']); ?></td>
                </tr>
                <tr>
                    <td style="padding: 5px; border: 1px solid #ddd;"><strong>Order Status:</strong></td>
                    <td style="padding: 5px; border: 1px solid #ddd;"><?php echo ucfirst($order['status']); ?></td>
                </tr>
            </table>
        </div>

        <!-- Product List -->
        <div class="product-list">
            <h3>Products</h3>
            <?php foreach ($product_details as $item): ?>
            <div class="product-item">
                <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="product-details">
                    Size: <?php echo htmlspecialchars($item['size']); ?> | 
                    Quantity: <?php echo $item['quantity']; ?> | 
                    Price: <?php echo formatPrice($item['price']); ?> each
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Barcode Area -->
        <div class="barcode">
            <div style="font-size: 12px; margin-bottom: 5px;">TRACKING NUMBER</div>
            <svg width="200" height="50" id="barcode"></svg>
            <div class="barcode-number"><?php echo htmlspecialchars($order['order_id']); ?></div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated shipping label. No signature required.</p>
            <p>Generated on: <?php echo date('F j, Y h:i A'); ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        // Generate barcode
        JsBarcode("#barcode", "<?php echo htmlspecialchars($order['order_id']); ?>", {
            format: "CODE128",
            width: 2,
            height: 40,
            displayValue: false
        });
    </script>
</body>
</html>
