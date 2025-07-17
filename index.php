<?php
require_once 'config/database.php';

// Handle booking inquiry form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_inquiry'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $checkin = sanitizeInput($_POST['checkin']);
    $checkout = sanitizeInput($_POST['checkout']);
    $room_type = sanitizeInput($_POST['room_type']);
    $guests = intval($_POST['guests']);
    $message = sanitizeInput($_POST['message']);
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, phone, check_in, check_out, room_type, guests_count, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $checkin, $checkout, $room_type, $guests, $message]);
        $success_message = "Thank you for your inquiry! We will contact you soon.";
    } catch(PDOException $e) {
        $error_message = "Error submitting inquiry. Please try again.";
    }
}

// Get room types for the form
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT * FROM room_types ORDER BY name");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sample rooms for display
    $stmt = $pdo->query("SELECT r.*, rt.name as room_type_name, ri.image_path FROM rooms r 
                        LEFT JOIN room_types rt ON r.room_type_id = rt.id 
                        LEFT JOIN room_images ri ON r.id = ri.room_id AND ri.is_primary = 1 
                        ORDER BY r.price_per_night LIMIT 4");
    $featured_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $room_types = [];
    $featured_rooms = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Hotel - Luxury Accommodation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #f39c12;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23e74c3c" width="1200" height="600"/><text x="600" y="300" text-anchor="middle" fill="white" font-size="48">Hotel Image</text></svg>');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #f39c12;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }

        /* Sections */
        .section {
            padding: 4rem 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #2c3e50;
        }

        /* About Section */
        .about {
            background: #f8f9fa;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .about-text {
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .about-image {
            text-align: center;
        }

        .about-image img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Rooms Section */
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .room-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .room-card:hover {
            transform: translateY(-5px);
        }

        .room-image {
            height: 200px;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 1.1rem;
        }

        .room-content {
            padding: 1.5rem;
        }

        .room-title {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .room-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .room-features {
            list-style: none;
            margin-bottom: 1rem;
        }

        .room-features li {
            padding: 0.2rem 0;
            color: #666;
        }

        .room-features li:before {
            content: "✓ ";
            color: #27ae60;
            font-weight: bold;
        }

        /* Booking Form */
        .booking-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Contact Section */
        .contact {
            background: #2c3e50;
            color: white;
        }

        .contact-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .contact-item {
            padding: 1.5rem;
        }

        .contact-item h3 {
            margin-bottom: 1rem;
            color: #f39c12;
        }

        /* Footer */
        footer {
            background: #1a252f;
            color: white;
            text-align: center;
            padding: 2rem 0;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .about-content {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">Grand Hotel</div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#rooms">Rooms</a></li>
                <li><a href="#booking">Book Now</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a href="login.php">Admin</a></li>
            </ul>
        </nav>
    </header>

    <section id="home" class="hero">
        <div class="hero-content">
            <h1>Welcome to Grand Hotel</h1>
            <p>Experience luxury and comfort in the heart of the city</p>
            <a href="#booking" class="btn">Book Your Stay</a>
        </div>
    </section>

    <section id="about" class="section about">
        <div class="container">
            <h2 class="section-title">About Grand Hotel</h2>
            <div class="about-content">
                <div class="about-text">
                    <p>Welcome to Grand Hotel, where luxury meets comfort in the heart of the city. Our hotel offers world-class amenities, exceptional service, and elegantly appointed rooms designed to make your stay unforgettable.</p>
                    <p>Since 1985, we have been providing guests with an unparalleled hospitality experience. Our commitment to excellence and attention to detail ensures that every moment of your stay is perfect.</p>
                    <p>Whether you're traveling for business or leisure, our dedicated staff is here to cater to your every need, making Grand Hotel your home away from home.</p>
                </div>
                <div class="about-image">
                    <div style="width: 100%; height: 300px; background: #ddd; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #666; font-size: 1.2rem;">
                        Hotel Lobby Image
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="rooms" class="section">
        <div class="container">
            <h2 class="section-title">Our Rooms</h2>
            <div class="rooms-grid">
                <?php foreach ($featured_rooms as $room): ?>
                <div class="room-card">
                    <div class="room-image">
                        <?php if ($room['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($room['image_path']); ?>" alt="<?php echo htmlspecialchars($room['room_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            Room Image
                        <?php endif; ?>
                    </div>
                    <div class="room-content">
                        <h3 class="room-title"><?php echo htmlspecialchars($room['room_name']); ?></h3>
                        <div class="room-price"><?php echo formatCurrency($room['price_per_night']); ?>/night</div>
                        <p><?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>...</p>
                        <ul class="room-features">
                            <?php 
                            $amenities = explode(',', $room['amenities']);
                            foreach (array_slice($amenities, 0, 3) as $amenity): 
                            ?>
                                <li><?php echo htmlspecialchars(trim($amenity)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <div style="text-align: center; margin-top: 1rem;">
                            <span style="color: #666;">Max <?php echo $room['max_occupancy']; ?> guests</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="booking" class="section" style="background: #f8f9fa;">
        <div class="container">
            <h2 class="section-title">Book Your Stay</h2>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form class="booking-form" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="checkin">Check-in Date *</label>
                        <input type="date" id="checkin" name="checkin" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="checkout">Check-out Date *</label>
                        <input type="date" id="checkout" name="checkout" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="room_type">Room Type</label>
                        <select id="room_type" name="room_type">
                            <option value="">Select Room Type</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['name']); ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="guests">Number of Guests *</label>
                        <select id="guests" name="guests" required>
                            <option value="1">1 Guest</option>
                            <option value="2">2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                            <option value="5">5+ Guests</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="message">Special Requests</label>
                    <textarea id="message" name="message" rows="4" placeholder="Any special requests or requirements..."></textarea>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" name="submit_inquiry" class="btn">Submit Inquiry</button>
                </div>
                
                <div style="text-align: center; margin-top: 1rem;">
                    <p>Or book directly via:</p>
                    <a href="https://wa.me/1234567890" target="_blank" class="btn" style="background: #25d366; margin: 0.5rem;">WhatsApp</a>
                    <a href="#" class="btn" style="background: #0066cc; margin: 0.5rem;">Booking.com</a>
                </div>
            </form>
        </div>
    </section>

    <section id="contact" class="section contact">
        <div class="container">
            <h2 class="section-title">Contact Us</h2>
            <div class="contact-content">
                <div class="contact-item">
                    <h3>Address</h3>
                    <p>123 Hotel Street<br>City Center, State 12345<br>United States</p>
                </div>
                <div class="contact-item">
                    <h3>Phone</h3>
                    <p>+1 (555) 123-4567<br>+1 (555) 123-4568</p>
                </div>
                <div class="contact-item">
                    <h3>Email</h3>
                    <p>info@grandhotel.com<br>reservations@grandhotel.com</p>
                </div>
                <div class="contact-item">
                    <h3>Hours</h3>
                    <p>Front Desk: 24/7<br>Concierge: 6 AM - 11 PM</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; 2024 Grand Hotel. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Smooth scrolling for navigation links
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

        // Form validation
        document.querySelector('.booking-form').addEventListener('submit', function(e) {
            const checkin = new Date(document.getElementById('checkin').value);
            const checkout = new Date(document.getElementById('checkout').value);
            
            if (checkout <= checkin) {
                e.preventDefault();
                alert('Check-out date must be after check-in date');
                return false;
            }
        });

        // Update checkout min date when checkin changes
        document.getElementById('checkin').addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            const checkoutDate = new Date(checkinDate);
            checkoutDate.setDate(checkoutDate.getDate() + 1);
            
            const checkoutInput = document.getElementById('checkout');
            checkoutInput.min = checkoutDate.toISOString().split('T')[0];
            
            if (checkoutInput.value && new Date(checkoutInput.value) <= checkinDate) {
                checkoutInput.value = checkoutDate.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>