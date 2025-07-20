<?php
require_once 'config/database.php';

// Pagination settings
$limit = 6; // blogs per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build query conditions
$where_conditions = ["status = 'published'"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(title LIKE :search OR content LIKE :search OR tags LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($category_id > 0) {
    $where_conditions[] = "category = :category_id";
    $params[':category_id'] = $category_id;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM blogs WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_blogs = $count_stmt->fetchColumn();
$total_pages = ceil($total_blogs / $limit);

// Get blogs with pagination
$sql = "SELECT id, title, slug, content, image, author, tags, created_at, category 
        FROM blogs 
        WHERE $where_clause 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$blogs = $stmt->fetchAll();

// Get categories for filter
$categories_sql = "SELECT id, name FROM blog_categories ORDER BY name";
$categories_stmt = $pdo->query($categories_sql);
$categories = $categories_stmt->fetchAll();

// Get category name for current filter
$current_category_name = 'All Posts';
if ($category_id > 0) {
    $cat_sql = "SELECT name FROM blog_categories WHERE id = :id";
    $cat_stmt = $pdo->prepare($cat_sql);
    $cat_stmt->execute([':id' => $category_id]);
    $cat_result = $cat_stmt->fetch();
    if ($cat_result) {
        $current_category_name = $cat_result['name'];
    }
}

// Helper function to get excerpt
function getExcerpt($content, $length = 150) {
    $content = strip_tags($content);
    if (strlen($content) <= $length) {
        return $content;
    }
    return substr($content, 0, $length) . '...';
}

// Helper function to format date
function timeAgo($date) {
    $time = time() - strtotime($date);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Blogs - Anugra Tours & Travels</title>
   <link rel="stylesheet" href="styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    /* Modern Blog Styles */
    .blog-hero {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 80px 20px 60px;
      text-align: center;
      margin-bottom: 50px;
    }

    .blog-hero h1 {
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 20px;
      font-family: 'Inter', sans-serif;
    }

    .blog-hero p {
      font-size: 1.2rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
      line-height: 1.6;
    }

    .blog-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Search and Filter */
    .blog-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 40px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .search-form {
      display: flex;
      align-items: center;
      background: white;
      border: 2px solid #e9ecef;
      border-radius: 25px;
      padding: 5px;
      transition: border-color 0.3s ease;
      min-width: 300px;
    }

    .search-form:focus-within {
      border-color: #667eea;
    }

    .search-form input {
      border: none;
      outline: none;
      padding: 10px 15px;
      flex: 1;
      font-size: 0.95rem;
    }

    .search-form button {
      background: #667eea;
      border: none;
      color: white;
      padding: 10px 15px;
      border-radius: 20px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .search-form button:hover {
      background: #5a6fd8;
    }

    .results-info {
      color: #666;
      font-size: 0.9rem;
    }

    /* Category Tabs */
    .blog-categories {
      margin-bottom: 40px;
      text-align: center;
    }

    .category-tabs {
      display: inline-flex;
      background: #f8f9fa;
      border-radius: 50px;
      padding: 8px;
      gap: 8px;
      flex-wrap: wrap;
      justify-content: center;
    }

    .category-tab {
      padding: 12px 24px;
      border: none;
      background: transparent;
      border-radius: 25px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s ease;
      color: #666;
      text-decoration: none;
    }

    .category-tab.active {
      background: #667eea;
      color: white;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .category-tab:hover {
      background: #e9ecef;
      color: #333;
    }

    .category-tab.active:hover {
      background: #667eea;
      color: white;
    }

    /* Blog Grid */
    .blog-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 30px;
      margin-bottom: 60px;
    }

    .blog-card {
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
      border: 1px solid #f0f0f0;
    }

    .blog-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }

    .blog-card-image {
      position: relative;
      overflow: hidden;
      height: 220px;
    }

    .blog-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .blog-card:hover img {
      transform: scale(1.05);
    }

    .blog-badge {
      position: absolute;
      top: 15px;
      left: 15px;
      background: rgba(102, 126, 234, 0.9);
      color: white;
      padding: 6px 12px;
      border-radius: 15px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .blog-content {
      padding: 25px;
    }

    .blog-content h3 {
      font-size: 1.3rem;
      font-weight: 600;
      margin-bottom: 12px;
      line-height: 1.4;
      color: #2c3e50;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .blog-excerpt {
      color: #666;
      font-size: 0.95rem;
      line-height: 1.6;
      margin-bottom: 15px;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .blog-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.85rem;
      color: #888;
      margin-bottom: 15px;
    }

    .blog-author {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .blog-date {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .read-more-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #667eea;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.9rem;
      transition: all 0.3s ease;
    }

    .read-more-btn:hover {
      color: #5a6fd8;
      transform: translateX(5px);
    }

    /* Featured Section */
    .featured-section {
      margin-bottom: 60px;
    }

    .section-title {
      font-size: 2.2rem;
      font-weight: 700;
      text-align: center;
      margin-bottom: 40px;
      color: #2c3e50;
      position: relative;
    }

    .section-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 2px;
    }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin: 50px 0;
    }

    .pagination a, .pagination span {
      padding: 12px 16px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .pagination a {
      background: #f8f9fa;
      color: #666;
    }

    .pagination a:hover {
      background: #667eea;
      color: white;
    }

    .pagination .current {
      background: #667eea;
      color: white;
    }

    /* Newsletter Section */
    .newsletter-section {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      color: white;
      padding: 60px 20px;
      border-radius: 20px;
      text-align: center;
      margin: 60px 0;
    }

    .newsletter-section h3 {
      font-size: 2rem;
      margin-bottom: 15px;
    }

    .newsletter-section p {
      margin-bottom: 30px;
      opacity: 0.9;
    }

    .newsletter-form {
      display: flex;
      max-width: 400px;
      margin: 0 auto;
      gap: 10px;
    }

    .newsletter-form input {
      flex: 1;
      padding: 15px;
      border: none;
      border-radius: 25px;
      outline: none;
    }

    .newsletter-form button {
      background: white;
      color: #f5576c;
      border: none;
      padding: 15px 25px;
      border-radius: 25px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.3s ease;
    }

    .newsletter-form button:hover {
      transform: scale(1.05);
    }

    .no-results {
      text-align: center;
      padding: 60px 20px;
      color: #666;
    }

    .no-results i {
      font-size: 4rem;
      margin-bottom: 20px;
      opacity: 0.5;
    }

    .no-results h3 {
      font-size: 1.5rem;
      margin-bottom: 10px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .blog-hero h1 {
        font-size: 2.5rem;
      }

      .blog-hero p {
        font-size: 1rem;
      }

      .blog-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .blog-controls {
        flex-direction: column;
        align-items: stretch;
      }

      .search-form {
        min-width: 100%;
      }

      .category-tabs {
        flex-direction: column;
        gap: 10px;
      }

      .newsletter-form {
        flex-direction: column;
        max-width: 100%;
      }
    }

    @media (max-width: 480px) {
      .blog-hero {
        padding: 60px 15px 40px;
      }

      .blog-container {
        padding: 0 15px;
      }

      .blog-content {
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

  <!-- Hero Section -->
  <section class="blog-hero">
    <h1>Travel Stories & Insights</h1>
    <p>Discover amazing destinations, travel tips, and inspiring stories from the heart of the Himalayas</p>
  </section>

  <div class="blog-container">
    <!-- Search and Filter Controls -->
    <div class="blog-controls">
      <form class="search-form" method="GET">
        <input type="text" name="search" placeholder="Search articles..." value="<?php echo htmlspecialchars($search); ?>">
        <?php if ($category_id > 0): ?>
          <input type="hidden" name="category" value="<?php echo $category_id; ?>">
        <?php endif; ?>
        <button type="submit"><i class="fas fa-search"></i></button>
      </form>
      
      <div class="results-info">
        Showing <?php echo count($blogs); ?> of <?php echo $total_blogs; ?> articles
        <?php if (!empty($search)): ?>
          for "<?php echo htmlspecialchars($search); ?>"
        <?php endif; ?>
        <?php if ($category_id > 0): ?>
          in <?php echo htmlspecialchars($current_category_name); ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Category Tabs -->
    <div class="blog-categories">
      <div class="category-tabs">
        <a href="blogs.php" class="category-tab <?php echo $category_id == 0 ? 'active' : ''; ?>">All Posts</a>
        <?php foreach ($categories as $category): ?>
          <a href="blogs.php?category=<?php echo $category['id']; ?>" 
             class="category-tab <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($category['name']); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Featured Posts Section -->
    <section class="featured-section">
      <h2 class="section-title"><?php echo $current_category_name; ?></h2>
      
      <?php if (empty($blogs)): ?>
        <div class="no-results">
          <i class="fas fa-search"></i>
          <h3>No articles found</h3>
          <p>Try adjusting your search or browse all articles</p>
          <a href="blogs.php" style="color: #667eea; text-decoration: none; font-weight: 600;">← Back to all articles</a>
        </div>
      <?php else: ?>
        <div class="blog-grid">
          <?php foreach ($blogs as $blog): ?>
            <article class="blog-card">
              <div class="blog-card-image">
                <?php if ($blog['image']): ?>
                  <img src="<?php echo htmlspecialchars($blog['image']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                <?php else: ?>
                  <img src="uploads/default-blog.jpg" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                <?php endif; ?>
                <span class="blog-badge">Featured</span>
              </div>
              <div class="blog-content">
                <h3><?php echo htmlspecialchars($blog['title']); ?></h3>
                <p class="blog-excerpt"><?php echo getExcerpt($blog['content']); ?></p>
                <div class="blog-meta">
                  <span class="blog-author">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($blog['author']); ?>
                  </span>
                  <span class="blog-date">
                    <i class="fas fa-calendar"></i>
                    <?php echo timeAgo($blog['created_at']); ?>
                  </span>
                </div>
                <a href="blog-view.php?slug=<?php echo urlencode($blog['slug']); ?>" class="read-more-btn">
                  Read More <i class="fas fa-arrow-right"></i>
                </a>
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

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <?php if ($i == $page): ?>
                <span class="current"><?php echo $i; ?></span>
              <?php else: ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                  <?php echo $i; ?>
                </a>
              <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
              <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                <i class="fas fa-chevron-right"></i>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
      <h3>Stay Updated</h3>
      <p>Get the latest travel stories and destination guides delivered to your inbox</p>
      <form class="newsletter-form" action="newsletter-subscribe.php" method="POST">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Subscribe</button>
      </form>
    </section>
  </div>

<!-- Footer -->
<footer class="site-footer">
  <div class="footer-container">
    <div class="footer-logo-contact">
      <h2>Anugra Tours</h2>
      <ul class="contact-info">
        <li>📞 +91-97321 81111</li>
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
  // Newsletter form
  document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
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
        alert('Thank you for subscribing! We\'ll keep you updated.');
        this.reset();
      } else {
        alert(data.message || 'There was an error. Please try again.');
      }
    })
    .catch(error => {
      alert('There was an error. Please try again.');
    });
  });
</script>

</body>
</html>