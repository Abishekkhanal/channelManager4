<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "traveller_reviews";

// PDO connection function (for compatibility with existing admin files)
function getConnection() {
    global $servername, $username, $password, $database;
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// MySQLi connection (for blog system)
try {
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch(Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// PDO connection (for blog system compatibility)
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Admin authentication function (for compatibility)
function requireAdminLogin() {
    session_start();
    if (!isset($_SESSION['admin'])) {
        header('Location: ../admin_login.php');
        exit();
    }
}

// Security functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>