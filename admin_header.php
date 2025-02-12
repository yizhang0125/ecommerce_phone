<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Set admin name
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-bg: #1a237e;
            --secondary-bg: #283593;
            --hover-color: #3949ab;
            --text-color: #fff;
            --transition: all 0.3s ease;
            --sidebar-width: 280px;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        /* Enhanced Sidebar */
        .sidebar {
            background: linear-gradient(180deg, var(--primary-bg), var(--secondary-bg));
            height: 100vh;
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
            transition: var(--transition);
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-header h4 {
            color: var(--text-color);
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
        }

        .sidebar a {
            color: var(--text-color);
            padding: 15px 25px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: var(--transition);
            margin: 4px 12px;
            border-radius: 10px;
        }

        .sidebar a:hover {
            background-color: var(--hover-color);
            transform: translateX(5px);
        }

        .sidebar a i {
            font-size: 1.25rem;
            margin-right: 15px;
            width: 25px;
            text-align: center;
        }

        .sidebar a.active {
            background-color: var(--hover-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Enhanced Navbar */
        .navbar {
            background: linear-gradient(90deg, var(--primary-bg), var(--secondary-bg));
            padding: 1rem;
            margin-left: var(--sidebar-width);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-text {
            color: var(--text-color) !important;
            font-weight: 500;
        }

        .btn-logout {
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: white;
            transition: var(--transition);
        }

        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: white;
            color: white;
        }

        /* Main Content Area */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: var(--transition);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 250px;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .navbar, .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
                position: fixed;
                top: 1rem;
                left: 1rem;
                z-index: 1001;
                width: 45px;
                height: 45px;
                background: var(--primary-bg);
                border: none;
                border-radius: 10px;
                color: white;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
                transition: var(--transition);
            }

            .sidebar-toggle:hover {
                background: var(--hover-color);
            }
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-shield-lock me-2"></i>Admin Panel</h4>
        </div>
        <?php 
        // Get current page filename
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <a href="admin_dashboard.php" class="<?php echo $current_page === 'admin_dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-house-door"></i> Dashboard
        </a>
        <a href="products_section.php" class="<?php echo $current_page === 'products_section.php' ? 'active' : ''; ?>">
            <i class="bi bi-box"></i> Products
        </a>
        <a href="orders_section.php" class="<?php echo $current_page === 'orders_section.php' ? 'active' : ''; ?>">
            <i class="bi bi-cart-fill"></i> Orders
        </a>
        <a href="shipping_fees.php" class="<?php echo $current_page === 'shipping_fees.php' ? 'active' : ''; ?>">
            <i class="bi bi-truck"></i> Shipping Fees
        </a>
        <a href="add_product.php" class="<?php echo $current_page === 'add_product.php' ? 'active' : ''; ?>">
            <i class="bi bi-plus-circle-fill"></i> Add Product
        </a>
        <a href="view_payment.php" class="<?php echo $current_page === 'view_payment.php' ? 'active' : ''; ?>">
            <i class="bi bi-credit-card-fill"></i> View Payment
        </a>
        <a href="index.php">
            <i class="bi bi-globe"></i> View Website
        </a>
    </div>

    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle d-lg-none">
        <i class="bi bi-list"></i>
    </button>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <div class="ms-auto d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($admin_name); ?>
                </span>
                <a href="admin_logout.php" class="btn btn-logout">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="main-content">
        <!-- Content will be inserted here -->

    <script>
        // Sidebar Toggle Functionality
        document.querySelector('.sidebar-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth <= 768 && 
                sidebar && 
                sidebarToggle && 
                !sidebar.contains(event.target) && 
                !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html> 