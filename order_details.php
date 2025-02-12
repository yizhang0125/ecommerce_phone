<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: order_history.php');
    exit();
}

$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get order details with security check for user ownership
$stmt = $conn->prepare("
    SELECT o.*, 
           (SELECT payment_status FROM payments WHERE order_id = o.id LIMIT 1) as payment_status,
           (SELECT card_number FROM payments WHERE order_id = o.id LIMIT 1) as card_number,
           (SELECT shipping_fee FROM settings WHERE id = 1) as shipping_fee
    FROM orders o
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: order_history.php');
    exit();
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - PhoneStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --secondary: #6366f1;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f5f3ff;
            --dark: #1e1b4b;
            --surface: #ffffff;
            --border: #e5e7eb;
        }

        body {
            background-color: var(--light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        .details-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .details-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid var(--border);
        }

        .info-group h3 {
            font-size: 1rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .items-table th {
            background: var(--light);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .items-table td {
            padding: 1.5rem;
            background: white;
            vertical-align: middle;
        }

        .items-table tfoot td {
            padding: 1rem 1.5rem;
            background: var(--light);
        }

        .items-table tfoot tr:last-child td {
            font-size: 1.1rem;
            background: white;
            border-top: 2px solid var(--border);
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .product-cell span {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .back-btn {
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .back-btn:hover {
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="details-container">
        <a href="order_history.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>

        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">Order #<?php echo $order['id']; ?></h1>
                <div class="status-badge bg-<?php echo strtolower($order['status']); ?>">
                    <?php echo $order['status']; ?>
                </div>
            </div>
        </div>

        <div class="details-card">
            <div class="order-info">
                <div class="info-group">
                    <h3>Order Date</h3>
                    <p><?php echo date('F j, Y', strtotime($order['order_date'])); ?></p>
                </div>
                <div class="info-group">
                    <h3>Shipping Address</h3>
                    <p>
                        <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                        <?php echo htmlspecialchars($order['shipping_city']); ?>,<br>
                        <?php echo htmlspecialchars($order['shipping_state']); ?> 
                        <?php echo htmlspecialchars($order['shipping_zip']); ?>
                    </p>
                </div>
                <div class="info-group">
                    <h3>Payment Method</h3>
                    <p>
                        <i class="bi bi-credit-card me-2"></i>
                        Visa Card <?php echo $order['card_number'] ? '(**** ' . substr($order['card_number'], -4) . ')' : ''; ?>
                    </p>
                </div>
                <div class="info-group">
                    <h3>Shipping Status</h3>
                    <p><?php echo $order['shipping_status']; ?></p>
                </div>
            </div>

            <h2 class="mb-4">Order Items</h2>
            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="product-image">
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end">Subtotal</td>
                            <td class="text-end">$<?php echo number_format($order['total_price'] - $order['shipping_fee'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end">Shipping Fee</td>
                            <td class="text-end">$<?php echo number_format($order['shipping_fee'], 2); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Grand Total</strong></td>
                            <td class="text-end"><strong>$<?php echo number_format($order['total_price'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
