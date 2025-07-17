<?php
require_once '../config/database.php';
requireAdminLogin();

// Get dashboard statistics
try {
    $pdo = getConnection();
    
    // Total bookings
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Upcoming bookings (next 30 days)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE check_in >= CURDATE() AND check_in <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $upcoming_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM bookings WHERE status = 'confirmed'");
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Occupancy rate calculation (simplified)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms");
    $total_rooms = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as occupied FROM bookings WHERE CURDATE() BETWEEN check_in AND check_out");
    $occupied_rooms = $stmt->fetch(PDO::FETCH_ASSOC)['occupied'];
    
    $occupancy_rate = $total_rooms > 0 ? ($occupied_rooms / $total_rooms) * 100 : 0;
    
    // Recent bookings
    $stmt = $pdo->query("SELECT b.*, r.room_name FROM bookings b LEFT JOIN rooms r ON b.room_id = r.id ORDER BY b.created_at DESC LIMIT 5");
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // New inquiries
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM inquiries WHERE status = 'new'");
    $new_inquiries = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch(PDOException $e) {
    $total_bookings = 0;
    $upcoming_bookings = 0;
    $total_revenue = 0;
    $occupancy_rate = 0;
    $recent_bookings = [];
    $new_inquiries = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Grand Hotel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .admin-header {
            background: #2c3e50;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .admin-nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-logo {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .admin-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .admin-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .admin-menu a:hover, .admin-menu a.active {
            background: #34495e;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }

        .page-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #2c3e50;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .stat-card.bookings .stat-value { color: #3498db; }
        .stat-card.revenue .stat-value { color: #27ae60; }
        .stat-card.occupancy .stat-value { color: #e74c3c; }
        .stat-card.inquiries .stat-value { color: #f39c12; }

        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .recent-bookings {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }

        .booking-item {
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .booking-item:last-child {
            border-bottom: none;
        }

        .booking-info h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .booking-info p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .booking-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .quick-actions {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .action-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .action-btn:hover {
            background: #2980b9;
        }

        .action-btn.rooms { background: #27ae60; }
        .action-btn.rooms:hover { background: #229954; }

        .action-btn.settings { background: #e74c3c; }
        .action-btn.settings:hover { background: #c0392b; }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #2980b9;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .admin-menu {
                display: none;
            }
            
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="admin-logo">Grand Hotel Admin</div>
            <ul class="admin-menu">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="rooms.php">Rooms</a></li>
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="availability.php">Availability</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
            <div class="admin-user">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="../logout.php" class="btn btn-sm">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1 class="page-title">Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card bookings">
                <div class="stat-value"><?php echo $total_bookings; ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card bookings">
                <div class="stat-value"><?php echo $upcoming_bookings; ?></div>
                <div class="stat-label">Upcoming Bookings</div>
            </div>
            <div class="stat-card revenue">
                <div class="stat-value"><?php echo formatCurrency($total_revenue); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card occupancy">
                <div class="stat-value"><?php echo number_format($occupancy_rate, 1); ?>%</div>
                <div class="stat-label">Occupancy Rate</div>
            </div>
            <div class="stat-card inquiries">
                <div class="stat-value"><?php echo $new_inquiries; ?></div>
                <div class="stat-label">New Inquiries</div>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="recent-bookings">
                <h2 class="section-title">Recent Bookings</h2>
                <?php if (empty($recent_bookings)): ?>
                    <p>No recent bookings found.</p>
                <?php else: ?>
                    <?php foreach ($recent_bookings as $booking): ?>
                        <div class="booking-item">
                            <div class="booking-info">
                                <h4><?php echo htmlspecialchars($booking['guest_name']); ?></h4>
                                <p>
                                    <?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?> • 
                                    <?php echo formatDate($booking['check_in']); ?> - <?php echo formatDate($booking['check_out']); ?>
                                </p>
                            </div>
                            <div class="booking-status status-<?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="bookings.php" class="btn">View All Bookings</a>
                </div>
            </div>

            <div class="quick-actions">
                <h2 class="section-title">Quick Actions</h2>
                <a href="rooms.php" class="action-btn rooms">Manage Rooms</a>
                <a href="bookings.php" class="action-btn">View Bookings</a>
                <a href="availability.php" class="action-btn">Update Availability</a>
                <a href="settings.php" class="action-btn settings">OTA Settings</a>
                <a href="sync_ota.php" class="action-btn" style="background: #9b59b6;">Sync to OTA</a>
            </div>
        </div>
    </div>
</body>
</html>