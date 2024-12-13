<?php
session_start();
require 'db_connection.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to the login page if not logged in
    header('Location: admin_login.php');
    exit();
}

// Fetch admin's name from session
$admin_name = $_SESSION['admin_name'];

// Fetch payment details from the database
$stmt = $conn->prepare("SELECT payments.id, payments.card_number, payments.card_holder, payments.exp_date, payments.payment_status, payments.created_at, orders.id AS order_id, users.name AS user_name 
                        FROM payments
                        JOIN orders ON payments.order_id = orders.id
                        JOIN users ON orders.user_id = users.id
                        ORDER BY payments.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Payment - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Navbar and Sidebar color */
        .navbar, .sidebar {
            background-color: #343a40; /* Same color for both navbar and sidebar */
        }

        .header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
        }

        .header-container .logo {
            width: 200px; /* Adjust width as needed */
            height: auto; /* Maintain aspect ratio */
            margin-right: 2px; /* Space between logo and heading */
        }

        .navbar {
            margin-bottom: 30px;
        }

        /* Sidebar Styles */
        .sidebar {
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding-top: 30px;
        }

        .sidebar a {
            color: #fff;
            padding: 12px 18px; /* Slightly larger padding for better spacing */
            text-decoration: none;
            display: block;
            margin: 8px 0; /* Adjusted margin for better spacing */
            border-radius: 5px;
            font-size: 16px; /* Slightly increased font size */
        }

        .sidebar a i {
            font-size: 20px; /* Slightly increased icon size */
            margin-right: 10px; /* Space between icon and text */
        }

        .sidebar a:hover {
            background-color: #007bff; /* Change background on hover */
        }

        .main-content {
            margin-left: 250px; /* Space for the sidebar */
            padding: 20px; /* Padding for main content */
            flex-grow: 1; /* Allow main content to grow */
        }

        .table th, .table td {
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
        <div class="d-flex align-items-center">
            <!-- Admin Name and Logout Button -->
            <span class="navbar-text me-3">
                <i class="bi bi-person-circle me-2"></i> <?php echo htmlspecialchars($admin_name); ?>
            </span>
            <a href="admin_logout.php" class="btn btn-transparent text-white">Logout</a>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar">
    <h4 class="text-white text-center">Admin Panel</h4>
    <a href="admin_dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a href="products.php"><i class="bi bi-box"></i> Products</a>
    <a href="orders.php"><i class="bi bi-cart-fill"></i> Orders</a>
    <a href="shipping_fees.php"><i class="bi bi-truck"></i> Shipping Fees</a>
    <a href="add_product.php"><i class="bi bi-plus-circle-fill"></i> Add Product</a>
    <a href="view_payment.php"><i class="bi bi-credit-card-fill"></i> View Payment</a>
    <a href="index.php" class="text-decoration-none"><i class="bi bi-globe"></i> View Website</a>
</div>

<!-- Main content -->
<div class="main-content">
    <h2>View Payment Details</h2>
    <p>Below are the details of all payments made by customers for their orders.</p>

    <!-- Payments Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Order ID</th>
                <th>User Name</th>
                <th>Card Number</th>
                <th>Card Holder</th>
                <th>Exp. Date</th>
                <th>Status</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                    <!-- Masking card number -->
                    <td><?php echo '**** **** **** ' . htmlspecialchars(substr($row['card_number'], -4)); ?></td>
                    <td><?php echo htmlspecialchars($row['card_holder']); ?></td>
                    <td><?php echo htmlspecialchars($row['exp_date']); ?></td>
                    <!-- Payment status with badge -->
                    <td><span class="badge <?php echo $row['payment_status'] === 'Completed' ? 'bg-success' : 'bg-warning'; ?>"><?php echo htmlspecialchars($row['payment_status']); ?></span></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php 
      // Free result set and close statement
      $result->free();
      $stmt->close();
      $conn->close();
    ?>
</div>

<!-- Bootstrap JS (Optional, for interactive components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html> 
