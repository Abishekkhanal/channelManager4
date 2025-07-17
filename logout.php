<?php
require_once 'config/database.php';

// Destroy all session data
session_destroy();

// Clear session variables
$_SESSION = array();

// Redirect to login page
header('Location: login.php');
exit();
?>