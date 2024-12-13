<?php
// Include the database connection
include('db_connection.php');

// Get POST data from the form
$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';
$card_number = isset($_POST['cardNumber']) ? $_POST['cardNumber'] : '';
$card_holder = isset($_POST['cardHolder']) ? $_POST['cardHolder'] : '';
$exp_date = isset($_POST['cardExpiry']) ? $_POST['cardExpiry'] : ''; // Get expiration date from the hidden input
$payment_status = 'Pending'; // Initial payment status

// Ensure the data is safe to insert
$order_id = $conn->real_escape_string($order_id);
$card_number = $conn->real_escape_string($card_number);
$card_holder = $conn->real_escape_string($card_holder);
$exp_date = $conn->real_escape_string($exp_date); // Ensure expiration date is also escaped

// Start a transaction to ensure data consistency
$conn->begin_transaction();

try {
    // Prepare the SQL query to insert payment information
    $sql = "INSERT INTO payments (order_id, card_number, card_holder, exp_date, payment_status) 
            VALUES ('$order_id', '$card_number', '$card_holder', '$exp_date', '$payment_status')";

    // Execute the query and check for success
    if ($conn->query($sql) === TRUE) {
        // Update the payment status to 'Successful' in the payments table
        $update_payment_sql = "UPDATE payments SET payment_status = 'Successful' WHERE order_id = '$order_id' AND payment_status = 'Pending'";
        if ($conn->query($update_payment_sql) === TRUE) {
            // Commit the transaction if both queries are successful
            $conn->commit();
            // Redirect to order_success.php with order_id
            header('Location: order_success.php?order_id=' . urlencode($order_id));
            exit();
        } else {
            // Rollback the transaction if the payment status update fails
            $conn->rollback();
            echo "Error updating payment status in payments table: " . $conn->error;
        }
    } else {
        // Rollback the transaction if payment insertion fails
        $conn->rollback();
        echo "Error inserting payment: " . $conn->error;
    }
} catch (Exception $e) {
    // Rollback in case of any exception
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

// Close the database connection
$conn->close();
?>
