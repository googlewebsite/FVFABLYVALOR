<?php
require_once 'config.php';

// Handle photo uploads
if ($_POST && $_FILES) {
    $action = $_POST['action'];
    
    if ($action == 'upload_profile_photo') {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            exit();
        }
        
        $customer_id = $_SESSION['customer_id'];
        $db = new Database();
        
        // Handle file upload
        if (!empty($_FILES['profile_photo']['name'])) {
            $file = $_FILES['profile_photo'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (in_array($file['type'], $allowed_types)) {
                $upload_dir = 'uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = 'customer_' . $customer_id . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // Update customer profile photo in database
                    $stmt = $db->prepare("UPDATE customers SET profile_photo = ? WHERE id = ?");
                    $stmt->bind_param("si", $file_path, $customer_id);
                    $stmt->execute();
                    
                    echo json_encode(['success' => true, 'photo_url' => $file_path]);
                    exit();
                }
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit();
    }
    
    if ($action == 'update_profile') {
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            exit();
        }
        
        $customer_id = $_SESSION['customer_id'];
        $db = new Database();
        
        $name = $_POST['name'];
        $phone = $_POST['phone'];
        
        $stmt = $db->prepare("UPDATE customers SET name = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $phone, $customer_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        exit();
    }
    
    if ($action == 'toggle_like') {
        $product_id = $_POST['product_id'];
        $customer_id = $_SESSION['customer_id'];
        $db = new Database();
        
        // Check if already liked
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE customer_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $customer_id, $product_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            // Remove from wishlist
            $stmt = $db->prepare("DELETE FROM wishlist WHERE customer_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $customer_id, $product_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'liked' => false, 'message' => 'Removed from wishlist']);
        } else {
            // Add to wishlist
            $stmt = $db->prepare("INSERT INTO wishlist (customer_id, product_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $customer_id, $product_id);
            $stmt->execute();
            echo json_encode(['success' => true, 'liked' => true, 'message' => 'Added to wishlist']);
        }
        exit();
    }
}

// Get customer data for display
$customer_data = null;
if (isLoggedIn()) {
    $customer_id = $_SESSION['customer_id'];
    $db = new Database();
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $customer_data = $stmt->get_result()->fetch_assoc();
}

header('Content-Type: application/json');
echo json_encode(['customer' => $customer_data]);
?>
