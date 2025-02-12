<?php
require 'db_connection.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $order_id = $_GET['id'];
    $status = $_GET['status'];

    // Update the order status
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if (!$stmt) {
        header("Location: orders_section.php?error=Failed to prepare statement");
        exit();
    }

    $stmt->bind_param("si", $status, $order_id);

    if ($stmt->execute()) {
        // If marking as completed, also update shipping status
        if ($status === 'completed') {
            $stmt = $conn->prepare("UPDATE orders SET shipping_status = 'Shipped' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
        }

        // Redirect with success message
        header("Location: orders_section.php?success=Order status updated successfully");
        exit();
    } else {
        // Redirect with error message
        header("Location: orders_section.php?error=Failed to update order status");
        exit();
    }
} else {
    // Redirect with error if parameters are missing
    header("Location: orders_section.php?error=Invalid parameters");
    exit();
}
?> 