<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['order_id'])) {
    die("Order ID not provided.");
}

$order_id = $_GET['order_id'];

// Fetch order details from the database
$stmt = $conn->prepare("
    SELECT o.*, p.card_number, p.payment_status 
    FROM orders o 
    LEFT JOIN payments p ON o.id = p.order_id 
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found.");
}

// Generate the PDF link
$pdf_link = "generate_pdf.php?order_id=" . urlencode($order_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - PhoneStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --secondary: #6366f1;
            --success: #22c55e;
            --light: #f5f3ff;
            --dark: #1e1b4b;
            --surface: #ffffff;
            --border: #e5e7eb;
            --shadow: rgba(67, 56, 202, 0.1);
        }

        body {
            background: linear-gradient(135deg, #f5f3ff 0%, #ffffff 100%);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Add animated background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.1), transparent 70%);
            z-index: -1;
        }

        .success-container {
            max-width: 800px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px var(--shadow);
            overflow: hidden;
            position: relative;
        }

        .success-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 3rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .success-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.2), transparent 70%);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
            box-shadow: 0 15px 30px rgba(34, 197, 94, 0.3);
            animation: scaleIn 0.5s ease-out;
            position: relative;
            z-index: 1;
        }

        .success-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            animation: pulse 2s infinite;
        }

        .success-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            animation: fadeInUp 0.5s ease-out 0.2s both;
        }

        .success-subtitle {
            opacity: 0.9;
            animation: fadeInUp 0.5s ease-out 0.3s both;
        }

        .success-content {
            padding: 2rem;
        }

        .order-details {
            background: rgba(245, 243, 255, 0.5);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(5px);
            border: 1px solid var(--border);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-row:hover {
            background: rgba(255, 255, 255, 0.5);
            transform: translateX(5px);
            padding: 1rem;
            margin: 0 -1rem;
            border-radius: 8px;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            font-weight: 600;
            color: var(--dark);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-action {
            flex: 1;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .btn-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        .btn-action:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
        }

        .btn-success {
            background: var(--success);
            border: none;
            color: white;
        }

        .btn-outline {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }

        .pdf-generating {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 8px 16px var(--shadow);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideIn 0.3s ease-out;
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
        }

        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @keyframes fadeInUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0; }
            100% { transform: scale(1); opacity: 0; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .success-container {
                margin: 1rem;
            }

            .success-header {
                padding: 2rem 1.5rem;
            }

            .success-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }

            .success-title {
                font-size: 1.75rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h1 class="success-title">Payment Successful!</h1>
            <p class="success-subtitle">Your order has been confirmed</p>
        </div>

        <div class="success-content">
            <div class="order-details">
                <div class="detail-row">
                    <span class="detail-label">Order Number</span>
                    <span class="detail-value">#<?php echo $order_id; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method</span>
                    <span class="detail-value">
                        <i class="bi bi-credit-card me-2"></i>
                        Visa Card (**** <?php echo substr($order['card_number'], -4); ?>)
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount Paid</span>
                    <span class="detail-value">$<?php echo number_format($order['total_price'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="<?php echo $pdf_link; ?>" class="btn-action btn-success" id="downloadBtn">
                    <i class="bi bi-file-pdf"></i>
                    Download Receipt
                </a>
                <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn-action btn-primary">
                    <i class="bi bi-eye"></i>
                    View Order Details
                </a>
                <a href="index.php" class="btn-action btn-outline">
                    <i class="bi bi-shop"></i>
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>

    <div class="pdf-generating" id="pdfNotification" style="display: none;">
        <i class="bi bi-file-pdf text-primary"></i>
        Generating your receipt...
    </div>

    <script>
        // Show PDF generation notification
        window.onload = function() {
            document.getElementById('pdfNotification').style.display = 'flex';
            
            // Trigger PDF download
            setTimeout(function() {
                window.location.href = "<?php echo $pdf_link; ?>";
                
                // Hide notification after download starts
                setTimeout(function() {
                    document.getElementById('pdfNotification').style.display = 'none';
                }, 1000);
            }, 1500);
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
