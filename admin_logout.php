<?php
require_once 'config.php';

// Handle logout
session_destroy();
header('Location: admin_login.php');
exit();
?>
