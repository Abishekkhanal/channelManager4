<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if email already exists in newsletter subscribers
        $check_sql = "SELECT id FROM newsletter_subscribers WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'You are already subscribed to our newsletter!'
            ]);
        } else {
            // Insert new subscriber
            $insert_sql = "INSERT INTO newsletter_subscribers (email, subscribed_at, status) VALUES (?, NOW(), 'active')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("s", $email);
            
            if ($insert_stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Thank you for subscribing! You will receive our latest updates.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sorry, there was an error. Please try again.'
                ]);
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid email address.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>