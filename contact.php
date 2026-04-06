<?php
require_once 'config.php';

$db = new Database();

// Handle contact form submission
if ($_POST && $_POST['action'] == 'submit_contact') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required";
    }
    
    if (empty($errors)) {
        // Save to database (you might want to create a contacts table)
        // For now, we'll just show success message
        
        // Send email notification (you would implement this)
        $to = "info@fvfablyvalor.com";
        $email_subject = "New Contact Form Submission: " . $subject;
        $email_body = "
            Name: $name\n
            Email: $email\n
            Phone: $phone\n
            Subject: $subject\n
            Message: $message\n
        ";
        
        // mail($to, $email_subject, $email_body, "From: $email");
        
        header('Location: contact.php?success=1');
        exit();
    } else {
        $error_message = implode(', ', $errors);
    }
}

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
    <title>Contact Us - Jemimah Fashion</title>
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
        
        .contact-content {
            padding: 80px 0;
        }
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
            margin-bottom: 64px;
        }
        
        .contact-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }
        
        .contact-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.15);
        }
        
        .contact-icon {
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
        
        .contact-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1a1a1a;
        }
        
        .contact-info {
            color: #666;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        
        .contact-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .contact-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .contact-form-section {
            background: #f8f9fa;
            padding: 80px 0;
        }
        
        .contact-form {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 48px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
        }
        
        .map-section {
            padding: 80px 0;
        }
        
        .map-container {
            height: 400px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            position: relative;
            background: #f0f0f0;
        }
        
        .map-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .map-placeholder i {
            font-size: 3rem;
            margin-bottom: 16px;
        }
        
        .faq-section {
            padding: 80px 0;
        }
        
        .faq-grid {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .faq-item {
            background: #ffffff;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .faq-item:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        .faq-question {
            padding: 24px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            color: #1a1a1a;
            transition: background 0.3s ease;
        }
        
        .faq-question:hover {
            background: #f8f9fa;
        }
        
        .faq-answer {
            padding: 0 24px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            color: #666;
            line-height: 1.6;
        }
        
        .faq-item.active .faq-answer {
            max-height: 200px;
            padding: 0 24px 24px;
        }
        
        .faq-item.active .faq-question {
            background: #f8f9fa;
            color: #667eea;
        }
        
        .faq-icon {
            transition: transform 0.3s ease;
        }
        
        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }
        
        .social-section {
            background: #000000;
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 24px;
        }
        
        .social-link {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-4px);
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
                    <li><a href="contact.php" class="active">Contact</a></li>
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
                <h1 class="hero-title">Get in Touch</h1>
                <p class="hero-subtitle">We're here to help! Reach out to us for any questions, feedback, or assistance with your orders</p>
            </div>
        </div>
    </section>

    <!-- Contact Methods -->
    <section class="contact-content">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-card animate-fade-in">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3 class="contact-title">Call Us</h3>
                    <p class="contact-info">
                        Speak directly with our customer service team for immediate assistance
                    </p>
                    <a href="tel:+919876543210" class="contact-link">+91 98765 43210</a>
                    <p style="color: #999; font-size: 0.9rem; margin-top: 8px;">
                        Mon-Sat: 9:00 AM - 8:00 PM<br>
                        Sunday: 10:00 AM - 6:00 PM
                    </p>
                </div>
                
                <div class="contact-card animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="contact-title">Email Us</h3>
                    <p class="contact-info">
                        Send us an email and we'll respond within 24 hours
                    </p>
                    <a href="mailto:info@fvfablyvalor.com" class="contact-link">info@fvfablyvalor.com</a>
                    <p style="color: #999; font-size: 0.9rem; margin-top: 8px;">
                        For order issues: orders@fvfablyvalor.com<br>
                        For partnerships: business@fvfablyvalor.com
                    </p>
                </div>
                
                <div class="contact-card animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="contact-title">Visit Us</h3>
                    <p class="contact-info">
                        Come visit our flagship store and experience our collection firsthand
                    </p>
                    <p style="color: #666; margin-top: 8px;">
                        123 Fashion Street<br>
                        Bandra West, Mumbai<br>
                        Maharashtra 400050
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form -->
    <section class="contact-form-section">
        <div class="container">
            <div class="contact-form animate-fade-in">
                <h2 style="font-size: 2.5rem; text-align: center; margin-bottom: 48px; color: #1a1a1a;">Send Us a Message</h2>
                
                <?php if (isset($_GET['success'])): ?>
                    <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 24px; text-align: center;">
                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                        Thank you for contacting us! We'll get back to you within 24 hours.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 24px; text-align: center;">
                        <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="submit_contact">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" pattern="[6-9][0-9]{9}" maxlength="10">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Subject *</label>
                            <select name="subject" class="form-control" required>
                                <option value="">Select a subject</option>
                                <option value="Order Inquiry" <?php echo ($_POST['subject'] ?? '') == 'Order Inquiry' ? 'selected' : ''; ?>>Order Inquiry</option>
                                <option value="Product Information" <?php echo ($_POST['subject'] ?? '') == 'Product Information' ? 'selected' : ''; ?>>Product Information</option>
                                <option value="Return/Exchange" <?php echo ($_POST['subject'] ?? '') == 'Return/Exchange' ? 'selected' : ''; ?>>Return/Exchange</option>
                                <option value="Payment Issue" <?php echo ($_POST['subject'] ?? '') == 'Payment Issue' ? 'selected' : ''; ?>>Payment Issue</option>
                                <option value="Partnership" <?php echo ($_POST['subject'] ?? '') == 'Partnership' ? 'selected' : ''; ?>>Partnership</option>
                                <option value="Other" <?php echo ($_POST['subject'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Message *</label>
                        <textarea name="message" class="form-control" rows="6" required placeholder="Tell us more about your inquiry..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="text-align: center;">
                        <button type="submit" class="btn btn-primary" style="font-size: 1.1rem; padding: 16px 48px;">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="container">
            <h2 style="font-size: 2.5rem; text-align: center; margin-bottom: 48px; color: #1a1a1a;">Find Our Store</h2>
            <div class="map-container animate-fade-in">
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt"></i>
                    <h3>123 Fashion Street, Mumbai</h3>
                    <p>Bandra West, Maharashtra 400050</p>
                    <a href="https://maps.google.com/?q=123+Fashion+Street+Bandra+West+Mumbai" target="_blank" style="color: white; text-decoration: underline; margin-top: 16px; display: inline-block;">
                        Get Directions
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2 style="font-size: 2.5rem; text-align: center; margin-bottom: 48px; color: #1a1a1a;">Frequently Asked Questions</h2>
            <div class="faq-grid">
                <div class="faq-item animate-fade-in">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How can I track my order?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        Once your order is shipped, you'll receive a tracking number via email and SMS. You can use this number to track your order on our website or the courier's website.
                    </div>
                </div>
                
                <div class="faq-item animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>What is your return policy?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        We offer a 7-day return policy for all unused items in original packaging. Simply contact our customer service team to initiate a return.
                    </div>
                </div>
                
                <div class="faq-item animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>Do you offer international shipping?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        Currently, we ship within India only. We're working on expanding our international shipping options soon.
                    </div>
                </div>
                
                <div class="faq-item animate-fade-in" style="animation-delay: 0.3s;">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How can I pay for my order?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        We accept UPI payments through various apps like Google Pay, PhonePe, and Paytm. All payments are secure and processed instantly.
                    </div>
                </div>
                
                <div class="faq-item animate-fade-in" style="animation-delay: 0.4s;">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span>How long does delivery take?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </div>
                    <div class="faq-answer">
                        Standard delivery takes 3-5 business days within major cities. For other locations, it may take 5-7 business days.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Social Section -->
    <section class="social-section">
        <div class="container">
            <h2 style="font-size: 2rem; margin-bottom: 16px;">Connect With Us</h2>
            <p style="opacity: 0.8; margin-bottom: 24px;">Follow us on social media for updates, offers, and fashion inspiration</p>
            <div class="social-links">
                <a href="https://instagram.com/fvfablyvalor" target="_blank" class="social-link">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://facebook.com/fvfablyvalor" target="_blank" class="social-link">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/fvfablyvalor" target="_blank" class="social-link">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://youtube.com/fvfablyvalor" target="_blank" class="social-link">
                    <i class="fab fa-youtube"></i>
                </a>
                <a href="https://wa.me/919876543210" target="_blank" class="social-link">
                    <i class="fab fa-whatsapp"></i>
                </a>
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
        function toggleFAQ(element) {
            const faqItem = element.parentElement;
            const allItems = document.querySelectorAll('.faq-item');
            
            // Close all other items
            allItems.forEach(item => {
                if (item !== faqItem) {
                    item.classList.remove('active');
                }
            });
            
            // Toggle current item
            faqItem.classList.toggle('active');
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
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const phone = document.querySelector('input[name="phone"]').value;
            const email = document.querySelector('input[name="email"]').value;
            
            if (phone && !/^[6-9]\d{9}$/.test(phone)) {
                alert('Please enter a valid 10-digit phone number');
                e.preventDefault();
                return;
            }
            
            if (email && !email.includes('@')) {
                alert('Please enter a valid email address');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
