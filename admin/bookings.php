<?php
require_once '../config/database.php';
requireAdminLogin();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
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
    
    // Get bookings with filters
    $query = "SELECT b.*, r.room_name, rt.name as room_type_name 
              FROM bookings b 
              LEFT JOIN rooms r ON b.room_id = r.id 
              LEFT JOIN room_types rt ON r.room_type_id = rt.id 
              $where_clause 
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
    
} catch(PDOException $e) {
    $bookings = [];
    $room_types = [];
    $booking_sources = [];
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
                            <th>Amount</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 2rem; color: #666;">
                                    No bookings found.
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

    <script>
        const bookings = <?php echo json_encode($bookings); ?>;

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
                        <p><strong>Total Amount:</strong> ${booking.total_amount ? '$' + parseFloat(booking.total_amount).toFixed(2) : 'N/A'}</p>
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

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>