<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user's orders
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT 
        o.*,
        (SELECT payment_status FROM payments WHERE order_id = o.id LIMIT 1) as payment_status,
        (SELECT card_number FROM payments WHERE order_id = o.id LIMIT 1) as card_number,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as total_items
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - PhoneStore</title>
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

        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .order-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background-color: #e0e7ff;
            color: #3730a3;
        }

        .status-completed {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.3rem;
        }

        .detail-value {
            font-weight: 500;
            color: var(--dark);
        }

        .order-total {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
            text-align: right;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--border);
        }

        .payment-info {
            background-color: var(--light);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .card-number {
            font-family: monospace;
            letter-spacing: 2px;
        }

        @media (max-width: 768px) {
            .orders-container {
                margin: 1rem auto;
            }

            .order-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .order-details {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="orders-container">
        <div class="page-header">
            <h1><i class="bi bi-clock-history me-2"></i>Order History</h1>
            <p class="mb-0">View and track all your orders</p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-x" style="font-size: 4rem; color: var(--primary);"></i>
                <h3 class="mt-3">No Orders Yet</h3>
                <p class="text-muted">Start shopping to see your orders here!</p>
                <a href="index.php" class="btn btn-primary mt-3">Shop Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-number">
                            Order #<?php echo htmlspecialchars($order['id']); ?>
                        </div>
                        <div class="order-date">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?php echo date('F j, Y', strtotime($order['order_date'])); ?>
                        </div>
                        <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </div>
                    </div>

                    <div class="order-details">
                        <div class="detail-group">
                            <span class="detail-label">Items</span>
                            <span class="detail-value"><?php echo $order['total_items']; ?> items</span>
                        </div>
                        <div class="detail-group">
                            <span class="detail-label">Shipping Address</span>
                            <span class="detail-value">
                                <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_city']); ?>, 
                                <?php echo htmlspecialchars($order['shipping_state']); ?> 
                                <?php echo htmlspecialchars($order['shipping_zip']); ?>
                            </span>
                        </div>
                        <?php if ($order['payment_status']): ?>
                            <div class="detail-group">
                                <span class="detail-label">Payment Method</span>
                                <span class="detail-value">
                                    <i class="bi bi-credit-card me-1"></i>
                                    Visa Card
                                    <div class="card-number">
                                        **** <?php echo substr($order['card_number'], -4); ?>
                                    </div>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="order-total">
                        Total: $<?php echo number_format($order['total_price'], 2); ?>
                    </div>
                    <div class="text-end mt-3">
                        <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                           class="btn btn-primary">
                            <i class="bi bi-eye me-2"></i>View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
