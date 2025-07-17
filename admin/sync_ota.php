<?php
require_once '../config/database.php';
requireAdminLogin();

$success_message = '';
$error_message = '';
$sync_results = [];

// Function to format data for different OTA providers
function formatDataForOTA($ota_name, $rooms_data) {
    switch (strtolower($ota_name)) {
        case 'booking.com':
            return formatForBookingCom($rooms_data);
        case 'expedia':
            return formatForExpedia($rooms_data);
        case 'agoda':
            return formatForAgoda($rooms_data);
        case 'airbnb':
            return formatForAirbnb($rooms_data);
        default:
            return formatGeneric($rooms_data);
    }
}

// Format for Booking.com XML API
function formatForBookingCom($rooms_data) {
    $xml = new SimpleXMLElement('<request></request>');
    $xml->addAttribute('version', '1.0');
    $xml->addChild('username', 'your_username');
    $xml->addChild('password', 'your_password');
    
    foreach ($rooms_data as $room) {
        $room_node = $xml->addChild('room');
        $room_node->addChild('id', $room['id']);
        $room_node->addChild('name', htmlspecialchars($room['room_name']));
        $room_node->addChild('rate', $room['price_per_night']);
        $room_node->addChild('availability', $room['available_count']);
        $room_node->addChild('date', $room['date']);
    }
    
    return $xml->asXML();
}

// Format for Expedia EQC API
function formatForExpedia($rooms_data) {
    $data = [
        'Entity' => [
            'resourceId' => 'your_resource_id',
            'RoomType' => []
        ]
    ];
    
    foreach ($rooms_data as $room) {
        $data['Entity']['RoomType'][] = [
            'id' => $room['id'],
            'name' => $room['room_name'],
            'Inventory' => [
                'date' => $room['date'],
                'totalInventoryAvailable' => $room['available_count']
            ],
            'Rate' => [
                'date' => $room['date'],
                'currency' => 'USD',
                'amount' => $room['price_per_night']
            ]
        ];
    }
    
    return json_encode($data);
}

// Format for Agoda XML API
function formatForAgoda($rooms_data) {
    $xml = new SimpleXMLElement('<SetInventoryRequest></SetInventoryRequest>');
    $xml->addAttribute('xmlns', 'http://www.agoda.com/xmlapi');
    
    $auth = $xml->addChild('Authentication');
    $auth->addChild('APIKey', 'your_api_key');
    
    foreach ($rooms_data as $room) {
        $inventory = $xml->addChild('Inventory');
        $inventory->addChild('PropertyID', 'your_property_id');
        $inventory->addChild('RoomTypeID', $room['id']);
        $inventory->addChild('Date', $room['date']);
        $inventory->addChild('Inventory', $room['available_count']);
        $inventory->addChild('Rate', $room['price_per_night']);
    }
    
    return $xml->asXML();
}

// Format for Airbnb API
function formatForAirbnb($rooms_data) {
    $data = [
        'listing_id' => 'your_listing_id',
        'availability' => []
    ];
    
    foreach ($rooms_data as $room) {
        $data['availability'][] = [
            'date' => $room['date'],
            'available' => $room['available_count'] > 0,
            'price' => [
                'amount' => $room['price_per_night'],
                'currency' => 'USD'
            ]
        ];
    }
    
    return json_encode($data);
}

// Generic format
function formatGeneric($rooms_data) {
    return json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'rooms' => $rooms_data
    ]);
}

// Function to send data to OTA
function sendToOTA($ota_setting, $formatted_data) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'X-API-Key: ' . $ota_setting['api_key']
    ];
    
    // Add authentication headers based on OTA
    if ($ota_setting['username'] && $ota_setting['password']) {
        $headers[] = 'Authorization: Basic ' . base64_encode($ota_setting['username'] . ':' . $ota_setting['password']);
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $ota_setting['endpoint_url'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $formatted_data,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false // For development - enable in production
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'success' => $http_code >= 200 && $http_code < 300,
        'http_code' => $http_code,
        'response' => $response,
        'error' => $error
    ];
}

// Handle sync request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_ota'])) {
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : null;
    $start_date = sanitizeInput($_POST['start_date']);
    $end_date = sanitizeInput($_POST['end_date']);
    
    try {
        $pdo = getConnection();
        
        // Get active OTA settings
        $stmt = $pdo->query("SELECT * FROM ota_settings WHERE is_active = 1");
        $ota_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($ota_settings)) {
            $error_message = "No active OTA configurations found.";
        } else {
            // Build query for rooms and availability
            $where_clause = '';
            $params = [$start_date, $end_date];
            
            if ($room_id) {
                $where_clause = ' AND r.id = ?';
                $params[] = $room_id;
            }
            
            // Get rooms data with availability
            $query = "SELECT r.id, r.room_name, r.price_per_night, 
                            COALESCE(ra.available_count, 1) as available_count,
                            COALESCE(ra.date, ?) as date
                     FROM rooms r
                     LEFT JOIN room_availability ra ON r.id = ra.room_id 
                     WHERE (ra.date BETWEEN ? AND ? OR ra.date IS NULL)
                     $where_clause
                     ORDER BY r.id, ra.date";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute(array_merge([$start_date], $params));
            $rooms_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate date range if no availability data exists
            if (empty($rooms_data)) {
                $stmt = $pdo->prepare("SELECT * FROM rooms" . ($room_id ? " WHERE id = ?" : ""));
                $stmt->execute($room_id ? [$room_id] : []);
                $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $rooms_data = [];
                foreach ($rooms as $room) {
                    $current_date = new DateTime($start_date);
                    $end_date_obj = new DateTime($end_date);
                    
                    while ($current_date <= $end_date_obj) {
                        $rooms_data[] = [
                            'id' => $room['id'],
                            'room_name' => $room['room_name'],
                            'price_per_night' => $room['price_per_night'],
                            'available_count' => 1,
                            'date' => $current_date->format('Y-m-d')
                        ];
                        $current_date->add(new DateInterval('P1D'));
                    }
                }
            }
            
            // Sync to each OTA
            foreach ($ota_settings as $ota_setting) {
                $formatted_data = formatDataForOTA($ota_setting['ota_name'], $rooms_data);
                $result = sendToOTA($ota_setting, $formatted_data);
                
                $sync_results[] = [
                    'ota_name' => $ota_setting['ota_name'],
                    'success' => $result['success'],
                    'http_code' => $result['http_code'],
                    'response' => $result['response'],
                    'error' => $result['error']
                ];
            }
            
            $success_message = "Sync completed for " . count($ota_settings) . " OTA provider(s).";
        }
        
    } catch(PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    } catch(Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get rooms for dropdown
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT id, room_name FROM rooms ORDER BY room_name");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get OTA settings
    $stmt = $pdo->query("SELECT * FROM ota_settings ORDER BY ota_name");
    $ota_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $rooms = [];
    $ota_settings = [];
}

// Handle individual room sync from URL parameter
$selected_room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTA Sync - Grand Hotel</title>
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

        .admin-menu a:hover {
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

        .sync-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .sync-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #9b59b6;
            padding-bottom: 0.5rem;
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
            border-color: #9b59b6;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #9b59b6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 0.9rem;
        }

        .btn:hover {
            background: #8e44ad;
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

        .sync-results {
            margin-top: 2rem;
        }

        .result-item {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .result-ota {
            font-weight: bold;
            color: #2c3e50;
        }

        .result-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
        }

        .result-details {
            font-size: 0.9rem;
            color: #666;
        }

        .ota-status {
            margin-bottom: 2rem;
        }

        .ota-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .ota-item {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 1rem;
            text-align: center;
        }

        .ota-item h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .ota-active {
            border-color: #27ae60;
            background: #d4edda;
        }

        .ota-inactive {
            border-color: #e74c3c;
            background: #f8d7da;
        }

        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .sync-info {
            background: #e8f4f8;
            border: 1px solid #3498db;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .sync-info h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .admin-menu {
                display: none;
            }
            
            .sync-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
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
        <h1 class="page-title">OTA Synchronization</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="sync-info">
            <h3>About OTA Sync</h3>
            <p>This tool synchronizes your room rates, availability, and inventory with connected OTA (Online Travel Agency) providers. The sync will send current pricing and availability data to all active OTA connections.</p>
        </div>

        <div class="sync-container">
            <div class="sync-card">
                <h2 class="card-title">Sync Configuration</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="room_id">Room (Optional)</label>
                        <select id="room_id" name="room_id">
                            <option value="">All Rooms</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>" <?php echo $selected_room_id == $room['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($room['room_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">Select a specific room or leave blank to sync all rooms</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                    </div>

                    <button type="submit" name="sync_ota" class="btn">Sync to OTA Providers</button>
                </form>

                <?php if (!empty($sync_results)): ?>
                    <div class="sync-results">
                        <h3>Sync Results</h3>
                        <?php foreach ($sync_results as $result): ?>
                            <div class="result-item">
                                <div class="result-header">
                                    <span class="result-ota"><?php echo htmlspecialchars($result['ota_name']); ?></span>
                                    <span class="result-status <?php echo $result['success'] ? 'status-success' : 'status-error'; ?>">
                                        <?php echo $result['success'] ? 'Success' : 'Failed'; ?>
                                    </span>
                                </div>
                                <div class="result-details">
                                    <p><strong>HTTP Code:</strong> <?php echo $result['http_code']; ?></p>
                                    <?php if ($result['error']): ?>
                                        <p><strong>Error:</strong> <?php echo htmlspecialchars($result['error']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($result['response'] && strlen($result['response']) < 200): ?>
                                        <p><strong>Response:</strong> <?php echo htmlspecialchars($result['response']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="sync-card">
                <h2 class="card-title">OTA Connections</h2>
                
                <div class="ota-status">
                    <?php if (empty($ota_settings)): ?>
                        <p>No OTA connections configured. <a href="settings.php">Add OTA settings</a> to enable synchronization.</p>
                    <?php else: ?>
                        <div class="ota-list">
                            <?php foreach ($ota_settings as $ota): ?>
                                <div class="ota-item <?php echo $ota['is_active'] ? 'ota-active' : 'ota-inactive'; ?>">
                                    <h4><?php echo htmlspecialchars($ota['ota_name']); ?></h4>
                                    <p><?php echo $ota['is_active'] ? 'Active' : 'Inactive'; ?></p>
                                    <small><?php echo formatDate($ota['updated_at']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 1rem;">
                    <h4 style="color: #856404; margin-bottom: 0.5rem;">Sync Information</h4>
                    <ul style="color: #856404; font-size: 0.9rem; padding-left: 1rem;">
                        <li>Data synced includes room rates, availability, and inventory</li>
                        <li>Sync frequency depends on OTA requirements</li>
                        <li>Failed syncs will be logged for troubleshooting</li>
                        <li>Some OTAs may have rate limits or specific sync windows</li>
                    </ul>
                </div>

                <div style="margin-top: 1rem; text-align: center;">
                    <a href="settings.php" class="btn btn-success">Manage OTA Settings</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set minimum end date to be after start date
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const endDate = document.getElementById('end_date');
            const minEndDate = new Date(startDate);
            minEndDate.setDate(minEndDate.getDate() + 1);
            
            endDate.min = minEndDate.toISOString().split('T')[0];
            
            if (endDate.value && new Date(endDate.value) <= startDate) {
                endDate.value = minEndDate.toISOString().split('T')[0];
            }
        });

        // Auto-refresh results every 30 seconds if sync is in progress
        <?php if (!empty($sync_results)): ?>
        setTimeout(function() {
            // You could add AJAX here to refresh results
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>