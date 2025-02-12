<?php
// Check if a session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db_connection.php';

header('Content-Type: application/json');

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$cart_id = $data['cart_id'] ?? 0;
$change = $data['change'] ?? 0;

if (!isset($_SESSION['user_id']) || !$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get current cart item
$stmt = $conn->prepare("
    SELECT c.quantity, p.stock_quantity, p.price 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->bind_param("ii", $cart_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found']);
    exit;
}

$cart_item = $result->fetch_assoc();
$new_quantity = $cart_item['quantity'];

if (is_numeric($change)) {
    // Updating quantity
    $new_quantity = $cart_item['quantity'] + $change;
    
    // Check stock limit and minimum quantity
    if ($new_quantity > $cart_item['stock_quantity']) {
        $new_quantity = $cart_item['stock_quantity'];
    }
    if ($new_quantity < 0) {
        $new_quantity = 0;
    }
} else {
    // Direct quantity update
    $new_quantity = max(0, min((int)$change, $cart_item['stock_quantity']));
}

if ($new_quantity === 0) {
    // Delete the item if quantity is 0
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
} else {
    // Update the quantity
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $new_quantity, $cart_id, $user_id);
    $stmt->execute();
}

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
    'newQuantity' => $new_quantity,
    'newTotal' => (float)$cart_data['total'],
    'cartCount' => (int)$cart_data['count'],
    'cartEmpty' => $cart_data['count'] == 0
]);
