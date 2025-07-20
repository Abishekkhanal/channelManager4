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

    /* Search and Filter */
    .blog-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 40px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .search-box {
      display: flex;
      align-items: center;
      background: white;
      border: 2px solid #e9ecef;
      border-radius: 25px;
      padding: 5px;
      transition: border-color 0.3s ease;
      min-width: 300px;
    }

    .search-box:focus-within {
      border-color: #667eea;
    }

    .search-box input {
      border: none;
      outline: none;
      padding: 10px 15px;
      flex: 1;
      font-size: 0.95rem;
    }

    .search-box button {
      background: #667eea;
      border: none;
      color: white;
      padding: 10px 15px;
      border-radius: 20px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .search-box button:hover {
      background: #5a6fd8;
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

      .search-box {
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
      <div class="search-box">
        <input type="text" placeholder="Search articles...">
        <button type="submit"><i class="fas fa-search"></i></button>
      </div>
    </div>

    <!-- Category Tabs -->
    <div class="blog-categories">
      <div class="category-tabs">
        <button class="category-tab active">All Posts</button>
        <button class="category-tab">Destinations</button>
        <button class="category-tab">Travel Tips</button>
        <button class="category-tab">Culture</button>
        <button class="category-tab">Adventure</button>
      </div>
    </div>

    <!-- Featured Posts Section -->
    <section class="featured-section">
      <h2 class="section-title">Featured Articles</h2>
      
      <div class="blog-grid">
        <article class="blog-card">
          <div class="blog-card-image">
            <img src="uploads/1752674104_Darjeeling-anugra.webp" alt="Darjeeling Dreams">
            <span class="blog-badge">Featured</span>
          </div>
          <div class="blog-content">
            <h3>Darjeeling Dreams: Discover the Queen of Hills with Anugra</h3>
            <p class="blog-excerpt">Welcome to Darjeeling, the crown jewel of the Eastern Himalayas! Nestled amidst emerald tea gardens and kissed by the morning sun over Kanchenjunga...</p>
            <div class="blog-meta">
              <span class="blog-author">
                <i class="fas fa-user"></i>
                Travel Guide
              </span>
              <span class="blog-date">
                <i class="fas fa-calendar"></i>
                3 days ago
              </span>
            </div>
            <a href="blog-view.php?slug=%2Fexplore-darjeeling-with-anugra" class="read-more-btn">
              Read More <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </article>

        <article class="blog-card">
          <div class="blog-card-image">
            <img src="uploads/1752933363_lachung-anugra.webp" alt="Lachung Village">
            <span class="blog-badge">Popular</span>
          </div>
          <div class="blog-content">
            <h3>Lachung Village: Discover the Snowy Gem of North Sikkim</h3>
            <p class="blog-excerpt">Experience the magical beauty of Lachung Village, where snow-capped mountains meet pristine valleys in the heart of North Sikkim...</p>
            <div class="blog-meta">
              <span class="blog-author">
                <i class="fas fa-user"></i>
                Travel Guide
              </span>
              <span class="blog-date">
                <i class="fas fa-calendar"></i>
                Today
              </span>
            </div>
            <a href="blog-view.php?slug=lachung-village-north-sikkim-travel-guide-anugra-tours" class="read-more-btn">
              Read More <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </article>

        <article class="blog-card">
          <div class="blog-card-image">
            <img src="uploads/1752583197_anugra-blog.jpg" alt="Sikkim Travel Guide">
            <span class="blog-badge">Guide</span>
          </div>
          <div class="blog-content">
            <h3>🌿 A Month-by-Month Travel Guide to Sikkim Changing Landscapes</h3>
            <p class="blog-excerpt">Plan your perfect Sikkim adventure with our comprehensive month-by-month guide to the region's ever-changing landscapes and seasonal highlights...</p>
            <div class="blog-meta">
              <span class="blog-author">
                <i class="fas fa-user"></i>
                Travel Guide
              </span>
              <span class="blog-date">
                <i class="fas fa-calendar"></i>
                5 days ago
              </span>
            </div>
            <a href="blog-view.php?slug=sikkim-travel-guide-month-by-month" class="read-more-btn">
              Read More <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </article>

        <article class="blog-card">
          <div class="blog-card-image">
            <img src="uploads/1752496963_sikkimAnugra_travels.jpeg" alt="Discover Sikkim">
            <span class="blog-badge">Adventure</span>
          </div>
          <div class="blog-content">
            <h3>Discover Sikkim: The Hidden Himalayan Paradise</h3>
            <p class="blog-excerpt">Uncover the secrets of Sikkim, a hidden gem in the Himalayas where ancient monasteries, pristine lakes, and towering peaks create an unforgettable experience...</p>
            <div class="blog-meta">
              <span class="blog-author">
                <i class="fas fa-user"></i>
                Travel Guide
              </span>
              <span class="blog-date">
                <i class="fas fa-calendar"></i>
                6 days ago
              </span>
            </div>
            <a href="blog-view.php?slug=discover-sikkim-hidden-himalayan-paradise" class="read-more-btn">
              Read More <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </article>

        <article class="blog-card">
          <div class="blog-card-image">
            <img src="uploads/1752851772_blog.webp" alt="Monsoon Magic">
            <span class="blog-badge">Seasonal</span>
          </div>
          <div class="blog-content">
            <h3>Enchanting Escapes: Monsoon Magic in North East India</h3>
            <p class="blog-excerpt">Experience the mystical beauty of North East India during monsoon season, when lush green landscapes and cascading waterfalls create a magical atmosphere...</p>
            <div class="blog-meta">
              <span class="blog-author">
                <i class="fas fa-user"></i>
                Travel Guide
              </span>
              <span class="blog-date">
                <i class="fas fa-calendar"></i>
                1 day ago
              </span>
            </div>
            <a href="blog-view.php?slug=monsoon-magic-northeast-india-anugra-tours" class="read-more-btn">
              Read More <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </article>

        <article class="blog-card">
          <div class="blog-card-image">
            <img src="uploads/1752770603_anugra-img.jpg" alt="Hidden Gems">
            <span class="blog-badge">Top 5</span>
          </div>
          <div class="blog-content">
            <h3>Top 5 Hidden Gems of North East India You Must Visit</h3>
            <p class="blog-excerpt">Discover the lesser-known treasures of North East India that offer breathtaking beauty, rich culture, and unforgettable experiences away from the crowds...</p>
            <div class="blog-meta">
              <span class="blog-author">
                <i class="fas fa-user"></i>
                Travel Guide
              </span>
              <span class="blog-date">
                <i class="fas fa-calendar"></i>
                2 days ago
              </span>
            </div>
            <a href="blog-view.php?slug=top-5-hidden-gems-northeast-india-anugra-tours" class="read-more-btn">
              Read More <i class="fas fa-arrow-right"></i>
            </a>
          </div>
        </article>
      </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
      <h3>Stay Updated</h3>
      <p>Get the latest travel stories and destination guides delivered to your inbox</p>
      <form class="newsletter-form">
        <input type="email" placeholder="Enter your email" required>
        <button type="submit">Subscribe</button>
      </form>
    </section>

    <!-- Pagination -->
    <div class="pagination">
      <a href="#"><i class="fas fa-chevron-left"></i></a>
      <span class="current">1</span>
      <a href="#">2</a>
      <a href="#">3</a>
      <span>...</span>
      <a href="#">10</a>
      <a href="#"><i class="fas fa-chevron-right"></i></a>
    </div>
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
  // Category tab functionality
  document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
    });
  });

  // Search functionality (placeholder)
  document.querySelector('.search-box button').addEventListener('click', function(e) {
    e.preventDefault();
    const query = document.querySelector('.search-box input').value;
    console.log('Searching for:', query);
    // Add actual search functionality here
  });

  // Newsletter form (placeholder)
  document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.querySelector('input[type="email"]').value;
    alert('Thank you for subscribing! We\'ll keep you updated.');
    this.reset();
  });
</script>

</body>
</html>