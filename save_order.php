<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_POST['full_name'] ?? '';
$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$address = $_POST['address'] ?? '';
$payment_method = $_POST['payment_method'] ?? '';

try {
    // Start transaction
    $conn->begin_transaction();

    // Generate order number
    $order_number = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

    // Get cart items and total
    $cart_stmt = $conn->prepare("
        SELECT c.id, c.product_id, c.quantity, p.price, p.stock_quantity 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    $total_amount = 0;
    while ($item = $cart_result->fetch_assoc()) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Get shipping fee
    $shipping_stmt = $conn->prepare("SELECT shipping_fee FROM settings WHERE id = 1");
    $shipping_stmt->execute();
    $shipping_fee = $shipping_stmt->get_result()->fetch_assoc()['shipping_fee'];

    $grand_total = $total_amount + $shipping_fee;

    // Create order with pending status
    $order_stmt = $conn->prepare("
        INSERT INTO orders (
            order_number,
            user_id,
            full_name,
            phone,
            email,
            address,
            total_amount,
            shipping_fee,
            payment_method,
            status,
            order_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $order_stmt->bind_param(
        "sissssddss",
        $order_number,
        $user_id,
        $full_name,
        $phone,
        $email,
        $address,
        $total_amount,
        $shipping_fee,
        $payment_method
    );
    
    $order_stmt->execute();
    $order_id = $conn->insert_id;

    $conn->commit();

    echo json_encode([
        'success' => true,
        'orderId' => $order_id,
        'orderNumber' => $order_number,
        'amount' => $grand_total
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 