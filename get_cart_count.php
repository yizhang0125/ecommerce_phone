<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_stmt = $conn->prepare("
        SELECT SUM(quantity) as total_items 
        FROM cart 
        WHERE user_id = ?
    ");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    if ($cart_result->num_rows > 0) {
        $cart_count = (int)$cart_result->fetch_assoc()['total_items'];
    }
}

echo json_encode([
    'success' => true,
    'cartCount' => $cart_count
]); 