<?php
require_once '../config/database.php';
requireAdminLogin();

$success_message = '';
$error_message = '';

// Handle different POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle walk-in booking creation
    if (isset($_POST['create_walk_in'])) {
        $guest_name = sanitizeInput($_POST['guest_name']);
        $guest_email = sanitizeInput($_POST['guest_email']);
        $guest_phone = sanitizeInput($_POST['guest_phone']);
        $room_id = intval($_POST['room_id']);
        $check_in = sanitizeInput($_POST['check_in']);
        $check_out = sanitizeInput($_POST['check_out']);
        $guests_count = intval($_POST['guests_count']);
        
        try {
            $pdo = getConnection();
            
            // Get room price and validate room exists
            $stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            $room_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$room_data) {
                throw new Exception("Selected room not found");
            }
            
            $room_price = $room_data['price_per_night'];
            
            // Validate dates
            $check_in_date = new DateTime($check_in);
            $check_out_date = new DateTime($check_out);
            
            if ($check_out_date <= $check_in_date) {
                throw new Exception("Check-out date must be after check-in date");
            }
            
            // Check room availability
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status != 'cancelled' AND ((check_in <= ? AND check_out > ?) OR (check_in < ? AND check_out >= ?))");
            $stmt->execute([$room_id, $check_in, $check_in, $check_out, $check_out]);
            $conflicts = $stmt->fetchColumn();
            
            if ($conflicts > 0) {
                throw new Exception("Room is not available for the selected dates");
            }
            
            // Calculate total amount (dates already validated above)
            $nights = $check_in_date->diff($check_out_date)->days;
            $total_amount = $room_price * $nights;
            
            // Create walk-in booking
            $stmt = $pdo->prepare("INSERT INTO bookings (guest_name, guest_email, guest_phone, room_id, check_in, check_out, guests_count, total_amount, booking_source, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'walk_in', 'confirmed')");
            $stmt->execute([$guest_name, $guest_email, $guest_phone, $room_id, $check_in, $check_out, $guests_count, $total_amount]);
            
            $success_message = "Walk-in booking created successfully!";
        } catch(PDOException $e) {
            $error_message = "Error creating walk-in booking: " . $e->getMessage();
        } catch(Exception $e) {
            $error_message = $e->getMessage();
        }
    }
    
    // Handle status updates
    if (isset($_POST['update_status'])) {
        $booking_id = intval($_POST['booking_id']);
        $new_status = sanitizeInput($_POST['status']);
        
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $booking_id]);
            $success_message = "Booking status updated successfully!";
        } catch(PDOException $e) {
            $error_message = "Error updating booking status.";
        }
    }
    
    // Handle adding expenses
    if (isset($_POST['add_expense'])) {
        $booking_id = intval($_POST['booking_id']);
        $expense_type = sanitizeInput($_POST['expense_type']);
        $description = sanitizeInput($_POST['description']);
        $amount = floatval($_POST['amount']);
        $quantity = intval($_POST['quantity']) ?: 1;
        
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO booking_expenses (booking_id, expense_type, description, amount, quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$booking_id, $expense_type, $description, $amount, $quantity]);
            $success_message = "Expense added successfully!";
        } catch(PDOException $e) {
            $error_message = "Error adding expense: " . $e->getMessage();
        }
    }
    
    // Handle deleting expenses
    if (isset($_POST['delete_expense'])) {
        $expense_id = intval($_POST['expense_id']);
        $booking_id = intval($_POST['booking_id']);
        
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("DELETE FROM booking_expenses WHERE id = ? AND booking_id = ?");
            $stmt->execute([$expense_id, $booking_id]);
            $success_message = "Expense deleted successfully!";
        } catch(PDOException $e) {
            $error_message = "Error deleting expense.";
        }
    }
}

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$room_type = isset($_GET['room_type']) ? $_GET['room_type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$ota_source = isset($_GET['ota_source']) ? $_GET['ota_source'] : '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($date_from) {
    $where_conditions[] = "b.check_in >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "b.check_out <= ?";
    $params[] = $date_to;
}

if ($room_type) {
    $where_conditions[] = "rt.name = ?";
    $params[] = $room_type;
}

if ($status) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status;
}

if ($ota_source) {
    $where_conditions[] = "b.booking_source = ?";
    $params[] = $ota_source;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

try {
    $pdo = getConnection();
    
    // Get bookings with filters and expense totals
    $query = "SELECT b.*, r.room_name, rt.name as room_type_name,
              COALESCE(SUM(be.amount * be.quantity), 0) as total_expenses
              FROM bookings b 
              LEFT JOIN rooms r ON b.room_id = r.id 
              LEFT JOIN room_types rt ON r.room_type_id = rt.id 
              LEFT JOIN booking_expenses be ON b.id = be.booking_id
              $where_clause 
              GROUP BY b.id
              ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get room types for filter
    $stmt = $pdo->query("SELECT DISTINCT rt.name FROM room_types rt");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get booking sources for filter
    $stmt = $pdo->query("SELECT DISTINCT booking_source FROM bookings WHERE booking_source IS NOT NULL");
    $booking_sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get expense items for quick selection
    $stmt = $pdo->query("SELECT * FROM expense_items WHERE is_active = 1 ORDER BY category, item_name");
    $expense_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all rooms for walk-in booking
    $stmt = $pdo->query("SELECT r.*, rt.name as room_type_name FROM rooms r LEFT JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_name");
    $all_rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $bookings = [];
    $room_types = [];
    $booking_sources = [];
    $expense_items = [];
    $all_rooms = [];
    
    // Log the error for debugging
    error_log("Bookings.php database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings Management - Grand Hotel</title>
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

        .filters {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .filters h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

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
            font-size: 0.9rem;
        }

        .btn:hover {
            background: #2980b9;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .btn-success {
            background: #27ae60;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-danger {
            background: #e74c3c;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-warning {
            background: #f39c12;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

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

        .bookings-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .booking-actions {
            display: flex;
            gap: 0.5rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .booking-details {
            margin-bottom: 1rem;
        }

        .booking-details h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .booking-details p {
            margin-bottom: 0.5rem;
            color: #666;
        }

        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .stat-card.total .stat-value { color: #3498db; }
        .stat-card.confirmed .stat-value { color: #27ae60; }
        .stat-card.pending .stat-value { color: #f39c12; }
        .stat-card.cancelled .stat-value { color: #e74c3c; }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: none;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            line-height: 1;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #e74c3c;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .expense-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        /* Walk-in Booking Styles */
        .walk-in-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #f39c12;
        }

        .walk-in-form .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
            margin-top: 2rem;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .expenses-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .expenses-table table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .expenses-table th,
        .expenses-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .expenses-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .bill-content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            font-family: 'Times New Roman', serif;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #138496, #117a8b);
        }

        @media print {
            .modal {
                display: block !important;
                position: static;
                background: none;
            }
            
            .modal-content {
                box-shadow: none;
                border: none;
                margin: 0;
                width: 100%;
                max-width: none;
            }
            
            .close, .bill-header button {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .admin-menu {
                display: none;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            .booking-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="admin-logo">Grand Hotel Admin</div>
            <ul class="admin-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="rooms.php">Rooms</a></li>
                <li><a href="bookings.php" class="active">Bookings</a></li>
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
        <h1 class="page-title">Bookings Management</h1>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Statistics Summary -->
        <div class="stats-summary">
            <div class="stat-card total">
                <div class="stat-value"><?php echo count($bookings); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card confirmed">
                <div class="stat-value"><?php echo count(array_filter($bookings, function($b) { return $b['status'] === 'confirmed'; })); ?></div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-value"><?php echo count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; })); ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card cancelled">
                <div class="stat-value"><?php echo count(array_filter($bookings, function($b) { return $b['status'] === 'cancelled'; })); ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>

        <!-- Walk-in Booking Section -->
        <?php if (isset($_GET['action']) && $_GET['action'] === 'walk_in'): ?>
        <div class="walk-in-section">
            <h2 class="section-title">Create Walk-in Booking</h2>
            <form method="POST" class="walk-in-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="guest_name">Guest Name *</label>
                        <input type="text" id="guest_name" name="guest_name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="guest_email">Email</label>
                        <input type="email" id="guest_email" name="guest_email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="guest_phone">Phone *</label>
                        <input type="tel" id="guest_phone" name="guest_phone" required class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="room_id">Room *</label>
                        <select id="room_id" name="room_id" required class="form-control">
                            <option value="">Select a Room</option>
                            <?php if (empty($all_rooms)): ?>
                                <option value="" disabled>No rooms available
                                    <?php if (!empty($error_message)): ?>
                                        (Database error)
                                    <?php endif; ?>
                                </option>
                            <?php else: ?>
                                <?php foreach ($all_rooms as $room): ?>
                                    <option value="<?php echo $room['id']; ?>">
                                        <?php echo htmlspecialchars($room['room_name']); ?> 
                                        (<?php echo htmlspecialchars($room['room_type_name']); ?>) 
                                        - <?php echo formatCurrency($room['price_per_night']); ?>/night
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="check_in">Check-in Date *</label>
                        <input type="date" id="check_in" name="check_in" required class="form-control" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="check_out">Check-out Date *</label>
                        <input type="date" id="check_out" name="check_out" required class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="guests_count">Number of Guests *</label>
                        <input type="number" id="guests_count" name="guests_count" required class="form-control" min="1" max="10" value="1">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_walk_in" class="btn btn-success">Create Walk-in Booking</button>
                    <a href="bookings.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <h3>Filter Bookings</h3>
            <form method="GET">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="date_from">Check-in From</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_to">Check-out To</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="form-group">
                        <label for="room_type">Room Type</label>
                        <select id="room_type" name="room_type">
                            <option value="">All Types</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['name']); ?>" <?php echo $room_type === $type['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ota_source">Booking Source</label>
                        <select id="ota_source" name="ota_source">
                            <option value="">All Sources</option>
                            <?php foreach ($booking_sources as $source): ?>
                                <option value="<?php echo htmlspecialchars($source['booking_source']); ?>" <?php echo $ota_source === $source['booking_source'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($source['booking_source'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn">Apply Filters</button>
                <a href="bookings.php" class="btn" style="background: #95a5a6;">Clear Filters</a>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="bookings-table">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Room</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Guests</th>
                            <th>Room Amount</th>
                            <th>Expenses</th>
                            <th>Total</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="11" style="text-align: center; padding: 2rem; color: #666;">
                                    No bookings found.
                                    <?php if (!empty($error_message)): ?>
                                        <br><small style="color: red;">Error: <?php echo htmlspecialchars($error_message); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong>
                                        <?php if ($booking['guest_email']): ?>
                                            <br><small><?php echo htmlspecialchars($booking['guest_email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?>
                                        <?php if ($booking['room_type_name']): ?>
                                            <br><small><?php echo htmlspecialchars($booking['room_type_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($booking['check_in']); ?></td>
                                    <td><?php echo formatDate($booking['check_out']); ?></td>
                                    <td><?php echo $booking['guests_count']; ?></td>
                                    <td><?php echo $booking['total_amount'] ? formatCurrency($booking['total_amount']) : 'N/A'; ?></td>
                                    <td><?php echo formatCurrency($booking['total_expenses']); ?></td>
                                    <td><strong><?php echo formatCurrency(($booking['total_amount'] ?: 0) + $booking['total_expenses']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(ucfirst($booking['booking_source'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="booking-actions">
                                            <button class="btn btn-sm" onclick="viewBooking(<?php echo $booking['id']; ?>)">View</button>
                                            <button class="btn btn-warning btn-sm" onclick="updateStatus(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">Status</button>
                                            <button class="btn btn-success btn-sm" onclick="manageExpenses(<?php echo $booking['id']; ?>)">Expenses</button>
                                            <button class="btn btn-info btn-sm" onclick="printBill(<?php echo $booking['id']; ?>)">Print Bill</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- View Booking Modal -->
    <div id="viewBookingModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('viewBookingModal')">&times;</span>
            <h2>Booking Details</h2>
            <div id="bookingDetails"></div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('updateStatusModal')">&times;</span>
            <h2>Update Booking Status</h2>
            <form method="POST">
                <input type="hidden" id="status_booking_id" name="booking_id">
                <div class="form-group">
                    <label for="new_status">New Status</label>
                    <select id="new_status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
            </form>
        </div>
    </div>

    <!-- Manage Expenses Modal -->
    <div id="expensesModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close" onclick="closeModal('expensesModal')">&times;</span>
            <h2>Manage Expenses</h2>
            
            <!-- Add Expense Form -->
            <div class="expense-form">
                <h3>Add New Expense</h3>
                <form method="POST" id="addExpenseForm">
                    <input type="hidden" id="expense_booking_id" name="booking_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expense_type">Category</label>
                            <select id="expense_type" name="expense_type" required onchange="updateQuickItems()">
                                <option value="">Select Category</option>
                                <option value="laundry">Laundry</option>
                                <option value="food">Food</option>
                                <option value="beverages">Beverages</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="quick_items">Quick Select</label>
                            <select id="quick_items" onchange="selectQuickItem()">
                                <option value="">Choose an item...</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <input type="text" id="description" name="description" required>
                        </div>
                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">Amount (₹)</label>
                            <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_expense" class="btn btn-success">Add Expense</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Current Expenses List -->
            <div class="expenses-list">
                <h3>Current Expenses</h3>
                <div id="expensesList"></div>
            </div>
        </div>
    </div>

    <!-- Print Bill Modal -->
    <div id="printBillModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <span class="close" onclick="closeModal('printBillModal')">&times;</span>
            <div class="bill-header">
                <button onclick="printBill()" class="btn btn-primary" style="float: right; margin-bottom: 1rem;">Print Bill</button>
                <h2>Guest Bill</h2>
            </div>
            <div id="billContent"></div>
        </div>
    </div>

    <script>
        const bookings = <?php echo json_encode($bookings); ?>;
        const expenseItems = <?php echo json_encode($expense_items); ?>;

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function viewBooking(bookingId) {
            const booking = bookings.find(b => b.id == bookingId);
            if (booking) {
                const detailsHtml = `
                    <div class="booking-details">
                        <h4>Guest Information</h4>
                        <p><strong>Name:</strong> ${booking.guest_name}</p>
                        <p><strong>Email:</strong> ${booking.guest_email || 'N/A'}</p>
                        <p><strong>Phone:</strong> ${booking.guest_phone || 'N/A'}</p>
                        
                        <h4>Booking Details</h4>
                        <p><strong>Room:</strong> ${booking.room_name || 'N/A'}</p>
                        <p><strong>Check-in:</strong> ${new Date(booking.check_in).toLocaleDateString()}</p>
                        <p><strong>Check-out:</strong> ${new Date(booking.check_out).toLocaleDateString()}</p>
                        <p><strong>Guests:</strong> ${booking.guests_count}</p>
                        <p><strong>Total Amount:</strong> ${booking.total_amount ? '₹' + parseFloat(booking.total_amount).toFixed(2) : 'N/A'}</p>
                        <p><strong>Source:</strong> ${booking.booking_source}</p>
                        <p><strong>Status:</strong> ${booking.status}</p>
                        <p><strong>Created:</strong> ${new Date(booking.created_at).toLocaleString()}</p>
                        
                        ${booking.ota_booking_id ? `<p><strong>OTA Booking ID:</strong> ${booking.ota_booking_id}</p>` : ''}
                    </div>
                `;
                document.getElementById('bookingDetails').innerHTML = detailsHtml;
                openModal('viewBookingModal');
            }
        }

        function updateStatus(bookingId, currentStatus) {
            document.getElementById('status_booking_id').value = bookingId;
            document.getElementById('new_status').value = currentStatus;
            openModal('updateStatusModal');
        }

        // New functions for expense management
        function manageExpenses(bookingId) {
            document.getElementById('expense_booking_id').value = bookingId;
            loadExpenses(bookingId);
            openModal('expensesModal');
        }

        function updateQuickItems() {
            const category = document.getElementById('expense_type').value;
            const quickSelect = document.getElementById('quick_items');
            
            // Clear existing options
            quickSelect.innerHTML = '<option value="">Choose an item...</option>';
            
            if (category) {
                const categoryItems = expenseItems.filter(item => item.category === category);
                categoryItems.forEach(item => {
                                    const option = document.createElement('option');
                option.value = JSON.stringify({description: item.item_name, amount: item.price});
                option.textContent = `${item.item_name} - ₹${parseFloat(item.price).toFixed(2)}`;
                quickSelect.appendChild(option);
                });
            }
        }

        function selectQuickItem() {
            const quickSelect = document.getElementById('quick_items');
            if (quickSelect.value) {
                const itemData = JSON.parse(quickSelect.value);
                document.getElementById('description').value = itemData.description;
                document.getElementById('amount').value = parseFloat(itemData.amount).toFixed(2);
            }
        }

        async function loadExpenses(bookingId) {
            try {
                const response = await fetch(`get_expenses.php?booking_id=${bookingId}`);
                const expenses = await response.json();
                
                let expensesHtml = '';
                if (expenses.length === 0) {
                    expensesHtml = '<p>No expenses added yet.</p>';
                } else {
                    expensesHtml = '<div class="expenses-table"><table class="table"><thead><tr><th>Category</th><th>Description</th><th>Qty</th><th>Amount</th><th>Total</th><th>Action</th></tr></thead><tbody>';
                    expenses.forEach(expense => {
                        const total = parseFloat(expense.amount) * parseInt(expense.quantity);
                        expensesHtml += `
                            <tr>
                                <td>${expense.expense_type.charAt(0).toUpperCase() + expense.expense_type.slice(1)}</td>
                                <td>${expense.description}</td>
                                <td>${expense.quantity}</td>
                                <td>₹${parseFloat(expense.amount).toFixed(2)}</td>
                                <td>₹${total.toFixed(2)}</td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this expense?')">
                                        <input type="hidden" name="expense_id" value="${expense.id}">
                                        <input type="hidden" name="booking_id" value="${bookingId}">
                                        <button type="submit" name="delete_expense" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        `;
                    });
                    expensesHtml += '</tbody></table></div>';
                }
                
                document.getElementById('expensesList').innerHTML = expensesHtml;
            } catch (error) {
                console.error('Error loading expenses:', error);
                document.getElementById('expensesList').innerHTML = '<p>Error loading expenses.</p>';
            }
        }

        async function printBill(bookingId) {
            if (typeof bookingId === 'undefined') {
                // If called from print button inside modal, print the current content
                window.print();
                return;
            }
            
            // Open the professional invoice page in a new window
            window.open(`print_bill.php?booking_id=${bookingId}`, '_blank');
        }

        function generateBillHTML(data) {
            const checkIn = new Date(data.booking.check_in);
            const checkOut = new Date(data.booking.check_out);
            const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
            
            let expensesHtml = '';
            let expensesTotal = 0;
            
            if (data.expenses && data.expenses.length > 0) {
                data.expenses.forEach(expense => {
                    const lineTotal = parseFloat(expense.amount) * parseInt(expense.quantity);
                    expensesTotal += lineTotal;
                    expensesHtml += `
                        <tr>
                            <td>${expense.description}</td>
                            <td>${expense.quantity}</td>
                            <td>₹${parseFloat(expense.amount).toFixed(2)}</td>
                            <td>₹${lineTotal.toFixed(2)}</td>
                        </tr>
                    `;
                });
            }
            
            const roomTotal = parseFloat(data.booking.total_amount) || 0;
            const grandTotal = roomTotal + expensesTotal;
            
            return `
                <div class="bill-content" style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">
                    <div style="text-align: center; margin-bottom: 2rem; border-bottom: 2px solid #333; padding-bottom: 1rem;">
                        <h1 style="color: #333; margin: 0;">Grand Hotel</h1>
                        <p style="margin: 0.5rem 0;">123 Hotel Street, City, State 12345</p>
                        <p style="margin: 0;">Phone: (555) 123-4567 | Email: info@grandhotel.com</p>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <h2 style="color: #333; border-bottom: 1px solid #ccc; padding-bottom: 0.5rem;">Guest Bill</h2>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1rem;">
                            <div>
                                <h3>Guest Information</h3>
                                <p><strong>Name:</strong> ${data.booking.guest_name}</p>
                                <p><strong>Email:</strong> ${data.booking.guest_email || 'N/A'}</p>
                                <p><strong>Phone:</strong> ${data.booking.guest_phone || 'N/A'}</p>
                            </div>
                            <div>
                                <h3>Booking Details</h3>
                                <p><strong>Room:</strong> ${data.booking.room_name || 'N/A'}</p>
                                <p><strong>Check-in:</strong> ${checkIn.toLocaleDateString()}</p>
                                <p><strong>Check-out:</strong> ${checkOut.toLocaleDateString()}</p>
                                <p><strong>Nights:</strong> ${nights}</p>
                                <p><strong>Guests:</strong> ${data.booking.guests_count}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 2rem;">
                        <h3>Room Charges</h3>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;">Description</th>
                                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: center;">Nights</th>
                                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: right;">Rate</th>
                                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">${data.booking.room_name || 'Room'} - ${data.booking.room_type_name || ''}</td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd; text-align: center;">${nights}</td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd; text-align: right;">₹${(roomTotal / nights).toFixed(2)}</td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd; text-align: right;">₹${roomTotal.toFixed(2)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    ${expensesHtml ? `
                    <div style="margin-bottom: 2rem;">
                        <h3>Additional Charges</h3>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: left;">Description</th>
                                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: center;">Qty</th>
                                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: right;">Rate</th>
                                    <th style="padding: 0.75rem; border: 1px solid #ddd; text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${expensesHtml}
                            </tbody>
                        </table>
                    </div>
                    ` : ''}
                    
                    <div style="border-top: 2px solid #333; padding-top: 1rem; margin-top: 2rem;">
                        <table style="width: 100%; font-size: 1.1rem;">
                            <tr>
                                <td style="text-align: right; padding: 0.5rem;"><strong>Room Total:</strong></td>
                                <td style="text-align: right; padding: 0.5rem; width: 120px;"><strong>₹${roomTotal.toFixed(2)}</strong></td>
                            </tr>
                            ${expensesTotal > 0 ? `
                            <tr>
                                <td style="text-align: right; padding: 0.5rem;"><strong>Additional Charges:</strong></td>
                                <td style="text-align: right; padding: 0.5rem;"><strong>₹${expensesTotal.toFixed(2)}</strong></td>
                            </tr>
                            ` : ''}
                            <tr style="border-top: 1px solid #333; font-size: 1.3rem; color: #333;">
                                <td style="text-align: right; padding: 1rem;"><strong>TOTAL AMOUNT:</strong></td>
                                <td style="text-align: right; padding: 1rem;"><strong>₹${grandTotal.toFixed(2)}</strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #ccc; color: #666;">
                        <p>Thank you for staying with us!</p>
                        <p style="font-size: 0.9rem;">Bill generated on ${new Date().toLocaleDateString()} at ${new Date().toLocaleTimeString()}</p>
                    </div>
                </div>
            `;
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Add validation for walk-in booking form
        document.addEventListener('DOMContentLoaded', function() {
            const checkInInput = document.getElementById('check_in');
            const checkOutInput = document.getElementById('check_out');
            
            if (checkInInput && checkOutInput) {
                // Update checkout min date when check-in changes
                checkInInput.addEventListener('change', function() {
                    const checkInDate = new Date(this.value);
                    checkInDate.setDate(checkInDate.getDate() + 1);
                    const minCheckOut = checkInDate.toISOString().split('T')[0];
                    checkOutInput.min = minCheckOut;
                    
                    // Clear checkout if it's now invalid
                    if (checkOutInput.value && checkOutInput.value <= this.value) {
                        checkOutInput.value = minCheckOut;
                    }
                });
                
                // Validate form on submit
                const walkInForm = document.querySelector('.walk-in-form');
                if (walkInForm) {
                    walkInForm.addEventListener('submit', function(e) {
                        const checkIn = new Date(checkInInput.value);
                        const checkOut = new Date(checkOutInput.value);
                        
                        if (checkOut <= checkIn) {
                            e.preventDefault();
                            alert('Check-out date must be after check-in date');
                            return false;
                        }
                        
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        
                        if (checkIn < today) {
                            e.preventDefault();
                            alert('Check-in date cannot be in the past');
                            return false;
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>