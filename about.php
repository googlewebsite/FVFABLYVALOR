<?php
require_once 'config.php';

$db = new Database();

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
    <title>About Us - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,101.3C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: #ffffff;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            text-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 32px;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .about-content {
            padding: 80px 0;
        }
        
        .story-section {
            margin-bottom: 80px;
        }
        
        .story-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            margin-top: 48px;
        }
        
        .value-card {
            background: #ffffff;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }
        
        .value-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.15);
        }
        
        .value-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2rem;
            color: white;
        }
        
        .value-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1a1a1a;
        }
        
        .value-description {
            color: #666;
            line-height: 1.6;
        }
        
        .team-section {
            background: #f8f9fa;
            padding: 80px 0;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 32px;
            margin-top: 48px;
        }
        
        .team-member {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .team-member:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.15);
        }
        
        .team-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        
        .team-info {
            padding: 24px;
        }
        
        .team-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a1a1a;
        }
        
        .team-role {
            color: #666;
            margin-bottom: 16px;
        }
        
        .team-social {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        
        .team-social a {
            width: 36px;
            height: 36px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .team-social a:hover {
            background: #000000;
            color: white;
            transform: scale(1.1);
        }
        
        .stats-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 32px;
            text-align: center;
        }
        
        .stat-item {
            padding: 32px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #ffffff 0%, #cccccc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 1.125rem;
            opacity: 0.9;
        }
        
        .cta-section {
            padding: 80px 0;
            text-align: center;
        }
        
        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1a1a1a;
        }
        
        .cta-description {
            font-size: 1.125rem;
            color: #666;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .timeline-section {
            padding: 80px 0;
        }
        
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
            transform: translateX(-50%);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 48px;
            display: flex;
            align-items: center;
        }
        
        .timeline-item:nth-child(odd) {
            flex-direction: row-reverse;
        }
        
        .timeline-content {
            flex: 1;
            padding: 24px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            margin: 0 24px;
        }
        
        .timeline-dot {
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            border: 4px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .timeline-year {
            font-size: 1.25rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .timeline-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1a1a1a;
        }
        
        .timeline-description {
            color: #666;
            line-height: 1.6;
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
                    <li><a href="about.php" class="active">About</a></li>
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
                <h1 class="hero-title">About Jemimah Fashion</h1>
                <p class="hero-subtitle">Redefining fashion with premium quality, exceptional style, and unmatched customer service since 2020</p>
            </div>
        </div>
    </section>

    <!-- Story Section -->
    <section class="about-content">
        <div class="container">
            <div class="story-section">
                <div class="grid grid-2 align-items-center">
                    <div>
                        <h2 style="font-size: 2.5rem; margin-bottom: 24px; color: #1a1a1a;">Our Story</h2>
                        <p style="font-size: 1.125rem; line-height: 1.8; color: #666; margin-bottom: 24px;">
                            FV FABLY VALOR was born from a simple vision: to create fashion that empowers individuals to express their unique style while maintaining the highest standards of quality and craftsmanship.
                        </p>
                        <p style="font-size: 1.125rem; line-height: 1.8; color: #666; margin-bottom: 24px;">
                            What started as a small boutique in Mumbai has grown into a beloved fashion destination, serving customers across India with carefully curated collections that blend contemporary trends with timeless elegance.
                        </p>
                        <p style="font-size: 1.125rem; line-height: 1.8; color: #666;">
                            Every piece in our collection tells a story of dedication, creativity, and passion for fashion. We believe that clothing should not only look good but also make you feel confident and comfortable in your own skin.
                        </p>
                    </div>
                    <div>
                        <img src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=600&h=400&fit=crop&auto=format" alt="Fashion Store" class="story-image">
                    </div>
                </div>
            </div>

            <!-- Values Section -->
            <div class="values-section">
                <h2 style="font-size: 2.5rem; text-align: center; margin-bottom: 48px; color: #1a1a1a;">Our Values</h2>
                <div class="values-grid">
                    <div class="value-card animate-fade-in">
                        <div class="value-icon">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h3 class="value-title">Quality First</h3>
                        <p class="value-description">
                            We never compromise on quality. Every product is carefully selected and crafted to meet our exacting standards, ensuring durability and comfort.
                        </p>
                    </div>
                    
                    <div class="value-card animate-fade-in" style="animation-delay: 0.1s;">
                        <div class="value-icon">
                            <i class="fas fa-palette"></i>
                        </div>
                        <h3 class="value-title">Creative Design</h3>
                        <p class="value-description">
                            Our design team stays ahead of trends while creating timeless pieces that transcend seasons and make lasting impressions.
                        </p>
                    </div>
                    
                    <div class="value-card animate-fade-in" style="animation-delay: 0.2s;">
                        <div class="value-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3 class="value-title">Customer Love</h3>
                        <p class="value-description">
                            Our customers are at the heart of everything we do. We strive to provide exceptional service and build lasting relationships.
                        </p>
                    </div>
                    
                    <div class="value-card animate-fade-in" style="animation-delay: 0.3s;">
                        <div class="value-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3 class="value-title">Sustainability</h3>
                        <p class="value-description">
                            We're committed to sustainable practices, from eco-friendly materials to responsible manufacturing processes that minimize our environmental impact.
                        </p>
                    </div>
                    
                    <div class="value-card animate-fade-in" style="animation-delay: 0.4s;">
                        <div class="value-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h3 class="value-title">Excellence</h3>
                        <p class="value-description">
                            From product design to customer service, we pursue excellence in every aspect of our business to exceed your expectations.
                        </p>
                    </div>
                    
                    <div class="value-card animate-fade-in" style="animation-delay: 0.5s;">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="value-title">Inclusivity</h3>
                        <p class="value-description">
                            Fashion is for everyone. We celebrate diversity and create pieces that make all individuals feel confident and beautiful.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item animate-fade-in">
                    <div class="stat-number">50K+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                <div class="stat-item animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Unique Designs</div>
                </div>
                <div class="stat-item animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="stat-number">4.8★</div>
                    <div class="stat-label">Customer Rating</div>
                </div>
                <div class="stat-item animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Quality Guarantee</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section">
        <div class="container">
            <h2 style="font-size: 2.5rem; text-align: center; margin-bottom: 48px; color: #1a1a1a;">Our Journey</h2>
            <div class="timeline">
                <div class="timeline-item animate-fade-in">
                    <div class="timeline-content">
                        <div class="timeline-year">2020</div>
                        <div class="timeline-title">The Beginning</div>
                        <div class="timeline-description">
                            FV FABLY VALOR was founded with a vision to revolutionize fashion retail in India.
                        </div>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                
                <div class="timeline-item animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="timeline-content">
                        <div class="timeline-year">2021</div>
                        <div class="timeline-title">First Store Launch</div>
                        <div class="timeline-description">
                            Opened our flagship store in Mumbai, featuring our debut collection that received overwhelming response.
                        </div>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                
                <div class="timeline-item animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="timeline-content">
                        <div class="timeline-year">2022</div>
                        <div class="timeline-title">Digital Expansion</div>
                        <div class="timeline-description">
                            Launched our e-commerce platform, bringing our fashion to customers across India.
                        </div>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                
                <div class="timeline-item animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="timeline-content">
                        <div class="timeline-year">2023</div>
                        <div class="timeline-title">Growth Milestone</div>
                        <div class="timeline-description">
                            Served over 25,000 customers and expanded our collection to include diverse fashion categories.
                        </div>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
                
                <div class="timeline-item animate-fade-in" style="animation-delay: 0.4s;">
                    <div class="timeline-content">
                        <div class="timeline-year">2024</div>
                        <div class="timeline-title">Innovation Focus</div>
                        <div class="timeline-description">
                            Introduced sustainable fashion lines and enhanced our customer experience with advanced technology.
                        </div>
                    </div>
                    <div class="timeline-dot"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Join Our Fashion Journey</h2>
                <p class="cta-description">
                    Discover the perfect blend of style, quality, and affordability. Explore our latest collection and find pieces that speak to your unique personality.
                </p>
                <div class="d-flex justify-content-center" style="gap: 1rem;">
                    <a href="products.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 16px 32px;">
                        <i class="fas fa-shopping-bag"></i> Shop Collection
                    </a>
                    <a href="contact.php" class="btn btn-secondary" style="font-size: 1.1rem; padding: 16px 32px;">
                        <i class="fas fa-envelope"></i> Contact Us
                    </a>
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
