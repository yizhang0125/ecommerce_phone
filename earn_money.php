<?php
session_start();
require 'db_connection.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Query to get earnings for each day
$sql = "SELECT DATE(order_date) AS order_day, SUM(total_price) AS daily_earnings 
        FROM orders 
        GROUP BY DATE(order_date) 
        ORDER BY order_day DESC";  // This will order by the most recent day
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Earnings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .navbar {
            background: linear-gradient(90deg, #007bff, #28a745);
            padding: 15px;
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        .navbar-text {
            color: white;
            font-size: 1rem;
            margin-right: 15px;
        }
        .navbar .btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            transition: background-color 0.3s;
        }
        .navbar .btn:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }
        .navbar i {
            font-size: 1.2rem;
            margin-right: 8px;
        }
        .container {
            margin-top: 40px;
        }
        .earnings-box {
            background-color: #fff;
            padding: 30px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            text-align: center;
            margin-bottom: 40px;
        }
        .earnings-box i {
            font-size: 100px;
            color: #28a745;
        }
        .earnings-box h3 {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            margin-top: 20px;
        }
        .earnings-box h2 {
            font-size: 50px;
            font-weight: 700;
            color: #28a745;
            margin-top: 10px;
        }
        .earnings-title {
            font-size: 26px;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 20px;
        }
        .table {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .table th, .table td {
            text-align: center;
            padding: 15px;
        }
        .table th {
            background-color: #007bff;
            color: white;
            font-size: 16px;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f2f2f2;
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <!-- Branding -->
            <a class="navbar-brand mx-auto" href="admin_dashboard.php">
                <i class="bi bi-speedometer2"></i> Admin Dashboard
            </a>

            <!-- Admin Name & Logout -->
            <div class="d-flex align-items-center">
                <span class="navbar-text">
                    <i class="bi bi-person-circle"></i> 
                    <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>
                </span>
                <a href="admin_logout.php" class="btn btn-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Earnings Box with Large Earn Money Icon -->
    <div class="container">
        <!-- Back Button -->
        <div class="d-flex justify-content-start mb-4">
            <button onclick="window.history.back()" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> Back
            </button>
        </div>

        <div class="earnings-box">
            <i class="bi bi-cash-coin"></i>
            <h3>Total Earnings Today</h3>
            <?php 
                // Query for today's earnings
                $sql_today = "SELECT SUM(total_price) AS daily_earnings 
                              FROM orders 
                              WHERE DATE(order_date) = CURDATE()";
                $stmt_today = $conn->prepare($sql_today);
                $stmt_today->execute();
                $result_today = $stmt_today->get_result();
                $row_today = $result_today->fetch_assoc();
                $daily_earnings = $row_today['daily_earnings'] ? number_format($row_today['daily_earnings'], 2) : '0.00';
            ?>
            <h2>$<?php echo $daily_earnings; ?></h2>
        </div>

        <!-- Table with Daily Earnings -->
        <div class="card">
            <div class="card-body">
                <p class="earnings-title">Earnings Per Day</p>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Earnings ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('F j, Y', strtotime($row['order_day'])); ?></td>
                                <td>$<?php echo number_format($row['daily_earnings'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
