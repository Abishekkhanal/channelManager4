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
             WHERE b.slug = :slug AND b.status = 'published'";
$blog_stmt = $pdo->prepare($blog_sql);
$blog_stmt->execute([':slug' => $slug]);
$blog = $blog_stmt->fetch();

if (!$blog) {
    header('Location: blogs.php');
    exit();
}

// Get like count
$like_sql = "SELECT COUNT(*) FROM likes WHERE blog_id = :blog_id";
$like_stmt = $pdo->prepare($like_sql);
$like_stmt->execute([':blog_id' => $blog['id']]);
$like_count = $like_stmt->fetchColumn();

// Check if user has liked this blog (by IP)
$user_ip = $_SERVER['REMOTE_ADDR'];
$user_liked_sql = "SELECT COUNT(*) FROM likes WHERE blog_id = :blog_id AND user_ip = :user_ip";
$user_liked_stmt = $pdo->prepare($user_liked_sql);
$user_liked_stmt->execute([':blog_id' => $blog['id'], ':user_ip' => $user_ip]);
$user_has_liked = $user_liked_stmt->fetchColumn() > 0;

// Get recent blogs (exclude current)
$recent_sql = "SELECT id, title, slug, image, created_at 
               FROM blogs 
               WHERE status = 'published' AND id != :current_id 
               ORDER BY created_at DESC 
               LIMIT 4";
$recent_stmt = $pdo->prepare($recent_sql);
$recent_stmt->execute([':current_id' => $blog['id']]);
$recent_blogs = $recent_stmt->fetchAll();

// Get popular blogs (by likes - exclude current)
$popular_sql = "SELECT b.id, b.title, b.slug, b.image, COUNT(l.id) as like_count
                FROM blogs b 
                LEFT JOIN likes l ON b.id = l.blog_id 
                WHERE b.status = 'published' AND b.id != :current_id 
                GROUP BY b.id 
                ORDER BY like_count DESC, b.created_at DESC 
                LIMIT 3";
$popular_stmt = $pdo->prepare($popular_sql);
$popular_stmt->execute([':current_id' => $blog['id']]);
$popular_blogs = $popular_stmt->fetchAll();

// Get comments for this blog
$comments_sql = "SELECT id, name, email, comment, parent_id, created_at 
                 FROM blog_comments 
                 WHERE blog_id = :blog_id AND status = 'approved' 
                 ORDER BY created_at ASC";
$comments_stmt = $pdo->prepare($comments_sql);
$comments_stmt->execute([':blog_id' => $blog['id']]);
$all_comments = $comments_stmt->fetchAll();

// Organize comments in a tree structure
function organizeComments($comments) {
    $organized = [];
    $children = [];
    
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == 0) {
            $organized[$comment['id']] = $comment;
            $organized[$comment['id']]['children'] = [];
        } else {
            $children[$comment['parent_id']][] = $comment;
        }
    }
    
    foreach ($children as $parent_id => $child_comments) {
        if (isset($organized[$parent_id])) {
            $organized[$parent_id]['children'] = $child_comments;
        }
    }
    
    return $organized;
}

$comments = organizeComments($all_comments);

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $comment_text = trim($_POST['comment']);
    $parent_id = (int)$_POST['parent_id'];
    
    if (!empty($name) && !empty($comment_text)) {
        $insert_comment_sql = "INSERT INTO blog_comments (blog_id, name, email, comment, parent_id, status, created_at) 
                               VALUES (:blog_id, :name, :email, :comment, :parent_id, 'pending', NOW())";
        $insert_stmt = $pdo->prepare($insert_comment_sql);
        $insert_stmt->execute([
            ':blog_id' => $blog['id'],
            ':name' => $name,
            ':email' => $email,
            ':comment' => $comment_text,
            ':parent_id' => $parent_id
        ]);
        
        $success_message = "Thank you for your comment! It will appear after moderation.";
        header("Location: blog-view.php?slug=" . urlencode($slug) . "&comment_success=1");
        exit();
    }
}

// Helper functions
function timeAgo($date) {
    $time = time() - strtotime($date);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

function getReadingTime($content) {
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Average reading speed
    return $reading_time;
}

// SEO Meta tags
$page_title = !empty($blog['seo_title']) ? $blog['seo_title'] : $blog['title'] . ' | Anugra Tours';
$page_description = !empty($blog['seo_description']) ? $blog['seo_description'] : substr(strip_tags($blog['content']), 0, 160);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($page_title); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
  <meta name="keywords" content="<?php echo htmlspecialchars($blog['tags']); ?>">
  
  <!-- Open Graph Meta Tags -->
  <meta property="og:title" content="<?php echo htmlspecialchars($blog['title']); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
  <meta property="og:image" content="<?php echo $blog['image'] ? htmlspecialchars($blog['image']) : 'uploads/default-blog.jpg'; ?>">
  <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
  <meta property="og:type" content="article">
  
  <link rel="stylesheet" href="styles.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    /* Modern Blog View Styles */
    .blog-view-container {
      max-width: 1400px;
      margin: 2rem auto;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 1fr 350px;
      gap: 40px;
    }

    .main-content {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .blog-header {
      position: relative;
      height: 400px;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.8), rgba(118, 75, 162, 0.8));
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .blog-header-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: -1;
    }

    .blog-header-content {
      padding: 40px;
      max-width: 800px;
    }

    .blog-header h1 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 20px;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
      line-height: 1.2;
    }

    .blog-meta-header {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 30px;
      font-size: 1rem;
      opacity: 0.9;
      flex-wrap: wrap;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .blog-body {
      padding: 40px;
    }

    .blog-tags {
      margin-bottom: 30px;
    }

    .blog-tags h4 {
      margin-bottom: 15px;
      color: #2c3e50;
      font-weight: 600;
    }

    .tag {
      display: inline-block;
      background: #f8f9fa;
      color: #667eea;
      padding: 6px 12px;
      border-radius: 15px;
      font-size: 0.85rem;
      margin-right: 8px;
      margin-bottom: 8px;
      transition: all 0.3s ease;
    }

    .tag:hover {
      background: #667eea;
      color: white;
    }

    .content {
      font-size: 1.1rem;
      line-height: 1.8;
      color: #333;
      margin-bottom: 40px;
    }

    .content h1, .content h2, .content h3, .content h4 {
      color: #2c3e50;
      margin-top: 30px;
      margin-bottom: 15px;
      font-weight: 600;
    }

    .content p {
      margin-bottom: 20px;
    }

    .content ul, .content ol {
      margin: 20px 0;
      padding-left: 30px;
    }

    .content li {
      margin-bottom: 10px;
    }

    .content a {
      color: #667eea;
      text-decoration: none;
      border-bottom: 1px solid transparent;
      transition: border-color 0.3s ease;
    }

    .content a:hover {
      border-bottom-color: #667eea;
    }

    .engagement-section {
      background: #f8f9fa;
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 30px;
    }

    .likes {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 20px;
    }

    .like-count {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 1.1rem;
      font-weight: 600;
    }

    .likes form button {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      padding: 12px 24px;
      border-radius: 25px;
      font-weight: 600;
      color: white;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .likes form button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .likes form button:disabled {
      background: #ccc;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .social-share {
      margin-bottom: 30px;
    }

    .social-share h3 {
      margin-bottom: 20px;
      color: #2c3e50;
    }

    .social-buttons {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
    }

    .social-share a {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      color: white;
      transition: all 0.3s ease;
    }

    .social-share a:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    }

    .social-share a:nth-child(1) { background: #3b5998; }
    .social-share a:nth-child(2) { background: #1DA1F2; }
    .social-share a:nth-child(3) { background: #25D366; }
    .social-share a:nth-child(4) { background: #6c757d; }

    /* Sidebar Styles */
    .sidebar {
      display: flex;
      flex-direction: column;
      gap: 30px;
    }

    .sidebar-widget {
      background: white;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      overflow: hidden;
    }

    .widget-header {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 20px;
      text-align: center;
    }

    .widget-header h3 {
      margin: 0;
      font-size: 1.3rem;
      font-weight: 600;
    }

    .widget-content {
      padding: 25px;
    }

    .recent-post {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      padding-bottom: 20px;
      border-bottom: 1px solid #f0f0f0;
    }

    .recent-post:last-child {
      margin-bottom: 0;
      padding-bottom: 0;
      border-bottom: none;
    }

    .recent-post-image {
      width: 80px;
      height: 60px;
      border-radius: 10px;
      object-fit: cover;
    }

    .recent-post-content h4 {
      font-size: 0.95rem;
      margin: 0 0 8px 0;
      line-height: 1.3;
      color: #2c3e50;
    }

    .recent-post-content a {
      text-decoration: none;
      color: inherit;
      transition: color 0.3s ease;
    }

    .recent-post-content a:hover {
      color: #667eea;
    }

    .recent-post-meta {
      font-size: 0.8rem;
      color: #888;
    }

    /* Ad Space */
    .ad-space {
      background: #f8f9fa;
      border: 2px dashed #ddd;
      padding: 40px 20px;
      text-align: center;
      color: #888;
      border-radius: 15px;
      transition: all 0.3s ease;
    }

    .ad-space:hover {
      background: #f0f0f0;
      border-color: #ccc;
    }

    .ad-space i {
      font-size: 3rem;
      margin-bottom: 15px;
      opacity: 0.5;
    }

    /* Popular Tags */
    .popular-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .popular-tag {
      background: #f8f9fa;
      color: #667eea;
      padding: 8px 15px;
      border-radius: 20px;
      font-size: 0.85rem;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .popular-tag:hover {
      background: #667eea;
      color: white;
    }

    /* Comments Section */
    .comments-section {
      background: white;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      margin-top: 30px;
    }

    .comments {
      padding: 40px;
    }

    .comments h3 {
      margin-bottom: 30px;
      color: #2c3e50;
      font-size: 1.5rem;
    }

    .comment-form {
      background: #f8f9fa;
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 30px;
    }

    .comments form {
      display: grid;
      gap: 20px;
    }

    .comments input,
    .comments textarea {
      width: 100%;
      padding: 15px;
      border: 2px solid #e9ecef;
      border-radius: 10px;
      font-size: 1rem;
      transition: border-color 0.3s ease;
    }

    .comments input:focus,
    .comments textarea:focus {
      outline: none;
      border-color: #667eea;
    }

    .comments button {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: #fff;
      padding: 15px 30px;
      border: none;
      border-radius: 25px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      justify-self: start;
    }

    .comments button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .comment {
      margin: 20px 0;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 10px;
      border-left: 4px solid #667eea;
    }

    .comment-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
    }

    .comment-author {
      font-weight: 600;
      color: #2c3e50;
    }

    .comment-date {
      font-size: 0.85rem;
      color: #888;
    }

    .comment-text {
      line-height: 1.6;
      color: #333;
    }

    .comment-replies {
      margin-left: 30px;
      margin-top: 20px;
    }

    .success-message {
      background: #d4edda;
      color: #155724;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      border: 1px solid #c3e6cb;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .blog-view-container {
        grid-template-columns: 1fr;
        gap: 30px;
      }

      .sidebar {
        order: -1;
      }

      .sidebar .sidebar-widget {
        display: none;
      }

      .sidebar .sidebar-widget:first-child,
      .sidebar .sidebar-widget:nth-child(2) {
        display: block;
      }
    }

    @media (max-width: 768px) {
      .blog-view-container {
        margin: 1rem auto;
        padding: 0 15px;
      }

      .blog-header h1 {
        font-size: 2rem;
      }

      .blog-header-content {
        padding: 30px 20px;
      }

      .blog-body {
        padding: 30px 20px;
      }

      .social-buttons {
        flex-direction: column;
      }

      .social-share a {
        justify-content: center;
      }

      .blog-meta-header {
        flex-direction: column;
        gap: 15px;
      }
    }

    @media (max-width: 480px) {
      .blog-header {
        height: 300px;
      }

      .blog-header h1 {
        font-size: 1.6rem;
      }

      .content {
        font-size: 1rem;
      }

      .engagement-section,
      .comment-form {
        padding: 20px;
      }
    }
  </style>
</head>
<body>

 <!-- Topbar (hidden on mobile) -->
  <div class="topbar">
    <div class="topbar-left">
      <span><i class="fas fa-blog"></i> Our Blog</span>
      <a href="review.php" style="text-decoration: none; color: #ffff;">
  <span><i class="fas fa-star" ;></i>Travellers Review</span>
    </div>
    <div class="topbar-right">
      <span><i class="fas fa-phone-alt"></i> +91-97321 81111</span>
      <span><i class="fas fa-envelope"></i> info@anugratours.com</span>
      <a href="#"><i class="fab fa-facebook-f"></i></a>
      <a href="#"><i class="fab fa-instagram"></i></a>
      <a href="#"><i class="fab fa-youtube"></i></a>
      <a class="btn-quote" href="#">Get A Quote</a>
    </div>
  </div>

  <!-- Navbar -->
  <header class="navbar">
    <a href="index.php">
    <img src="images/aNUGRHA LOGO.jpg" alt="Anugra Tours Logo" style="height: 40px;" />
  </a>
</div>
    <input type="checkbox" id="nav-toggle" class="nav-toggle">
    <label for="nav-toggle" class="nav-toggle-label">&#9776;</label>
    <nav>
     <ul class="nav-links">
  <li><a href="index.php">HOME</a></li>
  <li><a href="explore-sikkim.html">EXPLORE SIKKIM</a></li>
  <li>
    <a href="destinations.html">DESTINATIONS</a>
    <ul class="dropdown">
      <li><a href="gangtok.html">GANGTOK</a></li>
      <li><a href="pelling.html">PELLING</a></li>
      <li><a href="darjeeling.html">DARJEELING</a></li>
      <li><a href="nathula.html">NORTH SIKKIM</a></li>
      <li><a href="namchi-chardham.html">NAMCHI CHARDHAM</a></li>
      <li><a href="lachung.html">LACHUNG YUMTHANG VALLEY</a></li>
      <li><a href="tsomgo-lake.html">TSOMGO LAKE BABA MANDIR</a></li>
    </ul>
  </li>
  <li>
    <a href="#">TOUR PACKAGES</a>
    <ul class="dropdown">
      <li><a href="gangtok.html">GANGTOK TOUR PACKAGE</a></li>
      <li><a href="pelling.html">PELLING TOUR PACKAGE</a></li>
      <li><a href="darjeeling.html">DARJEELING TOUR PACKAGE</a></li>
      <li><a href="nathula.html">NORTH SIKKIM TOUR PACKAGE</a></li>
      <li><a href="namchi.html">NAMCHI CHARDHAM TOUR PACKAGE</a></li>
      <li><a href="yumthang.html">LACHUNG YUMTHANG VALLEY TOUR PACKAGE</a></li>
      <li><a href="tsomgo-lake.html">TSOMGO LAKE BABA MANDIR TOUR PACKAGE</a></li>
    </ul>
  <li><a href="hotels.html">HOTELS</a></li>
  <li><a href="contact.html">CONTACT</a></li>
  <li><a href="blogs.php">BLOGS</a></li>
  <li><a href="review.php">TRAVELLERS REVIEW</a></li>
  <li><a href="about.html">ABOUT US</a></li>
</ul>

    </nav>
  </header>

<div class="blog-view-container">
  <!-- Main Content -->
  <main class="main-content">
    <!-- Blog Header -->
    <header class="blog-header">
      <?php if ($blog['image']): ?>
        <img src="<?php echo htmlspecialchars($blog['image']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>" class="blog-header-image">
      <?php endif; ?>
      <div class="blog-header-content">
        <h1><?php echo htmlspecialchars($blog['title']); ?></h1>
        <div class="blog-meta-header">
          <span class="meta-item">
            <i class="fas fa-calendar-alt"></i>
            <?php echo date('M d, Y', strtotime($blog['created_at'])); ?>
          </span>
          <span class="meta-item">
            <i class="fas fa-clock"></i>
            <?php echo getReadingTime($blog['content']); ?> min read
          </span>
          <span class="meta-item">
            <i class="fas fa-user"></i>
            <?php echo htmlspecialchars($blog['author']); ?>
          </span>
          <?php if ($blog['category_name']): ?>
          <span class="meta-item">
            <i class="fas fa-folder"></i>
            <?php echo htmlspecialchars($blog['category_name']); ?>
          </span>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <!-- Blog Body -->
    <article class="blog-body">
      <!-- Tags -->
      <?php if (!empty($blog['tags'])): ?>
        <div class="blog-tags">
          <h4>Tags:</h4>
          <?php 
          $tags = explode(',', $blog['tags']);
          foreach ($tags as $tag): 
            $tag = trim($tag);
            if (!empty($tag)):
          ?>
            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
          <?php 
            endif;
          endforeach; 
          ?>
        </div>
      <?php endif; ?>

      <!-- Content -->
      <div class="content">
        <?php echo $blog['content']; ?>
      </div>

      <!-- Engagement Section -->
      <div class="engagement-section">
        <div class="likes">
          <div class="like-count">
            <strong><?php echo $like_count; ?> <i class="fas fa-heart" style="color:red;"></i></strong>
          </div>
          <form method="POST" action="like.php">
            <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
            <input type="hidden" name="slug" value="<?php echo htmlspecialchars($blog['slug']); ?>">
            <button type="submit" <?php echo $user_has_liked ? 'disabled' : ''; ?>>
              <i class="fas fa-thumbs-up"></i> 
              <?php echo $user_has_liked ? 'Liked' : 'Like this post'; ?>
            </button>
          </form>
        </div>

        <div class="social-share">
          <h3>🔗 Share this post:</h3>
          <div class="social-buttons">
            <?php 
            $current_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $encoded_url = urlencode($current_url);
            $encoded_title = urlencode($blog['title']);
            ?>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $encoded_url; ?>" target="_blank">
              <i class="fab fa-facebook"></i>Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?php echo $encoded_url; ?>&text=<?php echo $encoded_title; ?>" target="_blank">
              <i class="fab fa-twitter"></i>Twitter
            </a>
            <a href="https://api.whatsapp.com/send?text=<?php echo $encoded_title; ?>%20<?php echo $encoded_url; ?>" target="_blank">
              <i class="fab fa-whatsapp"></i>WhatsApp
            </a>
            <a href="#" onclick="navigator.clipboard.writeText('<?php echo $current_url; ?>'); alert('Link copied!')">
              <i class="fas fa-link"></i>Copy Link
            </a>
          </div>
        </div>
      </div>
    </article>
  </main>

  <!-- Sidebar -->
  <aside class="sidebar">
    <!-- Google Ad Space 1 -->
    <div class="sidebar-widget">
      <div class="ad-space">
        <i class="fas fa-ad"></i>
        <h4>Advertisement</h4>
        <p>Google Ad Space<br>300x250</p>
      </div>
    </div>

    <!-- Recent Blogs -->
    <?php if (!empty($recent_blogs)): ?>
    <div class="sidebar-widget">
      <div class="widget-header">
        <h3><i class="fas fa-clock"></i> Recent Posts</h3>
      </div>
      <div class="widget-content">
        <?php foreach ($recent_blogs as $recent): ?>
          <article class="recent-post">
            <img src="<?php echo $recent['image'] ? htmlspecialchars($recent['image']) : 'uploads/default-blog.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($recent['title']); ?>" class="recent-post-image">
            <div class="recent-post-content">
              <h4><a href="blog-view.php?slug=<?php echo urlencode($recent['slug']); ?>">
                <?php echo htmlspecialchars($recent['title']); ?>
              </a></h4>
              <div class="recent-post-meta">
                <i class="fas fa-calendar"></i> <?php echo timeAgo($recent['created_at']); ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Popular Posts -->
    <?php if (!empty($popular_blogs)): ?>
    <div class="sidebar-widget">
      <div class="widget-header">
        <h3><i class="fas fa-fire"></i> Most Popular</h3>
      </div>
      <div class="widget-content">
        <?php foreach ($popular_blogs as $popular): ?>
          <article class="recent-post">
            <img src="<?php echo $popular['image'] ? htmlspecialchars($popular['image']) : 'uploads/default-blog.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($popular['title']); ?>" class="recent-post-image">
            <div class="recent-post-content">
              <h4><a href="blog-view.php?slug=<?php echo urlencode($popular['slug']); ?>">
                <?php echo htmlspecialchars($popular['title']); ?>
              </a></h4>
              <div class="recent-post-meta">
                <i class="fas fa-heart"></i> <?php echo $popular['like_count']; ?> likes
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Google Ad Space 2 -->
    <div class="sidebar-widget">
      <div class="ad-space">
        <i class="fas fa-ad"></i>
        <h4>Advertisement</h4>
        <p>Google Ad Space<br>300x600</p>
      </div>
    </div>

    <!-- Popular Tags -->
    <?php if (!empty($blog['tags'])): ?>
    <div class="sidebar-widget">
      <div class="widget-header">
        <h3><i class="fas fa-tags"></i> Related Tags</h3>
      </div>
      <div class="widget-content">
        <div class="popular-tags">
          <?php 
          $tags = explode(',', $blog['tags']);
          foreach ($tags as $tag): 
            $tag = trim($tag);
            if (!empty($tag)):
          ?>
            <a href="blogs.php?search=<?php echo urlencode($tag); ?>" class="popular-tag"><?php echo htmlspecialchars($tag); ?></a>
          <?php 
            endif;
          endforeach; 
          ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Newsletter Signup -->
    <div class="sidebar-widget">
      <div class="widget-header">
        <h3><i class="fas fa-envelope"></i> Stay Updated</h3>
      </div>
      <div class="widget-content">
        <p style="margin-bottom: 20px; color: #666;">Get the latest travel stories delivered to your inbox.</p>
        <form style="display: flex; flex-direction: column; gap: 15px;" action="newsletter-subscribe.php" method="POST">
          <input type="email" name="email" placeholder="Your email address" style="padding: 12px; border: 2px solid #e9ecef; border-radius: 10px; outline: none;" required>
          <button type="submit" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600;">Subscribe</button>
        </form>
      </div>
    </div>
  </aside>
</div>

<!-- Comments Section -->
<div class="comments-section" style="max-width: 1400px; margin: 0 auto; padding: 0 20px;">
  <div class="comments">
    <h3>💬 Comments (<?php echo count($comments); ?>)</h3>
    
    <?php if (isset($_GET['comment_success'])): ?>
      <div class="success-message">
        <i class="fas fa-check-circle"></i> Thank you for your comment! It will appear after moderation.
      </div>
    <?php endif; ?>
    
    <div class="comment-form">
      <h4 style="margin-bottom: 20px; color: #2c3e50;">Leave a Comment</h4>
      <form method="POST">
        <input name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Email (optional)">
        <textarea name="comment" rows="4" placeholder="Your comment..." required></textarea>
        <input type="hidden" name="parent_id" value="0">
        <button type="submit"><i class="fas fa-comment-dots"></i> Submit Comment</button>
      </form>
    </div>

    <?php if (empty($comments)): ?>
      <div style="text-align: center; color: #888; padding: 40px 0;">
        <p>No comments yet. Be the first to share your thoughts!</p>
      </div>
    <?php else: ?>
      <?php foreach ($comments as $comment): ?>
        <div class="comment">
          <div class="comment-header">
            <span class="comment-author"><?php echo htmlspecialchars($comment['name']); ?></span>
            <span class="comment-date"><?php echo timeAgo($comment['created_at']); ?></span>
          </div>
          <div class="comment-text">
            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
          </div>
          
          <!-- Replies -->
          <?php if (!empty($comment['children'])): ?>
            <div class="comment-replies">
              <?php foreach ($comment['children'] as $reply): ?>
                <div class="comment">
                  <div class="comment-header">
                    <span class="comment-author"><?php echo htmlspecialchars($reply['name']); ?></span>
                    <span class="comment-date"><?php echo timeAgo($reply['created_at']); ?></span>
                  </div>
                  <div class="comment-text">
                    <?php echo nl2br(htmlspecialchars($reply['comment'])); ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Footer -->
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-logo-contact">
      <h2>Anugra Tours</h2>
      <ul class="contact-info">
        <li>📞 +91-97321 81111</li>
        <li>📱 +91 97321 81111</li>
        <li>📱 +91 97321 81111</li>
        <li>📱 +91 97321 81111</li>
      </ul>
    </div>

    <div class="footer-links">
      <div>
        <h4>■ COMPANY</h4>
        <ul>
          <li><a href="about.html">About Us</a></li>
          <li>Careers</li>
          <li><a href="review.php">Travellers Review</a></li>
          <li>Privacy Policy</li>
          <li><a href="contact.html">Contact Us</a></li>
        </ul>
      </div>
      <div>
        <h4>■ LINKS</h4>
        <ul>
          <li>Tourism Info</a></li>
          <li>Gallery</a></li>
          <li>Video Gallery</a></li>
          <li>Places of Interests</a></li>
          <li>Book A Trip</li>
          <li><a href="review.php">Post A Review</a></li>
          <li>Become a Partner</li>
        </ul>
      </div>
      <div>
        <h4>■ EXPLORE SIKKIM</h4>
        <ul>
          <li><a href="gangtok.html">Gangtok</a></li>
          <li><a href="darjeeling.html">Darjeeling</a></li>
          <li><a href="pelling.html">Pelling</a></li>
          <li><a href="namchi-chardham.html">Namchi Chardham</a></li>
          <li><a href="yumthang.html">North Sikkim</a></li>
          <li><a href="lachung.html">Lachung Yumthang</a></li>
        </ul>
      </div>
      <div>
        <h4>■ TOUR PACKAGES</h4>
        <ul>
          <li><a href="explore-sikkim.html">Sikkim Tour Package</a></li>
          <li><a href="darjeeling.html">Sikkim Darjeeling Tours</a></li>
          <li>Student Tours</a></li>
          <li>Honeymoon Tours</a></li>
          <li>Bhutan Packages</li>
          <li>Buddhist Tours</li>
          <li>Group Tours</li>
          <li>Offbeat Tours</li>
        </ul>
      </div>
    </div>

    <!-- Government Registration Section -->
    <div class="govt-registration" style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc;">
      <img src="images/govt-of-sikkim-logo.png" alt="Government of Sikkim Logo" style="height: 60px;" />
      <p style="margin-top: 10px; font-weight: 600; color: #ffffff;">Registered with Government of Sikkim</p>
    </div>

    <!-- Credit Section -->
    <div class="footer-credit" style="text-align: center; padding: 15px 0; font-size: 14px; color: #666;">
      © 2025 Anugra Tours. All rights reserved. <br>
      Designed & Developed by <strong><a href="https://abishekkhanal.github.io/" style="color: rgb(30, 210, 30); text-decoration: none;">Abishek Khanal</a></strong>
    </div>
  </div>
</footer>

<!-- BACK TO TOP BUTTON -->
<button id="backToTop" style="display: none; position: fixed; bottom: 30px; right: 30px; z-index: 1000;">↑</button>

<script src="js/script.js"></script>
<script>
  // Newsletter signup
  document.querySelector('.sidebar .widget-content form').addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.querySelector('input[type="email"]').value;
    
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
        alert('Thank you for subscribing! We\'ll keep you updated with our latest travel stories.');
        this.reset();
      } else {
        alert(data.message || 'There was an error. Please try again.');
      }
    })
    .catch(error => {
      alert('There was an error. Please try again.');
    });
  });

  // Smooth scroll for internal links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth'
        });
      }
    });
  });
</script>

</body>
</html>