<?php
require_once 'config.php';

$db = new Database();

// Get product details
$product_id = $_GET['id'];
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit();
}

// Parse product data
$images = json_decode($product['images'], true) ?? [];
$sizes = json_decode($product['sizes'], true) ?? [];
$video = $product['video'];

// Get related products
$stmt = $db->prepare("SELECT * FROM products WHERE id != ? AND status = 'active' ORDER BY RAND() LIMIT 4");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$related_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get cart count
$cart_count = 0;
if (isLoggedIn()) {
    $customer_id = $_SESSION['customer_id'];
    $result = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
    $result->bind_param("i", $customer_id);
    $result->execute();
    $cart_result = $result->get_result();
    $cart_count = $cart_result->fetch_assoc()['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title']); ?> - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .product-gallery {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .main-image {
            flex: 1;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .main-image img {
            width: 100%;
            height: 500px;
            object-fit: cover;
        }
        
        .thumbnail-list {
            width: 80px;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 5px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.3s;
        }
        
        .thumbnail.active,
        .thumbnail:hover {
            border-color: #000;
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding: 2rem 0;
        }
        
        .product-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #000;
        }
        
        .product-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: #000;
            margin-bottom: 1rem;
        }
        
        .product-price .original {
            text-decoration: line-through;
            color: #999;
            font-size: 1.2rem;
            margin-left: 0.5rem;
        }
        
        .product-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .size-selector {
            margin-bottom: 2rem;
        }
        
        .size-options {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .size-option {
            padding: 0.75rem 1.5rem;
            border: 2px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .size-option:hover {
            border-color: #000;
        }
        
        .size-option.selected {
            border-color: #000;
            background: #000;
            color: white;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: #f8f9fa;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .quantity-input {
            width: 60px;
            height: 40px;
            text-align: center;
            border: none;
            font-size: 1rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-buttons .btn {
            flex: 1;
            padding: 1rem;
            font-size: 1.1rem;
        }
        
        .product-meta {
            padding: 2rem 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin-bottom: 2rem;
        }
        
        .meta-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }
        
        .meta-label {
            font-weight: 600;
            color: #333;
        }
        
        .meta-value {
            color: #666;
        }
        
        .video-container {
            margin-top: 2rem;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .video-container video {
            width: 100%;
            height: auto;
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
                        <?php if (isLoggedIn()): ?>
                            <span id="wishlist-count" style="position: absolute; top: -8px; right: -8px; background: red; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center;">0</span>
                        <?php endif; ?>
                    </a>
                    <a href="cart.php" title="Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span style="position: absolute; top: -8px; right: -8px; background: red; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <a href="account.php" title="My Account">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" title="Login/Register">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Product Details -->
    <section style="padding: 2rem 0;">
        <div class="container">
            <div class="grid grid-2">
                <!-- Product Gallery -->
                <div>
                    <div class="product-gallery">
                        <div class="main-image">
                            <img id="mainImage" src="<?php echo !empty($images) ? htmlspecialchars($images[0]) : 'https://via.placeholder.com/500x500'; ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                        </div>
                        
                        <?php if (!empty($images)): ?>
                            <div class="thumbnail-list">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="thumbnail <?php echo $index == 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo htmlspecialchars($image); ?>', this)">
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Product image <?php echo $index + 1; ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($video): ?>
                        <div class="video-container">
                            <video controls>
                                <source src="<?php echo htmlspecialchars($video); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Information -->
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                    
                    <div class="product-price">
                        <?php echo formatPrice($product['selling_price']); ?>
                        <?php if ($product['mrp_price'] > $product['selling_price']): ?>
                            <span class="original"><?php echo formatPrice($product['mrp_price']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <?php if (!empty($sizes)): ?>
                        <div class="size-selector">
                            <label class="form-label">Select Size:</label>
                            <div class="size-options">
                                <?php foreach ($sizes as $size): ?>
                                    <div class="size-option" onclick="selectSize(this, '<?php echo htmlspecialchars($size); ?>')">
                                        <?php echo htmlspecialchars($size); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="quantity-selector">
                        <label class="form-label">Quantity:</label>
                        <div class="quantity-controls">
                            <button class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                            <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" readonly>
                            <button class="quantity-btn" onclick="changeQuantity(1)">+</button>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="addToCart()">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                        <button class="btn btn-secondary" onclick="buyNow()">
                            <i class="fas fa-bolt"></i> Buy Now
                        </button>
                    </div>
                    
                    <div class="product-meta">
                        <div class="meta-item">
                            <span class="meta-label">Availability:</span>
                            <span class="meta-value">
                                <?php if ($product['stock_quantity'] > 10): ?>
                                    <span style="color: #28a745;">✓ In Stock</span>
                                <?php elseif ($product['stock_quantity'] > 0): ?>
                                    <span style="color: #ffc107;">⚠ Only <?php echo $product['stock_quantity']; ?> left</span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">✗ Out of Stock</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Product ID:</span>
                            <span class="meta-value">#FV<?php echo str_pad($product['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Category:</span>
                            <span class="meta-value">Fashion</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <section style="padding: 4rem 0; background: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-4">
                <h2 style="font-size: 2rem; margin-bottom: 1rem;">Related Products</h2>
            </div>
            
            <div class="grid grid-4">
                <?php foreach ($related_products as $related_product): ?>
                    <div class="card">
                        <?php 
                        $related_images = json_decode($related_product['images'], true);
                        if (!empty($related_images)): 
                        ?>
                            <img src="<?php echo htmlspecialchars($related_images[0]); ?>" alt="<?php echo htmlspecialchars($related_product['title']); ?>" class="card-image">
                        <?php else: ?>
                            <div style="width: 100%; height: 250px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                No Image
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($related_product['title']); ?></h3>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($related_product['short_description']); ?></p>
                            
                            <div class="card-price">
                                <?php echo formatPrice($related_product['selling_price']); ?>
                                <?php if ($related_product['mrp_price'] > $related_product['selling_price']): ?>
                                    <span class="original"><?php echo formatPrice($related_product['mrp_price']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex" style="gap: 0.5rem; margin-top: 1rem;">
                                <a href="product.php?id=<?php echo $related_product['id']; ?>" class="btn btn-primary" style="flex: 1;">View Details</a>
                                <button class="btn btn-secondary" onclick="addToCartRelated(<?php echo $related_product['id']; ?>)" style="padding: 0.75rem;">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

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
        let selectedSize = '';
        let productId = <?php echo $product_id; ?>;
        
        function changeImage(imageSrc, thumbnail) {
            document.getElementById('mainImage').src = imageSrc;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }
        
        function selectSize(element, size) {
            // Remove previous selection
            document.querySelectorAll('.size-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selection to clicked element
            element.classList.add('selected');
            selectedSize = size;
        }
        
        function changeQuantity(change) {
            const quantityInput = document.getElementById('quantity');
            const currentQuantity = parseInt(quantityInput.value);
            const maxQuantity = <?php echo $product['stock_quantity']; ?>;
            
            const newQuantity = currentQuantity + change;
            if (newQuantity >= 1 && newQuantity <= maxQuantity) {
                quantityInput.value = newQuantity;
            }
        }
        
        function addToCart() {
            <?php if (!isLoggedIn()): ?>
                if (confirm('Please login to add products to cart. Redirect to login page?')) {
                    window.location.href = 'login.php';
                }
            <?php else: ?>
                if (<?php echo !empty($sizes) ? 'selectedSize === ""' : 'false'; ?>) {
                    alert('Please select a size');
                    return;
                }
                
                const quantity = document.getElementById('quantity').value;
                
                // Add to cart via AJAX
                fetch('ajax_add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId + '&quantity=' + quantity + '&size=' + selectedSize
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product added to cart successfully!');
                        // Update cart count
                        const cartCount = document.querySelector('.nav-icons a[href="cart.php"] span');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count;
                        }
                    } else {
                        alert(data.message || 'Failed to add product to cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to add product to cart');
                });
            <?php endif; ?>
        }
        
        function buyNow() {
            <?php if (!isLoggedIn()): ?>
                if (confirm('Please login to continue with purchase. Redirect to login page?')) {
                    window.location.href = 'login.php';
                }
            <?php else: ?>
                if (<?php echo !empty($sizes) ? 'selectedSize === ""' : 'false'; ?>) {
                    alert('Please select a size');
                    return;
                }
                
                const quantity = document.getElementById('quantity').value;
                
                // Redirect to checkout with product details
                const params = new URLSearchParams({
                    product_id: productId,
                    quantity: quantity,
                    size: selectedSize,
                    buy_now: '1'
                });
                
                window.location.href = 'checkout.php?' + params.toString();
            <?php endif; ?>
        }
        
        function addToCartRelated(relatedProductId) {
            <?php if (!isLoggedIn()): ?>
                if (confirm('Please login to add products to cart. Redirect to login page?')) {
                    window.location.href = 'login.php';
                }
            <?php else: ?>
                // Add to cart via AJAX
                fetch('ajax_add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + relatedProductId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Product added to cart successfully!');
                        // Update cart count
                        const cartCount = document.querySelector('.nav-icons a[href="cart.php"] span');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count;
                        }
                    } else {
                        alert(data.message || 'Failed to add product to cart');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to add product to cart');
                });
            <?php endif; ?>
        }
    </script>
</body>
</html>
