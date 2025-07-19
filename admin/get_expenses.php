<?php
require_once '../config/database.php';
requireAdminLogin();

header('Content-Type: application/json');

if (!isset($_GET['booking_id'])) {
    echo json_encode([]);
    exit();
}

$booking_id = intval($_GET['booking_id']);

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM booking_expenses WHERE booking_id = ? ORDER BY created_at DESC");
    $stmt->execute([$booking_id]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($expenses);
} catch(PDOException $e) {
    echo json_encode([]);
}
?>