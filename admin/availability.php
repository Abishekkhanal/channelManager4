<?php
require_once '../config/database.php';
requireAdminLogin();

$success_message = '';
$error_message = '';

// Handle availability updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_availability'])) {
        $room_id = intval($_POST['room_id']);
        $date = sanitizeInput($_POST['date']);
        $available_count = intval($_POST['available_count']);
        
        try {
            $pdo = getConnection();
            
            // Check if availability record exists
            $stmt = $pdo->prepare("SELECT id FROM room_availability WHERE room_id = ? AND date = ?");
            $stmt->execute([$room_id, $date]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE room_availability SET available_count = ? WHERE room_id = ? AND date = ?");
                $stmt->execute([$available_count, $room_id, $date]);
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO room_availability (room_id, date, available_count) VALUES (?, ?, ?)");
                $stmt->execute([$room_id, $date, $available_count]);
            }
            
            $success_message = "Availability updated successfully!";
        } catch(PDOException $e) {
            $error_message = "Error updating availability: " . $e->getMessage();
        }
    } elseif (isset($_POST['bulk_update'])) {
        $room_id = intval($_POST['bulk_room_id']);
        $start_date = sanitizeInput($_POST['start_date']);
        $end_date = sanitizeInput($_POST['end_date']);
        $available_count = intval($_POST['bulk_available_count']);
        
        try {
            $pdo = getConnection();
            $pdo->beginTransaction();
            
            // Generate dates between start and end date
            $current_date = new DateTime($start_date);
            $end_date_obj = new DateTime($end_date);
            
            while ($current_date <= $end_date_obj) {
                $date_str = $current_date->format('Y-m-d');
                
                // Check if record exists
                $stmt = $pdo->prepare("SELECT id FROM room_availability WHERE room_id = ? AND date = ?");
                $stmt->execute([$room_id, $date_str]);
                
                if ($stmt->rowCount() > 0) {
                    // Update existing record
                    $stmt = $pdo->prepare("UPDATE room_availability SET available_count = ? WHERE room_id = ? AND date = ?");
                    $stmt->execute([$available_count, $room_id, $date_str]);
                } else {
                    // Insert new record
                    $stmt = $pdo->prepare("INSERT INTO room_availability (room_id, date, available_count) VALUES (?, ?, ?)");
                    $stmt->execute([$room_id, $date_str, $available_count]);
                }
                
                $current_date->add(new DateInterval('P1D'));
            }
            
            $pdo->commit();
            $success_message = "Bulk availability updated successfully!";
        } catch(Exception $e) {
            $pdo->rollback();
            $error_message = "Error updating bulk availability: " . $e->getMessage();
        }
    }
}

// Get selected date range for display
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d', strtotime('+30 days'));

try {
    $pdo = getConnection();
    
    // Get all rooms
    $stmt = $pdo->query("SELECT r.*, rt.name as room_type_name FROM rooms r LEFT JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_name");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get availability data for the date range
    $stmt = $pdo->prepare("SELECT ra.*, r.room_name FROM room_availability ra 
                          LEFT JOIN rooms r ON ra.room_id = r.id 
                          WHERE ra.date BETWEEN ? AND ? 
                          ORDER BY ra.date, r.room_name");
    $stmt->execute([$start_date, $end_date]);
    $availability_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get bookings for the date range to show occupied rooms
    $stmt = $pdo->prepare("SELECT b.*, r.room_name FROM bookings b 
                          LEFT JOIN rooms r ON b.room_id = r.id 
                          WHERE b.status = 'confirmed' 
                          AND ((b.check_in BETWEEN ? AND ?) OR (b.check_out BETWEEN ? AND ?) OR (b.check_in <= ? AND b.check_out >= ?))
                          ORDER BY b.check_in");
    $stmt->execute([$start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $rooms = [];
    $availability_data = [];
    $bookings = [];
}

// Generate date range for display
$dates = [];
$current = new DateTime($start_date);
$end = new DateTime($end_date);

while ($current <= $end) {
    $dates[] = $current->format('Y-m-d');
    $current->add(new DateInterval('P1D'));
}

// Organize availability data by room and date
$availability_matrix = [];
foreach ($availability_data as $avail) {
    $availability_matrix[$avail['room_id']][$avail['date']] = $avail['available_count'];
}

// Organize bookings by room and date
$booking_matrix = [];
foreach ($bookings as $booking) {
    $check_in = new DateTime($booking['check_in']);
    $check_out = new DateTime($booking['check_out']);
    
    while ($check_in < $check_out) {
        $date_str = $check_in->format('Y-m-d');
        if (!isset($booking_matrix[$booking['room_id']][$date_str])) {
            $booking_matrix[$booking['room_id']][$date_str] = 0;
        }
        $booking_matrix[$booking['room_id']][$date_str]++;
        $check_in->add(new DateInterval('P1D'));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Availability Management - Grand Hotel</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 20px;
        }

        .page-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: #2c3e50;
        }

        .controls {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
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

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
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

        .availability-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            text-align: center;
            border-bottom: 1px solid #eee;
            border-right: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table th:first-child {
            position: sticky;
            left: 0;
            z-index: 11;
            background: #2c3e50;
            color: white;
        }

        .table td:first-child {
            position: sticky;
            left: 0;
            background: #f8f9fa;
            font-weight: bold;
            text-align: left;
            padding-left: 1rem;
        }

        .availability-cell {
            position: relative;
            min-width: 80px;
        }

        .availability-input {
            width: 60px;
            padding: 0.25rem;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-align: center;
            font-size: 0.9rem;
        }

        .availability-status {
            padding: 0.25rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-limited {
            background: #fff3cd;
            color: #856404;
        }

        .status-full {
            background: #f8d7da;
            color: #721c24;
        }

        .booking-indicator {
            font-size: 0.7rem;
            color: #666;
            margin-top: 0.25rem;
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

        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .admin-menu {
                display: none;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
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
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="availability.php" class="active">Availability</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
            <div class="admin-user">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="../logout.php" class="btn btn-sm">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1 class="page-title">Availability Management</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="controls">
            <div class="quick-actions">
                <button class="btn btn-success" onclick="openModal('bulkUpdateModal')">Bulk Update</button>
                <button class="btn btn-warning" onclick="openModal('quickUpdateModal')">Quick Update</button>
                <a href="sync_ota.php" class="btn" style="background: #9b59b6;">Sync to OTA</a>
            </div>
            
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn">Update View</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="availability-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <?php foreach ($dates as $date): ?>
                            <th>
                                <?php echo date('M d', strtotime($date)); ?>
                                <br>
                                <small><?php echo date('D', strtotime($date)); ?></small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($room['room_name']); ?></strong>
                                <br>
                                <small><?php echo htmlspecialchars($room['room_type_name']); ?></small>
                            </td>
                            <?php foreach ($dates as $date): ?>
                                <td class="availability-cell">
                                    <?php
                                    $available = isset($availability_matrix[$room['id']][$date]) ? $availability_matrix[$room['id']][$date] : 1;
                                    $booked = isset($booking_matrix[$room['id']][$date]) ? $booking_matrix[$room['id']][$date] : 0;
                                    $remaining = $available - $booked;
                                    
                                    $status_class = 'status-available';
                                    if ($remaining <= 0) {
                                        $status_class = 'status-full';
                                    } elseif ($remaining <= 2) {
                                        $status_class = 'status-limited';
                                    }
                                    ?>
                                    <div class="availability-status <?php echo $status_class; ?>">
                                        <?php echo $remaining; ?> / <?php echo $available; ?>
                                    </div>
                                    <?php if ($booked > 0): ?>
                                        <div class="booking-indicator">
                                            <?php echo $booked; ?> booked
                                        </div>
                                    <?php endif; ?>
                                    <button class="btn btn-sm" onclick="updateAvailability(<?php echo $room['id']; ?>, '<?php echo $date; ?>', <?php echo $available; ?>)" style="margin-top: 0.25rem;">
                                        Edit
                                    </button>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Update Availability Modal -->
    <div id="updateAvailabilityModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('updateAvailabilityModal')">&times;</span>
            <h2>Update Availability</h2>
            <form method="POST">
                <input type="hidden" id="update_room_id" name="room_id">
                <input type="hidden" id="update_date" name="date">
                
                <div class="form-group">
                    <label for="available_count">Available Count</label>
                    <input type="number" id="available_count" name="available_count" min="0" required>
                </div>
                
                <button type="submit" name="update_availability" class="btn btn-success">Update</button>
            </form>
        </div>
    </div>

    <!-- Bulk Update Modal -->
    <div id="bulkUpdateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('bulkUpdateModal')">&times;</span>
            <h2>Bulk Update Availability</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="bulk_room_id">Room</label>
                    <select id="bulk_room_id" name="bulk_room_id" required>
                        <option value="">Select Room</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>"><?php echo htmlspecialchars($room['room_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="bulk_start_date">Start Date</label>
                        <input type="date" id="bulk_start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="bulk_end_date">End Date</label>
                        <input type="date" id="bulk_end_date" name="end_date" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="bulk_available_count">Available Count</label>
                    <input type="number" id="bulk_available_count" name="bulk_available_count" min="0" required>
                </div>
                
                <button type="submit" name="bulk_update" class="btn btn-success">Bulk Update</button>
            </form>
        </div>
    </div>

    <!-- Quick Update Modal -->
    <div id="quickUpdateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('quickUpdateModal')">&times;</span>
            <h2>Quick Update</h2>
            <p>Select a date range and availability count to apply to all rooms:</p>
            <form method="POST" id="quickUpdateForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="quick_start_date">Start Date</label>
                        <input type="date" id="quick_start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="quick_end_date">End Date</label>
                        <input type="date" id="quick_end_date" name="end_date" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="quick_available_count">Available Count</label>
                    <input type="number" id="quick_available_count" name="bulk_available_count" min="0" required>
                </div>
                
                <button type="button" onclick="quickUpdateAll()" class="btn btn-success">Apply to All Rooms</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function updateAvailability(roomId, date, currentCount) {
            document.getElementById('update_room_id').value = roomId;
            document.getElementById('update_date').value = date;
            document.getElementById('available_count').value = currentCount;
            openModal('updateAvailabilityModal');
        }

        function quickUpdateAll() {
            const startDate = document.getElementById('quick_start_date').value;
            const endDate = document.getElementById('quick_end_date').value;
            const availableCount = document.getElementById('quick_available_count').value;
            
            if (!startDate || !endDate || !availableCount) {
                alert('Please fill in all fields');
                return;
            }
            
            if (confirm('This will update availability for ALL rooms in the selected date range. Are you sure?')) {
                const rooms = <?php echo json_encode($rooms); ?>;
                
                // Create a form for each room and submit them
                rooms.forEach(room => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';
                    
                    form.innerHTML = `
                        <input name="bulk_room_id" value="${room.id}">
                        <input name="start_date" value="${startDate}">
                        <input name="end_date" value="${endDate}">
                        <input name="bulk_available_count" value="${availableCount}">
                        <input name="bulk_update" value="1">
                    `;
                    
                    document.body.appendChild(form);
                    form.submit();
                });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Set default dates for bulk update
        document.getElementById('bulk_start_date').value = '<?php echo $start_date; ?>';
        document.getElementById('bulk_end_date').value = '<?php echo $end_date; ?>';
        document.getElementById('quick_start_date').value = '<?php echo $start_date; ?>';
        document.getElementById('quick_end_date').value = '<?php echo $end_date; ?>';
    </script>
</body>
</html>