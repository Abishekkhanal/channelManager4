<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blog_id = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
    $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    if ($blog_id > 0) {
        // Check if user has already liked this blog
        $check_sql = "SELECT COUNT(*) FROM likes WHERE blog_id = :blog_id AND user_ip = :user_ip";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([':blog_id' => $blog_id, ':user_ip' => $user_ip]);
        $already_liked = $check_stmt->fetchColumn() > 0;
        
        if (!$already_liked) {
            // Add like
            $insert_sql = "INSERT INTO likes (blog_id, user_ip, created_at) VALUES (:blog_id, :user_ip, NOW())";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([':blog_id' => $blog_id, ':user_ip' => $user_ip]);
        }
    }
    
    // Redirect back to blog page
    if (!empty($slug)) {
        header("Location: blog-view.php?slug=" . urlencode($slug));
    } else {
        header("Location: blogs.php");
    }
    exit();
}

// If not POST request, redirect to blogs
header("Location: blogs.php");
exit();
?>