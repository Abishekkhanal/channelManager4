<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit();
    }
    
    try {
        // Create newsletter table if it doesn't exist
        $create_table_sql = "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'unsubscribed') DEFAULT 'active'
        )";
        $pdo->exec($create_table_sql);
        
        // Check if email already exists
        $check_sql = "SELECT COUNT(*) FROM newsletter_subscribers WHERE email = :email";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([':email' => $email]);
        $exists = $check_stmt->fetchColumn() > 0;
        
        if ($exists) {
            echo json_encode(['success' => false, 'message' => 'Email is already subscribed']);
            exit();
        }
        
        // Add email to newsletter
        $insert_sql = "INSERT INTO newsletter_subscribers (email) VALUES (:email)";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([':email' => $email]);
        
        echo json_encode(['success' => true, 'message' => 'Successfully subscribed to newsletter']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>