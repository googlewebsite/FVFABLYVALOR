<?php
require_once 'config.php';

// Handle admin login
if ($_POST) {
    $password = $_POST['password'];
    
    // Password-only admin authentication
    if ($password === '987654321') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'Administrator';
        header('Location: admin_dashboard.php');
        exit();
    } else {
        $error = "Invalid password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Jemimah Fashion</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
        }
        
        .login-box {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo h1 {
            color: #000000;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-logo p {
            color: #666666;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <h1>Jemimah Fashion</h1>
                <p>Admin Panel</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Admin Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Login to Admin Panel
                </button>
            </form>
            
            <p class="text-center mt-3" style="color: #666666; font-size: 0.9rem;">
                Default: 987654321
            </p>
        </div>
    </div>
</body>
</html>
