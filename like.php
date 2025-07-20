<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['blog_id'])) {
    $blog_id = (int)$_POST['blog_id'];
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    if ($blog_id > 0) {
        // Check if user has already liked this blog
        $check_sql = "SELECT id FROM likes WHERE blog_id = ? AND user_ip = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $blog_id, $user_ip);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // User has already liked, so remove the like
            $delete_sql = "DELETE FROM likes WHERE blog_id = ? AND user_ip = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("is", $blog_id, $user_ip);
            $delete_stmt->execute();
            $delete_stmt->close();
            $action = 'unliked';
        } else {
            // User hasn't liked, so add the like
            $insert_sql = "INSERT INTO likes (blog_id, user_ip, created_at) VALUES (?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("is", $blog_id, $user_ip);
            $insert_stmt->execute();
            $insert_stmt->close();
            $action = 'liked';
        }
        $check_stmt->close();
        
        // Get updated like count
        $count_sql = "SELECT COUNT(*) as total_likes FROM likes WHERE blog_id = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("i", $blog_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_likes = $count_result->fetch_assoc()['total_likes'];
        $count_stmt->close();
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'total_likes' => $total_likes
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid blog ID'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>