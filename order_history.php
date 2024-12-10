<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION['user_id']; // Assuming user is logged in and user_id is stored in session

// Fetch the most recent 5 orders
$stmt = $conn->prepare("SELECT orders.id, orders.total_price, orders.order_date, orders.status, orders.shipping_status
                        FROM orders 
                        WHERE orders.user_id = ? 
                        ORDER BY orders.order_date DESC 
                        LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch older orders that are archived
$stmt_old = $conn->prepare("SELECT orders.id, orders.total_price, orders.order_date, orders.status, orders.shipping_status 
                            FROM orders 
                            WHERE orders.user_id = ? 
                            ORDER BY orders.order_date DESC 
                            LIMIT 5, 999999"); // Fetch older orders
$stmt_old->bind_param("i", $user_id);
$stmt_old->execute();
$old_orders_result = $stmt_old->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Order History</h1>

        <div class="row">
            <div class="col-md-12">
                <h2>Recent Orders</h2>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Total Price</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Shipping Status</th> <!-- Added column for shipping status -->
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td>$<?php echo number_format($row['total_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['status'] === 'Shipped' ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $row['shipping_status'] === 'Delivered' ? 'bg-success' : ($row['shipping_status'] === 'Pending' ? 'bg-warning' : 'bg-danger'); ?>">
                                        <?php echo htmlspecialchars($row['shipping_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_details.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-info">View Details</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Button to show older orders -->
                <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#older-orders">Show Older Orders</button>

                <!-- Collapsible section for older orders -->
                <div id="older-orders" class="collapse mt-4">
                    <h3>Archived Orders</h3>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Total Price</th>
                                <th>Order Date</th>
                                <th>Status</th>
                                <th>Shipping Status</th> <!-- Added column for shipping status -->
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $old_orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td>$<?php echo number_format($row['total_price'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['order_date']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $row['status'] === 'Shipped' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $row['shipping_status'] === 'Delivered' ? 'bg-success' : ($row['shipping_status'] === 'Pending' ? 'bg-warning' : 'bg-danger'); ?>">
                                            <?php echo htmlspecialchars($row['shipping_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-info">View Details</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (Optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$stmt->close();
$stmt_old->close();
$conn->close();
?>
