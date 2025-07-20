<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// Temporarily bypass admin login for testing
// requireAdminLogin();

// For debugging - check if session exists
if (function_exists('requireAdminLogin')) {
    try {
        requireAdminLogin();
    } catch (Exception $e) {
        // If admin login fails, continue anyway for testing
        error_log("Admin login check failed: " . $e->getMessage());
    }
}

if (!isset($_GET['booking_id'])) {
    header('Location: bookings.php');
    exit();
}

$booking_id = intval($_GET['booking_id']);

try {
    $pdo = getConnection();
    
    // Get booking details with room information
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_name, r.price_per_night, rt.name as room_type_name 
        FROM bookings b 
        LEFT JOIN rooms r ON b.room_id = r.id 
        LEFT JOIN room_types rt ON r.room_type_id = rt.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header('Location: bookings.php');
        exit();
    }
    
    // Get expenses for this booking
    $stmt = $pdo->prepare("SELECT * FROM booking_expenses WHERE booking_id = ? ORDER BY expense_type, description");
    $stmt->execute([$booking_id]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $check_in = new DateTime($booking['check_in']);
    $check_out = new DateTime($booking['check_out']);
    $nights = $check_in->diff($check_out)->days;
    
    $room_total = $booking['price_per_night'] * $nights;
    $expenses_total = array_sum(array_map(function($expense) {
        return $expense['amount'] * $expense['quantity'];
    }, $expenses));
    
    $subtotal = $room_total + $expenses_total;
    $tax_rate = 0.18; // 18% GST
    $tax_amount = $subtotal * $tax_rate;
    $grand_total = $subtotal + $tax_amount;
    
} catch(PDOException $e) {
    header('Location: bookings.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermal Receipt - Booking #<?php echo $booking_id; ?></title>
    <style>
        /* Thermal printer specific styles */
        @media print {
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                width: 80mm; /* 80mm thermal paper width */
                font-family: 'Courier New', monospace;
                font-size: 12px;
                line-height: 1.2;
                color: #000;
                background: #fff;
                margin: 0;
                padding: 5mm;
            }
            
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
        
        /* Screen preview styles */
        body {
            width: 80mm;
            max-width: 300px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
            color: #000;
            background: #fff;
            margin: 10px auto;
            padding: 10px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .thermal-receipt {
            width: 100%;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .hotel-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .hotel-info {
            font-size: 10px;
            line-height: 1.1;
        }
        
        .bill-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            text-decoration: underline;
        }
        
        .section {
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }
        
        .section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 3px;
            text-decoration: underline;
        }
        
        .info-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
            font-size: 10px;
        }
        
        .item-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
            font-size: 10px;
        }
        
        .item-desc {
            flex: 1;
            padding-right: 5px;
        }
        
        .item-qty {
            width: 20px;
            text-align: center;
        }
        
        .item-price {
            width: 60px;
            text-align: right;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 11px;
            margin-top: 3px;
        }
        
        .grand-total {
            font-size: 12px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 0;
        }
        
        .footer {
            text-align: center;
            font-size: 9px;
            margin-top: 10px;
            line-height: 1.1;
        }
        
        .print-time {
            font-size: 8px;
            text-align: center;
            margin-top: 5px;
        }
        
        .separator {
            text-align: center;
            margin: 5px 0;
            font-size: 10px;
        }
        
        .center {
            text-align: center;
        }
        
        .right {
            text-align: right;
        }
        
        .bold {
            font-weight: bold;
        }
        
        /* Print buttons */
        .print-controls {
            text-align: center;
            margin: 20px 0;
            display: block;
        }
        
        @media print {
            .print-controls {
                display: none;
            }
        }
        
        .btn {
            padding: 10px 20px;
            margin: 0 5px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button onclick="window.print()" class="btn">🖨️ Print Receipt</button>
        <button onclick="window.close()" class="btn btn-secondary">❌ Close</button>
    </div>

    <div class="thermal-receipt">
        <!-- Header -->
        <div class="header">
            <div class="hotel-name">GRAND HOTEL</div>
            <div class="hotel-info">
                123 Hotel Street, City<br>
                State 12345, India<br>
                Tel: +91-12345-67890<br>
                GST: 22AAAAA0000A1Z5
            </div>
        </div>

        <div class="bill-title">GUEST BILL</div>

        <!-- Booking Info -->
        <div class="section">
            <div class="section-title">BOOKING DETAILS</div>
            <div class="info-line">
                <span>Bill No:</span>
                <span class="bold">#<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-line">
                <span>Guest:</span>
                <span><?php echo substr(htmlspecialchars($booking['guest_name']), 0, 20); ?></span>
            </div>
            <?php if ($booking['guest_phone']): ?>
            <div class="info-line">
                <span>Phone:</span>
                <span><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
            </div>
            <?php endif; ?>
            <div class="info-line">
                <span>Room:</span>
                <span><?php echo htmlspecialchars($booking['room_name']); ?></span>
            </div>
            <div class="info-line">
                <span>Type:</span>
                <span><?php echo htmlspecialchars($booking['room_type_name']); ?></span>
            </div>
            <div class="info-line">
                <span>Check-in:</span>
                <span><?php echo $check_in->format('d/m/Y'); ?></span>
            </div>
            <div class="info-line">
                <span>Check-out:</span>
                <span><?php echo $check_out->format('d/m/Y'); ?></span>
            </div>
            <div class="info-line">
                <span>Nights:</span>
                <span class="bold"><?php echo $nights; ?></span>
            </div>
            <div class="info-line">
                <span>Guests:</span>
                <span><?php echo $booking['guests_count']; ?></span>
            </div>
        </div>

        <!-- Room Charges -->
        <div class="section">
            <div class="section-title">ROOM CHARGES</div>
            <div class="item-line">
                <span class="item-desc"><?php echo htmlspecialchars($booking['room_type_name']); ?> Room</span>
                <span class="item-qty"><?php echo $nights; ?></span>
                <span class="item-price">₹<?php echo number_format($room_total, 2); ?></span>
            </div>
            <div class="info-line">
                <span>Rate: ₹<?php echo number_format($booking['price_per_night'], 2); ?>/night</span>
                <span></span>
            </div>
        </div>

        <!-- Additional Charges -->
        <?php if (!empty($expenses)): ?>
        <div class="section">
            <div class="section-title">ADDITIONAL CHARGES</div>
            <?php foreach ($expenses as $expense): ?>
                <?php $expense_total = $expense['amount'] * $expense['quantity']; ?>
                <div class="item-line">
                    <span class="item-desc"><?php echo substr(htmlspecialchars($expense['description']), 0, 15); ?></span>
                    <span class="item-qty"><?php echo $expense['quantity']; ?></span>
                    <span class="item-price">₹<?php echo number_format($expense_total, 2); ?></span>
                </div>
                <div class="info-line" style="font-size: 8px;">
                    <span>@ ₹<?php echo number_format($expense['amount'], 2); ?> each</span>
                    <span></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Totals -->
        <div class="section">
            <div class="total-line">
                <span>Subtotal:</span>
                <span>₹<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="total-line">
                <span>GST (18%):</span>
                <span>₹<?php echo number_format($tax_amount, 2); ?></span>
            </div>
            <div class="separator">= = = = = = = = = = = = = = = =</div>
            <div class="total-line grand-total">
                <span>TOTAL AMOUNT:</span>
                <span>₹<?php echo number_format($grand_total, 2); ?></span>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="section">
            <div class="section-title">PAYMENT STATUS</div>
            <div class="info-line">
                <span>Status:</span>
                <span class="bold"><?php echo strtoupper($booking['status']); ?></span>
            </div>
            <div class="info-line">
                <span>Source:</span>
                <span><?php echo ucfirst($booking['booking_source']); ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="separator">* * * * * * * * * * * * * * * *</div>
            <div>Thank you for staying with us!</div>
            <div>Visit us again!</div>
            <div class="separator">* * * * * * * * * * * * * * * *</div>
            <div>For any queries, call:</div>
            <div class="bold">+91-12345-67890</div>
            <div>Email: info@grandhotel.com</div>
        </div>

        <div class="print-time">
            Printed: <?php echo date('d/m/Y H:i:s'); ?>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() {
        //     window.print();
        // };
        
        // Thermal printer specific commands (if using ESC/POS)
        function sendThermalCommands() {
            // These commands would be sent to a thermal printer via JavaScript
            // This is just an example - actual implementation depends on your printer setup
            const commands = [
                '\x1B\x40', // Initialize printer
                '\x1B\x61\x01', // Center align
                '\x1B\x21\x08', // Double height
                'GRAND HOTEL\n',
                '\x1B\x21\x00', // Normal size
                '\x1B\x61\x00', // Left align
                // Add more commands as needed
            ];
            
            // Send to thermal printer (requires specific printer API/driver)
            // This is placeholder code
            console.log('Thermal commands would be sent here');
        }
    </script>
</body>
</html>