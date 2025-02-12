<?php
session_start();
require 'db_connection.php';

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

// Retrieve POST data
$user_id = $_SESSION['user_id'];
$shipping_address = trim($_POST['shipping_address']);
$shipping_city = trim($_POST['shipping_city']);
$shipping_state = trim($_POST['shipping_state']);
$shipping_zip = trim($_POST['shipping_zip']);

// Validate shipping data
if (empty($shipping_address) || empty($shipping_city) || 
    empty($shipping_state) || !preg_match('/^\d{5}$/', $shipping_zip)) {
    die("Invalid shipping information provided.");
}

// Fetch the current shipping fee from the settings table
$stmt = $conn->prepare("SELECT shipping_fee FROM settings WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();
$current_shipping_fee = $result->fetch_assoc()['shipping_fee'];
$stmt->close();

// Fetch cart items to calculate total price
$stmt = $conn->prepare("SELECT cart.product_id, cart.quantity, products.price 
                        FROM cart 
                        JOIN products ON cart.product_id = products.id 
                        WHERE cart.user_id = ?");
if (!$stmt) {
    die("Failed to prepare statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Failed to execute statement: " . $stmt->error);
}
$result = $stmt->get_result();

// Initialize total price
$total_price = 0;

// Calculate total price by summing up (quantity * price) for each cart item
while ($row = $result->fetch_assoc()) {
    $total_price += $row['quantity'] * $row['price'];
}

// Add the shipping fee to the total price
$total_price += $current_shipping_fee;

// Start a transaction
$conn->begin_transaction();

try {
    // Insert order with shipping information
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, 
            total_price, 
            status,
            shipping_status,
            shipping_address, 
            shipping_city, 
            shipping_state, 
            shipping_zip,
            order_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $status = 'Pending';
    $shipping_status = 'Pending';

    $stmt->bind_param(
        "idssssss", 
        $user_id, 
        $total_price, 
        $status,
        $shipping_status,
        $shipping_address, 
        $shipping_city, 
        $shipping_state, 
        $shipping_zip
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert each cart item as an order item
    $stmt = $conn->prepare("SELECT cart.product_id, cart.quantity, products.price 
                            FROM cart 
                            JOIN products ON cart.product_id = products.id 
                            WHERE cart.user_id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        $stmt->bind_param("iiid", $order_id, $row['product_id'], $row['quantity'], $row['price']);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
        $stmt->close();
    }

    // Clear the cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    $stmt->close();

    // Commit the transaction
    $conn->commit();

    // Redirect to the payment page
    header("Location: payment.php?order_id=$order_id");
    exit();
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    die("Error: " . $e->getMessage());
}
?>
