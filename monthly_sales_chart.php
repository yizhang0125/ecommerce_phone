<?php
session_start();
require 'db_connection.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Query to get earnings for each month
$sql = "SELECT MONTH(order_date) AS month, YEAR(order_date) AS year, SUM(total_price) AS monthly_earnings 
        FROM orders 
        GROUP BY MONTH(order_date), YEAR(order_date) 
        ORDER BY YEAR(order_date) DESC, MONTH(order_date) DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.6, maximum-scale=1.5, user-scalable=yes">
    <title>Monthly Sales Line Chart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
            display: flex;
            flex-direction: column;
            height: 100vh;
            margin: 0;
        }
        .navbar, .sidebar {
            background-color: #343a40; /* Same color for both navbar and sidebar */
            color: white;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        .sidebar {
            height: 100vh;
            width: 220px; /* Slightly wider sidebar */
            padding-top: 30px;
            position: fixed;
            top: 0;
            left: 0;
        }
        .sidebar a {
            color: #fff;
            padding: 14px 18px; /* Slightly larger padding */
            text-decoration: none;
            display: flex;
            align-items: center;
            margin: 8px 0;
            border-radius: 5px;
            font-size: 16px; /* Slightly larger font size */
        }
        .sidebar a i {
            font-size: 20px; /* Slightly larger icon size */
            margin-right: 12px;
        }
        .sidebar a:hover {
            background-color: #007bff;
        }
        .container {
            margin-top: 40px;
            margin-left: 220px; /* Adjust for wider sidebar width */
        }
        .card-body {
            text-align: center;
        }
        canvas {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
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

    <!-- Line Chart Container -->
    <div class="container">
        <div class="card">
            <div class="card-body">
                <h3 class="mb-4" style="font-size: 24px;">Monthly Sales Trend</h3>
                <canvas id="salesLineChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Extract data from PHP array
        const salesData = [];
        const labels = [];

        <?php while ($row = $result->fetch_assoc()): ?>
            labels.push(`<?php echo $row['year']; ?>-<?php echo str_pad($row['month'], 2, '0', STR_PAD_LEFT); ?>`);
            salesData.push(<?php echo $row['monthly_earnings']; ?>);
        <?php endwhile; ?>

        const ctx = document.getElementById('salesLineChart').getContext('2d');
        const salesLineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Monthly Sales ($)',
                    data: salesData,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                size: 20 // Smaller legend font size
                            }
                        }
                    },
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Months',
                            font: {
                                size: 18 // Smaller x-axis title font size
                            }
                        },
                        ticks: {
                            font: {
                                size: 14 // Smaller x-axis tick font size
                            }
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Sales ($)',
                            font: {
                                size: 18 // Smaller y-axis title font size
                            }
                        },
                        ticks: {
                            font: {
                                size: 14 // Smaller y-axis tick font size
                            }
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

</body>
</html>
