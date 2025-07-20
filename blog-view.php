<?php
require_once 'config/database.php';

// Get blog slug from URL
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: blogs.php');
    exit();
}

// Get blog details
$blog_sql = "SELECT b.*, bc.name as category_name 
             FROM blogs b 
             LEFT JOIN blog_categories bc ON b.category = bc.id 
             WHERE b.slug = ? AND b.status = 'published'";
$blog_stmt = $conn->prepare($blog_sql);
$blog_stmt->bind_param("s", $slug);
$blog_stmt->execute();
$blog_result = $blog_stmt->get_result();

if ($blog_result->num_rows === 0) {
    header('Location: blogs.php');
    exit();
}

$blog = $blog_result->fetch_assoc();
$blog_stmt->close();

// Get user's IP for like functionality
$user_ip = $_SERVER['REMOTE_ADDR'];

// Check if user has already liked this blog
$like_check_sql = "SELECT id FROM likes WHERE blog_id = ? AND user_ip = ?";
$like_check_stmt = $conn->prepare($like_check_sql);
$like_check_stmt->bind_param("is", $blog['id'], $user_ip);
$like_check_stmt->execute();
$like_check_result = $like_check_stmt->get_result();
$user_has_liked = $like_check_result->num_rows > 0;
$like_check_stmt->close();

// Get total likes for this blog
$likes_count_sql = "SELECT COUNT(*) as total_likes FROM likes WHERE blog_id = ?";
$likes_count_stmt = $conn->prepare($likes_count_sql);
$likes_count_stmt->bind_param("i", $blog['id']);
$likes_count_stmt->execute();
$likes_count_result = $likes_count_stmt->get_result();
$likes_count = $likes_count_result->fetch_assoc()['total_likes'];
$likes_count_stmt->close();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $comment_text = trim($_POST['comment']);
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    
    if (!empty($name) && !empty($email) && !empty($comment_text)) {
        $comment_sql = "INSERT INTO blog_comments (blog_id, name, email, comment, parent_id, created_at, status) 
                        VALUES (?, ?, ?, ?, ?, NOW(), 'approved')";
        $comment_stmt = $conn->prepare($comment_sql);
        $comment_stmt->bind_param("isssi", $blog['id'], $name, $email, $comment_text, $parent_id);
        $comment_stmt->execute();
        $comment_stmt->close();
        
        // Redirect to prevent resubmission
        header("Location: " . $_SERVER['REQUEST_URI'] . "#comments");
        exit();
    }
}

// Get comments for this blog
$comments_sql = "SELECT * FROM blog_comments 
                 WHERE blog_id = ? AND status = 'approved' 
                 ORDER BY created_at ASC";
$comments_stmt = $conn->prepare($comments_sql);
$comments_stmt->bind_param("i", $blog['id']);
$comments_stmt->execute();
$comments_result = $comments_stmt->get_result();
$all_comments = [];
while ($row = $comments_result->fetch_assoc()) {
    $all_comments[] = $row;
}
$comments_stmt->close();

// Organize comments into parent and replies
$parent_comments = [];
$replies = [];
foreach ($all_comments as $comment) {
    if ($comment['parent_id'] == 0) {
        $parent_comments[] = $comment;
    } else {
        if (!isset($replies[$comment['parent_id']])) {
            $replies[$comment['parent_id']] = [];
        }
        $replies[$comment['parent_id']][] = $comment;
    }
}

// Get recent blogs for sidebar
$recent_sql = "SELECT title, slug, created_at, image FROM blogs WHERE status = 'published' AND id != ? ORDER BY created_at DESC LIMIT 5";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $blog['id']);
$recent_stmt->execute();
$recent_result = $recent_stmt->get_result();
$recent_blogs = [];
while ($row = $recent_result->fetch_assoc()) {
    $recent_blogs[] = $row;
}
$recent_stmt->close();

// Get popular blogs (by likes, excluding current blog)
$popular_sql = "SELECT b.title, b.slug, b.created_at, b.image, COUNT(l.id) as like_count 
                FROM blogs b 
                LEFT JOIN likes l ON b.id = l.blog_id 
                WHERE b.status = 'published' AND b.id != ?
                GROUP BY b.id 
                ORDER BY like_count DESC, b.created_at DESC 
                LIMIT 5";
$popular_stmt = $conn->prepare($popular_sql);
$popular_stmt->bind_param("i", $blog['id']);
$popular_stmt->execute();
$popular_result = $popular_stmt->get_result();
$popular_blogs = [];
while ($row = $popular_result->fetch_assoc()) {
    $popular_blogs[] = $row;
}
$popular_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($blog['seo_title'] ?: $blog['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($blog['seo_description'] ?: substr(strip_tags($blog['content']), 0, 160)); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($blog['tags']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($blog['author']); ?>">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($blog['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr(strip_tags($blog['content']), 0, 160)); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <?php if ($blog['image']): ?>
        <meta property="og:image" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/' . $blog['image']; ?>">
    <?php endif; ?>
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($blog['title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars(substr(strip_tags($blog['content']), 0, 160)); ?>">
    <?php if ($blog['image']): ?>
        <meta name="twitter:image" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/' . $blog['image']; ?>">
    <?php endif; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #2d3748;
            background: #f7fafc;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .blog-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            position: relative;
            overflow: hidden;
        }

        .blog-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.2);
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .breadcrumb {
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .breadcrumb a:hover {
            opacity: 0.8;
        }

        .blog-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .blog-meta {
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
            opacity: 0.9;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .category-badge {
            background: rgba(255,255,255,0.2);
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 50px;
            margin: 50px 0;
        }

        .article-content {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .featured-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .article-body {
            padding: 40px;
        }

        .article-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #4a5568;
        }

        .article-text h2,
        .article-text h3,
        .article-text h4 {
            color: #2d3748;
            margin: 30px 0 15px 0;
            font-weight: 600;
        }

        .article-text h2 {
            font-size: 1.6rem;
        }

        .article-text h3 {
            font-size: 1.4rem;
        }

        .article-text p {
            margin-bottom: 20px;
        }

        .article-text ul,
        .article-text ol {
            margin: 20px 0;
            padding-left: 30px;
        }

        .article-text li {
            margin-bottom: 8px;
        }

        .article-text blockquote {
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 30px 0;
            background: #f7fafc;
            border-radius: 0 10px 10px 0;
            font-style: italic;
        }

        /* Social Share & Actions */
        .article-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 30px 40px;
            border-top: 1px solid #e2e8f0;
            background: #f7fafc;
        }

        .like-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .like-btn {
            background: none;
            border: 2px solid #e2e8f0;
            color: #4a5568;
            padding: 12px 20px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .like-btn:hover,
        .like-btn.liked {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .like-count {
            font-weight: 600;
            color: #667eea;
        }

        .social-share {
            display: flex;
            gap: 10px;
        }

        .share-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .share-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .share-facebook { background: #3b5998; }
        .share-twitter { background: #1da1f2; }
        .share-linkedin { background: #0077b5; }
        .share-whatsapp { background: #25d366; }

        /* Tags */
        .article-tags {
            padding: 30px 40px;
            border-top: 1px solid #e2e8f0;
        }

        .tags-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #2d3748;
        }

        .tag-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tag {
            background: #e2e8f0;
            color: #4a5568;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .tag:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        /* Comments Section */
        .comments-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-top: 30px;
            padding: 40px;
        }

        .comments-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 30px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .comment-form {
            background: #f7fafc;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-control.textarea {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .comment {
            border-bottom: 1px solid #e2e8f0;
            padding: 25px 0;
        }

        .comment:last-child {
            border-bottom: none;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .comment-author {
            font-weight: 600;
            color: #2d3748;
        }

        .comment-date {
            color: #718096;
            font-size: 0.9rem;
        }

        .comment-text {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .comment-reply {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .comment-replies {
            margin-left: 40px;
            margin-top: 20px;
            border-left: 3px solid #e2e8f0;
            padding-left: 20px;
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .sidebar-widget {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .widget-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recent-blog {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .recent-blog:last-child {
            border-bottom: none;
        }

        .recent-blog-image {
            width: 70px;
            height: 70px;
            border-radius: 10px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .recent-blog-content h4 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 5px;
            line-height: 1.3;
        }

        .recent-blog-content h4 a {
            color: #2d3748;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .recent-blog-content h4 a:hover {
            color: #667eea;
        }

        .recent-blog-date {
            font-size: 0.8rem;
            color: #718096;
        }

        /* Newsletter */
        .newsletter-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .newsletter-input {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .newsletter-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .newsletter-btn {
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .newsletter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Google Ads */
        .ad-space {
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            color: #718096;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: 500;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .blog-title {
                font-size: 2.2rem;
            }

            .blog-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .blog-title {
                font-size: 1.8rem;
            }

            .article-body {
                padding: 30px 20px;
            }

            .article-actions {
                padding: 20px;
                flex-direction: column;
                gap: 20px;
            }

            .article-tags {
                padding: 20px;
            }

            .comments-section {
                padding: 30px 20px;
            }

            .comment-replies {
                margin-left: 20px;
                padding-left: 15px;
            }

            .sidebar-widget {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="blog-header">
        <div class="container">
            <div class="header-content">
                <nav class="breadcrumb">
                    <a href="index.php">Home</a> / 
                    <a href="blogs.php">Blogs</a> / 
                    <?php echo htmlspecialchars($blog['title']); ?>
                </nav>
                
                <h1 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h1>
                
                <div class="blog-meta">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>By <?php echo htmlspecialchars($blog['author']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('F d, Y', strtotime($blog['created_at'])); ?></span>
                    </div>
                    <?php if ($blog['category_name']): ?>
                        <div class="meta-item">
                            <span class="category-badge"><?php echo htmlspecialchars($blog['category_name']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <i class="fas fa-heart"></i>
                        <span><?php echo $likes_count; ?> likes</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Article -->
            <main>
                <article class="article-content">
                    <?php if (!empty($blog['image'])): ?>
                        <img src="<?php echo htmlspecialchars($blog['image']); ?>" 
                             alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                             class="featured-image">
                    <?php endif; ?>
                    
                    <div class="article-body">
                        <div class="article-text">
                            <?php echo $blog['content']; ?>
                        </div>
                    </div>
                    
                    <!-- Article Actions -->
                    <div class="article-actions">
                        <div class="like-section">
                            <button class="like-btn <?php echo $user_has_liked ? 'liked' : ''; ?>" 
                                    onclick="toggleLike(<?php echo $blog['id']; ?>)">
                                <i class="fas fa-heart"></i>
                                <span id="likeText"><?php echo $user_has_liked ? 'Liked' : 'Like'; ?></span>
                            </button>
                            <span class="like-count" id="likeCount"><?php echo $likes_count; ?> likes</span>
                        </div>
                        
                        <div class="social-share">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                               target="_blank" class="share-btn share-facebook" title="Share on Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode($blog['title']); ?>" 
                               target="_blank" class="share-btn share-twitter" title="Share on Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                               target="_blank" class="share-btn share-linkedin" title="Share on LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="https://wa.me/?text=<?php echo urlencode($blog['title'] . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" 
                               target="_blank" class="share-btn share-whatsapp" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Tags -->
                    <?php if (!empty($blog['tags'])): ?>
                        <div class="article-tags">
                            <div class="tags-title">Tags:</div>
                            <div class="tag-list">
                                <?php 
                                $tags = explode(',', $blog['tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                    <a href="blogs.php?search=<?php echo urlencode($tag); ?>" class="tag">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </a>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </article>

                <!-- Comments Section -->
                <section class="comments-section" id="comments">
                    <h3 class="comments-title">
                        <i class="fas fa-comments"></i> Comments (<?php echo count($parent_comments); ?>)
                    </h3>
                    
                    <!-- Comment Form -->
                    <form method="POST" class="comment-form">
                        <h4 style="margin-bottom: 20px; color: #2d3748;">Leave a Comment</h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                            <div class="form-group">
                                <label for="name">Name *</label>
                                <input type="text" id="name" name="name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="comment">Comment *</label>
                            <textarea id="comment" name="comment" class="form-control textarea" 
                                      placeholder="Share your thoughts..." required></textarea>
                        </div>
                        
                        <button type="submit" name="submit_comment" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> Post Comment
                        </button>
                    </form>
                    
                    <!-- Comments List -->
                    <div class="comments-list">
                        <?php if (empty($parent_comments)): ?>
                            <p style="text-align: center; color: #718096; padding: 40px 0;">
                                No comments yet. Be the first to share your thoughts!
                            </p>
                        <?php else: ?>
                            <?php foreach ($parent_comments as $comment): ?>
                                <div class="comment">
                                    <div class="comment-header">
                                        <div class="comment-author"><?php echo htmlspecialchars($comment['name']); ?></div>
                                        <div class="comment-date"><?php echo date('M d, Y \a\t g:i A', strtotime($comment['created_at'])); ?></div>
                                    </div>
                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                                    
                                    <!-- Replies -->
                                    <?php if (isset($replies[$comment['id']])): ?>
                                        <div class="comment-replies">
                                            <?php foreach ($replies[$comment['id']] as $reply): ?>
                                                <div class="comment">
                                                    <div class="comment-header">
                                                        <div class="comment-author"><?php echo htmlspecialchars($reply['name']); ?></div>
                                                        <div class="comment-date"><?php echo date('M d, Y \a\t g:i A', strtotime($reply['created_at'])); ?></div>
                                                    </div>
                                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </main>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Recent Blogs -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-clock"></i> Recent Posts
                    </h3>
                    <?php foreach ($recent_blogs as $recent): ?>
                        <div class="recent-blog">
                            <?php if (!empty($recent['image'])): ?>
                                <img src="<?php echo htmlspecialchars($recent['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($recent['title']); ?>" 
                                     class="recent-blog-image">
                            <?php else: ?>
                                <div class="recent-blog-image"></div>
                            <?php endif; ?>
                            <div class="recent-blog-content">
                                <h4><a href="blog-view.php?slug=<?php echo urlencode($recent['slug']); ?>">
                                    <?php echo htmlspecialchars($recent['title']); ?>
                                </a></h4>
                                <div class="recent-blog-date">
                                    <?php echo date('M d, Y', strtotime($recent['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Popular Blogs -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-fire"></i> Popular Posts
                    </h3>
                    <?php foreach ($popular_blogs as $popular): ?>
                        <div class="recent-blog">
                            <?php if (!empty($popular['image'])): ?>
                                <img src="<?php echo htmlspecialchars($popular['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($popular['title']); ?>" 
                                     class="recent-blog-image">
                            <?php else: ?>
                                <div class="recent-blog-image"></div>
                            <?php endif; ?>
                            <div class="recent-blog-content">
                                <h4><a href="blog-view.php?slug=<?php echo urlencode($popular['slug']); ?>">
                                    <?php echo htmlspecialchars($popular['title']); ?>
                                </a></h4>
                                <div class="recent-blog-date">
                                    <i class="fas fa-heart"></i> <?php echo $popular['like_count']; ?> likes
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Newsletter Subscription -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-envelope"></i> Newsletter
                    </h3>
                    <p style="margin-bottom: 20px; color: #718096;">
                        Subscribe to get the latest travel stories and tips delivered to your inbox.
                    </p>
                    <form class="newsletter-form" id="newsletterForm">
                        <input type="email" class="newsletter-input" placeholder="Your email address" required>
                        <button type="submit" class="newsletter-btn">
                            <i class="fas fa-paper-plane"></i> Subscribe
                        </button>
                    </form>
                    <div id="newsletterMessage"></div>
                </div>

                <!-- Google Ads Space -->
                <div class="ad-space">
                    <i class="fas fa-ad" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                    <p>Google Ads<br><small>Advertisement</small></p>
                </div>

                <!-- Another Ad Space -->
                <div class="ad-space">
                    <i class="fas fa-ad" style="font-size: 1.5rem; margin-bottom: 8px;"></i>
                    <p>Sponsored Content<br><small>Advertisement</small></p>
                </div>
            </aside>
        </div>
    </div>

    <script>
        // Like functionality
        function toggleLike(blogId) {
            fetch('like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'blog_id=' + blogId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeBtn = document.querySelector('.like-btn');
                    const likeText = document.getElementById('likeText');
                    const likeCount = document.getElementById('likeCount');
                    
                    if (data.action === 'liked') {
                        likeBtn.classList.add('liked');
                        likeText.textContent = 'Liked';
                    } else {
                        likeBtn.classList.remove('liked');
                        likeText.textContent = 'Like';
                    }
                    
                    likeCount.textContent = data.total_likes + ' likes';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Newsletter subscription
        document.getElementById('newsletterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[type="email"]').value;
            const messageDiv = document.getElementById('newsletterMessage');
            
            fetch('newsletter-subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                    this.reset();
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-error">' + data.message + '</div>';
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="alert alert-error">An error occurred. Please try again.</div>';
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>