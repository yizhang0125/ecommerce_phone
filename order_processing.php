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
$total_price = $_POST['total_price'];
$shipping_address = $_POST['shipping_address'];
$shipping_city = $_POST['shipping_city'];
$shipping_state = $_POST['shipping_state'];
$shipping_zip = $_POST['shipping_zip'];

// Start a transaction
$conn->begin_transaction();

try {
    // Insert the order into the orders table
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address, shipping_city, shipping_state, shipping_zip) VALUES (?, ?, 'Pending', ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("idssss", $user_id, $total_price, $shipping_address, $shipping_city, $shipping_state, $shipping_zip);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    $order_id = $stmt->insert_id;
    $stmt->close();

    // Insert each cart item as an order item
    $stmt = $conn->prepare("SELECT cart.product_id, cart.quantity, products.price FROM cart JOIN products ON cart.product_id = products.id WHERE cart.user_id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
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

    // Redirect to the order success page
    header("Location: order_success.php?order_id=$order_id");
    exit();
} catch (Exception $e) {
    // Rollback the transaction in case of error
    $conn->rollback();
    die("Error: " . $e->getMessage());
}
?>
