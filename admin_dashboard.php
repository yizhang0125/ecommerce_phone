<?php 
require_once 'admin_header.php';
    require 'db_connection.php';

// Success message handling
    $success_message = isset($_SESSION['login_success']) ? $_SESSION['login_success'] : null;
    unset($_SESSION['login_success']);

// Fetch recent orders with order items
$recent_orders_query = "SELECT o.*, u.name as customer_name, 
                              GROUP_CONCAT(p.name SEPARATOR ', ') as product_names,
                              o.total_price as order_total
                       FROM orders o 
                       JOIN users u ON o.user_id = u.id 
                       JOIN order_items oi ON o.id = oi.order_id
                       JOIN products p ON oi.product_id = p.id
                       GROUP BY o.id
                       ORDER BY o.order_date DESC 
                       LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_query);

// Fetch total orders count
$total_orders_query = "SELECT COUNT(DISTINCT order_id) as total FROM order_items";
$total_orders = $conn->query($total_orders_query)->fetch_assoc()['total'];

// Fetch total revenue from completed orders
$revenue_query = "SELECT SUM(total_price) as total 
                 FROM orders 
                 WHERE status = 'completed'";
$total_revenue = $conn->query($revenue_query)->fetch_assoc()['total'] ?? 0;
?>

<!-- Main content -->
<div class="dashboard-content">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="header-title">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard Overview
                </h1>
                <p class="header-subtitle">Monitor and manage your store performance</p>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Statistics Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card bg-primary text-white">
                <div class="stats-icon">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($total_orders); ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card bg-success text-white">
                <div class="stats-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stats-info">
                    <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card bg-info text-white">
                <div class="stats-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stats-info">
                    <h3><?php echo number_format($conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total']); ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="bi bi-clock-history me-2"></i>Recent Orders
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Products</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Shipping Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $recent_orders_result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td>
                                <?php 
                                $products = htmlspecialchars($order['product_names']);
                                echo strlen($products) > 50 ? 
                                     substr($products, 0, 47) . '...' : 
                                     $products; 
                                ?>
                            </td>
                            <td>$<?php echo number_format($order['order_total'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($order['status']) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($order['shipping_status']) {
                                        'delivered' => 'success',
                                        'shipped' => 'info',
                                        'processing' => 'warning',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($order['shipping_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>
                                <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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

<style>
    .page-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .header-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        color: white;
        display: flex;
        align-items: center;
    }

    .header-subtitle {
        color: rgba(255, 255, 255, 0.8);
        margin: 0.5rem 0 0;
        font-size: 1.1rem;
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

    /* New styles for statistics cards */
    .stats-card {
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .stats-icon {
        font-size: 2.5rem;
        margin-right: 20px;
        opacity: 0.8;
    }

    .stats-info h3 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 600;
    }

    .stats-info p {
        margin: 5px 0 0;
        opacity: 0.8;
    }

    /* Table styles */
    .table {
        margin-bottom: 0;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .table td {
        vertical-align: middle;
    }

    .badge {
        padding: 0.5em 0.8em;
    }

    .card {
        border: none;
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        padding: 1rem;
    }

    .card-title {
        color: #333;
        font-weight: 600;
    }

    .dashboard-content {
        padding: 20px;
    }
</style>

</div> <!-- Close main-content div from admin_header.php -->
</body>
</html>
