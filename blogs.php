<?php
require_once 'config/database.php';

// Get page and search parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$limit = 9; // Number of blogs per page
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = ["status = 'published'"];
$params = [];
$types = "";

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR content LIKE ? OR tags LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($category_filter > 0) {
    $where_conditions[] = "category = ?";
    $params[] = $category_filter;
    $types .= "i";
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM blogs $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_blogs = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $count_result = $conn->query($count_sql);
    $total_blogs = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_blogs / $limit);

// Get blogs with pagination
$sql = "SELECT b.*, bc.name as category_name 
        FROM blogs b 
        LEFT JOIN blog_categories bc ON b.category = bc.id 
        $where_clause 
        ORDER BY b.created_at DESC 
        LIMIT ? OFFSET ?";

$limit_params = $params;
$limit_params[] = $limit;
$limit_params[] = $offset;
$limit_types = $types . "ii";

$stmt = $conn->prepare($sql);
if (!empty($limit_params)) {
    $stmt->bind_param($limit_types, ...$limit_params);
}
$stmt->execute();
$result = $stmt->get_result();
$blogs = [];
while ($row = $result->fetch_assoc()) {
    $blogs[] = $row;
}
$stmt->close();

// Get categories for filter dropdown
$categories_sql = "SELECT id, name FROM blog_categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Get recent blogs for sidebar
$recent_sql = "SELECT title, slug, created_at, image FROM blogs WHERE status = 'published' ORDER BY created_at DESC LIMIT 5";
$recent_result = $conn->query($recent_sql);
$recent_blogs = [];
while ($row = $recent_result->fetch_assoc()) {
    $recent_blogs[] = $row;
}

// Get popular blogs (by view count or likes)
$popular_sql = "SELECT b.title, b.slug, b.created_at, b.image, COUNT(l.id) as like_count 
                FROM blogs b 
                LEFT JOIN likes l ON b.id = l.blog_id 
                WHERE b.status = 'published' 
                GROUP BY b.id 
                ORDER BY like_count DESC, b.created_at DESC 
                LIMIT 5";
$popular_result = $conn->query($popular_sql);
$popular_blogs = [];
while ($row = $popular_result->fetch_assoc()) {
    $popular_blogs[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Blogs - Discover Amazing Destinations</title>
    <meta name="description" content="Explore our collection of travel blogs featuring amazing destinations, travel tips, and adventure stories from around the world.">
    <meta name="keywords" content="travel blog, destinations, travel tips, adventure, tourism, travel stories">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Travel Blogs - Discover Amazing Destinations">
    <meta property="og:description" content="Explore our collection of travel blogs featuring amazing destinations, travel tips, and adventure stories.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    
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

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.1;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 40px;
            font-weight: 300;
        }

        /* Search Section */
        .search-section {
            background: white;
            padding: 40px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
            z-index: 2;
            margin-top: -40px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            max-width: 800px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 2;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            min-width: 250px;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .category-select {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            min-width: 150px;
        }

        .search-btn {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Main Content */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin: 40px 0;
        }

        .blogs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .blog-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: fit-content;
        }

        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .blog-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .blog-content {
            padding: 25px;
        }

        .blog-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: #718096;
        }

        .blog-category {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .blog-date {
            font-weight: 500;
        }

        .blog-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .blog-title a {
            color: #2d3748;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .blog-title a:hover {
            color: #667eea;
        }

        .blog-excerpt {
            color: #718096;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .blog-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .blog-author {
            font-weight: 600;
            color: #4a5568;
        }

        .read-more {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: gap 0.3s ease;
        }

        .read-more:hover {
            gap: 8px;
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
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .recent-blog-content h4 {
            font-size: 0.9rem;
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

        /* Google Ads Placeholder */
        .ad-space {
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 10px;
            padding: 40px 20px;
            text-align: center;
            color: #718096;
            margin: 20px 0;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 50px 0;
        }

        .pagination a,
        .pagination span {
            padding: 12px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: white;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border: 2px solid #667eea;
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: 80px 20px;
            color: #718096;
        }

        .no-results i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #4a5568;
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
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .search-form {
                flex-direction: column;
            }

            .main-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .blogs-grid {
                grid-template-columns: 1fr;
            }

            .blog-card {
                margin-bottom: 20px;
            }

            .container {
                padding: 0 15px;
            }
        }

        @media (max-width: 480px) {
            .hero-section {
                padding: 60px 0;
            }

            .search-section {
                padding: 30px 0;
            }

            .blog-content {
                padding: 20px;
            }

            .sidebar-widget {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Travel Stories & Adventures</h1>
                <p class="hero-subtitle">Discover amazing destinations, travel tips, and inspiring stories from around the world</p>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" 
                       placeholder="Search blogs..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="category" class="category-select">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"
                                <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <div class="main-content">
            <!-- Blogs Section -->
            <main>
                <?php if (empty($blogs)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No blogs found</h3>
                        <p>Try adjusting your search criteria or browse all blogs.</p>
                    </div>
                <?php else: ?>
                    <div class="blogs-grid">
                        <?php foreach ($blogs as $blog): ?>
                            <article class="blog-card">
                                <?php if (!empty($blog['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($blog['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($blog['title']); ?>" 
                                         class="blog-image">
                                <?php else: ?>
                                    <div class="blog-image"></div>
                                <?php endif; ?>
                                
                                <div class="blog-content">
                                    <div class="blog-meta">
                                        <?php if ($blog['category_name']): ?>
                                            <span class="blog-category"><?php echo htmlspecialchars($blog['category_name']); ?></span>
                                        <?php endif; ?>
                                        <span class="blog-date"><?php echo date('M d, Y', strtotime($blog['created_at'])); ?></span>
                                    </div>
                                    
                                    <h2 class="blog-title">
                                        <a href="blog-view.php?slug=<?php echo urlencode($blog['slug']); ?>">
                                            <?php echo htmlspecialchars($blog['title']); ?>
                                        </a>
                                    </h2>
                                    
                                    <p class="blog-excerpt">
                                        <?php echo htmlspecialchars(substr(strip_tags($blog['content']), 0, 150)); ?>...
                                    </p>
                                    
                                    <div class="blog-footer">
                                        <span class="blog-author">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($blog['author']); ?>
                                        </span>
                                        <a href="blog-view.php?slug=<?php echo urlencode($blog['slug']); ?>" class="read-more">
                                            Read More <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            ?>

                            <?php if ($start > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                <?php if ($start > 2): ?><span>...</span><?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($end < $total_pages): ?>
                                <?php if ($end < $total_pages - 1): ?><span>...</span><?php endif; ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Google Ads Space -->
                <div class="ad-space">
                    <i class="fas fa-ad" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>Google Ads Space<br><small>Advertisement placeholder</small></p>
                </div>
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
            </aside>
        </div>
    </div>

    <script>
        // Newsletter subscription
        document.getElementById('newsletterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[type="email"]').value;
            const messageDiv = document.getElementById('newsletterMessage');
            
            // AJAX request to newsletter subscription handler
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

        // Smooth scrolling for pagination
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = this.href;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>