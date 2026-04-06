<?php
require_once 'config.php';

// Handle AJAX requests
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to add products to cart']);
    exit;
}

$db = new Database();
$customer_id = $_SESSION['customer_id'];
$product_id = $_POST['product_id'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;
$size = $_POST['size'] ?? '';

// Validate product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Check stock
if ($product['stock_quantity'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    exit;
}

// Check if product already in cart
$stmt = $db->prepare("SELECT * FROM cart WHERE customer_id = ? AND product_id = ? AND size = ?");
$stmt->bind_param("iis", $customer_id, $product_id, $size);
$stmt->execute();
$existing_item = $stmt->get_result()->fetch_assoc();

if ($existing_item) {
    // Update quantity
    $new_quantity = $existing_item['quantity'] + $quantity;
    if ($new_quantity > $product['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
        exit;
    }
    
    $stmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_quantity, $existing_item['id']);
    $stmt->execute();
} else {
    // Add new item
    $stmt = $db->prepare("INSERT INTO cart (customer_id, product_id, quantity, size) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $customer_id, $product_id, $quantity, $size);
    $stmt->execute();
}

// Get updated cart count
$stmt = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_result = $stmt->get_result();
$cart_count = $cart_result->fetch_assoc()['total'] ?? 0;

echo json_encode([
    'success' => true, 
    'message' => 'Product added to cart successfully',
    'cart_count' => $cart_count
]);
?>
