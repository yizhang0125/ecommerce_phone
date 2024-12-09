<?php
session_start();
require 'db_connection.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $zip = $_POST['zip'];

    // Update shipping details in the database
    $stmt = $conn->prepare("UPDATE orders 
                            SET shipping_address = ?, shipping_city = ?, shipping_state = ?, shipping_zip = ?, shipping_status = 'Shipped' 
                            WHERE id = ?");
    $stmt->bind_param("ssssi", $address, $city, $state, $zip, $order_id);

    if ($stmt->execute()) {
        echo "Shipping details updated successfully.";
    } else {
        echo "Error updating shipping details: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ship Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Ship Order</h1>
        <form method="post" action="ship_order.php">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($_GET['id']); ?>">
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <input type="text" class="form-control" id="address" name="address" required>
            </div>
            <div class="mb-3">
                <label for="city" class="form-label">City</label>
                <input type="text" class="form-control" id="city" name="city" required>
            </div>
            <div class="mb-3">
                <label for="state" class="form-label">State</label>
                <input type="text" class="form-control" id="state" name="state" required>
            </div>
            <div class="mb-3">
                <label for="zip" class="form-label">ZIP Code</label>
                <input type="text" class="form-control" id="zip" name="zip" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Shipping Details</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
