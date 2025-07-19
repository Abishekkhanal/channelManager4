<?php
require_once '../config/database.php';
requireAdminLogin();

header('Content-Type: application/json');

if (!isset($_GET['booking_id'])) {
    echo json_encode(['error' => 'Booking ID not provided']);
    exit();
}

$booking_id = intval($_GET['booking_id']);

try {
    $pdo = getConnection();
    
    // Get booking details with room information
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_name, rt.name as room_type_name 
        FROM bookings b 
        LEFT JOIN rooms r ON b.room_id = r.id 
        LEFT JOIN room_types rt ON r.room_type_id = rt.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['error' => 'Booking not found']);
        exit();
    }
    
    // Get expenses for this booking
    $stmt = $pdo->prepare("SELECT * FROM booking_expenses WHERE booking_id = ? ORDER BY expense_type, description");
    $stmt->execute([$booking_id]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'booking' => $booking,
        'expenses' => $expenses
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>