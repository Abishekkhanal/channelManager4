<?php
require_once '../config/database.php';
requireAdminLogin();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_room'])) {
        // Add new room
        $room_name = sanitizeInput($_POST['room_name']);
        $room_type_id = intval($_POST['room_type_id']);
        $description = sanitizeInput($_POST['description']);
        $price_per_night = floatval($_POST['price_per_night']);
        $max_occupancy = intval($_POST['max_occupancy']);
        $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
        $cancellation_policy = sanitizeInput($_POST['cancellation_policy']);
        
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("INSERT INTO rooms (room_name, room_type_id, description, price_per_night, max_occupancy, amenities, cancellation_policy) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$room_name, $room_type_id, $description, $price_per_night, $max_occupancy, $amenities, $cancellation_policy]);
            
            $room_id = $pdo->lastInsertId();
            
            // Handle image uploads
            if (isset($_FILES['room_images']) && !empty($_FILES['room_images']['name'][0])) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $is_primary = true;
                foreach ($_FILES['room_images']['name'] as $key => $filename) {
                    if (!empty($filename)) {
                        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
                        $new_filename = 'room_' . $room_id . '_' . time() . '_' . $key . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['room_images']['tmp_name'][$key], $upload_path)) {
                            $stmt = $pdo->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");
                            $stmt->execute([$room_id, 'uploads/' . $new_filename, $is_primary]);
                            $is_primary = false; // Only first image is primary
                        }
                    }
                }
            }
            
            $success_message = "Room added successfully!";
        } catch(PDOException $e) {
            $error_message = "Error adding room: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit_room'])) {
        // Edit existing room
        $room_id = intval($_POST['room_id']);
        $room_name = sanitizeInput($_POST['room_name']);
        $room_type_id = intval($_POST['room_type_id']);
        $description = sanitizeInput($_POST['description']);
        $price_per_night = floatval($_POST['price_per_night']);
        $max_occupancy = intval($_POST['max_occupancy']);
        $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
        $cancellation_policy = sanitizeInput($_POST['cancellation_policy']);
        
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE rooms SET room_name = ?, room_type_id = ?, description = ?, price_per_night = ?, max_occupancy = ?, amenities = ?, cancellation_policy = ? WHERE id = ?");
            $stmt->execute([$room_name, $room_type_id, $description, $price_per_night, $max_occupancy, $amenities, $cancellation_policy, $room_id]);
            
            // Handle new image uploads
            if (isset($_FILES['room_images']) && !empty($_FILES['room_images']['name'][0])) {
                $upload_dir = '../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                foreach ($_FILES['room_images']['name'] as $key => $filename) {
                    if (!empty($filename)) {
                        $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
                        $new_filename = 'room_' . $room_id . '_' . time() . '_' . $key . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['room_images']['tmp_name'][$key], $upload_path)) {
                            $stmt = $pdo->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");
                            $stmt->execute([$room_id, 'uploads/' . $new_filename, false]);
                        }
                    }
                }
            }
            
            $success_message = "Room updated successfully!";
        } catch(PDOException $e) {
            $error_message = "Error updating room: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_room'])) {
        // Delete room
        $room_id = intval($_POST['room_id']);
        
        try {
            $pdo = getConnection();
            
            // Get images to delete files
            $stmt = $pdo->prepare("SELECT image_path FROM room_images WHERE room_id = ?");
            $stmt->execute([$room_id]);
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Delete room (images will be deleted by foreign key cascade)
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            
            // Delete image files
            foreach ($images as $image) {
                if (file_exists('../' . $image['image_path'])) {
                    unlink('../' . $image['image_path']);
                }
            }
            
            $success_message = "Room deleted successfully!";
        } catch(PDOException $e) {
            $error_message = "Error deleting room: " . $e->getMessage();
        }
    }
}

// Get all rooms with their types and images
try {
    $pdo = getConnection();
    $stmt = $pdo->query("SELECT r.*, rt.name as room_type_name, ri.image_path as primary_image 
                        FROM rooms r 
                        LEFT JOIN room_types rt ON r.room_type_id = rt.id 
                        LEFT JOIN room_images ri ON r.id = ri.room_id AND ri.is_primary = 1 
                        ORDER BY r.created_at DESC");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get room types for dropdown
    $stmt = $pdo->query("SELECT * FROM room_types ORDER BY name");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $rooms = [];
    $room_types = [];
}

// Available amenities
$available_amenities = ['Wi-Fi', 'AC', 'TV', 'Mini Bar', 'Balcony', 'Jacuzzi', 'Butler Service', 'Connecting Rooms', 'Work Desk', 'Safe', 'Hair Dryer', 'Bathrobe', 'Slippers'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms Management - Grand Hotel</title>
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

        .form-container {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
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

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.5rem;
        }

        .amenity-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .amenity-item input {
            width: auto;
        }

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .room-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            overflow: hidden;
        }

        .room-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .room-content {
            padding: 1.5rem;
        }

        .room-title {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .room-type {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .room-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .room-description {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .room-amenities {
            margin-bottom: 1rem;
        }

        .room-amenities h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .amenity-tag {
            display: inline-block;
            background: #ecf0f1;
            color: #2c3e50;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            margin: 0.25rem;
        }

        .room-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
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
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
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

        .file-upload {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .file-upload:hover {
            border-color: #3498db;
        }

        .file-upload input {
            display: none;
        }

        @media (max-width: 768px) {
            .admin-menu {
                display: none;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .rooms-grid {
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
                <li><a href="rooms.php" class="active">Rooms</a></li>
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 class="page-title">Rooms Management</h1>
            <button class="btn btn-success" onclick="openModal('addRoomModal')">Add New Room</button>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="rooms-grid">
            <?php foreach ($rooms as $room): ?>
                <div class="room-card">
                    <div class="room-image">
                        <?php if ($room['primary_image']): ?>
                            <img src="../<?php echo htmlspecialchars($room['primary_image']); ?>" alt="<?php echo htmlspecialchars($room['room_name']); ?>">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </div>
                    <div class="room-content">
                        <h3 class="room-title"><?php echo htmlspecialchars($room['room_name']); ?></h3>
                        <div class="room-type"><?php echo htmlspecialchars($room['room_type_name']); ?></div>
                        <div class="room-price"><?php echo formatCurrency($room['price_per_night']); ?>/night</div>
                        <div class="room-description"><?php echo htmlspecialchars(substr($room['description'], 0, 100)); ?>...</div>
                        
                        <div class="room-amenities">
                            <h4>Amenities:</h4>
                            <?php 
                            $amenities = explode(',', $room['amenities']);
                            foreach ($amenities as $amenity): 
                                if (trim($amenity)):
                            ?>
                                <span class="amenity-tag"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <strong>Max Occupancy:</strong> <?php echo $room['max_occupancy']; ?> guests
                        </div>
                        
                        <div class="room-actions">
                            <button class="btn btn-warning btn-sm" onclick="editRoom(<?php echo $room['id']; ?>)">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteRoom(<?php echo $room['id']; ?>)">Delete</button>
                            <a href="sync_ota.php?room_id=<?php echo $room['id']; ?>" class="btn btn-sm" style="background: #9b59b6;">Sync to OTA</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addRoomModal')">&times;</span>
            <h2>Add New Room</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="room_name">Room Name</label>
                        <input type="text" id="room_name" name="room_name" required>
                    </div>
                    <div class="form-group">
                        <label for="room_type_id">Room Type</label>
                        <select id="room_type_id" name="room_type_id" required>
                            <option value="">Select Type</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price_per_night">Price per Night ($)</label>
                        <input type="number" id="price_per_night" name="price_per_night" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="max_occupancy">Max Occupancy</label>
                        <input type="number" id="max_occupancy" name="max_occupancy" min="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Amenities</label>
                    <div class="amenities-grid">
                        <?php foreach ($available_amenities as $amenity): ?>
                            <div class="amenity-item">
                                <input type="checkbox" id="amenity_<?php echo str_replace(' ', '_', $amenity); ?>" name="amenities[]" value="<?php echo htmlspecialchars($amenity); ?>">
                                <label for="amenity_<?php echo str_replace(' ', '_', $amenity); ?>"><?php echo htmlspecialchars($amenity); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="cancellation_policy">Cancellation Policy</label>
                    <textarea id="cancellation_policy" name="cancellation_policy" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Room Images</label>
                    <div class="file-upload" onclick="document.getElementById('room_images').click()">
                        <input type="file" id="room_images" name="room_images[]" multiple accept="image/*">
                        <p>Click to select images or drag and drop</p>
                    </div>
                </div>
                
                <button type="submit" name="add_room" class="btn btn-success">Add Room</button>
            </form>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div id="editRoomModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editRoomModal')">&times;</span>
            <h2>Edit Room</h2>
            <form method="POST" enctype="multipart/form-data" id="editRoomForm">
                <input type="hidden" id="edit_room_id" name="room_id">
                <!-- Form fields will be populated by JavaScript -->
                <div id="editRoomFields"></div>
                <button type="submit" name="edit_room" class="btn btn-warning">Update Room</button>
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

        function editRoom(roomId) {
            // This would normally fetch room data via AJAX
            // For now, we'll redirect to a simple edit form
            window.location.href = `edit_room.php?id=${roomId}`;
        }

        function deleteRoom(roomId) {
            if (confirm('Are you sure you want to delete this room? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="room_id" value="${roomId}">
                    <input type="hidden" name="delete_room" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // File upload preview
        document.getElementById('room_images').addEventListener('change', function(e) {
            const files = e.target.files;
            const fileUpload = document.querySelector('.file-upload p');
            if (files.length > 0) {
                fileUpload.textContent = `${files.length} file(s) selected`;
            } else {
                fileUpload.textContent = 'Click to select images or drag and drop';
            }
        });
    </script>
</body>
</html>