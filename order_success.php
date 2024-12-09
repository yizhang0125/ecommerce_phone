<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['order_id'])) {
    echo "Order ID is missing.";
    exit();
}

$order_id = $_GET['order_id'];

// Fetch order details
$stmt = $conn->prepare("SELECT total_price, status FROM orders WHERE id = ?");
if (!$stmt) {
    die("Failed to prepare statement: " . $conn->error);
}
if (!$stmt->bind_param("i", $order_id)) {
    die("Failed to bind parameters: " . $stmt->error);
}
if (!$stmt->execute()) {
    die("Failed to execute statement: " . $stmt->error);
}
$stmt->bind_result($total_price, $status);
$stmt->fetch();
$stmt->close();

if (!$total_price) {
    echo "Order not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Order Success</h1>
        <p>Thank you for your order!</p>
        <p>Your order ID is: <?php echo htmlspecialchars($order_id); ?></p>
        <p>Total Amount: $<?php echo number_format($total_price, 2); ?></p>
        <p>Status: <?php echo htmlspecialchars($status); ?></p>
        <a href="index.php" class="btn btn-primary">Go to Home</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
