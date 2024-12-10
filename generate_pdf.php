<?php
session_start();
require 'db_connection.php';  // Include your database connection
require 'vendor/autoload.php'; // Ensure Dompdf is included using Composer's autoloader
use Dompdf\Dompdf;

if (!isset($_GET['order_id'])) {
    die("Order ID not provided.");
}

$order_id = $_GET['order_id'];

// Fetch the order details from the database
$stmt = $conn->prepare("SELECT o.total_price, o.status, o.shipping_address, o.shipping_city, o.shipping_state, o.shipping_zip, u.name 
                        FROM orders o
                        JOIN users u ON o.user_id = u.id
                        WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    die("Order not found.");
}

$order = $order_result->fetch_assoc();
$stmt->close();

// Fetch the shipping fee from the settings table (assuming it's stored there)
$stmt = $conn->prepare("SELECT shipping_fee FROM settings WHERE id = 1");
$stmt->execute();
$shipping_result = $stmt->get_result();
$shipping_fee = 0; // Default value for shipping fee
if ($shipping_result->num_rows > 0) {
    $shipping_fee = $shipping_result->fetch_assoc()['shipping_fee'];
}

// Fetch the order items
$stmt = $conn->prepare("SELECT p.name, oi.quantity, oi.price 
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items_result = $stmt->get_result();

// Start building the PDF content
$html = "
<html>
<head>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f1f1f1;
        }
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        h1 {
            font-size: 28px;
            text-align: center;
            color: #1e72b5;
            font-weight: bold;
            margin-bottom: 30px;
        }
        .header {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f1f1f1;
            padding-bottom: 15px;
        }
        .header .order-info {
            text-align: right;
            font-size: 14px;
            color: #666;
        }
        .order-info p {
            margin: 5px 0;
        }
        .receipt-details {
            margin-top: 20px;
            font-size: 16px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
        }
        .receipt-details p {
            margin: 12px 0;
        }
        .receipt-details strong {
            font-weight: bold;
            color: #333;
        }
        .product-table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }
        .product-table th, .product-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .product-table th {
            background-color: #f1f1f1;
            color: #333;
            font-size: 16px;
        }
        .product-table td {
            font-size: 14px;
            color: #555;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 12px;
            color: #aaa;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <div class='order-info'>
                <p><strong>Invoice No:</strong> #$order_id</p>
                <p><strong>Date:</strong> " . date('F j, Y') . "</p>
                <p><strong>Status:</strong> " . htmlspecialchars($order['status']) . "</p>
            </div>
        </div>

        <h1>Payment Receipt</h1>

        <div class='receipt-details'>
            <p><strong>Name:</strong> " . htmlspecialchars($order['name']) . "</p>
            <p><strong>Shipping Address:</strong> " . htmlspecialchars($order['shipping_address']) . ", " . htmlspecialchars($order['shipping_city']) . ", " . htmlspecialchars($order['shipping_state']) . " " . htmlspecialchars($order['shipping_zip']) . "</p>
            <p><strong>Payment Status:</strong> Successful</p>
        </div>

        <div class='product-table'>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>" . 
                // Loop through order items
                implode('', array_map(function($row) {
                    $total_price = $row['quantity'] * $row['price'];
                    return "<tr>
                            <td>" . htmlspecialchars($row['name']) . "</td>
                            <td>$" . number_format($row['price'], 2) . "</td>
                            <td>" . htmlspecialchars($row['quantity']) . "</td>
                            <td>$" . number_format($total_price, 2) . "</td>
                        </tr>";
                }, $order_items_result->fetch_all(MYSQLI_ASSOC))) . "
                    <tr>
                        <td><strong>Shipping Fee:</strong></td>
                        <td colspan='3' align='right'>$" . number_format($shipping_fee, 2) . "</td>
                    </tr>
                    <tr>
                        <td><strong>Subtotal:</strong></td>
                        <td colspan='3' align='right'>$" . number_format($order['total_price'], 2) . "</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class='footer'>
            <p>Thank you for shopping with us!</p>
            <p>For support, contact us at support@yourstore.com</p>
        </div>
    </div>

    <script>
        // Trigger PDF download and redirect to order_success.php
        window.onload = function() {
            // Trigger the download using PHP script (we assume the PHP script is triggered from the same page)
            window.location.href = 'download_pdf.php?order_id=" . $order_id . "';

            // Redirect after a short delay to order_success.php
            setTimeout(function() {
                window.location.href = 'order_success.php?order_id=" . $order_id . "'; 
            }, 2000);  // Redirect after 2 seconds
        }
    </script>
</body>
</html>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->render();

// Stream the PDF to the browser with automatic download
$dompdf->stream("payment_receipt_$order_id.pdf", array("Attachment" => 1));

// Close the database connection
$stmt->close();
$conn->close();
?>
