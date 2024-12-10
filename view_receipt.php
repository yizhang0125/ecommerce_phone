<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['order_id'])) {
    die("Order ID is required.");
}

$order_id = $_GET['order_id'];

// Fetch the order details including products and payment
$stmt = $conn->prepare("
    SELECT o.id AS order_id, o.total_price, o.order_date, p.payment_status, p.created_at, p.card_holder, oi.quantity, pr.name AS product_name, oi.price AS product_price
    FROM orders o
    JOIN payments p ON o.id = p.order_id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products pr ON oi.product_id = pr.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the shipping fee from settings
$settings_result = $conn->query("SELECT shipping_fee FROM settings LIMIT 1");
$settings = $settings_result->fetch_assoc();
$shipping_fee = $settings ? $settings['shipping_fee'] : 0.00; // Default to 0 if no shipping fee is set

// Check if the order exists
if ($result->num_rows === 0) {
    die("Order not found.");
}

$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Order #<?php echo htmlspecialchars($order['order_id']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
        }
        h1 {
            font-size: 28px;
            color: #333;
        }
        .card {
            margin-bottom: 30px;
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .card-body {
            padding: 20px;
        }
        .card-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }
        table th, table td {
            text-align: left;
            padding: 12px;
            font-size: 16px;
        }
        table th {
            background-color: #f1f1f1;
        }
        .total {
            font-size: 20px;
            font-weight: bold;
            text-align: right;
            padding-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #555;
            font-size: 14px;
        }
        .badge-completed {
            background-color: #28a745;
        }
        .badge-pending {
            background-color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-5">Receipt for Order #<?php echo htmlspecialchars($order['order_id']); ?></h1>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Order Information</h5>
                <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['order_date']); ?></p>
                <p><strong>Payment Status:</strong> <span class="badge <?php echo ($order['payment_status'] === 'Completed' ? 'badge-completed' : 'badge-pending'); ?>"><?php echo htmlspecialchars($order['payment_status']); ?></span></p>
                <p><strong>Payment Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                <p><strong>Cardholder:</strong> <?php echo htmlspecialchars($order['card_holder']); ?></p>
                <p><strong>Shipping Fee:</strong> $<?php echo number_format($shipping_fee, 2); ?></p>
            </div>
        </div>

        <h3 class="mt-4">Ordered Products</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_price = 0;
                // Loop to show all products in the order
                do {
                    $product_total = $order['quantity'] * $order['product_price'];
                    $total_price += $product_total;
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($order['product_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($order['quantity']) . "</td>";
                    echo "<td>$" . number_format($order['product_price'], 2) . "</td>";
                    echo "<td>$" . number_format($product_total, 2) . "</td>";
                    echo "</tr>";
                } while ($order = $result->fetch_assoc());
                ?>
            </tbody>
        </table>

        <div class="total">
            <p><strong>Total Price (Including Shipping):</strong> $<?php echo number_format($total_price + $shipping_fee, 2); ?></p>
        </div>

        <div class="footer">
            <p>Thank you for your order! We hope to serve you again soon.</p>
        </div>

        <div class="text-center mt-4">
            <a href="download_receipt.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">Download Receipt (PDF)</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>
