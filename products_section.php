<?php
session_start();
require 'db_connection.php';

// Fetch products from the database
$stmt = $conn->prepare("SELECT products.id, products.name, products.price, products.stock_quantity, categories.name AS category_name 
                        FROM products 
                        JOIN categories ON products.category_id = categories.id");
$stmt->execute();
$result = $stmt->get_result();

$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
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
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 30px;
            transition: left 0.3s ease;
            z-index: 1050; /* Ensure it's above other content */
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
                left: -255px; /* Hidden initially */
            }

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

            .sidebar-toggle.sticky {
                position: fixed;
                top: 10px;
                left: 10px;
                z-index: 1050;
            }

            .main-content {
                margin-left: 0; /* Full width for main content */
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4 class="text-white text-center">Admin Panel</h4>
    <a href="admin_dashboard.php">
        <i class="bi bi-house-door"></i> Dashboard
    </a>
    <a href="products_section.php">
        <i class="bi bi-box"></i> Products
    </a>
    <a href="orders_section.php">
        <i class="bi bi-cart-fill"></i> Orders
    </a>
    <a href="shipping_fees.php">
        <i class="bi bi-truck"></i> Shipping Fees
    </a>
    <a href="add_product.php">
        <i class="bi bi-plus-circle-fill"></i> Add Product
    </a>
    <a href="view_payment.php">
        <i class="bi bi-credit-card-fill"></i> View Payment
    </a>
    <a href="index.php" class="text-decoration-none">
        <i class="bi bi-globe"></i> View Website
    </a>
</div>

<!-- Sidebar Toggle Button for Phones -->
<button class="sidebar-toggle d-lg-none">☰</button>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <!-- Admin Name and Logout Button -->
        <div class="ms-auto d-flex align-items-center">
            <span class="navbar-text me-3">
                <i class="bi bi-person-circle me-2"></i> <?php echo htmlspecialchars($admin_name); ?>
            </span>
            <a href="admin_logout.php" class="btn btn-transparent text-white">Logout</a>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="main-content">
    <h2>Products</h2>

    <!-- Add Product Button -->
    <a href="add_product.php" class="btn btn-primary mb-3">Add Product</a>

    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                <p class="card-text">
                    Price: $<?php echo htmlspecialchars($row['price']); ?><br>
                    Stock: <?php echo htmlspecialchars($row['stock_quantity']); ?><br>
                    Category: <?php echo htmlspecialchars($row['category_name']); ?>
                </p>
                <a href="edit_product.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-warning">Edit</a>
                <form method="post" action="delete_product.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    <?php endwhile; ?>

    <!-- Back to Dashboard Button -->
    <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>

<!-- Bootstrap JS (Optional) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar toggle functionality -->
<script>
    document.querySelector('.sidebar-toggle').addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.style.left = sidebar.style.left === '0px' ? '-255px' : '0px';
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
