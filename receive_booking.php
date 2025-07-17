<?php
require_once 'config/database.php';

// Set content type to JSON
header('Content-Type: application/json');

// Function to send JSON response
function sendResponse($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Function to validate API key
function validateApiKey($api_key) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id, ota_name FROM ota_settings WHERE api_key = ? AND is_active = 1");
        $stmt->execute([$api_key]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        return false;
    }
}

// Function to find room by name or type
function findRoomByType($room_type) {
    try {
        $pdo = getConnection();
        
        // First try to find by exact room name
        $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_name = ?");
        $stmt->execute([$room_type]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room) {
            return $room['id'];
        }
        
        // If not found, try to find by room type
        $stmt = $pdo->prepare("SELECT r.id FROM rooms r 
                              LEFT JOIN room_types rt ON r.room_type_id = rt.id 
                              WHERE rt.name = ? 
                              LIMIT 1");
        $stmt->execute([$room_type]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $room ? $room['id'] : null;
    } catch(PDOException $e) {
        return null;
    }
}

// Function to check and update availability
function updateAvailability($room_id, $check_in, $check_out) {
    try {
        $pdo = getConnection();
        
        // Calculate number of nights
        $checkin_date = new DateTime($check_in);
        $checkout_date = new DateTime($check_out);
        $nights = $checkin_date->diff($checkout_date)->days;
        
        // Check availability for each night
        for ($i = 0; $i < $nights; $i++) {
            $current_date = clone $checkin_date;
            $current_date->add(new DateInterval('P' . $i . 'D'));
            $date_str = $current_date->format('Y-m-d');
            
            // Check current availability
            $stmt = $pdo->prepare("SELECT available_count FROM room_availability WHERE room_id = ? AND date = ?");
            $stmt->execute([$room_id, $date_str]);
            $availability = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $current_available = $availability ? $availability['available_count'] : 1;
            
            // Check current bookings for this date
            $stmt = $pdo->prepare("SELECT COUNT(*) as booked FROM bookings 
                                  WHERE room_id = ? 
                                  AND status = 'confirmed' 
                                  AND ? >= check_in 
                                  AND ? < check_out");
            $stmt->execute([$room_id, $date_str, $date_str]);
            $booked = $stmt->fetch(PDO::FETCH_ASSOC)['booked'];
            
            // Check if room is available
            if ($booked >= $current_available) {
                return false; // Room not available
            }
        }
        
        return true; // Room is available
    } catch(Exception $e) {
        return false;
    }
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Only POST requests are allowed');
}

// Get the raw POST data
$raw_data = file_get_contents('php://input');

if (empty($raw_data)) {
    sendResponse('error', 'No data received');
}

// Try to parse as JSON first
$booking_data = json_decode($raw_data, true);

// If JSON parsing failed, try XML
if (json_last_error() !== JSON_ERROR_NONE) {
    try {
        $xml = simplexml_load_string($raw_data);
        $booking_data = json_decode(json_encode($xml), true);
    } catch(Exception $e) {
        sendResponse('error', 'Invalid JSON or XML format');
    }
}

// Validate API key
$api_key = $booking_data['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if (empty($api_key)) {
    sendResponse('error', 'API key is required');
}

$ota_info = validateApiKey($api_key);
if (!$ota_info) {
    sendResponse('error', 'Invalid or inactive API key');
}

// Extract booking information (flexible field mapping)
$guest_name = $booking_data['guest_name'] ?? $booking_data['customer_name'] ?? $booking_data['name'] ?? '';
$guest_email = $booking_data['guest_email'] ?? $booking_data['email'] ?? '';
$guest_phone = $booking_data['guest_phone'] ?? $booking_data['phone'] ?? '';
$room_type = $booking_data['room_type'] ?? $booking_data['room_name'] ?? $booking_data['accommodation_type'] ?? '';
$check_in = $booking_data['check_in'] ?? $booking_data['checkin'] ?? $booking_data['arrival_date'] ?? '';
$check_out = $booking_data['check_out'] ?? $booking_data['checkout'] ?? $booking_data['departure_date'] ?? '';
$guests_count = intval($booking_data['guests'] ?? $booking_data['guests_count'] ?? $booking_data['pax'] ?? 1);
$total_amount = floatval($booking_data['total_amount'] ?? $booking_data['total_price'] ?? $booking_data['amount'] ?? 0);
$ota_booking_id = $booking_data['booking_id'] ?? $booking_data['reservation_id'] ?? $booking_data['id'] ?? '';

// Validate required fields
if (empty($guest_name) || empty($check_in) || empty($check_out)) {
    sendResponse('error', 'Required fields missing: guest_name, check_in, check_out');
}

// Validate dates
if (!strtotime($check_in) || !strtotime($check_out)) {
    sendResponse('error', 'Invalid date format');
}

if (strtotime($check_in) >= strtotime($check_out)) {
    sendResponse('error', 'Check-out date must be after check-in date');
}

// Find room
$room_id = null;
if (!empty($room_type)) {
    $room_id = findRoomByType($room_type);
}

try {
    $pdo = getConnection();
    
    // Check if booking already exists (prevent duplicates)
    if (!empty($ota_booking_id)) {
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE ota_booking_id = ? AND booking_source = ?");
        $stmt->execute([$ota_booking_id, $ota_info['ota_name']]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse('error', 'Booking already exists', ['booking_id' => $ota_booking_id]);
        }
    }
    
    // Check availability if room is specified
    if ($room_id) {
        if (!updateAvailability($room_id, $check_in, $check_out)) {
            sendResponse('error', 'Room not available for selected dates');
        }
    }
    
    // Insert booking
    $stmt = $pdo->prepare("INSERT INTO bookings (guest_name, guest_email, guest_phone, room_id, check_in, check_out, guests_count, total_amount, booking_source, ota_booking_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
    
    $stmt->execute([
        $guest_name,
        $guest_email,
        $guest_phone,
        $room_id,
        $check_in,
        $check_out,
        $guests_count,
        $total_amount,
        $ota_info['ota_name'],
        $ota_booking_id
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    // Log the booking for debugging
    error_log("OTA Booking received: ID $booking_id, Guest: $guest_name, OTA: " . $ota_info['ota_name']);
    
    sendResponse('success', 'Booking created successfully', [
        'booking_id' => $booking_id,
        'ota_booking_id' => $ota_booking_id,
        'guest_name' => $guest_name,
        'check_in' => $check_in,
        'check_out' => $check_out,
        'room_type' => $room_type,
        'status' => 'confirmed'
    ]);
    
} catch(PDOException $e) {
    error_log("Database error in receive_booking.php: " . $e->getMessage());
    sendResponse('error', 'Database error occurred');
} catch(Exception $e) {
    error_log("General error in receive_booking.php: " . $e->getMessage());
    sendResponse('error', 'An error occurred while processing the booking');
}
?>