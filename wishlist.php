<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

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

// Debug: Check if prepare method exists
if (!method_exists($db, 'prepare')) {
    die('Database prepare method not available');
}

echo "<!-- Debug: Database connection status: " . ($db->conn ? 'OK' : 'FAILED') . " -->";

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

// Get wishlist items
$wishlist_items = [];
if (isLoggedIn()) {
    $customer_id = $_SESSION['customer_id'];
    $db = new Database();
    $stmt = $db->prepare("
        SELECT w.*, p.title, p.short_description, p.selling_price, p.mrp_price, p.images 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        WHERE w.customer_id = ? 
        ORDER BY w.created_at DESC
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $wishlist_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .wishlist-container {
            max-width: 1200px;
            margin: 2rem auto;
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 32px;
        }
        
        .wishlist-item {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .wishlist-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.15);
        }
        
        .wishlist-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .wishlist-image:hover {
            transform: scale(1.05);
        }
        
        .wishlist-content {
            padding: 20px;
        }
        
        .wishlist-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a1a1a;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .wishlist-title:hover {
            color: #667eea;
        }
        
        .wishlist-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #000000;
            margin-bottom: 16px;
        }
        
        .wishlist-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .wishlist-heart {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .wishlist-heart:hover {
            background: #ff4757;
            transform: scale(1.1);
        }
        
        .wishlist-heart.liked {
            background: #ff4757;
            color: white;
        }
        
        .empty-wishlist {
            text-align: center;
            padding: 80px 20px;
            color: #666;
        }
        
        .empty-wishlist i {
            font-size: 4rem;
            margin-bottom: 24px;
            opacity: 0.5;
        }
        
        .btn-remove {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }
        
        .btn-remove:hover {
            background: #c82333;
        }
        
        .btn-add-to-cart {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s ease;
            flex: 1;
        }
        
        .btn-add-to-cart:hover {
            background: #218838;
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
                    <a href="wishlist.php" title="Wishlist" class="active">
                        <i class="fas fa-heart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span style="position: absolute; top: -8px; right: -8px; background: red; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo count($wishlist_items); ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="cart.php" title="Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span style="position: absolute; top: -8px; right: -8px; background: red; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="account.php" title="My Account">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Wishlist Section -->
    <section style="padding: 2rem 0;">
        <div class="wishlist-container">
            <h2 style="margin-bottom: 32px;">My Wishlist</h2>
            
            <?php if (!isLoggedIn()): ?>
                <div class="empty-wishlist">
                    <i class="fas fa-heart"></i>
                    <h3>Please Login to View Your Wishlist</h3>
                    <p>Sign in to your account to see your saved items and continue shopping.</p>
                    <div style="margin-top: 24px;">
                        <a href="account.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 16px 32px;">
                            <i class="fas fa-sign-in-alt"></i> Login to Your Account
                        </a>
                    </div>
                </div>
            <?php elseif (empty($wishlist_items)): ?>
                <div class="empty-wishlist">
                    <i class="fas fa-heart"></i>
                    <h3>Your Wishlist is Empty</h3>
                    <p>Start adding items you love to your wishlist and never lose track of them.</p>
                    <div style="margin-top: 24px;">
                        <a href="products.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 16px 32px;">
                            <i class="fas fa-shopping-bag"></i> Start Shopping
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="wishlist-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <button class="wishlist-heart liked" onclick="toggleWishlist(<?php echo $item['product_id']; ?>, this)" title="Remove from wishlist">
                                <i class="fas fa-heart"></i>
                                <span class="wishlist-count" style="position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo count($wishlist_items); ?></span>
                            </button>
                            
                            <?php 
                            $images = json_decode($item['images'], true);
                            if (!empty($images)): 
                            ?>
                                <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="wishlist-image" onclick="viewProduct(<?php echo $item['product_id']; ?>)">
                            <?php else: ?>
                                <div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999; cursor: pointer;" onclick="viewProduct(<?php echo $item['product_id']; ?>)">
                                    <i class="fas fa-image" style="font-size: 3rem; opacity: 0.5;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="wishlist-content">
                                <h3 class="wishlist-title" onclick="viewProduct(<?php echo $item['product_id']; ?>)">
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </h3>
                                <p style="color: #666; font-size: 0.9rem; margin-bottom: 12px; line-height: 1.4;">
                                    <?php echo htmlspecialchars($item['short_description']); ?>
                                </p>
                                
                                <div class="wishlist-price">
                                    <?php echo formatPrice($item['selling_price']); ?>
                                    <?php if ($item['mrp_price'] > $item['selling_price']): ?>
                                        <span style="color: #999; text-decoration: line-through; font-size: 0.9rem; margin-left: 8px;">
                                            <?php echo formatPrice($item['mrp_price']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="wishlist-actions">
                                    <button class="btn-add-to-cart" onclick="addToCartFromWishlist(<?php echo $item['product_id']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                    <button class="btn-remove" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>, this)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
                        📧 info@jemimahfashion.com<br>
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
        function toggleWishlist(productId, button) {
            <?php if (!isLoggedIn()): ?>
                if (confirm('Please login to manage your wishlist. Redirect to login page?')) {
                    window.location.href = 'account.php';
                }
                return;
            <?php endif; ?>
            
            fetch('ajax_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=toggle_like&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.liked) {
                        button.classList.add('liked');
                        showNotification('Added to wishlist!', 'success');
                    } else {
                        button.classList.remove('liked');
                        showNotification('Removed from wishlist', 'info');
                    }
                    
                    // Update wishlist count
                    updateWishlistCount();
                } else {
                    showNotification(data.message || 'Failed to update wishlist', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to update wishlist', 'error');
            });
        }
        
        function removeFromWishlist(productId, button) {
            if (!confirm('Are you sure you want to remove this item from your wishlist?')) {
                toggleWishlist(productId, button.closest('.wishlist-item').querySelector('.wishlist-heart'));
            }
        }
        
        function addToCartFromWishlist(productId) {
            <?php if (!isLoggedIn()): ?>
                if (confirm('Please login to add items to cart. Redirect to login page?')) {
                    window.location.href = 'account.php';
                }
                return;
            <?php endif; ?>
            
            // Add to cart logic
            fetch('ajax_add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&quantity=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Product added to cart successfully!', 'success');
                    updateCartCount(data.cart_count);
                } else {
                    showNotification(data.message || 'Failed to add to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to add to cart', 'error');
            });
        }
        
        function viewProduct(productId) {
            window.location.href = 'product.php?id=' + productId;
        }
        
        function updateWishlistCount() {
            // Update wishlist count in navigation
            fetch('ajax_profile.php')
            .then(response => response.json())
            .then(data => {
                if (data.customer) {
                    const wishlistCount = document.querySelector('.nav-icons a[href="wishlist.php"] span');
                    if (wishlistCount) {
                        const count = document.querySelectorAll('.wishlist-item').length;
                        if (count > 0) {
                            wishlistCount.textContent = count;
                            wishlistCount.style.display = 'flex';
                        } else {
                            wishlistCount.style.display = 'none';
                        }
                    }
                }
            });
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
        
        // Initialize wishlist count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateWishlistCount();
        });
    </script>
</body>
</html>
