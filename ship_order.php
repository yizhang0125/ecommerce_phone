<?php
require 'db_connection.php';

if (isset($_GET['id'])) {
    $orderId = $_GET['id'];

    // Update the order status to 'Shipped'
    $stmt = $conn->prepare("UPDATE orders SET shipping_status = 'Shipped' WHERE id = ?");
    $stmt->bind_param("i", $orderId);

    if ($stmt->execute()) {
        echo "Order marked as shipped.";
        // Redirect back to the orders page or dashboard
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Error updating order status.";
    }
}
?>
