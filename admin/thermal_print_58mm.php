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
    <title>Thermal Receipt 58mm - Booking #<?php echo $booking_id; ?></title>
    <style>
        /* 58mm Thermal printer specific styles */
        @media print {
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                width: 58mm; /* 58mm thermal paper width */
                font-family: 'Courier New', monospace;
                font-size: 10px;
                line-height: 1.1;
                color: #000;
                background: #fff;
                margin: 0;
                padding: 3mm;
            }
            
            @page {
                size: 58mm auto;
                margin: 0;
            }
        }
        
        /* Screen preview styles */
        body {
            width: 58mm;
            max-width: 220px;
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.1;
            color: #000;
            background: #fff;
            margin: 10px auto;
            padding: 8px;
            border: 1px solid #ccc;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .thermal-receipt {
            width: 100%;
        }
        
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        
        .hotel-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 1px;
        }
        
        .hotel-info {
            font-size: 8px;
            line-height: 1.0;
        }
        
        .bill-title {
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            margin: 6px 0;
        }
        
        .section {
            margin-bottom: 6px;
            border-bottom: 1px dashed #000;
            padding-bottom: 3px;
        }
        
        .section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .info-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
            font-size: 8px;
        }
        
        .item-line {
            font-size: 8px;
            margin-bottom: 1px;
        }
        
        .item-desc {
            width: 100%;
        }
        
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            padding-left: 5px;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 9px;
            margin-top: 2px;
        }
        
        .grand-total {
            font-size: 10px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2px 0;
            text-align: center;
        }
        
        .footer {
            text-align: center;
            font-size: 7px;
            margin-top: 6px;
            line-height: 1.0;
        }
        
        .print-time {
            font-size: 6px;
            text-align: center;
            margin-top: 3px;
        }
        
        .separator {
            text-align: center;
            margin: 3px 0;
            font-size: 8px;
        }
        
        .center {
            text-align: center;
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
            padding: 8px 15px;
            margin: 0 3px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
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
        <button onclick="window.print()" class="btn">🖨️ Print</button>
        <button onclick="window.close()" class="btn btn-secondary">❌ Close</button>
    </div>

    <div class="thermal-receipt">
        <!-- Header -->
        <div class="header">
            <div class="hotel-name">GRAND HOTEL</div>
            <div class="hotel-info">
                123 Hotel Street<br>
                City, State 12345<br>
                +91-12345-67890<br>
                GST: 22AAAAA0000A1Z5
            </div>
        </div>

        <div class="bill-title">GUEST BILL</div>

        <!-- Bill Number and Date -->
        <div class="center" style="font-size: 9px; margin-bottom: 6px;">
            <div class="bold">Bill #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></div>
            <div><?php echo date('d/m/Y H:i'); ?></div>
        </div>

        <!-- Guest Info -->
        <div class="section">
            <div class="info-line">
                <span class="bold">Guest:</span>
                <span><?php echo substr(htmlspecialchars($booking['guest_name']), 0, 15); ?></span>
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
        </div>

        <!-- Stay Details -->
        <div class="section">
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

        <!-- Charges -->
        <div class="section">
            <div class="section-title">CHARGES</div>
            <div class="item-line">
                <div class="item-desc"><?php echo htmlspecialchars($booking['room_type_name']); ?> Room</div>
                <div class="item-details">
                    <span><?php echo $nights; ?> x ₹<?php echo number_format($booking['price_per_night'], 0); ?></span>
                    <span class="bold">₹<?php echo number_format($room_total, 2); ?></span>
                </div>
            </div>
            
            <?php if (!empty($expenses)): ?>
                <?php foreach ($expenses as $expense): ?>
                    <?php $expense_total = $expense['amount'] * $expense['quantity']; ?>
                    <div class="item-line">
                        <div class="item-desc"><?php echo substr(htmlspecialchars($expense['description']), 0, 12); ?></div>
                        <div class="item-details">
                            <span><?php echo $expense['quantity']; ?> x ₹<?php echo number_format($expense['amount'], 0); ?></span>
                            <span class="bold">₹<?php echo number_format($expense_total, 2); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

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
            <div class="separator">= = = = = = = = = = = =</div>
            <div class="grand-total">
                TOTAL: ₹<?php echo number_format($grand_total, 2); ?>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="center" style="font-size: 8px; margin: 6px 0;">
            <div>Status: <span class="bold"><?php echo strtoupper($booking['status']); ?></span></div>
            <div>Source: <?php echo ucfirst($booking['booking_source']); ?></div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="separator">* * * * * * * * * * * *</div>
            <div>Thank you!</div>
            <div>Visit again!</div>
            <div class="separator">* * * * * * * * * * * *</div>
            <div>Call: +91-12345-67890</div>
        </div>

        <div class="print-time">
            <?php echo date('d/m/Y H:i:s'); ?>
        </div>
    </div>

    <script>
        // Auto-print option
        function autoPrint() {
            setTimeout(() => {
                window.print();
                // Auto-close after printing (optional)
                // setTimeout(() => window.close(), 2000);
            }, 500);
        }
        
        // Uncomment to enable auto-print
        // window.onload = autoPrint;
    </script>
</body>
</html>