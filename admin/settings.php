<?php
require_once '../config/database.php';
requireAdminLogin();

$success_message = '';
$error_message = '';

// Handle OTA settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_ota_settings'])) {
        $ota_name = sanitizeInput($_POST['ota_name']);
        $api_key = sanitizeInput($_POST['api_key']);
        $username = sanitizeInput($_POST['username']);
        $password = sanitizeInput($_POST['password']);
        $endpoint_url = sanitizeInput($_POST['endpoint_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $pdo = getConnection();
            
            // Check if OTA setting already exists
            $stmt = $pdo->prepare("SELECT id FROM ota_settings WHERE ota_name = ?");
            $stmt->execute([$ota_name]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing setting
                $stmt = $pdo->prepare("UPDATE ota_settings SET api_key = ?, username = ?, password = ?, endpoint_url = ?, is_active = ? WHERE ota_name = ?");
                $stmt->execute([$api_key, $username, $password, $endpoint_url, $is_active, $ota_name]);
            } else {
                // Insert new setting
                $stmt = $pdo->prepare("INSERT INTO ota_settings (ota_name, api_key, username, password, endpoint_url, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ota_name, $api_key, $username, $password, $endpoint_url, $is_active]);
            }
            
            $success_message = "OTA settings saved successfully!";
        } catch(PDOException $e) {
            $error_message = "Error saving OTA settings: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_ota_setting'])) {
        $ota_id = intval($_POST['ota_id']);
        
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("DELETE FROM ota_settings WHERE id = ?");
            $stmt->execute([$ota_id]);
            
            $success_message = "OTA setting deleted successfully!";
        } catch(PDOException $e) {
            $error_message = "Error deleting OTA setting: " . $e->getMessage();
        }
    }
}

// Get all OTA settings
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT * FROM ota_settings ORDER BY ota_name");
    $ota_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $ota_settings = [];
}

// Common OTA providers
$ota_providers = [
    'Booking.com' => 'https://distribution-xml.booking.com/json/bookings',
    'Expedia' => 'https://services.expediapartnercentral.com/eqc/ar',
    'Agoda' => 'https://xmlapi.agoda.com/api/ar',
    'Airbnb' => 'https://api.airbnb.com/v2/calendar',
    'Hotels.com' => 'https://api.ean.com/ean-services/rs/hotel/v3',
    'Custom' => ''
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTA Settings - Grand Hotel</title>
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

        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
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

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input {
            width: auto;
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

        .ota-list {
            margin-top: 2rem;
        }

        .ota-item {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .ota-info h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .ota-info p {
            color: #666;
            font-size: 0.9rem;
        }

        .ota-actions {
            display: flex;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }

        .connection-test {
            margin-top: 1rem;
            padding: 1rem;
            background: #e8f4f8;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }

        .connection-test h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .test-result {
            margin-top: 0.5rem;
            padding: 0.5rem;
            border-radius: 3px;
            font-size: 0.9rem;
        }

        .test-success {
            background: #d4edda;
            color: #155724;
        }

        .test-error {
            background: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .admin-menu {
                display: none;
            }
            
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .ota-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
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
                <li><a href="settings.php" class="active">Settings</a></li>
            </ul>
            <div class="admin-user">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="../logout.php" class="btn btn-sm">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1 class="page-title">OTA Settings</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            <div class="settings-card">
                <h2 class="card-title">Add/Update OTA Configuration</h2>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="ota_name">OTA Provider</label>
                        <select id="ota_name" name="ota_name" required onchange="updateEndpointUrl()">
                            <option value="">Select OTA Provider</option>
                            <?php foreach ($ota_providers as $name => $url): ?>
                                <option value="<?php echo htmlspecialchars($name); ?>" data-url="<?php echo htmlspecialchars($url); ?>">
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="api_key">API Key</label>
                        <input type="text" id="api_key" name="api_key" required>
                        <div class="help-text">Enter your OTA API key or access token</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="endpoint_url">Endpoint URL</label>
                        <input type="url" id="endpoint_url" name="endpoint_url" required>
                        <div class="help-text">API endpoint URL for the OTA provider</div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <label for="is_active">Active</label>
                        </div>
                    </div>

                    <button type="submit" name="save_ota_settings" class="btn btn-success">Save Configuration</button>
                    <button type="button" onclick="testConnection()" class="btn btn-warning">Test Connection</button>
                </form>

                <div class="connection-test" id="connectionTest" style="display: none;">
                    <h4>Connection Test</h4>
                    <div id="testResult"></div>
                </div>
            </div>

            <div class="settings-card">
                <h2 class="card-title">Current OTA Configurations</h2>
                
                <?php if (empty($ota_settings)): ?>
                    <p>No OTA configurations found. Add your first OTA connection to get started.</p>
                <?php else: ?>
                    <div class="ota-list">
                        <?php foreach ($ota_settings as $ota): ?>
                            <div class="ota-item">
                                <div class="ota-info">
                                    <h4><?php echo htmlspecialchars($ota['ota_name']); ?></h4>
                                    <p><strong>Endpoint:</strong> <?php echo htmlspecialchars($ota['endpoint_url']); ?></p>
                                    <p><strong>Username:</strong> <?php echo htmlspecialchars($ota['username'] ?: 'Not set'); ?></p>
                                    <p><strong>Last Updated:</strong> <?php echo formatDate($ota['updated_at']); ?></p>
                                    <span class="status-badge <?php echo $ota['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $ota['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <div class="ota-actions">
                                    <button class="btn btn-sm" onclick="editOTA(<?php echo htmlspecialchars(json_encode($ota)); ?>)">Edit</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteOTA(<?php echo $ota['id']; ?>)">Delete</button>
                                    <button class="btn btn-warning btn-sm" onclick="testOTAConnection(<?php echo $ota['id']; ?>)">Test</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 2rem; padding: 1rem; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                    <h4 style="color: #856404; margin-bottom: 0.5rem;">Integration Guide</h4>
                    <p style="color: #856404; font-size: 0.9rem;">
                        To integrate with OTA providers, you'll need to obtain API credentials from each platform:
                    </p>
                    <ul style="color: #856404; font-size: 0.9rem; margin-top: 0.5rem; padding-left: 1rem;">
                        <li><strong>Booking.com:</strong> Register as a partner and get XML API access</li>
                        <li><strong>Expedia:</strong> Apply for EPC (Expedia Partner Central) access</li>
                        <li><strong>Agoda:</strong> Contact Agoda Partner Services for API access</li>
                        <li><strong>Airbnb:</strong> Use Airbnb API through their partner program</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateEndpointUrl() {
            const select = document.getElementById('ota_name');
            const urlInput = document.getElementById('endpoint_url');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption && selectedOption.dataset.url) {
                urlInput.value = selectedOption.dataset.url;
            } else {
                urlInput.value = '';
            }
        }

        function editOTA(ota) {
            document.getElementById('ota_name').value = ota.ota_name;
            document.getElementById('api_key').value = ota.api_key;
            document.getElementById('username').value = ota.username || '';
            document.getElementById('password').value = ota.password || '';
            document.getElementById('endpoint_url').value = ota.endpoint_url;
            document.getElementById('is_active').checked = ota.is_active == 1;
        }

        function deleteOTA(otaId) {
            if (confirm('Are you sure you want to delete this OTA configuration? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="ota_id" value="${otaId}">
                    <input type="hidden" name="delete_ota_setting" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function testConnection() {
            const otaName = document.getElementById('ota_name').value;
            const apiKey = document.getElementById('api_key').value;
            const endpointUrl = document.getElementById('endpoint_url').value;
            
            if (!otaName || !apiKey || !endpointUrl) {
                alert('Please fill in all required fields before testing connection.');
                return;
            }
            
            const testDiv = document.getElementById('connectionTest');
            const resultDiv = document.getElementById('testResult');
            
            testDiv.style.display = 'block';
            resultDiv.innerHTML = '<p>Testing connection...</p>';
            
            // Simulate connection test (in real implementation, this would make an AJAX call)
            setTimeout(() => {
                // This is a mock test - in real implementation, you'd make an actual API call
                const isSuccess = Math.random() > 0.3; // 70% success rate for demo
                
                if (isSuccess) {
                    resultDiv.innerHTML = '<div class="test-result test-success">✓ Connection successful! API is responding correctly.</div>';
                } else {
                    resultDiv.innerHTML = '<div class="test-result test-error">✗ Connection failed. Please check your credentials and endpoint URL.</div>';
                }
            }, 2000);
        }

        function testOTAConnection(otaId) {
            // In real implementation, this would test the specific OTA connection
            alert('Testing connection for OTA ID: ' + otaId + '\n\nThis would make an actual API call to test the connection.');
        }

        // Clear form function
        function clearForm() {
            document.getElementById('ota_name').value = '';
            document.getElementById('api_key').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('endpoint_url').value = '';
            document.getElementById('is_active').checked = true;
            document.getElementById('connectionTest').style.display = 'none';
        }

        // Add clear button functionality
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'btn';
            clearBtn.textContent = 'Clear Form';
            clearBtn.onclick = clearForm;
            clearBtn.style.marginLeft = '0.5rem';
            
            form.appendChild(clearBtn);
        });
    </script>
</body>
</html>