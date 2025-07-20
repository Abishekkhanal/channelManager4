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
    <title>Invoice - Booking #<?php echo $booking['id']; ?> - Grand Hotel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
        }

        .invoice-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .invoice-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .hotel-logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .hotel-tagline {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .invoice-content {
            padding: 2rem;
        }

        .invoice-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .invoice-info h3,
        .guest-info h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .booking-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .booking-details h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .charges-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .charges-table th,
        .charges-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .charges-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
        }

        .charges-table .amount {
            text-align: right;
            font-weight: 600;
        }

        .charges-table .total-row {
            background: #f8f9fa;
            font-weight: 600;
        }

        .charges-table .grand-total-row {
            background: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .summary-box {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            text-align: center;
        }

        .summary-item {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
        }

        .summary-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }

        .summary-value {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .payment-info {
            background: #e8f5e8;
            border: 2px solid #27ae60;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .payment-status {
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 1rem;
        }

        .terms {
            background: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .terms h4 {
            color: #856404;
            margin-bottom: 0.5rem;
        }

        .terms p {
            color: #856404;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .invoice-footer {
            background: #2c3e50;
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .footer-contact {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .contact-item {
            text-align: center;
        }

        .contact-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .contact-value {
            font-weight: 600;
        }

        .print-actions {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            margin: 0 0.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }

            .invoice-container {
                box-shadow: none;
                margin: 0;
                border-radius: 0;
            }

            .print-actions {
                display: none;
            }

            .charges-table {
                border: 1px solid #000;
            }

            .charges-table th,
            .charges-table td {
                border: 1px solid #000;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .invoice-container {
                margin: 1rem;
            }

            .invoice-meta {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-contact {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Print Actions -->
        <div class="print-actions">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Invoice</button>
            <button onclick="downloadPDF()" class="btn btn-success">📄 Download PDF</button>
            <a href="bookings.php" class="btn btn-secondary">← Back to Bookings</a>
        </div>

        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="hotel-logo">Grand Hotel</div>
            <div class="hotel-tagline">Luxury & Comfort Redefined</div>
        </div>

        <!-- Invoice Content -->
        <div class="invoice-content">
            <!-- Invoice Meta Information -->
            <div class="invoice-meta">
                <div class="invoice-info">
                    <h3>Invoice Details</h3>
                    <div class="info-row">
                        <span class="info-label">Invoice Number:</span>
                        <span class="info-value">#INV-<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Invoice Date:</span>
                        <span class="info-value"><?php echo date('M d, Y'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Booking ID:</span>
                        <span class="info-value">#<?php echo $booking['id']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Booking Source:</span>
                        <span class="info-value"><?php echo ucfirst($booking['booking_source']); ?></span>
                    </div>
                </div>

                <div class="guest-info">
                    <h3>Guest Information</h3>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['guest_email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Guests:</span>
                        <span class="info-value"><?php echo $booking['guests_count']; ?> Guest(s)</span>
                    </div>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="booking-details">
                <h3>Stay Details</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">Room</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['room_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Room Type</div>
                        <div class="detail-value"><?php echo htmlspecialchars($booking['room_type_name']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Check-in</div>
                        <div class="detail-value"><?php echo formatDate($booking['check_in']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Check-out</div>
                        <div class="detail-value"><?php echo formatDate($booking['check_out']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total Nights</div>
                        <div class="detail-value"><?php echo $nights; ?> Night(s)</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Rate per Night</div>
                        <div class="detail-value"><?php echo formatCurrency($booking['price_per_night']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Charges Breakdown -->
            <table class="charges-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Rate</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Room Charges -->
                    <tr>
                        <td>
                            <strong>Room Charges</strong><br>
                            <small><?php echo htmlspecialchars($booking['room_name']); ?> - <?php echo $nights; ?> night(s)</small>
                        </td>
                        <td><?php echo $nights; ?></td>
                        <td><?php echo formatCurrency($booking['price_per_night']); ?></td>
                        <td class="amount"><?php echo formatCurrency($room_total); ?></td>
                    </tr>

                    <!-- Additional Expenses -->
                    <?php if (!empty($expenses)): ?>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td>
                                    <strong><?php echo ucfirst($expense['expense_type']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($expense['description']); ?></small>
                                </td>
                                <td><?php echo $expense['quantity']; ?></td>
                                <td><?php echo formatCurrency($expense['amount']); ?></td>
                                <td class="amount"><?php echo formatCurrency($expense['amount'] * $expense['quantity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Subtotal -->
                    <tr class="total-row">
                        <td colspan="3"><strong>Subtotal</strong></td>
                        <td class="amount"><strong><?php echo formatCurrency($subtotal); ?></strong></td>
                    </tr>

                    <!-- Tax -->
                    <tr>
                        <td colspan="3">GST (18%)</td>
                        <td class="amount"><?php echo formatCurrency($tax_amount); ?></td>
                    </tr>

                    <!-- Grand Total -->
                    <tr class="grand-total-row">
                        <td colspan="3"><strong>GRAND TOTAL</strong></td>
                        <td class="amount"><strong><?php echo formatCurrency($grand_total); ?></strong></td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary Box -->
            <div class="summary-box">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Room Total</div>
                        <div class="summary-value"><?php echo formatCurrency($room_total); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Additional Charges</div>
                        <div class="summary-value"><?php echo formatCurrency($expenses_total); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Tax (18%)</div>
                        <div class="summary-value"><?php echo formatCurrency($tax_amount); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Grand Total</div>
                        <div class="summary-value"><?php echo formatCurrency($grand_total); ?></div>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="payment-info">
                <div class="payment-status">
                    <?php if ($booking['status'] === 'confirmed'): ?>
                        ✅ Payment Confirmed
                    <?php elseif ($booking['status'] === 'pending'): ?>
                        ⏳ Payment Pending
                    <?php else: ?>
                        ❌ Booking Cancelled
                    <?php endif; ?>
                </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="terms">
                <h4>Terms & Conditions</h4>
                <p>
                    • Check-in time: 2:00 PM | Check-out time: 12:00 PM<br>
                    • Late check-out may incur additional charges<br>
                    • All rates are inclusive of applicable taxes<br>
                    • Hotel reserves the right to verify guest identification<br>
                    • Smoking is strictly prohibited in rooms
                </p>
            </div>
        </div>

        <!-- Invoice Footer -->
        <div class="invoice-footer">
            <div class="footer-contact">
                <div class="contact-item">
                    <div class="contact-label">Address</div>
                    <div class="contact-value">123 Luxury Lane, Hotel District, City 12345</div>
                </div>
                <div class="contact-item">
                    <div class="contact-label">Phone</div>
                    <div class="contact-value">+91 98765 43210</div>
                </div>
                <div class="contact-item">
                    <div class="contact-label">Email</div>
                    <div class="contact-value">info@grandhotel.com</div>
                </div>
                <div class="contact-item">
                    <div class="contact-label">Website</div>
                    <div class="contact-value">www.grandhotel.com</div>
                </div>
            </div>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.2);">
                <p>Thank you for choosing Grand Hotel. We look forward to serving you again!</p>
            </div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            // Simple client-side PDF generation using browser's print functionality
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Invoice - Booking #<?php echo $booking['id']; ?></title>
                        <style>${document.querySelector('style').innerHTML}</style>
                    </head>
                    <body>
                        ${document.querySelector('.invoice-container').innerHTML}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Auto-focus print on page load if accessed with print parameter
        if (window.location.search.includes('print=1')) {
            window.onload = function() {
                setTimeout(() => window.print(), 1000);
            };
        }
    </script>
</body>
</html>