<?php
require_once '../config/database.php';
requireAdminLogin();

$success_message = '';
$error_message = '';
$room = null;
$room_types = [];
$room_images = [];

// Get room ID from URL
$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$room_id) {
    header('Location: rooms.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Get room types
    $stmt = $pdo->query("SELECT * FROM room_types ORDER BY name");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get room details
    $stmt = $pdo->prepare("SELECT r.*, rt.name as room_type_name FROM rooms r LEFT JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
    $stmt->execute([$room_id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        header('Location: rooms.php');
        exit();
    }
    
    // Get room images
    $stmt = $pdo->prepare("SELECT * FROM room_images WHERE room_id = ? ORDER BY is_primary DESC, id ASC");
    $stmt->execute([$room_id]);
    $room_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Error loading room data: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_room'])) {
        $room_name = sanitizeInput($_POST['room_name']);
        $room_type_id = intval($_POST['room_type_id']);
        $description = sanitizeInput($_POST['description']);
        $price_per_night = floatval($_POST['price_per_night']);
        $max_occupancy = intval($_POST['max_occupancy']);
        $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
        $cancellation_policy = sanitizeInput($_POST['cancellation_policy']);
        
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE rooms SET room_name = ?, room_type_id = ?, description = ?, price_per_night = ?, max_occupancy = ?, amenities = ?, cancellation_policy = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
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
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array(strtolower($file_extension), $allowed_extensions)) {
                            $new_filename = 'room_' . $room_id . '_' . time() . '_' . $key . '.' . $file_extension;
                            $upload_path = $upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['room_images']['tmp_name'][$key], $upload_path)) {
                                $stmt = $pdo->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");
                                $stmt->execute([$room_id, 'uploads/' . $new_filename, false]);
                            }
                        }
                    }
                }
            }
            
            $success_message = "Room updated successfully!";
            
            // Refresh room data
            $stmt = $pdo->prepare("SELECT r.*, rt.name as room_type_name FROM rooms r LEFT JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
            $stmt->execute([$room_id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT * FROM room_images WHERE room_id = ? ORDER BY is_primary DESC, id ASC");
            $stmt->execute([$room_id]);
            $room_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $error_message = "Error updating room: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_image'])) {
        $image_id = intval($_POST['image_id']);
        
        try {
            $pdo = getConnection();
            
            // Get image path before deleting
            $stmt = $pdo->prepare("SELECT image_path FROM room_images WHERE id = ? AND room_id = ?");
            $stmt->execute([$image_id, $room_id]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($image) {
                // Delete from database
                $stmt = $pdo->prepare("DELETE FROM room_images WHERE id = ? AND room_id = ?");
                $stmt->execute([$image_id, $room_id]);
                
                // Delete file
                $file_path = '../' . $image['image_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                
                $success_message = "Image deleted successfully!";
                
                // Refresh images
                $stmt = $pdo->prepare("SELECT * FROM room_images WHERE room_id = ? ORDER BY is_primary DESC, id ASC");
                $stmt->execute([$room_id]);
                $room_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch(PDOException $e) {
            $error_message = "Error deleting image: " . $e->getMessage();
        }
    } elseif (isset($_POST['set_primary'])) {
        $image_id = intval($_POST['image_id']);
        
        try {
            $pdo = getConnection();
            
            // Remove primary from all images of this room
            $stmt = $pdo->prepare("UPDATE room_images SET is_primary = FALSE WHERE room_id = ?");
            $stmt->execute([$room_id]);
            
            // Set new primary image
            $stmt = $pdo->prepare("UPDATE room_images SET is_primary = TRUE WHERE id = ? AND room_id = ?");
            $stmt->execute([$image_id, $room_id]);
            
            $success_message = "Primary image updated successfully!";
            
            // Refresh images
            $stmt = $pdo->prepare("SELECT * FROM room_images WHERE room_id = ? ORDER BY is_primary DESC, id ASC");
            $stmt->execute([$room_id]);
            $room_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $error_message = "Error updating primary image: " . $e->getMessage();
        }
    }
}

$available_amenities = ['Wi-Fi', 'AC', 'TV', 'Mini Bar', 'Balcony', 'Jacuzzi', 'Butler Service', 'Connecting Rooms', 'Work Desk', 'Coffee Machine', 'Safe', 'Hair Dryer'];
$current_amenities = $room ? explode(',', $room['amenities']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room - <?php echo htmlspecialchars($room['room_name']); ?> - Grand Hotel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .nav h1 {
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
        }

        .page-header h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: none;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
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

        .form-group.full-width {
            grid-column: 1 / -1;
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
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .amenity-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s;
        }

        .amenity-checkbox:hover {
            background: #f8f9fa;
        }

        .amenity-checkbox input {
            width: auto;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .images-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e1e5e9;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .image-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .image-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .image-card .image-actions {
            padding: 0.5rem;
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
        }

        .primary-badge {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            background: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .file-upload {
            border: 2px dashed #667eea;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: #f8f9ff;
            transition: background 0.3s;
        }

        .file-upload:hover {
            background: #f0f2ff;
        }

        .actions-bar {
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e1e5e9;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .images-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .actions-bar {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <h1>Grand Hotel Admin</h1>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="rooms.php">Rooms</a>
                <a href="bookings.php">Bookings</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>Edit Room</h2>
            <p>Update room details and manage images</p>
        </div>

        <div class="content-card">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="room_name">Room Name</label>
                        <input type="text" id="room_name" name="room_name" value="<?php echo htmlspecialchars($room['room_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="room_type_id">Room Type</label>
                        <select id="room_type_id" name="room_type_id" required>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo $room['room_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price_per_night">Price per Night ($)</label>
                        <input type="number" id="price_per_night" name="price_per_night" step="0.01" min="0" value="<?php echo $room['price_per_night']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="max_occupancy">Max Occupancy</label>
                        <input type="number" id="max_occupancy" name="max_occupancy" min="1" value="<?php echo $room['max_occupancy']; ?>" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Describe the room features and amenities..."><?php echo htmlspecialchars($room['description']); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label>Amenities</label>
                    <div class="amenities-grid">
                        <?php foreach ($available_amenities as $amenity): ?>
                            <div class="amenity-checkbox">
                                <input type="checkbox" id="amenity_<?php echo str_replace(' ', '_', strtolower($amenity)); ?>" 
                                       name="amenities[]" value="<?php echo $amenity; ?>" 
                                       <?php echo in_array($amenity, $current_amenities) ? 'checked' : ''; ?>>
                                <label for="amenity_<?php echo str_replace(' ', '_', strtolower($amenity)); ?>"><?php echo $amenity; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="cancellation_policy">Cancellation Policy</label>
                    <textarea id="cancellation_policy" name="cancellation_policy" placeholder="Describe the cancellation policy..."><?php echo htmlspecialchars($room['cancellation_policy']); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="room_images">Add New Images</label>
                    <div class="file-upload">
                        <input type="file" id="room_images" name="room_images[]" accept="image/*" multiple>
                        <p>Choose image files or drag and drop here</p>
                        <small>Supported formats: JPG, JPEG, PNG, GIF</small>
                    </div>
                </div>

                <button type="submit" name="update_room" class="btn">Update Room</button>
            </form>

            <!-- Current Images Section -->
            <?php if (!empty($room_images)): ?>
                <div class="images-section">
                    <h3>Current Images</h3>
                    <div class="images-grid">
                        <?php foreach ($room_images as $image): ?>
                            <div class="image-card">
                                <?php if ($image['is_primary']): ?>
                                    <div class="primary-badge">Primary</div>
                                <?php endif; ?>
                                <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="Room Image" 
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDIwMCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTUwIiBmaWxsPSIjZjhmOWZhIi8+Cjx0ZXh0IHg9IjEwMCIgeT0iNzUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGRvbWluYW50LWJhc2VsaW5lPSJjZW50cmFsIiBmaWxsPSIjNjc3Njg5Ij5JbWFnZSBOb3QgRm91bmQ8L3RleHQ+Cjwvc3ZnPgo='">
                                <div class="image-actions">
                                    <?php if (!$image['is_primary']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                            <button type="submit" name="set_primary" class="btn btn-warning btn-sm">Set Primary</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this image?')">
                                        <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                        <button type="submit" name="delete_image" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="actions-bar">
                <a href="rooms.php" class="btn btn-secondary">Back to Rooms</a>
                <div>
                    <a href="edit_room.php?id=<?php echo $room_id; ?>" class="btn btn-secondary">Refresh</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // File upload preview
        document.getElementById('room_images').addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length > 0) {
                const fileNames = Array.from(files).map(file => file.name).join(', ');
                e.target.parentElement.querySelector('p').textContent = `Selected: ${fileNames}`;
            }
        });

        // Drag and drop functionality
        const fileUpload = document.querySelector('.file-upload');
        const fileInput = document.getElementById('room_images');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUpload.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUpload.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            fileUpload.style.background = '#e6e9ff';
        }

        function unhighlight(e) {
            fileUpload.style.background = '#f8f9ff';
        }

        fileUpload.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            
            if (files.length > 0) {
                const fileNames = Array.from(files).map(file => file.name).join(', ');
                fileUpload.querySelector('p').textContent = `Selected: ${fileNames}`;
            }
        }

        // Click to upload
        fileUpload.addEventListener('click', () => {
            fileInput.click();
        });
    </script>
</body>
</html>