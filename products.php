<?php
require_once 'config.php';

$db = new Database();

// Get search query and filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'latest';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';

// Build query
$where_conditions = ["p.status = 'active'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

if (!empty($category)) {
    $where_conditions[] = "p.category = ?";
    $params[] = $category;
    $types .= 's';
}

if (!empty($min_price)) {
    $where_conditions[] = "p.selling_price >= ?";
    $params[] = $min_price;
    $types .= 'd';
}

if (!empty($max_price)) {
    $where_conditions[] = "p.selling_price <= ?";
    $params[] = $max_price;
    $types .= 'd';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Sorting
$order_by = 'ORDER BY p.created_at DESC';
switch ($sort) {
    case 'price_low':
        $order_by = 'ORDER BY p.selling_price ASC';
        break;
    case 'price_high':
        $order_by = 'ORDER BY p.selling_price DESC';
        break;
    case 'name_asc':
        $order_by = 'ORDER BY p.title ASC';
        break;
    case 'name_desc':
        $order_by = 'ORDER BY p.title DESC';
        break;
}

// Get products
$query = "SELECT * FROM products p $where_clause $order_by";
if (!empty($params)) {
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->query($query);
}
$products = $result->fetch_all(MYSQLI_ASSOC);

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
    <title>Products - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-sidebar {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 24px;
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .filter-section {
            margin-bottom: 32px;
        }
        
        .filter-title {
            font-weight: 600;
            margin-bottom: 16px;
            color: #1a1a1a;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .category-chip {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 20px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            text-align: center;
        }
        
        .category-chip:hover,
        .category-chip.active {
            border-color: #000000;
            background: #000000;
            color: #ffffff;
        }
        
        .price-range {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .price-range input {
            flex: 1;
        }
        
        .sort-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .results-count {
            font-weight: 500;
            color: #666;
        }
        
        .sort-dropdown {
            padding: 8px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: #ffffff;
            cursor: pointer;
            font-size: 14px;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 48px;
        }
        
        .pagination button {
            padding: 8px 16px;
            border: 2px solid #e9ecef;
            background: #ffffff;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .pagination button:hover:not(:disabled) {
            border-color: #000000;
            background: #000000;
            color: #ffffff;
        }
        
        .pagination button.active {
            background: #000000;
            color: #ffffff;
            border-color: #000000;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .view-toggle {
            display: flex;
            gap: 8px;
        }
        
        .view-btn {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            background: #ffffff;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .view-btn.active {
            border-color: #000000;
            background: #000000;
            color: #ffffff;
        }
        
        .quick-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        
        .quick-filter {
            padding: 6px 16px;
            border: 1px solid #e9ecef;
            border-radius: 20px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .quick-filter:hover,
        .quick-filter.active {
            border-color: #000000;
            background: #000000;
            color: #ffffff;
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
                        <input type="text" name="search" placeholder="Search for products..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php" class="active">Products</a></li>
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

    <!-- Products Section -->
    <section style="padding: 2rem 0;">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Products</h1>
                <p style="color: #666;"><?php echo count($products); ?> products found</p>
            </div>

            <!-- Quick Category Filters -->
            <div class="quick-filters mb-4">
                <div class="quick-filter <?php echo empty($category) ? 'active' : ''; ?>" onclick="filterByCategory('')">
                    All Products
                </div>
                <div class="quick-filter <?php echo $category == 'shoes' ? 'active' : ''; ?>" onclick="filterByCategory('shoes')">
                    👟 Shoes
                </div>
                <div class="quick-filter <?php echo $category == 'sports' ? 'active' : ''; ?>" onclick="filterByCategory('sports')">
                    🏃 Sports Shoes
                </div>
                <div class="quick-filter <?php echo $category == 'casual' ? 'active' : ''; ?>" onclick="filterByCategory('casual')">
                    👞 Casual Shoes
                </div>
                <div class="quick-filter <?php echo $category == 'formal' ? 'active' : ''; ?>" onclick="filterByCategory('formal')">
                    🤵 Formal Shoes
                </div>
                <div class="quick-filter <?php echo $category == 'sneakers' ? 'active' : ''; ?>" onclick="filterByCategory('sneakers')">
                    👟 Sneakers
                </div>
                <div class="quick-filter <?php echo $category == 'sandals' ? 'active' : ''; ?>" onclick="filterByCategory('sandals')">
                    🩴 Sandals
                </div>
                <div class="quick-filter <?php echo $category == 'boots' ? 'active' : ''; ?>" onclick="filterByCategory('boots')">
                    🥾 Boots
                </div>
            </div>

            <div class="d-flex" style="gap: 2rem;">
                <!-- Filters Sidebar -->
                <div style="width: 250px; flex-shrink: 0;">
                    <div class="card">
                        <div class="card-content">
                            <h3>Filters</h3>
                            
                            <form method="GET" action="products.php">
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                
                                <!-- Price Range -->
                                <div class="form-group">
                                    <label class="form-label">Price Range</label>
                                    <div class="d-flex" style="gap: 0.5rem;">
                                        <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo htmlspecialchars($min_price); ?>">
                                        <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo htmlspecialchars($max_price); ?>">
                                    </div>
                                </div>
                                
                                <!-- Sort By -->
                                <div class="form-group">
                                    <label class="form-label">Sort By</label>
                                    <select name="sort" class="form-control">
                                        <option value="latest" <?php echo $sort == 'latest' ? 'selected' : ''; ?>>Latest</option>
                                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                                <a href="products.php" class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem;">Clear Filters</a>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div style="flex: 1;">
                    <?php if (!empty($products)): ?>
                        <div class="grid grid-4">
                            <?php foreach ($products as $product): ?>
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
                    <?php else: ?>
                        <div class="text-center" style="padding: 4rem 2rem;">
                            <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
                            <h2>No products found</h2>
                            <p style="color: #666; margin-bottom: 2rem;">Try adjusting your search or filters</p>
                            <a href="products.php" class="btn btn-primary">View All Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: #000000; color: white; padding: 3rem 0 1rem;">
        <div class="container">
            <div class="grid grid-4">
                <div>
                    <h3 style="margin-bottom: 1rem;">FV FABLY VALOR</h3>
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
        function filterByCategory(category) {
            const url = new URL(window.location);
            if (category) {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }
            window.location.href = url.toString();
        }
        
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
                    alert('Failed to add product to cart. Please try again.');
                });
        <?php endif; ?>
        }
    </script>

</body>
</html>
