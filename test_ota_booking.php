<?php
/**
 * Test script for OTA booking reception
 * This script simulates different OTA providers sending booking data
 */

// Test booking data for different scenarios
$test_bookings = [
    // Booking.com style booking
    [
        'name' => 'Booking.com Test',
        'data' => [
            'api_key' => 'test_booking_com_key',
            'guest_name' => 'John Smith',
            'guest_email' => 'john.smith@email.com',
            'guest_phone' => '+1-555-0123',
            'room_type' => 'Deluxe',
            'check_in' => '2024-03-15',
            'check_out' => '2024-03-17',
            'guests_count' => 2,
            'total_amount' => 300.00,
            'booking_id' => 'BDC123456789',
            'booking_source' => 'Booking.com'
        ]
    ],
    
    // Expedia style booking
    [
        'name' => 'Expedia Test',
        'data' => [
            'api_key' => 'test_expedia_key',
            'customer_name' => 'Jane Doe',
            'email' => 'jane.doe@email.com',
            'phone' => '+1-555-0456',
            'accommodation_type' => 'Suite',
            'arrival_date' => '2024-03-20',
            'departure_date' => '2024-03-22',
            'pax' => 3,
            'total_price' => 450.00,
            'reservation_id' => 'EXP987654321'
        ]
    ],
    
    // Agoda style booking
    [
        'name' => 'Agoda Test',
        'data' => [
            'api_key' => 'test_agoda_key',
            'name' => 'Bob Johnson',
            'email' => 'bob.johnson@email.com',
            'room_name' => 'Family Room',
            'checkin' => '2024-03-25',
            'checkout' => '2024-03-27',
            'guests' => 4,
            'amount' => 400.00,
            'id' => 'AGD555666777'
        ]
    ],
    
    // Minimal booking (testing required fields only)
    [
        'name' => 'Minimal Booking Test',
        'data' => [
            'api_key' => 'test_minimal_key',
            'guest_name' => 'Alice Brown',
            'check_in' => '2024-03-30',
            'check_out' => '2024-04-01'
        ]
    ]
];

// Function to send booking to the endpoint
function sendBooking($booking_data, $endpoint_url) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($booking_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $booking_data['api_key']
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'http_code' => $http_code,
        'response' => $response,
        'error' => $error
    ];
}

// Determine the endpoint URL
$endpoint_url = 'http://localhost/hotel-booking/receive_booking.php';

// Check if we're running from command line or web
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>OTA Booking Test</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .test-result { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
            .success { border-color: #4CAF50; background-color: #f0f8f0; }
            .error { border-color: #f44336; background-color: #fff0f0; }
            .test-name { font-weight: bold; font-size: 1.2em; margin-bottom: 10px; }
            .response { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 3px; }
            pre { white-space: pre-wrap; word-wrap: break-word; }
        </style>
    </head>
    <body>
        <h1>OTA Booking Reception Test</h1>
        <p>Testing endpoint: <strong>$endpoint_url</strong></p>
        <hr>";
}

// Test each booking scenario
foreach ($test_bookings as $test) {
    $result = sendBooking($test['data'], $endpoint_url);
    
    $is_success = $result['http_code'] >= 200 && $result['http_code'] < 300;
    $response_data = json_decode($result['response'], true);
    
    if ($is_cli) {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Testing: " . $test['name'] . "\n";
        echo str_repeat("-", 50) . "\n";
        echo "HTTP Code: " . $result['http_code'] . "\n";
        echo "Status: " . ($is_success ? "SUCCESS" : "FAILED") . "\n";
        
        if ($result['error']) {
            echo "Error: " . $result['error'] . "\n";
        }
        
        if ($result['response']) {
            echo "Response: " . $result['response'] . "\n";
        }
        
        echo "Request Data:\n";
        echo json_encode($test['data'], JSON_PRETTY_PRINT) . "\n";
        
    } else {
        echo "<div class='test-result " . ($is_success ? 'success' : 'error') . "'>";
        echo "<div class='test-name'>" . htmlspecialchars($test['name']) . "</div>";
        echo "<p><strong>HTTP Code:</strong> " . $result['http_code'] . "</p>";
        echo "<p><strong>Status:</strong> " . ($is_success ? "SUCCESS" : "FAILED") . "</p>";
        
        if ($result['error']) {
            echo "<p><strong>Error:</strong> " . htmlspecialchars($result['error']) . "</p>";
        }
        
        if ($result['response']) {
            echo "<div class='response'>";
            echo "<strong>Response:</strong><br>";
            echo "<pre>" . htmlspecialchars($result['response']) . "</pre>";
            echo "</div>";
        }
        
        echo "<details>";
        echo "<summary>Request Data</summary>";
        echo "<pre>" . htmlspecialchars(json_encode($test['data'], JSON_PRETTY_PRINT)) . "</pre>";
        echo "</details>";
        
        echo "</div>";
    }
}

if (!$is_cli) {
    echo "
        <hr>
        <h2>Test Summary</h2>
        <p>This test script simulates different OTA providers sending booking data to your hotel booking system.</p>
        <p>For production use:</p>
        <ul>
            <li>Configure real API keys in the admin settings</li>
            <li>Set up proper OTA credentials</li>
            <li>Enable SSL/HTTPS for secure communication</li>
            <li>Implement rate limiting and IP whitelisting</li>
        </ul>
        
        <h3>Next Steps</h3>
        <ol>
            <li>Set up the database using database.sql</li>
            <li>Create an admin account via signup.php</li>
            <li>Configure OTA settings in the admin panel</li>
            <li>Test with real OTA integrations</li>
        </ol>
    </body>
    </html>";
}

if ($is_cli) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Test completed!\n";
    echo "Check the admin panel to see if bookings were created.\n";
}
?>