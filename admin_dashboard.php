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

// Fetch products
$stmt = $conn->prepare("SELECT products.id, products.name, products.price, products.stock_quantity, categories.name AS category_name 
                        FROM products 
                        JOIN categories ON products.category_id = categories.id");
$stmt->execute();
$result = $stmt->get_result();

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
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
        <div class="d-flex">
            <span class="navbar-text me-3">
                <i class="bi bi-person-circle me-2"></i> <?php echo htmlspecialchars($admin_name); ?>
            </span>
            <!-- Logout Button with White Text Only -->
            <a href="admin_logout.php" class="btn btn-transparent text-white">Logout</a>
        </div>
    </div>
</nav>



    <div class="container">
        <!-- Products Section -->
        <div class="row">
            <div class="col-md-6">
                <h2>Products</h2>
                <!-- Add Product Button -->
                <a href="add_product.php" class="btn btn-primary mb-3">Add Product</a>

                <?php
                // Fetch products
                $stmt = $conn->prepare("SELECT products.id, products.name, products.price, products.stock_quantity, categories.name AS category_name 
                                        FROM products 
                                        JOIN categories ON products.category_id = categories.id");
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()): ?>
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
            </div>

            <!-- Orders Section -->
            <div class="col-md-6">
                <h2>Orders</h2>
                <?php
                // Fetch orders
                $stmt = $conn->prepare("SELECT orders.id, orders.total_price, orders.order_date, orders.status, users.name AS user_name 
                                        FROM orders 
                                        JOIN users ON orders.user_id = users.id");
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Order ID: <?php echo htmlspecialchars($row['id']); ?></h5>
                            <p class="card-text">
                                Total Price: $<?php echo htmlspecialchars($row['total_price']); ?><br>
                                Order Date: <?php echo htmlspecialchars($row['order_date']); ?><br>
                                User: <?php echo htmlspecialchars($row['user_name']); ?><br>
                                Status: <span class="badge <?php echo $row['status'] === 'Shipped' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </p>
                            <a href="view_order.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-info">View Details</a>
                            
                            <!-- Add Completed button if the order is not completed or shipped -->
                            <?php if ($row['status'] !== 'Completed' && $row['status'] !== 'Shipped'): ?>
                                <a href="complete_order.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-primary">Mark as Completed</a>
                            <?php endif; ?>

                            <?php if ($row['status'] !== 'Shipped'): ?>
                                <a href="ship_order.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-success">Ship Order</a>
                            <?php endif; ?>
                            
                            <form method="post" action="delete_order.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                <button type="submit" class="btn btn-danger">Delete Order</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (Optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
