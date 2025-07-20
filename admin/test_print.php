<?php
// Simple test file to debug print functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
try {
    require_once '../config/database.php';
    echo "<h2>Database Connection Test</h2>";
    $pdo = getConnection();
    echo "<p style='color: green;'>✓ Database connected successfully</p>";
    
    // Test if bookings exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total bookings in database: <strong>{$result['count']}</strong></p>";
    
    if ($result['count'] > 0) {
        // Get first booking for testing
        $stmt = $pdo->query("SELECT b.*, r.room_name, rt.name as room_type_name FROM bookings b LEFT JOIN rooms r ON b.room_id = r.id LEFT JOIN room_types rt ON r.room_type_id = rt.id LIMIT 1");
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Booking Data:</h3>";
        echo "<pre>" . print_r($booking, true) . "</pre>";
        
        echo "<h3>Test Print Links:</h3>";
        echo "<p><a href='print_bill.php?booking_id={$booking['id']}' target='_blank'>🖨️ Test Regular Print Bill</a></p>";
        echo "<p><a href='thermal_print.php?booking_id={$booking['id']}' target='_blank'>🧾 Test Thermal Print 80mm</a></p>";
        echo "<p><a href='thermal_print_58mm.php?booking_id={$booking['id']}' target='_blank'>🧾 Test Thermal Print 58mm</a></p>";
        
        echo "<h3>JavaScript Test Buttons:</h3>";
        echo "<button onclick='testPrintBill({$booking['id']})' class='btn'>Test Print Bill JS</button>";
        echo "<button onclick='testThermalPrint({$booking['id']})' class='btn'>Test Thermal Print JS</button>";
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
.btn {
    padding: 10px 20px;
    margin: 5px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.btn:hover {
    background: #0056b3;
}
</style>

<script>
function testPrintBill(bookingId) {
    console.log('Testing print bill with booking ID:', bookingId);
    
    try {
        const printWindow = window.open(`print_bill.php?booking_id=${bookingId}`, '_blank');
        if (!printWindow) {
            alert('Pop-up blocked! Please allow pop-ups for this site.');
            return false;
        }
        console.log('Print window opened successfully');
    } catch (error) {
        console.error('Error opening print window:', error);
        alert('Error opening print window: ' + error.message);
    }
}

function testThermalPrint(bookingId) {
    console.log('Testing thermal print with booking ID:', bookingId);
    
    try {
        const thermalWindow = window.open(`thermal_print.php?booking_id=${bookingId}`, '_blank', 'width=400,height=600');
        if (!thermalWindow) {
            alert('Pop-up blocked! Please allow pop-ups for this site.');
            return false;
        }
        console.log('Thermal print window opened successfully');
    } catch (error) {
        console.error('Error opening thermal print window:', error);
        alert('Error opening thermal print window: ' + error.message);
    }
}

// Test if JavaScript is working
console.log('Test print JavaScript loaded successfully');
</script>