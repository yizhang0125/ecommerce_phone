<?php
session_start();
require 'db_connection.php';

// Define number of records per page (one date per page)
$records_per_page = 1; 

// Get the current page from the URL (default to 1 if not set)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Fetch distinct dates for pagination
$distinct_dates_query = $conn->prepare("SELECT DISTINCT DATE(order_date) as order_date FROM orders ORDER BY order_date DESC");
$distinct_dates_query->execute();
$distinct_dates_result = $distinct_dates_query->get_result();
$distinct_dates = $distinct_dates_result->fetch_all(MYSQLI_ASSOC);

// Calculate total pages based on distinct dates
$total_dates = count($distinct_dates);
$total_pages = ceil($total_dates / $records_per_page);

// Get the specific date for the current page
$current_date = $distinct_dates[$page - 1]['order_date'] ?? null;

if ($current_date) {
    // Fetch orders for the specific date
    $stmt = $conn->prepare("
        SELECT orders.id, orders.total_price, orders.order_date, orders.status, orders.shipping_status, users.name AS user_name 
        FROM orders 
        JOIN users ON orders.user_id = users.id
        WHERE DATE(order_date) = ?
        ORDER BY order_date DESC
    ");
    $stmt->bind_param("s", $current_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Collect orders for the current date
    $orders = $result->fetch_all(MYSQLI_ASSOC);
}

// Check if a success message is set in the URL
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>Orders</h2>

            <!-- Display success message if exists -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($current_date): ?>
                <h3 class="mt-4">Date: <?php echo htmlspecialchars($current_date); ?></h3>
                <?php foreach ($orders as $order): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Order ID: <?php echo htmlspecialchars($order['id']); ?></h5>
                            <p class="card-text">
                                Total Price: $<?php echo htmlspecialchars($order['total_price']); ?><br>
                                User: <?php echo htmlspecialchars($order['user_name']); ?><br>
                                Status: <span class="badge <?php echo $order['status'] === 'Shipped' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span><br>
                                <strong>Shipping Status:</strong> 
                                <span class="badge <?php echo $order['shipping_status'] === 'Shipped' ? 'bg-success' : 'bg-warning'; ?>">
                                    <?php echo htmlspecialchars($order['shipping_status']); ?>
                                </span>
                            </p>
                            <a href="view_order.php?id=<?php echo htmlspecialchars($order['id']); ?>" class="btn btn-info">View Details</a>

                            <?php if ($order['status'] !== 'Completed' && $order['status'] !== 'Shipped'): ?>
                                <a href="complete_order.php?id=<?php echo htmlspecialchars($order['id']); ?>&success=Order marked as completed!" class="btn btn-primary">Mark as Completed</a>
                            <?php endif; ?>

                            <?php if ($order['status'] !== 'Shipped'): ?>
                                <a href="ship_order.php?id=<?php echo htmlspecialchars($order['id']); ?>&success=Order has been shipped!" class="btn btn-success">Ship Order</a>
                            <?php endif; ?>

                            <form method="post" action="delete_order.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                <button type="submit" class="btn btn-danger">Delete Order</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No orders available for this page.</p>
            <?php endif; ?>

            <!-- Pagination -->
            <nav>
                <ul class="pagination">
                    <!-- Previous Page Link -->
                    <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>

                    <!-- Page Numbers -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next Page Link -->
                    <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
                        