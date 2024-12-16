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
            height: 100vh; /* Full viewport height */
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            padding-top: 30px;
            background-color: #343a40; /* Sidebar color */
            display: flex;
            flex-direction: column; /* Align items vertically */
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
            height: 100vh; /* Ensure main content fills the viewport */
            overflow-y: auto; /* Enable scrolling for main content */
        }

        .table th, .table td {
            text-align: center;
        }

        /* Media Query for phones (up to 768px) */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed; /* Fixed sidebar for mobile */
                top: 0;
                left: -250px; /* Start off-screen to the left */
                width: 250px; /* Sidebar width */
                background-color: #343a40; /* Sidebar background color */
                padding-top: 30px;
                height: 100vh; /* Ensure sidebar occupies full height */
                transition: left 0.3s ease-in-out; /* Smooth animation */
                z-index: 1050; /* Ensure it's above other content */
                display: flex;
                flex-direction: column; /* Align items vertically */
            }

            .sidebar.active {
                left: 0; /* Move sidebar into view */
            }

            .sidebar a {
                color: #fff;
                padding: 12px 18px;
                text-decoration: none;
                display: block;
                margin: 8px 0;
                border-radius: 5px;
                font-size: 16px;
            }

            .sidebar a i {
                font-size: 20px;
                margin-right: 10px;
            }

            .sidebar a:hover {
                background-color: #007bff;
            }

            .main-content {
                margin-left: 0; /* Remove sidebar margin for mobile */
                height: 100vh; /* Ensure main content fills the viewport */
                padding: 10px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow-y: auto; /* Enable scrolling for main content */
            }

/* Sidebar Toggle Button for Phones */
.sidebar-toggle {
    display: block;
    position: fixed;
    top: 10px;
    left: 10px;
    z-index: 1050;
    width: 30px; /* Smaller width */
    height: 30px; /* Smaller height */
    padding: 0;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px; /* Smaller icon size */
    cursor: pointer;
}


            .sidebar-toggle:hover {
                background-color: #0056b3; /* Darker blue on hover */
            }
        }

    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid justify-content-end">
        <!-- Admin Name and Logout Button -->
        <span class="navbar-text me-3">
            <i class="bi bi-person-circle me-2"></i> <?php echo htmlspecialchars($admin_name); ?>
        </span>
        <a href="admin_logout.php" class="btn btn-transparent text-white">Logout</a>
    </div>
</nav>


<!-- Sidebar -->
<div class="sidebar">
    <h4 class="text-white text-center">Admin Panel</h4>
    <a href="admin_dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
    <a href="products_section.php"><i class="bi bi-box"></i> Products</a>
    <a href="orders_section.php"><i class="bi bi-cart-fill"></i> Orders</a>
    <a href="shipping_fees.php"><i class="bi bi-truck"></i> Shipping Fees</a>
    <a href="add_product.php"><i class="bi bi-plus-circle-fill"></i> Add Product</a>
    <a href="view_payment.php"><i class="bi bi-credit-card-fill"></i> View Payment</a>
    <a href="index.php" class="text-decoration-none"><i class="bi bi-globe"></i> View Website</a>
</div>

<!-- Sidebar Toggle Button for Phones -->
<button class="sidebar-toggle d-lg-none">☰</button>

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
<script>
    // Sidebar toggle functionality
    document.querySelector('.sidebar-toggle').addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
    });

    // Sticky sidebar toggle button
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    window.addEventListener('scroll', function() {
        if (window.innerWidth <= 768) {
            if (window.scrollY > 10) {
                sidebarToggle.classList.add('sticky');
            } else {
                sidebarToggle.classList.remove('sticky');
            }
        }
    });
</script>

</body>
</html>
