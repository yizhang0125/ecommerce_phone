<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$cart_id = $data['cart_id'] ?? 0;

if (!isset($_SESSION['user_id']) || !$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Delete the cart item
$stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();

// Get new cart total
$stmt = $conn->prepare("
    SELECT SUM(c.quantity * p.price) as total, SUM(c.quantity) as count
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_data = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'newTotal' => (float)$cart_data['total'],
    'cartCount' => (int)$cart_data['count'],
    'cartEmpty' => $cart_data['count'] == 0
]); 