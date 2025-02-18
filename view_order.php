<?php
session_start();
require 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Get order details
$order_id = $_GET['id'];
$stmt = $conn->prepare("
    SELECT o.*, 
           u.name as customer_name,
           u.email as customer_email,
           p.payment_status,
           p.card_number,
           COALESCE(s.shipping_fee, 0) as shipping_fee
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN payments p ON o.id = p.order_id
    LEFT JOIN settings s ON s.id = 1
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .order-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .order-items {
            border-collapse: separate;
            border-spacing: 0 1rem;
            width: 100%;
        }

        .order-items td {
            padding: 1rem;
            vertical-align: middle;
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
            border-radius: 10px;
            background: #f8fafc;
            padding: 0.5rem;
        }

        .customer-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            padding: 2rem;
            background: var(--light);
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .info-group h3 {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-group p {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--dark);
            margin: 0;
        }

        .timeline {
            position: relative;
            padding: 2rem;
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 2rem;
            border-left: 2px solid var(--border);
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: var(--primary);
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-date {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .timeline-content {
            font-weight: 500;
            color: var(--dark);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border: none;
            border-radius: 10px;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .back-button:hover {
            transform: translateX(-5px);
            color: var(--secondary);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-action {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container py-4">
        <a href="orders.php" class="back-button">
            <i class="bi bi-arrow-left"></i>
            Back to Orders
        </a>

        <div class="order-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-0">Order #<?php echo $order_id; ?></h1>
                    <p class="mb-0">Placed on <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></p>
                </div>
                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                    <?php echo $order['status']; ?>
                </span>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="order-card">
                    <h2 class="mb-4">Order Items</h2>
                    <table class="order-items">
                        <tbody>
                            <?php while ($item = $order_items->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="product-image">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h5>
                                                <p class="mb-0 text-muted">Quantity: <?php echo $item['quantity']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                        <small class="text-muted">$<?php echo number_format($item['price'], 2); ?> each</small>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-end">Subtotal:</td>
                                <td class="text-end fw-bold">$<?php echo number_format($order['total_price'] - ($order['shipping_fee'] ?? 0), 2); ?></td>
                            </tr>
                            <tr>
                                <td class="text-end">Shipping:</td>
                                <td class="text-end fw-bold">$<?php echo number_format($order['shipping_fee'] ?? 0, 2); ?></td>
                            </tr>
                            <tr>
                                <td class="text-end"><h4 class="mb-0">Total:</h4></td>
                                <td class="text-end">
                                    <h4 class="mb-0">$<?php echo number_format($order['total_price'], 2); ?></h4>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="col-md-4">
                <div class="order-card">
                    <h2 class="mb-4">Customer Details</h2>
                    <div class="customer-info">
                        <div class="info-group">
                            <h3>Customer Name</h3>
                            <p><?php echo htmlspecialchars($order['customer_name']); ?></p>
                        </div>
                        <div class="info-group">
                            <h3>Email</h3>
                            <p><?php echo htmlspecialchars($order['customer_email']); ?></p>
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
                                Visa Card (**** <?php echo substr($order['card_number'], -4); ?>)
                            </p>
                        </div>
                    </div>

                    <div class="timeline">
                        <h3 class="mb-4">Order Timeline</h3>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></div>
                            <div class="timeline-content">Order Placed</div>
                        </div>
                        <?php if ($order['payment_status'] === 'completed'): ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo date('M j, Y g:i A', strtotime($order['payment_date'])); ?></div>
                            <div class="timeline-content">Payment Completed</div>
                        </div>
                        <?php endif; ?>
                        <?php if ($order['shipping_status'] === 'shipped'): ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo date('M j, Y g:i A', strtotime($order['shipping_date'])); ?></div>
                            <div class="timeline-content">Order Shipped</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
