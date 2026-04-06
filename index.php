<?php
require_once 'config.php';

$db = new Database();

// Get featured products
$result = $db->query("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 8");
$featured_products = $result->fetch_all(MYSQLI_ASSOC);

// Get banners for homepage
$result = $db->query("SELECT * FROM banners WHERE status = 'active' AND position = 'home' ORDER BY created_at DESC");
$banners = $result->fetch_all(MYSQLI_ASSOC);

// Get trending products (most ordered)
$result = $db->query("
    SELECT p.*, COUNT(o.id) as order_count 
    FROM products p 
    LEFT JOIN orders o ON JSON_CONTAINS(o.product_details, JSON_QUOTE(p.title), '$.name')
    WHERE p.status = 'active' 
    GROUP BY p.id 
    ORDER BY order_count DESC, p.created_at DESC 
    LIMIT 8
");
$trending_products = $result->fetch_all(MYSQLI_ASSOC);

// Get cart count
$cart_count = 0;
if (isLoggedIn()) {
    $customer_id = $_SESSION['customer_id'];
    $result = $db->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
    $result->bind_param("i", $customer_id);
    $result->execute();
    $cart_result = $result->get_result();
    $cart_count = $cart_result->fetch_assoc()['total'] ?? 0;
    
    // Get wishlist items for like button state
    $wishlist_result = $db->prepare("SELECT product_id FROM wishlist WHERE customer_id = ?");
    $wishlist_result->bind_param("i", $customer_id);
    $wishlist_result->execute();
    $wishlist_items = $wishlist_result->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Jemimah Fashion</title>
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
                        <input type="text" name="search" placeholder="Search for products..." value="<?php echo $_GET['search'] ?? ''; ?>">
                        <button type="submit" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">Home</a></li>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content animate-fade-in">
                <h1 class="hero-title">Jemimah Fashion</h1>
                <p class="hero-subtitle">Discover Premium Fashion Collection with Unmatched Style and Quality</p>
                <div class="d-flex justify-content-center" style="gap: 1rem;">
                    <a href="products.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 16px 32px;">
                        <i class="fas fa-shopping-bag"></i> Shop Now
                    </a>
                    <a href="#featured" class="btn btn-secondary" style="font-size: 1.1rem; padding: 16px 32px; background: rgba(255,255,255,0.2); color: white; border: 2px solid white;">
                        <i class="fas fa-eye"></i> Explore Collection
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Banner Section -->
    <section style="padding: 4rem 0;" id="featured">
        <div class="container">
            <?php if (!empty($banners)): ?>
                <div class="banner-slider">
                    <div class="banner-item">
                        <img src="<?php echo htmlspecialchars($banners[0]['image']); ?>" alt="<?php echo htmlspecialchars($banners[0]['title']); ?>">
                        <div class="banner-overlay">
                            <h2 class="banner-title"><?php echo htmlspecialchars($banners[0]['title']); ?></h2>
                            <p class="banner-description">Exclusive offers just for you</p>
                            <?php if ($banners[0]['link']): ?>
                                <a href="<?php echo htmlspecialchars($banners[0]['link']); ?>" class="btn btn-primary">Shop Now</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Default Banner -->
                <div class="banner-slider">
                    <div class="banner-item">
                        <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1400&h=500&fit=crop" alt="Fashion Collection">
                        <div class="banner-overlay">
                            <h2 class="banner-title">New Season Collection</h2>
                            <p class="banner-description">Up to 50% off on selected items</p>
                            <a href="products.php" class="btn btn-primary">Shop Now</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Products -->
    <section style="padding: 4rem 0;">
        <div class="container">
            <div class="text-center mb-4">
                <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Featured Products</h2>
                <p style="color: #666; font-size: 1.1rem;">Handpicked selection of our finest collection</p>
            </div>
            
            <div class="grid grid-4">
                <?php foreach ($featured_products as $index => $product): ?>
                    <div class="card product-card animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                        <?php if ($product['mrp_price'] > $product['selling_price']): ?>
                            <div class="product-badge">SALE</div>
                        <?php endif; ?>
                        
                        <button class="product-wishlist <?php echo $wishlist_items && in_array($product['id'], array_column($wishlist_items, 'product_id')) ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $product['id']; ?>, this)" title="<?php echo $wishlist_items && in_array($product['id'], array_column($wishlist_items, 'product_id')) ? 'Remove from wishlist' : 'Add to wishlist'; ?>">
                                    <i class="fas fa-heart"></i>
                                    <span class="wishlist-count" style="position: absolute; top: -8px; right: -8px; background: #ff4757; color: white; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo count($wishlist_items); ?></span>
                                </button>
                        
                        <?php 
                        $images = json_decode($product['images'], true);
                        if (!empty($images)): 
                        ?>
                            <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="card-image">
                        <?php else: ?>
                            <div style="width: 100%; height: 300px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                <i class="fas fa-image" style="font-size: 3rem; opacity: 0.5;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem; line-height: 1.4;"><?php echo htmlspecialchars($product['short_description']); ?></p>
                            
                            <div class="card-price">
                                <?php echo formatPrice($product['selling_price']); ?>
                                <?php if ($product['mrp_price'] > $product['selling_price']): ?>
                                    <span class="original"><?php echo formatPrice($product['mrp_price']); ?></span>
                                    <span style="background: #ef4444; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-left: 8px;">
                                        <?php echo round((($product['mrp_price'] - $product['selling_price']) / $product['mrp_price']) * 100); ?>% OFF
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex" style="gap: 0.5rem; margin-top: 1rem;">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="flex: 1;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <button class="btn btn-secondary" onclick="addToCart(<?php echo $product['id']; ?>)" style="padding: 0.75rem;">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="products.php" class="btn btn-primary">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Trending Items -->
    <section style="padding: 4rem 0; background: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-4">
                <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Trending Items</h2>
                <p style="color: #666; font-size: 1.1rem;">Most popular products this week</p>
            </div>
            
            <div class="grid grid-4">
                <?php foreach ($trending_products as $product): ?>
                    <div class="card">
                        <?php 
                        $images = json_decode($product['images'], true);
                        if (!empty($images)): 
                        ?>
                            <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="card-image">
                        <?php else: ?>
                            <div style="width: 100%; height: 250px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                No Image
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($product['short_description']); ?></p>
                            
                            <div class="card-price">
                                <?php echo formatPrice($product['selling_price']); ?>
                                <?php if ($product['mrp_price'] > $product['selling_price']): ?>
                                    <span class="original"><?php echo formatPrice($product['mrp_price']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex" style="gap: 0.5rem; margin-top: 1rem;">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="flex: 1;">View Details</a>
                                <button class="btn btn-secondary" onclick="addToCart(<?php echo $product['id']; ?>)" style="padding: 0.75rem;">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section style="padding: 4rem 0;">
        <div class="container">
            <div class="grid grid-3">
                <div class="text-center">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🚚</div>
                    <h3>Free Shipping</h3>
                    <p style="color: #666;">On orders above ₹999</p>
                </div>
                <div class="text-center">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">💰</div>
                    <h3>Secure Payment</h3>
                    <p style="color: #666;">100% secure UPI payments</p>
                </div>
                <div class="text-center">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔄</div>
                    <h3>Easy Returns</h3>
                    <p style="color: #666;">7-day return policy</p>
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
                <p style="color: #ccc;">&copy; 2026 Jemimah Fashion. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        let selectedSize = '';
        
        function addToCart(productId) {
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
                    body: 'product_id=' + productId + '&quantity=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success notification
                        showNotification('Product added to cart successfully!', 'success');
                        
                        // Update cart count
                        const cartCount = document.querySelector('.nav-icons a[href="cart.php"] span');
                        if (cartCount) {
                            cartCount.textContent = data.cart_count;
                            cartCount.style.display = 'flex';
                        }
                        
                        // Animate button
                        event.target.innerHTML = '<i class="fas fa-check"></i> Added';
                        setTimeout(() => {
                            event.target.innerHTML = '<i class="fas fa-cart-plus"></i>';
                        }, 2000);
                    } else {
                        showNotification(data.message || 'Failed to add product to cart', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Failed to add product to cart', 'error');
                });
            <?php endif; ?>
        }
        
        function toggleLike(productId, button) {
            <?php if (!isLoggedIn()): ?>
                if (confirm('Please login to add items to wishlist. Redirect to login page?')) {
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
                        button.innerHTML = '<i class="fas fa-heart"></i>';
                        showNotification('Added to wishlist!', 'success');
                    } else {
                        button.classList.remove('liked');
                        button.innerHTML = '<i class="far fa-heart"></i>';
                        showNotification('Removed from wishlist', 'info');
                    }
                } else {
                    showNotification(data.message || 'Failed to update wishlist', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to update wishlist', 'error');
            });
        }
        
        function showNotification(message, type = 'success') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            
            // Add styles
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
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Banner slider (if multiple banners)
        let currentBanner = 0;
        const banners = document.querySelectorAll('.banner-item');
        
        if (banners.length > 1) {
            setInterval(() => {
                banners[currentBanner].style.display = 'none';
                currentBanner = (currentBanner + 1) % banners.length;
                banners[currentBanner].style.display = 'block';
            }, 5000);
        }
        
        // Add scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all animate elements
        document.querySelectorAll('.animate-fade-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
