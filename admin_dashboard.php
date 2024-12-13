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
    
    $success_message = isset($_SESSION['login_success']) ? $_SESSION['login_success'] : null;

    // Clear the success message after displaying it
    unset($_SESSION['login_success']);
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard</title>
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
    background-color: #007bff;
}

/* Main content styles */
.main-content {
    margin-left: 260px;
    padding: 20px;
}

/* Box Cards for Actions */
.action-box {
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    background-color: #f8f9fa;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    transition: all 0.3s ease;
}
.action-box:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    transform: translateY(-5px);
}
.action-box i {
    font-size: 50px;
    margin-bottom: 10px;
}
.action-box a {
    display: block;
    margin-top: 10px;
    font-weight: bold;
    text-decoration: none;
    color: #007bff;
}

/* Media Query for phones (up to 768px) */
@media (max-width: 768px) {
    .sidebar {
        display: block; /* Show sidebar */
    }

    .main-content {
        margin-left: 0; /* Full width for main content */
    }

    .sidebar-toggle {
    position: absolute;
    top: 10px;
    left: 10px;
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 8px; /* Minimal border-radius for a square shape */
    z-index: 1050;
}

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
        <a href="products_section.php"><i class="bi bi-box"></i> Products</a>
        <a href="orders_section.php"><i class="bi bi-cart-fill"></i> Orders</a>
        <a href="shipping_fees.php"><i class="bi bi-truck"></i> Shipping Fees</a>
        <a href="add_product.php"><i class="bi bi-plus-circle-fill"></i> Add Product</a>
        <a href="view_payment.php"><i class="bi bi-credit-card-fill"></i> View Payment</a>
        <a href="index.php" class="text-decoration-none"><i class="bi bi-globe"></i> View Website</a>
    </div>

    <!-- Main content -->
    <div class="main-content">
        <!-- Success Message (Placed in the main content area) -->
        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <h2>Welcome to the Admin Dashboard</h2>
        <p>Use the boxes below to manage products, orders, and other website settings.</p>

        <!-- Action Boxes -->
        <div class="row">
            <!-- Add Product Box -->
            <div class="col-md-3">
                <div class="action-box">
                    <i class="bi bi-plus-circle-fill text-success"></i>
                    <h4>Add Product</h4>
                    <a href="add_product.php">Go to Add Product</a>
                </div>
            </div>

            <!-- View Products Box -->
            <div class="col-md-3">
                <div class="action-box">
                    <i class="bi bi-boxes text-info"></i>
                    <h4>Products</h4>
                    <a href="products_section.php">Go to View Products</a>
                </div>
            </div>

            <!-- View Orders Box -->
            <div class="col-md-3">
                <div class="action-box">
                    <i class="bi bi-cart-fill text-primary"></i>
                    <h4>View Orders</h4>
                    <a href="orders_section.php">Go to Orders</a>
                </div>
            </div>

            <!-- Shipping Fees Box -->
            <div class="col-md-3">
                <div class="action-box">
                    <i class="bi bi-truck text-warning"></i>
                    <h4>Shipping Fees</h4>
                    <a href="shipping_fees.php">Manage Shipping Fees</a>
                </div>
            </div>

            <!-- View Website Box -->
            <div class="col-md-3">
                <div class="action-box">
                    <i class="bi bi-globe text-info"></i>
                    <h4>View Website</h4>
                    <a href="index.php" class="text-decoration-none">Go to View Website</a>
                </div>
            </div>

            <!-- View Payment Box -->
            <div class="col-md-3">
                <div class="action-box">
                    <i class="bi bi-credit-card-fill text-secondary"></i>
                    <h4>View Payment</h4>
                    <a href="view_payment.php">Go to View Payment</a>
                </div>
            </div>

            <!-- Today's Earnings Box -->
            <div class="col-md-3">
                <div class="action-box">
                    <i class="bi bi-cash-coin text-success"></i>
                    <h4>Today's Earnings</h4>
                    <a href="earn_money.php">Today's Earnings</a>
                </div>
            </div>

               <!-- Monthly Sales Chart Box -->
                <div class="col-md-3">
                    <div class="action-box">
                        <i class="bi bi-bar-chart-line text-primary"></i>
                        <h4>Monthly Sales</h4>
                        <a href="monthly_sales_chart.php">View Monthly Sales</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Toggle Button for Phones -->
    <button class="sidebar-toggle d-lg-none">☰</button>

    <!-- Bootstrap JS (Optional, for interactive components) -->
    <script>
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.style.left = sidebar.style.left === '0px' ? '-255px' : '0px';
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
