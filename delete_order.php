<?php
require 'db_connection.php';

// Check if the order ID is set
if (isset($_POST['id'])) {
    $order_id = $_POST['id'];

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Delete associated order items
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        $stmt->bind_param("i", $order_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
        $stmt->close();

        // Delete the order
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        $stmt->bind_param("i", $order_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute statement: " . $stmt->error);
        }
        $stmt->close();

        // Commit the transaction
        $conn->commit();

        // Redirect with a success message
        header('Location: orders_section.php?success=Delete Orders Successfull');
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if something failed
        $conn->rollback();

        // Redirect with an error message
        header('Location: orders_section.php?error=' . urlencode("Error deleting order: " . $e->getMessage()));
        exit();
    }
} else {
    // Redirect with an error message if order ID is not set
    header('Location: orders_section.php?error=Order ID not specified.');
    exit();
}
