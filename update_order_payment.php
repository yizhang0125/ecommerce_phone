<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['orderId'] ?? '';
$paypal_details = $data['paypalDetails'] ?? [];

if (empty($order_id) || empty($paypal_details)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // Update order status and payment details
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'paid',
            payment_details = ?,
            payment_date = NOW()
        WHERE order_number = ?
    ");
    
    $payment_details = json_encode($paypal_details);
    $stmt->bind_param("ss", $payment_details, $order_id);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'orderId' => $order_id
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 