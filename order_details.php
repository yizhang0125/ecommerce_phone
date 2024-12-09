<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['id'])) {
    die("Order ID is required.");
}

$order_id = $_GET['id'];

// Fetch order details
$stmt = $conn->prepare("SELECT orders.id, orders.total_price, orders.order_date, orders.status, users.name AS user_name
                        FROM orders 
                        JOIN users ON orders.user_id = users.id
                        WHERE orders.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    die("Order not found.");
}

$order = $order_result->fetch_assoc();

// Fetch ordered products
$stmt = $conn->prepare("SELECT products.name, order_items.quantity, order_items.price 
                        FROM order_items 
                        JOIN products ON order_items.product_id = products.id 
                        WHERE order_items.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Order Details</h1>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Order ID: <?php echo htmlspecialchars($order['id']); ?></h5>
                <p class="card-text">
                    Total Price: $<?php echo htmlspecialchars($order['total_price']); ?><br>
                    Order Date: <?php echo htmlspecialchars($order['order_date']); ?><br>
                    Status: <span class="badge <?php echo $order['status'] === 'Shipped' ? 'bg-success' : 'bg-warning'; ?>">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span><br>
                    User: <?php echo htmlspecialchars($order['user_name']); ?>
                </p>
            </div>
        </div>

        <h3 class="mt-4">Ordered Products</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>$<?php echo htmlspecialchars($item['price']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS (Optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
