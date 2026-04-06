<?php
// Database Configuration - Jemimah Fashion
class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = 'dada';
    public $conn;
    
    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function insert_id() {
        return $this->conn->insert_id;
    }
    
    public function close() {
        $this->conn->close();
    }
}

// Helper functions
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

function generateOrderId() {
    return 'FV' . time() . rand(100, 999);
}

function uploadFile($file, $target_dir = "uploads/") {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . '_' . basename($file["name"]);
    $target_file = $target_dir . $file_name;
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    return false;
}

function sendWhatsAppNotification($phone, $message) {
    // This is a placeholder for WhatsApp API integration
    // You can integrate with Twilio, WhatsApp Business API, etc.
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['customer_id']);
}

function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// Start session
session_start();
?>
