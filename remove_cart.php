<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cart_id = $_POST['id'];

    // Delete cart item
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
    $stmt->bind_param("i", $cart_id);
    if ($stmt->execute()) {
        // Redirect to the cart page
        header("Location: view_cart.php");
        exit();
    } else {
        echo "Failed to remove item: " . $stmt->error;
    }
} else {
    echo "Invalid request.";
}
?>
