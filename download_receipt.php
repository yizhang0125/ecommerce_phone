<?php
session_start();
require 'db_connection.php';
require 'vendor/autoload.php'; // Include Dompdf via Composer

use Dompdf\Dompdf;
use Dompdf\Options;

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

// Initialize Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$pdf = new Dompdf($options);

// Create HTML content for the receipt with improved design and layout
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Order #' . $order['order_id'] . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
            background-color: #f4f4f4;
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
            text-align: center;
            color: #333;
        }
        .card {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .card-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }
        .card-body {
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            text-align: left;
            padding: 12px;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f4f4f4;
        }
        .total {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            margin-top: 30px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #555;
            font-size: 14px;
        }
        .badge-completed {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .badge-pending {
            background-color: #ffc107;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Receipt for Order #' . $order['order_id'] . '</h1>
        
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Order Information</h5>
                <p><strong>Order Date:</strong> ' . $order['order_date'] . '</p>
                <p><strong>Payment Status:</strong> <span class="badge ' . ($order['payment_status'] === 'Completed' ? 'badge-completed' : 'badge-pending') . '">' . $order['payment_status'] . '</span></p>
                <p><strong>Payment Date:</strong> ' . $order['created_at'] . '</p>
                <p><strong>Cardholder:</strong> ' . $order['card_holder'] . '</p>
                <p><strong>Shipping Fee:</strong> $' . number_format($shipping_fee, 2) . '</p>
            </div>
        </div>

        <h3>Ordered Products</h3>
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>';

$total_price = 0;
do {
    $product_total = $order['quantity'] * $order['product_price'];
    $total_price += $product_total;
    $html .= '
    <tr>
        <td>' . $order['product_name'] . '</td>
        <td>' . $order['quantity'] . '</td>
        <td>$' . number_format($order['product_price'], 2) . '</td>
        <td>$' . number_format($product_total, 2) . '</td>
    </tr>';
} while ($order = $result->fetch_assoc());

$html .= '</tbody></table>';

$html .= '
        <div class="total">
            <p><strong>Total Price (Including Shipping):</strong> $' . number_format($total_price + $shipping_fee, 2) . '</p>
        </div>

        <div class="footer">
            <p>Thank you for your order! We hope to serve you again soon.</p>
        </div>
    </div>
</body>
</html>';

$pdf->loadHtml($html);

// (Optional) Set paper size
$pdf->setPaper('A4', 'portrait');

// Render the PDF
$pdf->render();

// Output the generated PDF (force download)
$pdf->stream('receipt_order_' . $order_id . '.pdf', array('Attachment' => 1));

// Close the database connection
$stmt->close();
$conn->close();
?>
