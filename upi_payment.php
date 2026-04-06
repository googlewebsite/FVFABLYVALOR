<?php
// UPI Details for Jemimah Fashion
$upi_id = "jesuslifemylife@okaxis";
$name = "JemimahFashion";
$amount = "499";
$currency = "INR";

// Generate UPI link
$upi_link = "upi://pay?pa=$upi_id&pn=$name&am=$amount&cu=$currency";

// Generate QR
$qr = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($upi_link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UPI Payment - Jemimah Fashion</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
body {
  font-family: 'Inter', Arial, sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  margin: 0;
  padding: 20px;
}

/* Container */
.payment-container {
  width: 100%;
  max-width: 400px;
  background: white;
  border-radius: 20px;
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
  padding: 30px;
  text-align: center;
  animation: slideUp 0.5s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Header */
.payment-header {
  margin-bottom: 25px;
}

.payment-header h2 {
  color: #1a1a1a;
  font-size: 1.8rem;
  margin-bottom: 8px;
}

.payment-header p {
  color: #666;
  font-size: 0.9rem;
}

/* QR Code */
.qr-section {
  margin: 25px 0;
}

.qr-code {
  background: #f8f9fa;
  border-radius: 15px;
  padding: 20px;
  display: inline-block;
  position: relative;
}

.qr-code img {
  width: 180px;
  height: 180px;
  border-radius: 10px;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.qr-code img:hover {
  transform: scale(1.05);
}

.qr-label {
  margin-top: 10px;
  font-size: 0.9rem;
  color: #666;
}

/* Payment Apps */
.payment-apps {
  margin: 25px 0;
}

.payment-apps h3 {
  color: #1a1a1a;
  font-size: 1.1rem;
  margin-bottom: 15px;
}

.app-icons {
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
}

.app-icon {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 15px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.app-icon:hover {
  transform: translateY(-3px);
  border-color: #667eea;
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.2);
}

.app-icon img {
  width: 60px;
  height: 60px;
  object-fit: contain;
}

.app-text {
  font-size: 1.1rem;
  font-weight: 700;
  color: #667eea;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Pay Button */
.pay-button {
  width: 100%;
  padding: 15px;
  margin-top: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.pay-button:before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s ease;
}

.pay-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.pay-button:hover:before {
  left: 100%;
}

/* Mobile Responsive */
@media (max-width: 480px) {
  .payment-container {
    margin: 10px;
    padding: 20px;
  }
  
  .payment-header h2 {
    font-size: 1.5rem;
  }
  
  .app-icons {
    gap: 10px;
  }
  
  .app-icon {
    padding: 10px;
  }
  
  .app-icon img {
    width: 50px;
    height: 50px;
  }
}

/* Loading Animation */
.loading {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.8);
  z-index: 10000;
  justify-content: center;
  align-items: center;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>

</head>
<body>

<div class="payment-container">
  <!-- Header -->
  <div class="payment-header">
    <h2>UPI Payment</h2>
    <p>Pay ₹<?php echo $amount; ?> to complete your order</p>
  </div>

  <!-- QR Code -->
  <div class="qr-section">
    <div class="qr-code">
      <img src="<?php echo $qr; ?>" onclick="payNow()" alt="QR Code">
      <div class="qr-label">Scan QR Code or Click to Pay</div>
    </div>
  </div>

  <!-- Payment Apps -->
  <div class="payment-apps">
    <h3>Choose Payment App</h3>
    <div class="app-icons">
      <div class="app-icon" onclick="payNow()">
        <span class="app-text">GPAY</span>
      </div>
      <div class="app-icon" onclick="payNow()">
        <span class="app-text">PHONEPE</span>
      </div>
      <div class="app-icon" onclick="payNow()">
        <span class="app-text">PAYTM</span>
      </div>
      <div class="app-icon" onclick="payNow()">
        <span class="app-text">UPI</span>
      </div>
    </div>
  </div>

  <!-- Pay Button -->
  <button class="pay-button" onclick="payNow()">
    <i class="fas fa-lock" style="margin-right: 8px;"></i>
    Pay ₹<?php echo $amount; ?> Securely
  </button>
</div>

<!-- Loading Overlay -->
<div class="loading" id="loading">
  <div class="loading-spinner"></div>
</div>

<script>
// Use PHP value inside JS
let upi_link = "<?php echo $upi_link; ?>";

// Open UPI App
function payNow() {
  // Show loading
  document.getElementById('loading').style.display = 'flex';
  
  // Simulate payment processing
  setTimeout(() => {
    // Hide loading
    document.getElementById('loading').style.display = 'none';
    
    // Show success message
    showNotification('Payment successful! Redirecting...', 'success');
    
    // Redirect to success page
    setTimeout(() => {
      window.location.href = 'payment_success.php';
    }, 2000);
  }, 1500);
}

// Show notification
function showNotification(message, type = 'success') {
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
    <span>${message}</span>
  `;
  
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: ${type === 'success' ? '#10b981' : '#ef4444'};
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

// Add hover effects
document.addEventListener('DOMContentLoaded', function() {
  const appIcons = document.querySelectorAll('.app-icon');
  appIcons.forEach(icon => {
    icon.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-3px) scale(1.05)';
    });
    
    icon.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0) scale(1)';
    });
  });
});
</script>

</body>
</html>
