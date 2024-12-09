<?php
session_start();
require 'db_connection.php';

// Fetch orders with status 'Awaiting Shipment'
$stmt = $conn->prepare("SELECT orders.id, orders.total_price, orders.order_date, users.name 
                        FROM orders 
                        JOIN users ON orders.user_id = users.id 
                        WHERE orders.status = 'Awaiting Shipment' 
                        ORDER BY orders.order_date DESC");
if (!$stmt) {
    die("Failed to prepare statement: " . $conn->error);
}
if (!$stmt->execute()) {
    die("Failed to execute statement: " . $stmt->error);
}
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Awaiting Shipment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Orders Awaiting Shipment</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total Price</th>
                    <th>Order Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>$<?php echo number_format($row['total_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                        <td>
                            <a href="view_order.php?order_id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">View</a>
                            <!-- Add more actions if needed -->
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No orders awaiting shipment.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
