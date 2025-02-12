<?php
session_start();
require 'db_connection.php';

// Get selected date or default to today
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get earnings for selected date
$query = "SELECT 
            COUNT(id) as total_orders,
            SUM(total_price) as total_earnings,
            COUNT(DISTINCT user_id) as total_customers
          FROM orders 
          WHERE DATE(order_date) = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$result = $stmt->get_result();
$day_stats = $result->fetch_assoc();

// Get orders for selected date
$orders_query = "SELECT o.*, u.name as customer_name
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE DATE(o.order_date) = ?
                ORDER BY o.order_date DESC";

$stmt = $conn->prepare($orders_query);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$orders_result = $stmt->get_result();

// Get dates with earnings
$dates_query = "SELECT DISTINCT DATE(order_date) as order_date, 
                COUNT(id) as order_count,
                SUM(total_price) as daily_total 
                FROM orders 
                GROUP BY DATE(order_date) 
                ORDER BY order_date DESC";
$dates_result = $conn->query($dates_query);
$dates_with_earnings = [];
while ($date_row = $dates_result->fetch_assoc()) {
    $dates_with_earnings[] = [
        'date' => $date_row['order_date'],
        'count' => $date_row['order_count'],
        'total' => $date_row['daily_total']
    ];
}

require_once 'admin_header.php';
?>

<div class="dashboard-content">
    <!-- Header Section with Date Filter -->
    <div class="content-header-wrapper mb-4">
        <div class="content-header p-4">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="header-title">
                            <i class="bi bi-cash-coin me-2"></i>Daily Earnings
                        </h1>
                        <p class="header-subtitle">Financial overview for selected date</p>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="date-filter">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-calendar3"></i>
                                </span>
                                <select name="date" class="form-select" onchange="this.form.submit()">
                                    <option value="">Select Date</option>
                                    <?php foreach ($dates_with_earnings as $date): ?>
                                        <option value="<?php echo $date['date']; ?>" 
                                                <?php echo ($selected_date == $date['date']) ? 'selected' : ''; ?>>
                                            <?php 
                                            echo date('F j, Y', strtotime($date['date'])) . 
                                                 ' ($' . number_format($date['total'], 2) . 
                                                 ' - ' . $date['count'] . ' orders)'; 
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="container-fluid mb-4">
        <div class="row g-3">
            <!-- Total Earnings Card -->
            <div class="col-md-4">
                <div class="stats-card primary">
                    <div class="stats-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stats-info">
                        <h3>$<?php echo number_format($day_stats['total_earnings'] ?? 0, 2); ?></h3>
                        <p>Total Earnings on <?php echo date('F j, Y', strtotime($selected_date)); ?></p>
                    </div>
                </div>
            </div>

            <!-- Orders Count Card -->
            <div class="col-md-4">
                <div class="stats-card success">
                    <div class="stats-icon">
                        <i class="bi bi-bag-check"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $day_stats['total_orders'] ?? 0; ?></h3>
                        <p>Orders on <?php echo date('F j, Y', strtotime($selected_date)); ?></p>
                    </div>
                </div>
            </div>

            <!-- Customers Count Card -->
            <div class="col-md-4">
                <div class="stats-card info">
                    <div class="stats-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo $day_stats['total_customers'] ?? 0; ?></h3>
                        <p>Customers on <?php echo date('F j, Y', strtotime($selected_date)); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Orders Table -->
    <div class="container-fluid">
        <div class="modern-card">
            <h5 class="card-title mb-4">Today's Orders</h5>
            <div class="table-responsive">
                <table class="table modern-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result->num_rows > 0): ?>
                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td><?php echo date('h:i A', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="no-orders">
                                        <i class="bi bi-inbox text-muted"></i>
                                        <p>No orders today</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-card.primary .stats-icon { background: #e8f0ff; color: #4e73df; }
.stats-card.success .stats-icon { background: #e6f8f3; color: #1cc88a; }
.stats-card.info .stats-icon { background: #e3f2fd; color: #36b9cc; }

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
}

.stats-info h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
    color: #2c3e50;
}

.stats-info p {
    margin: 0;
    color: #6c757d;
}

.modern-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.card-title {
    color: #2c3e50;
    font-weight: 600;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.modern-table {
    margin: 0;
}

.modern-table th {
    background: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.status-badge {
    padding: 0.5em 1em;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.completed { background: #d1e7dd; color: #0f5132; }
.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.cancelled { background: #f8d7da; color: #842029; }

.no-orders {
    text-align: center;
    padding: 2rem;
}

.no-orders i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.no-orders p {
    color: #6c757d;
    margin: 0;
}

.content-header-wrapper {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    border-radius: 15px;
    margin: 1rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.header-title {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
}

.header-subtitle {
    color: rgba(255, 255, 255, 0.8);
    margin: 0.5rem 0 0;
}

/* Add these new styles for the date filter */
.date-filter {
    display: flex;
    justify-content: flex-end;
}

.date-filter .input-group {
    max-width: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 5px;
}

.date-filter .input-group-text {
    background: transparent;
    border: none;
    color: white;
}

.date-filter .form-select {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    cursor: pointer;
    width: 300px;
}

.date-filter .form-select option {
    background: #fff;
    color: #333;
    padding: 10px;
}

.date-filter .form-control::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

.date-filter .btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.date-filter .btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}
</style>

</div> <!-- Close main-content div from admin_header.php -->
</body>
</html>
