<?php
require_once '../config/database.php';
requireAdminLogin();

$success_message = '';
$error_message = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $inquiry_id = intval($_POST['inquiry_id']);
    $new_status = sanitizeInput($_POST['status']);
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $inquiry_id]);
        $success_message = "Inquiry status updated successfully!";
    } catch(PDOException $e) {
        $error_message = "Error updating inquiry status.";
    }
}

// Handle creating booking from inquiry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $inquiry_id = intval($_POST['inquiry_id']);
    
    try {
        $pdo = getConnection();
        
        // Get inquiry details
        $stmt = $pdo->prepare("SELECT * FROM inquiries WHERE id = ?");
        $stmt->execute([$inquiry_id]);
        $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inquiry) {
            // Find available room
            $room_id = null;
            if ($inquiry['room_type']) {
                $stmt = $pdo->prepare("SELECT r.id FROM rooms r 
                                     JOIN room_types rt ON r.room_type_id = rt.id 
                                     WHERE rt.name = ? LIMIT 1");
                $stmt->execute([$inquiry['room_type']]);
                $room = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($room) {
                    $room_id = $room['id'];
                }
            }
            
            if (!$room_id) {
                // Get first available room
                $stmt = $pdo->query("SELECT id FROM rooms LIMIT 1");
                $room = $stmt->fetch(PDO::FETCH_ASSOC);
                $room_id = $room['id'];
            }
            
            // Calculate total amount
            $stmt = $pdo->prepare("SELECT price_per_night FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            $room_price = $stmt->fetch(PDO::FETCH_ASSOC)['price_per_night'];
            
            $check_in = new DateTime($inquiry['check_in']);
            $check_out = new DateTime($inquiry['check_out']);
            $nights = $check_in->diff($check_out)->days;
            $total_amount = $room_price * $nights;
            
            // Create booking
            $stmt = $pdo->prepare("INSERT INTO bookings (guest_name, guest_email, guest_phone, room_id, check_in, check_out, guests_count, total_amount, booking_source, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'inquiry', 'confirmed')");
            $stmt->execute([
                $inquiry['name'],
                $inquiry['email'],
                $inquiry['phone'],
                $room_id,
                $inquiry['check_in'],
                $inquiry['check_out'],
                $inquiry['guests_count'],
                $total_amount
            ]);
            
            // Update inquiry status
            $stmt = $pdo->prepare("UPDATE inquiries SET status = 'responded' WHERE id = ?");
            $stmt->execute([$inquiry_id]);
            
            $success_message = "Booking created successfully from inquiry!";
        }
    } catch(PDOException $e) {
        $error_message = "Error creating booking: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $where_conditions[] = "created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

try {
    $pdo = getConnection();
    
    // Get inquiries with filters
    $stmt = $pdo->prepare("SELECT * FROM inquiries $where_clause ORDER BY created_at DESC");
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) as responded_count,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count
        FROM inquiries");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $inquiries = [];
    $stats = ['total' => 0, 'new_count' => 0, 'responded_count' => 0, 'closed_count' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inquiries - Grand Hotel Admin</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }

        .page-title {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            color: #2c3e50;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
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
            font-size: 1rem;
        }

        .stat-card.total .stat-value { color: #3498db; }
        .stat-card.new .stat-value { color: #f39c12; }
        .stat-card.responded .stat-value { color: #27ae60; }
        .stat-card.closed .stat-value { color: #95a5a6; }

        .filters {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
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

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #2980b9;
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

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .inquiries-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
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

        .status-new { background: #fff3cd; color: #856404; }
        .status-responded { background: #d4edda; color: #155724; }
        .status-closed { background: #d1ecf1; color: #0c5460; }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }

        .modal-header {
            margin-bottom: 1rem;
        }

        .modal-title {
            font-size: 1.5rem;
            color: #2c3e50;
        }

        .close {
            float: right;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }

        .close:hover {
            color: #000;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .table {
                font-size: 0.9rem;
            }

            .table th, .table td {
                padding: 0.5rem;
            }

            .actions {
                flex-direction: column;
            }

            .admin-menu {
                display: none;
            }
        }

        @media (max-width: 480px) {
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="bookings.php">Bookings</a></li>
                <li><a href="rooms.php">Rooms</a></li>
                <li><a href="inquiries.php" class="active">Inquiries</a></li>
                <li><a href="availability.php">Availability</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
            <div>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="../logout.php" class="btn btn-sm">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1 class="page-title">Manage Inquiries</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Inquiries</div>
            </div>
            <div class="stat-card new">
                <div class="stat-value"><?php echo $stats['new_count']; ?></div>
                <div class="stat-label">New Inquiries</div>
            </div>
            <div class="stat-card responded">
                <div class="stat-value"><?php echo $stats['responded_count']; ?></div>
                <div class="stat-label">Responded</div>
            </div>
            <div class="stat-card closed">
                <div class="stat-value"><?php echo $stats['closed_count']; ?></div>
                <div class="stat-label">Closed</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="responded" <?php echo $status_filter === 'responded' ? 'selected' : ''; ?>>Responded</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_from">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_to">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">Filter</button>
                        <a href="inquiries.php" class="btn" style="background: #95a5a6;">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Inquiries Table -->
        <div class="inquiries-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>Guest Details</th>
                        <th>Stay Details</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inquiries)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">No inquiries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inquiries as $inquiry): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($inquiry['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($inquiry['email']); ?></small><br>
                                    <?php if ($inquiry['phone']): ?>
                                        <small><?php echo htmlspecialchars($inquiry['phone']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>Check-in:</strong> <?php echo formatDate($inquiry['check_in']); ?><br>
                                    <strong>Check-out:</strong> <?php echo formatDate($inquiry['check_out']); ?><br>
                                    <strong>Guests:</strong> <?php echo $inquiry['guests_count']; ?><br>
                                    <strong>Room Type:</strong> <?php echo htmlspecialchars($inquiry['room_type'] ?? 'Any'); ?>
                                </td>
                                <td>
                                    <?php if ($inquiry['message']): ?>
                                        <small><?php echo htmlspecialchars(substr($inquiry['message'], 0, 100)); ?><?php echo strlen($inquiry['message']) > 100 ? '...' : ''; ?></small>
                                    <?php else: ?>
                                        <em>No message</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $inquiry['status']; ?>">
                                        <?php echo ucfirst($inquiry['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo formatDate($inquiry['created_at']); ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button onclick="updateStatus(<?php echo $inquiry['id']; ?>, '<?php echo $inquiry['status']; ?>')" class="btn btn-sm btn-warning">Update Status</button>
                                        <?php if ($inquiry['status'] === 'new'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="inquiry_id" value="<?php echo $inquiry['id']; ?>">
                                                <button type="submit" name="create_booking" class="btn btn-sm btn-success" onclick="return confirm('Create booking from this inquiry?')">Create Booking</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2 class="modal-title">Update Inquiry Status</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="inquiry_id" id="modal_inquiry_id">
                <div class="form-group">
                    <label for="modal_status">Status</label>
                    <select name="status" id="modal_status" class="form-control" required>
                        <option value="new">New</option>
                        <option value="responded">Responded</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
                    <button type="button" onclick="closeModal()" class="btn" style="background: #95a5a6;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateStatus(inquiryId, currentStatus) {
            document.getElementById('modal_inquiry_id').value = inquiryId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('statusModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>