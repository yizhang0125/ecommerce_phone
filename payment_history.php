<?php
session_start();
require 'db_connection.php';

$user_id = $_SESSION['user_id']; // Assuming user is logged in and user_id is stored in session

// Define the number of items per page (10 orders per page)
$orders_per_page = 15;

// Get the current page number (default to 1 if not set)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $orders_per_page;

// Fetch the total number of records for the user to calculate total pages
$count_stmt = $conn->prepare("SELECT COUNT(DISTINCT o.id) AS total FROM payments p
                              JOIN orders o ON p.order_id = o.id
                              WHERE o.user_id = ?");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_orders = $count_result->fetch_assoc()['total'];

// Calculate total number of pages
$total_pages = ceil($total_orders / $orders_per_page);

// Fetch payment details for the current page
$stmt = $conn->prepare("
    SELECT p.id AS payment_id, p.order_id, o.total_price, p.payment_status, p.created_at, p.card_holder, oi.quantity, pr.name AS product_name, oi.price AS product_price
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products pr ON oi.product_id = pr.id
    WHERE o.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $orders_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the shipping fee from the settings
$settings_result = $conn->query("SELECT shipping_fee FROM settings LIMIT 1");
$settings = $settings_result->fetch_assoc();
$shipping_fee = $settings ? $settings['shipping_fee'] : 0.00; // Default to 0 if no shipping fee is set
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History with Products</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
            position: relative;
        }
        .payment-card-header {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-radius: 8px 8px 0 0;
        }
        .badge-completed {
            background-color: #28a745;
        }
        .badge-pending {
            background-color: #ffc107;
        }
        .payment-total {
            font-weight: bold;
            font-size: 18px;
        }
        .shipping-fee {
            font-weight: bold;
            font-size: 16px;
            color: #007bff;
        }
        /* Center the View Receipt button at the bottom */
        .payment-card-footer {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-5">Payment History with Products</h1>

        <?php
        // Loop through the results and display each order in a card
        $current_order_id = null;
        $total_order_price = 0;
        $order_button_displayed = false; // Flag to track if the "View Receipt" button has been displayed

        while ($row = $result->fetch_assoc()) {
            // If we're at the start of a new order, display its details (payment, total price)
            if ($row['order_id'] !== $current_order_id) {
                if ($current_order_id !== null) {
                    // Display total for the last order (including shipping fee)
                    $total_order_with_shipping = $total_order_price + $shipping_fee; // Include shipping fee in total
                    echo "<div class='payment-total text-end'>Total: $" . number_format($total_order_with_shipping, 2) . "</div>";
                    echo "<div class='shipping-fee text-end'><strong>Shipping Fee:</strong> $" . number_format($shipping_fee, 2) . "</div>";
                    echo "</div>"; // Close previous card
                }
                $current_order_id = $row['order_id'];
                $total_order_price = 0;  // Reset total for the new order
                $order_button_displayed = false; // Reset flag for the new order
                
                // Start a new payment card
                echo "<div class='payment-card'>";
                echo "<div class='payment-card-header'>Order ID: " . htmlspecialchars($row['order_id']) . "</div>";
                echo "<div class='mb-3'><strong>Payment ID:</strong> " . htmlspecialchars($row['payment_id']) . "</div>";
                echo "<div class='mb-3'><strong>Status:</strong> <span class='badge " . ($row['payment_status'] === 'Completed' ? 'badge-completed' : 'badge-pending') . "'>" . htmlspecialchars($row['payment_status']) . "</span></div>";
                echo "<div class='mb-3'><strong>Payment Date:</strong> " . htmlspecialchars($row['created_at']) . "</div>";
                echo "<div class='mb-3'><strong>Cardholder:</strong> " . htmlspecialchars($row['card_holder']) . "</div>"; // Show the cardholder name
            }
            
            // Calculate the total for this product (quantity * price) and add it to the order total
            $product_total = $row['quantity'] * $row['product_price'];
            $total_order_price += $product_total;
            ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Product:</strong> <?php echo htmlspecialchars($row['product_name']); ?>
                </div>
                <div class="col-md-2">
                    <strong>Quantity:</strong> <?php echo htmlspecialchars($row['quantity']); ?>
                </div>
                <div class="col-md-2">
                    <strong>Price:</strong> $<?php echo number_format($row['product_price'], 2); ?>
                </div>
                <div class="col-md-2">
                    <strong>Total:</strong> $<?php echo number_format($product_total, 2); ?>
                </div>
            </div>

            <!-- Display View Receipt button only once per order -->
            <?php
            if (!$order_button_displayed) {
                echo "<div class='payment-card-footer'>";
                echo "<a href='view_receipt.php?order_id=" . htmlspecialchars($row['order_id']) . "' class='btn btn-primary'>View Receipt</a>";
                echo "</div>";
                $order_button_displayed = true; // Set flag to true after the button has been displayed
            }
        } ?>

        <!-- Final total for the last order (including shipping fee) -->
        <?php if ($current_order_id !== null) {
            $total_order_with_shipping = $total_order_price + $shipping_fee; // Include shipping fee in total
            echo "<div class='payment-total text-end'>Total: $" . number_format($total_order_with_shipping, 2) . "</div>";
            echo "<div class='shipping-fee text-end'><strong>Shipping Fee:</strong> $" . number_format($shipping_fee, 2) . "</div>";
            echo "</div>"; // Close the last card
        } ?>

        <!-- Pagination Links -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Bootstrap JS (Optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$stmt->close();
$count_stmt->close();
$conn->close();
?>
