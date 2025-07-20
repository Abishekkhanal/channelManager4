<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Darjeeling Dreams: Discover the Queen of Hills with Anugra | Anugra Tours</title>
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
      <img src="uploads/1752674104_Darjeeling-anugra.webp" alt="Darjeeling Dreams" class="blog-header-image">
      <div class="blog-header-content">
        <h1>Darjeeling Dreams: Discover the Queen of Hills with Anugra</h1>
        <div class="blog-meta-header">
          <span class="meta-item">
            <i class="fas fa-calendar-alt"></i>
            16 Jul 2025
          </span>
          <span class="meta-item">
            <i class="fas fa-clock"></i>
            5 min read
          </span>
          <span class="meta-item">
            <i class="fas fa-user"></i>
            Anugra Travel Guide
          </span>
        </div>
      </div>
    </header>

    <!-- Blog Body -->
    <article class="blog-body">
      <!-- Tags -->
      <div class="blog-tags">
        <h4>Tags:</h4>
        <span class="tag">Darjeeling tour</span>
        <span class="tag">Darjeeling travel</span>
        <span class="tag">Darjeeling packages</span>
        <span class="tag">Tea gardens</span>
        <span class="tag">Toy train ride</span>
        <span class="tag">Kanchenjunga view</span>
        <span class="tag">Queen of Hills</span>
      </div>

      <!-- Content -->
      <div class="content">
        <b>Darjeeling Diaries with Anugra Tours and Travels: Your Perfect Himalayan Getaway</b>
        <p>Welcome to Darjeeling, the crown jewel of the Eastern Himalayas! Nestled amidst emerald tea gardens and kissed by the morning sun over Kanchenjunga, Darjeeling is a dream destination for nature lovers, tea connoisseurs, and adventure seekers alike. With Anugra Tours and Travels, your journey to this hill paradise becomes not just a trip—but a cherished memory.</p>
        
        <b>Why Visit Darjeeling with Anugra Tours and Travels?</b>
        <p>At <b>Anugra Tours and Travels</b>, we believe in crafting soulful travel experiences. Whether you're a solo explorer, a honeymooning couple, or a family looking for a scenic break, our carefully curated Darjeeling packages ensure comfort, adventure, and unforgettable views.</p>
        
        <b>Top Attractions in Our Darjeeling Package</b>
        <ul>
          <li><b>Tiger Hill Sunrise:</b> Witness the breathtaking sunrise over Kanchenjunga, and on clear mornings, get a rare view of Mount Everest.</li>
          <li><b>Darjeeling Himalayan Railway (Toy Train):</b> Ride the iconic UNESCO World Heritage toy train through tea gardens and hillsides.</li>
          <li><b>Batasia Loop & War Memorial:</b> Enjoy a 360° view of Darjeeling with snow-covered peaks in the backdrop.</li>
          <li><b>Padmaja Naidu Zoological Park:</b> Meet rare Himalayan wildlife including the Red Panda and Snow Leopard.</li>
          <li><b>Peace Pagoda:</b> Experience spiritual calm and panoramic town views from this serene Japanese structure.</li>
          <li><b>Tea Estate Walk:</b> Visit Happy Valley Tea Estate and taste freshly brewed world-famous Darjeeling Tea.</li>
        </ul>
        
        <b>Explore Local Culture & Cuisine</b>
        <p>Darjeeling is a beautiful blend of cultures—Nepali, Tibetan, Lepcha, and Bengali. Our guided tours give you an authentic glimpse into local life. Try delicious momos, thukpa, churpi, and sip aromatic Darjeeling tea. Don't miss the charming Mall Road and vibrant local markets.</p>
        
        <b>Why Book With Anugra?</b>
        <ul>
          <li>🚗 Pick-up & drop from Siliguri, NJP, Bagdogra, or Gangtok</li>
          <li>🏨 Hand-picked hotels with scenic views</li>
          <li>🧑‍🤝‍🧑 Friendly guides and local expertise</li>
          <li>📷 Sightseeing, cultural tours, and photography points</li>
          <li>📞 24x7 support throughout your trip</li>
        </ul>
        
        <b>Travel Tips</b>
        <ul>
          <li>📅 Best time to visit: March–May or October–December</li>
          <li>🧣 Carry light woollens even in summer</li>
          <li>📸 Keep your camera ready for postcard-perfect views</li>
        </ul>
        
        <b>Plan Your Darjeeling Tour with Us</b>
        <p>Let <b>Anugra Tours and Travels</b> take you on a seamless, scenic, and soul-soothing trip to the Queen of Hills. Our local team ensures every detail is handled, so you can relax and enjoy every view, every sip of tea, and every smile along the way.</p>
        
        <p><b>📍 Office:</b> Lingding, Gangtok, Sikkim<br><b>📞 Call:</b> +91 97321 81111<br><b>🌐 Website:</b> <a href="https://anugratravels.com/" target="_blank">www.anugratravels.com</a></p>
      </div>

      <!-- Engagement Section -->
      <div class="engagement-section">
        <div class="likes">
          <div class="like-count">
            <strong>0 <i class="fas fa-heart" style="color:red;"></i></strong>
          </div>
          <form method="POST" action="like.php">
            <input type="hidden" name="blog_id" value="16">
            <input type="hidden" name="slug" value="/explore-darjeeling-with-anugra">
            <button type="submit"><i class="fas fa-thumbs-up"></i> Like this post</button>
          </form>
        </div>

        <div class="social-share">
          <h3>🔗 Share this post:</h3>
          <div class="social-buttons">
            <a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Fanugratravels.com%2F%2Fblog-view.php%3Fslug%3D%252Fexplore-darjeeling-with-anugra" target="_blank"><i class="fab fa-facebook"></i>Facebook</a>
            <a href="https://twitter.com/intent/tweet?url=https%3A%2F%2Fanugratravels.com%2F%2Fblog-view.php%3Fslug%3D%252Fexplore-darjeeling-with-anugra&text=Darjeeling+Dreams%3A+Discover+the+Queen+of+Hills+with+Anugra" target="_blank"><i class="fab fa-twitter"></i>Twitter</a>
            <a href="https://api.whatsapp.com/send?text=Darjeeling+Dreams%3A+Discover+the+Queen+of+Hills+with+Anugra+https%3A%2F%2Fanugratravels.com%2F%2Fblog-view.php%3Fslug%3D%252Fexplore-darjeeling-with-anugra" target="_blank"><i class="fab fa-whatsapp"></i>WhatsApp</a>
            <a href="#" onclick="navigator.clipboard.writeText('https://anugratravels.com//blog-view.php?slug=%2Fexplore-darjeeling-with-anugra'); alert('Link copied!')"><i class="fas fa-link"></i>Copy Link</a>
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
    <div class="sidebar-widget">
      <div class="widget-header">
        <h3><i class="fas fa-clock"></i> Recent Posts</h3>
      </div>
      <div class="widget-content">
        <article class="recent-post">
          <img src="uploads/1752933363_lachung-anugra.webp" alt="Lachung Village" class="recent-post-image">
          <div class="recent-post-content">
            <h4><a href="blog-view.php?slug=lachung-village-north-sikkim-travel-guide-anugra-tours">Lachung Village: Discover the Snowy Gem of North Sikkim</a></h4>
            <div class="recent-post-meta">
              <i class="fas fa-calendar"></i> Today
            </div>
          </div>
        </article>

        <article class="recent-post">
          <img src="uploads/1752583197_anugra-blog.jpg" alt="Sikkim Travel Guide" class="recent-post-image">
          <div class="recent-post-content">
            <h4><a href="blog-view.php?slug=sikkim-travel-guide-month-by-month">🌿 A Month-by-Month Travel Guide to Sikkim</a></h4>
            <div class="recent-post-meta">
              <i class="fas fa-calendar"></i> 5 days ago
            </div>
          </div>
        </article>

        <article class="recent-post">
          <img src="uploads/1752496963_sikkimAnugra_travels.jpeg" alt="Discover Sikkim" class="recent-post-image">
          <div class="recent-post-content">
            <h4><a href="blog-view.php?slug=discover-sikkim-hidden-himalayan-paradise">Discover Sikkim: The Hidden Himalayan Paradise</a></h4>
            <div class="recent-post-meta">
              <i class="fas fa-calendar"></i> 6 days ago
            </div>
          </div>
        </article>

        <article class="recent-post">
          <img src="uploads/1752851772_blog.webp" alt="Monsoon Magic" class="recent-post-image">
          <div class="recent-post-content">
            <h4><a href="blog-view.php?slug=monsoon-magic-northeast-india-anugra-tours">Enchanting Escapes: Monsoon Magic in North East India</a></h4>
            <div class="recent-post-meta">
              <i class="fas fa-calendar"></i> 1 day ago
            </div>
          </div>
        </article>
      </div>
    </div>

    <!-- Popular Posts -->
    <div class="sidebar-widget">
      <div class="widget-header">
        <h3><i class="fas fa-fire"></i> Most Popular</h3>
      </div>
      <div class="widget-content">
        <article class="recent-post">
          <img src="uploads/1752674104_Darjeeling-anugra.webp" alt="Darjeeling Dreams" class="recent-post-image">
          <div class="recent-post-content">
            <h4><a href="blog-view.php?slug=%2Fexplore-darjeeling-with-anugra">Darjeeling Dreams: Discover the Queen of Hills</a></h4>
            <div class="recent-post-meta">
              <i class="fas fa-eye"></i> 1,254 views
            </div>
          </div>
        </article>

        <article class="recent-post">
          <img src="uploads/1752770603_anugra-img.jpg" alt="Hidden Gems" class="recent-post-image">
          <div class="recent-post-content">
            <h4><a href="blog-view.php?slug=top-5-hidden-gems-northeast-india-anugra-tours">Top 5 Hidden Gems of North East India</a></h4>
            <div class="recent-post-meta">
              <i class="fas fa-eye"></i> 987 views
            </div>
          </div>
        </article>

        <article class="recent-post">
          <img src="uploads/1752933363_lachung-anugra.webp" alt="Lachung Village" class="recent-post-image">
          <div class="recent-post-content">
            <h4><a href="blog-view.php?slug=lachung-village-north-sikkim-travel-guide-anugra-tours">Lachung Village: Snowy Gem of North Sikkim</a></h4>
            <div class="recent-post-meta">
              <i class="fas fa-eye"></i> 743 views
            </div>
          </div>
        </article>
      </div>
    </div>

    <!-- Google Ad Space 2 -->
    <div class="sidebar-widget">
      <div class="ad-space">
        <i class="fas fa-ad"></i>
        <h4>Advertisement</h4>
        <p>Google Ad Space<br>300x600</p>
      </div>
    </div>

    <!-- Popular Tags -->
    <div class="sidebar-widget">
      <div class="widget-header">
        <h3><i class="fas fa-tags"></i> Popular Tags</h3>
      </div>
      <div class="widget-content">
        <div class="popular-tags">
          <a href="#" class="popular-tag">Sikkim Tours</a>
          <a href="#" class="popular-tag">Darjeeling</a>
          <a href="#" class="popular-tag">North East India</a>
          <a href="#" class="popular-tag">Gangtok</a>
          <a href="#" class="popular-tag">Adventure</a>
          <a href="#" class="popular-tag">Himalayas</a>
          <a href="#" class="popular-tag">Tea Gardens</a>
          <a href="#" class="popular-tag">Travel Tips</a>
          <a href="#" class="popular-tag">Culture</a>
          <a href="#" class="popular-tag">Photography</a>
        </div>
      </div>
    </div>

    <!-- Newsletter Signup -->
    <div class="sidebar-widget">
      <div class="widget-header">
        <h3><i class="fas fa-envelope"></i> Stay Updated</h3>
      </div>
      <div class="widget-content">
        <p style="margin-bottom: 20px; color: #666;">Get the latest travel stories delivered to your inbox.</p>
        <form style="display: flex; flex-direction: column; gap: 15px;">
          <input type="email" placeholder="Your email address" style="padding: 12px; border: 2px solid #e9ecef; border-radius: 10px; outline: none;">
          <button type="submit" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600;">Subscribe</button>
        </form>
      </div>
    </div>
  </aside>
</div>

<!-- Comments Section -->
<div class="comments-section" style="max-width: 1400px; margin: 0 auto; padding: 0 20px;">
  <div class="comments">
    <h3>💬 Comments</h3>
    
    <div class="comment-form">
      <h4 style="margin-bottom: 20px; color: #2c3e50;">Leave a Comment</h4>
      <form method="POST">
        <input name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Email (optional)">
        <textarea name="comment" rows="4" placeholder="Your comment..." required></textarea>
        <input type="hidden" name="parent_id" value="">
        <button type="submit"><i class="fas fa-comment-dots"></i> Submit Comment</button>
      </form>
    </div>

    <div style="text-align: center; color: #888; padding: 40px 0;">
      <p>No comments yet. Be the first to share your thoughts!</p>
    </div>
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
  // Reply button functionality
  document.querySelectorAll('.reply-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const id = btn.dataset.id;
      document.querySelectorAll('.reply-form').forEach(f => f.style.display = 'none');
      document.getElementById('reply-form-' + id).style.display = 'block';
    });
  });

  // Newsletter signup
  document.querySelector('.sidebar .widget-content form').addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.querySelector('input[type="email"]').value;
    alert('Thank you for subscribing! We\'ll keep you updated with our latest travel stories.');
    this.reset();
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