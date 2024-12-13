<?php
require 'db_connection.php';

if (isset($_GET['id'])) {
    $orderId = $_GET['id'];

    // Update the order status to 'Completed'
    $stmt = $conn->prepare("UPDATE orders SET status = 'Completed' WHERE id = ?");
    $stmt->bind_param("i", $orderId);

    if ($stmt->execute()) {
        // Redirect back to the orders page with a success message including OrderID
        header("Location: orders_section.php?success=Order%20ID%20" . urlencode($orderId) . "%20marked%20as%20completed!");
        exit();
    } else {
        // Redirect back to the orders page with an error message
        header("Location: orders_section.php?error=Error%20updating%20order%20status.");
        exit();
    }
} else {
    // Redirect back to the orders page with an error message if no ID is provided
    header("Location: orders_section.php?error=Invalid%20order%20ID.");
    exit();
}
