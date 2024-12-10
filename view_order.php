<?php
session_start();
require 'db_connection.php';

// Check if an order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid order ID.");
}

$order_id = $_GET['id'];

// Fetch order details
$stmt = $conn->prepare("SELECT orders.id, orders.total_price, orders.order_date, orders.shipping_address, orders.shipping_city, orders.shipping_state, orders.shipping_zip, orders.shipping_status, orders.status AS order_status, users.name AS user_name
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

// Fetch order items
$stmt = $conn->prepare("SELECT order_items.product_id, products.name, order_items.quantity, products.price
                        FROM order_items
                        JOIN products ON order_items.product_id = products.id
                        WHERE order_items.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();

// Fetch shipping fee from settings (assuming it's stored in a settings table)
$settings_result = $conn->query("SELECT shipping_fee FROM settings LIMIT 1");
$settings = $settings_result->fetch_assoc();
$shipping_fee = $settings ? $settings['shipping_fee'] : 0.00; // Default to 0 if no shipping fee is set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Order Details</h1>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Order ID: <?php echo htmlspecialchars($order['id']); ?></h5>
                <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>
                <p><strong>Total Price:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
                <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                <p><strong>City:</strong> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
                <p><strong>State:</strong> <?php echo htmlspecialchars($order['shipping_state']); ?></p>
                <p><strong>ZIP Code:</strong> <?php echo htmlspecialchars($order['shipping_zip']); ?></p>
                
                <!-- Display Shipping Status with color -->
                <p><strong>Shipping Status:</strong> 
                    <span class="badge 
                        <?php
                        // Apply colors based on shipping status
                        switch ($order['shipping_status']) {
                            case 'Shipped':
                                echo 'bg-success';
                                break;
                            case 'Pending':
                                echo 'bg-warning';
                                break;
                            case 'Cancelled':
                                echo 'bg-danger';
                                break;
                            case 'Processing':
                                echo 'bg-info';
                                break;
                            default:
                                echo 'bg-secondary';
                        }
                        ?>">
                        <?php echo htmlspecialchars($order['shipping_status']); ?>
                    </span>
                </p>

                <p><strong>Order Status:</strong> 
                    <span class="badge <?php echo $order['order_status'] === 'Completed' ? 'bg-success' : ($order['order_status'] === 'Shipped' ? 'bg-primary' : 'bg-warning'); ?>">
                        <?php echo htmlspecialchars($order['order_status']); ?>
                    </span>
                </p>

                <p><strong>Shipping Fee:</strong> $<?php echo number_format($shipping_fee, 2); ?></p> <!-- Display shipping fee -->
            </div>
        </div>

        <h2 class="mb-4">Order Items</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $order_total = 0;
                while ($item = $items_result->fetch_assoc()): 
                    $item_total = $item['quantity'] * $item['price'];
                    $order_total += $item_total;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                    <td>$<?php echo number_format($item_total, 2); ?></td>
                </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="3" class="text-end"><strong>Order Total (Excluding Shipping)</strong></td>
                    <td>$<?php echo number_format($order_total, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end"><strong>Shipping Fee</strong></td>
                    <td>$<?php echo number_format($shipping_fee, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total Price (Including Shipping)</strong></td>
                    <td>$<?php echo number_format($order_total + $shipping_fee, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <a href="admin_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
