<?php
session_start();
require 'db_connection.php';

// Fetch monthly sales data
$query = "SELECT 
            DATE_FORMAT(o.order_date, '%Y-%m') as month,
            COUNT(o.id) as total_orders,
            SUM(o.total_price) as total_sales,
            COUNT(DISTINCT o.user_id) as unique_customers
          FROM orders o
          WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
          ORDER BY month ASC";

$result = $conn->query($query);
$sales_data = [];
$months = [];
$sales = [];
$orders = [];
$customers = [];

while ($row = $result->fetch_assoc()) {
    $months[] = date('M Y', strtotime($row['month']));
    $sales[] = (float)$row['total_sales'];
    $orders[] = (int)$row['total_orders'];
    $customers[] = (int)$row['unique_customers'];
}

require_once 'admin_header.php';
?>

<div class="dashboard-content">
    <!-- Header Section -->
    <div class="content-header-wrapper mb-4">
        <div class="content-header p-4">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-12">
                        <h1 class="header-title">
                            <i class="bi bi-graph-up me-2"></i>Monthly Sales Analysis
                        </h1>
                        <p class="header-subtitle">Track your business performance</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="container-fluid mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stats-info">
                        <h3>$<?php echo number_format(array_sum($sales), 2); ?></h3>
                        <p>Total Sales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="bi bi-bag"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo array_sum($orders); ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stats-info">
                        <h3><?php echo array_sum($customers); ?></h3>
                        <p>Unique Customers</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="container-fluid">
        <div class="row g-4">
            <!-- Sales Chart -->
            <div class="col-12">
                <div class="chart-card">
                    <h5 class="chart-title">Monthly Sales Overview</h5>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            
            <!-- Orders vs Customers Chart -->
            <div class="col-12">
                <div class="chart-card">
                    <h5 class="chart-title">Orders & Customers Comparison</h5>
                    <canvas id="comparisonChart"></canvas>
                </div>
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

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: #f0f4ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #4e73df;
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

.chart-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 1.5rem;
}

.chart-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
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
</style>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Monthly Sales ($)',
            data: <?php echo json_encode($sales); ?>,
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Comparison Chart
const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
new Chart(comparisonCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Orders',
            data: <?php echo json_encode($orders); ?>,
            backgroundColor: '#4e73df',
            borderRadius: 6
        }, {
            label: 'Unique Customers',
            data: <?php echo json_encode($customers); ?>,
            backgroundColor: '#1cc88a',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

</div> <!-- Close main-content div from admin_header.php -->
</body>
</html>
