<?php
session_start();
require 'db_connection.php';

// Check if user is an admin
if (!isset($_SESSION['admin_id'])) {
    die("You are not authorized to access this page.");
}

// Set admin name
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';  // Default to 'Admin' if not set

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the shipping fee from the form
    $shipping_fee = $_POST['shipping_fee'];

    // Update or set the shipping fee in the database
    $stmt = $conn->prepare("UPDATE settings SET shipping_fee = ? WHERE id = 1");
    $stmt->bind_param("d", $shipping_fee);
    if ($stmt->execute()) {
        $message = "Shipping fee updated successfully!";
    } else {
        $message = "Error updating shipping fee.";
    }
    $stmt->close();
}

// Check if settings with id = 1 exist
$stmt = $conn->prepare("SELECT shipping_fee FROM settings WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the current shipping fee
    $current_shipping_fee = $result->fetch_assoc()['shipping_fee'];
} else {
    // If no row exists with id = 1, insert a default row
    $stmt = $conn->prepare("INSERT INTO settings (id, shipping_fee) VALUES (1, 5.00)"); // Default shipping fee is 5.00
    $stmt->execute();
    $current_shipping_fee = 5.00; // Set the default shipping fee
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Shipping Fee</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
/* Navbar and Sidebar color */
.navbar, .sidebar {
    background-color: #343a40; /* Same color for both navbar and sidebar */
}

/* Navbar styles */
.navbar {
    margin-bottom: 30px; /* Matches view_payment.php */
}

/* Sidebar Styles */
.sidebar {
    height: 100vh; /* Full viewport height */
    position: fixed;
    top: 0;
    left: 0;
    width: 250px; /* Fixed width */
    padding-top: 30px; /* Space at the top */
    background-color: #343a40; /* Sidebar background color */
    display: flex;
    flex-direction: column; /* Align items vertically */
    z-index: 1050; /* Ensure it's above other content */
}

.sidebar a {
    color: #fff; /* White text */
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

/* Media Query for phones (up to 768px) */
@media (max-width: 768px) {
    .sidebar {
        left: -250px; /* Start off-screen to the left */
        transition: left 0.3s ease-in-out; /* Smooth animation */
    }

    .sidebar.active {
        left: 0; /* Move sidebar into view */
    }

    .sidebar a {
        padding: 10px 16px; /* Adjusted padding for mobile */
        font-size: 14px; /* Adjusted font size for mobile */
    }

    .sidebar a i {
        font-size: 18px; /* Smaller icon size for mobile */
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

    .sidebar-toggle:hover {
        background-color: #0056b3; /* Darker blue on hover */
    }
}

.container {
    margin-left: 250px; /* Space between sidebar and container */
    padding: 20px;
}

@media (max-width: 768px) {
    .container {
        margin-left: 0; /* Remove margin-left for mobile view */
        padding: 20px;
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

    <div class="container">
        <h2 class="text-center mb-4">Set Shipping Fee</h2>

        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="shipping_fee" class="form-label">Shipping Fee ($):</label>
                <input type="number" name="shipping_fee" id="shipping_fee" class="form-control" step="0.01" value="<?php echo htmlspecialchars($current_shipping_fee); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Shipping Fee</button>
        </form>
    </div>

    <!-- Sidebar Toggle Functionality -->
    <script>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
