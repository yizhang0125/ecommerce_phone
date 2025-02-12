<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user's payments
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT p.*, o.total_price, o.order_date, p.created_at as payment_date
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE o.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - PhoneStore</title>
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

        .payments-container {
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

        .payment-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            border-left: 5px solid var(--primary);
        }

        .payment-card:hover {
            transform: translateY(-5px);
        }

        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .payment-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-group {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.3rem;
        }

        .info-value {
            font-weight: 500;
            color: var(--dark);
        }

        .card-number {
            font-family: monospace;
            letter-spacing: 2px;
            background: var(--light);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-block;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .status-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-failed {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .amount {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
        }

        .payment-date {
            color: #666;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .payment-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .payment-info {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="payments-container">
        <div class="page-header">
            <h1><i class="bi bi-credit-card me-2"></i>Payment History</h1>
            <p class="mb-0">View all your payment transactions</p>
        </div>

        <?php if (empty($payments)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cash-coin" style="font-size: 4rem; color: var(--primary);"></i>
                <h3 class="mt-3">No Payments Yet</h3>
                <p class="text-muted">You haven't made any payments yet.</p>
                <a href="index.php" class="btn btn-primary mt-3">Start Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($payments as $payment): ?>
                <div class="payment-card">
                    <div class="payment-header">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-credit-card-2-front fs-3 text-primary"></i>
                            <div>
                                <div class="card-number">
                                    **** <?php echo substr($payment['card_number'], -4); ?>
                                </div>
                            </div>
                        </div>
                        <div class="payment-date">
                            <i class="bi bi-calendar3"></i>
                            <?php echo date('F j, Y h:i A', strtotime($payment['created_at'])); ?>
                        </div>
                        <div class="status-badge status-<?php echo strtolower($payment['payment_status']); ?>">
                            <?php echo $payment['payment_status']; ?>
                        </div>
                    </div>

                    <div class="payment-info">
                        <div class="info-group">
                            <span class="info-label">Order ID</span>
                            <span class="info-value">
                                <a href="order_details.php?id=<?php echo $payment['order_id']; ?>" 
                                   class="text-decoration-none">
                                    #<?php echo $payment['order_id']; ?>
                                </a>
                            </span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Card Holder</span>
                            <span class="info-value"><?php echo htmlspecialchars($payment['card_holder']); ?></span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Card Expiry</span>
                            <span class="info-value"><?php echo htmlspecialchars($payment['exp_date']); ?></span>
                        </div>
                        <div class="info-group">
                            <span class="info-label">Amount</span>
                            <span class="amount">$<?php echo number_format($payment['total_price'], 2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
