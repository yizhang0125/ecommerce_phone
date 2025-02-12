<?php
require_once 'admin_header.php';
require 'db_connection.php';

// Get search term if any
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Pagination
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Build query conditions
$conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $conditions[] = "(o.id LIKE ? OR u.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($status_filter)) {
    $conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

// Add this before your query
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'today';

// Build the date condition
$date_condition = "";
switch($date_filter) {
    case 'today':
        $date_condition = "DATE(o.order_date) = CURDATE()";
        break;
    case 'yesterday':
        $date_condition = "DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'last7days':
        $date_condition = "DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'last30days':
        $date_condition = "DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    case 'all':
        $date_condition = "1=1"; // Show all orders
        break;
    default:
        $date_condition = "DATE(o.order_date) = CURDATE()";
}

// Get total number of orders
$count_query = "SELECT COUNT(DISTINCT o.id) as total 
                FROM orders o 
                JOIN users u ON o.user_id = u.id";

// Add date condition to count query
if ($date_filter === 'today') {
    $count_query .= " WHERE DATE(o.order_date) = CURDATE()";
} else if ($date_filter === 'yesterday') {
    $count_query .= " WHERE DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} else if ($date_filter === 'last7days') {
    $count_query .= " WHERE DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} else if ($date_filter === 'last30days') {
    $count_query .= " WHERE DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
}

// Add search and status conditions to count query
if (!empty($search)) {
    $where_clause = strpos($count_query, 'WHERE') ? ' AND' : ' WHERE';
    $count_query .= $where_clause . " (o.id LIKE '%$search%' OR u.name LIKE '%$search%')";
}

if (!empty($status_filter)) {
    $where_clause = strpos($count_query, 'WHERE') ? ' AND' : ' WHERE';
    $count_query .= $where_clause . " o.status = '$status_filter'";
}

$total_orders = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $items_per_page);

// Fetch orders with user information
$query = "SELECT o.*, u.name as user_name 
         FROM orders o 
         JOIN users u ON o.user_id = u.id";

// Add date filter condition if selected
if ($date_filter === 'today') {
    $query .= " WHERE DATE(o.order_date) = CURDATE()";
} else if ($date_filter === 'yesterday') {
    $query .= " WHERE DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} else if ($date_filter === 'last7days') {
    $query .= " WHERE DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} else if ($date_filter === 'last30days') {
    $query .= " WHERE DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
// For 'all' option, no WHERE clause needed

// Add search condition if provided
if (!empty($search)) {
    $where_clause = strpos($query, 'WHERE') ? ' AND' : ' WHERE';
    $query .= $where_clause . " (o.id LIKE '%$search%' OR u.name LIKE '%$search%')";
            }

// Add status filter if provided
if (!empty($status_filter)) {
    $where_clause = strpos($query, 'WHERE') ? ' AND' : ' WHERE';
    $query .= $where_clause . " o.status = '$status_filter'";
}

// Add ordering and pagination
$query .= " ORDER BY o.order_date DESC LIMIT $items_per_page OFFSET $offset";

$result = $conn->query($query);

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch_assoc()['count'],
    'completed' => $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(total_price) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'] ?? 0
];
?>

<div class="dashboard-content">
    <!-- Header Section with Gradient Background -->
    <div class="content-header-wrapper mb-4">
        <div class="content-header p-4">
        <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-12">
                        <h1 class="header-title">
                            <i class="bi bi-cart-check me-2"></i>Orders Management
                        </h1>
                        <p class="header-subtitle">View and manage customer orders</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards with Enhanced Glass Effect -->
    <div class="container-fluid mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-3">
                <div class="stat-card primary">
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <i class="bi bi-cart-fill"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                            <div class="stat-label">Total Orders</div>
                            <div class="stat-sublabel">All time orders</div>
                        </div>
                    </div>
                    <div class="stat-progress">
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="stat-card warning">
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
                            <div class="stat-label">Pending Orders</div>
                            <div class="stat-sublabel">Needs attention</div>
                        </div>
                    </div>
                    <div class="stat-progress">
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo ($stats['pending']/$stats['total']*100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="stat-card success">
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value"><?php echo number_format($stats['completed']); ?></div>
                            <div class="stat-label">Completed Orders</div>
                            <div class="stat-sublabel">Successfully delivered</div>
                        </div>
                    </div>
                    <div class="stat-progress">
                        <div class="progress">
                            <div class="progress-bar" style="width: <?php echo ($stats['completed']/$stats['total']*100); ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="stat-card info">
                    <div class="stat-card-content">
                        <div class="stat-icon-wrapper">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-sublabel">From completed orders</div>
                        </div>
                    </div>
                    <div class="stat-progress">
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Search and Filter Section -->
    <div class="search-filter-container mb-4">
        <div class="modern-card">
            <form method="GET" class="row g-3">
                <!-- Add Date Filter -->
                <div class="col-12 col-md-3">
                    <div class="filter-box">
                        <div class="filter-wrapper">
                            <i class="bi bi-calendar filter-icon"></i>
                            <select name="date_filter" class="form-select filter-select" onchange="this.form.submit()">
                                <option value="today" <?php echo (!isset($_GET['date_filter']) || $_GET['date_filter'] == 'today') ? 'selected' : ''; ?>>Today's Orders</option>
                                <option value="yesterday" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] == 'yesterday') ? 'selected' : ''; ?>>Yesterday's Orders</option>
                                <option value="last7days" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] == 'last7days') ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="last30days" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] == 'last30days') ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="all" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] == 'all') ? 'selected' : ''; ?>>All Orders</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Existing Search Box -->
                <div class="col-12 col-md-4">
                    <div class="search-box">
                        <div class="search-wrapper">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" 
                                   name="search" 
                                   class="form-control search-input" 
                                   placeholder="Search orders by ID, customer name..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   autocomplete="off">
                            <div class="search-clear" <?php echo empty($search) ? 'style="display:none;"' : ''; ?>>
                                <i class="bi bi-x-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Existing Status Filter -->
                <div class="col-12 col-md-3">
                    <div class="filter-box">
                        <div class="filter-wrapper">
                            <i class="bi bi-funnel filter-icon"></i>
                            <select name="status" class="form-select filter-select">
                                <option value="">All Orders</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                                    <i class="bi bi-clock-history"></i> Pending Orders
                                </option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>
                                    <i class="bi bi-arrow-repeat"></i> Processing
                                </option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>
                                    <i class="bi bi-check-circle"></i> Completed
                                </option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>
                                    <i class="bi bi-x-circle"></i> Cancelled
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Existing Search Button -->
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary search-btn w-100">
                        <i class="bi bi-search me-2"></i>Search Orders
                    </button>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="orders_section.php" class="btn btn-outline-secondary clear-btn w-100 mt-2">
                        <i class="bi bi-x-circle me-2"></i>Clear Filters
                    </a>
                <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table with Modern Design -->
    <div class="modern-card">
        <div class="table-responsive">
            <table class="table modern-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Shipping Status</th>
                        <th>Order Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch orders with user information
                    $query = "SELECT o.*, u.name as user_name 
                             FROM orders o 
                             JOIN users u ON o.user_id = u.id";

                    // Add date filter condition if selected
                    if ($date_filter === 'today') {
                        $query .= " WHERE DATE(o.order_date) = CURDATE()";
                    } else if ($date_filter === 'yesterday') {
                        $query .= " WHERE DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                    } else if ($date_filter === 'last7days') {
                        $query .= " WHERE DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    } else if ($date_filter === 'last30days') {
                        $query .= " WHERE DATE(o.order_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    }
                    // For 'all' option, no WHERE clause needed

                    // Add search condition if provided
                    if (!empty($search)) {
                        $where_clause = strpos($query, 'WHERE') ? ' AND' : ' WHERE';
                        $query .= $where_clause . " (o.id LIKE '%$search%' OR u.name LIKE '%$search%')";
                    }

                    // Add status filter if provided
                    if (!empty($status_filter)) {
                        $where_clause = strpos($query, 'WHERE') ? ' AND' : ' WHERE';
                        $query .= $where_clause . " o.status = '$status_filter'";
                    }

                    // Add ordering and pagination
                    $query .= " ORDER BY o.order_date DESC LIMIT $items_per_page OFFSET $offset";

                    $result = $conn->query($query);

                    while ($order = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                        <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $order['status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                            </span>
                        </td>
                        <td>
                                    <span class="badge <?php echo $order['shipping_status'] === 'Shipped' ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo htmlspecialchars($order['shipping_status']); ?>
                                    </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        <td class="text-center">
                            <div class="action-buttons">
                                <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i> View
                                </a>

                                <?php if ($order['status'] !== 'completed'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success"
                                            onclick="markAsCompleted(<?php echo $order['id']; ?>)">
                                        <i class="bi bi-check-circle"></i> Complete
                                    </button>
                                <?php endif; ?>

                                <?php if ($order['shipping_status'] !== 'Shipped'): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="shipOrder(<?php echo $order['id']; ?>)">
                                        <i class="bi bi-truck"></i> Ship
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
                            </div>
                        </div>

                <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_filter=<?php echo urlencode($date_filter); ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_filter=<?php echo urlencode($date_filter); ?>">
                    <?php echo $i; ?>
                </a>
                            </li>
                        <?php endfor; ?>

            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&date_filter=<?php echo urlencode($date_filter); ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
                        </li>
                    </ul>
                </nav>
    <?php endif; ?>
    </div>

<style>
/* Keep existing styles and add/modify these */
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

.modern-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.modern-table th {
    background: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    padding: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.modern-table td {
    padding: 1rem;
    vertical-align: middle;
}

.customer-name {
    font-weight: 500;
    color: #2c3e50;
}

.price-tag {
    font-weight: 600;
    color: #2c3e50;
    font-size: 1.1rem;
}

.status-badge {
    padding: 0.5em 1em;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.processing {
    background: #cce5ff;
    color: #004085;
}

.status-badge.completed {
    background: #d4edda;
    color: #155724;
}

.status-badge.shipped {
    background: #d4edda;
    color: #155724;
}

.order-products {
    max-width: 250px;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.action-buttons .btn {
    padding: 0.4rem 0.8rem;
    font-size: 0.85rem;
}

.btn-outline-success:hover {
    background-color: #198754;
    color: white;
}

.btn-outline-primary:hover {
    background-color: #0d6efd;
    color: white;
}

.badge {
    padding: 0.5em 1em;
    font-size: 0.875rem;
}

.btn i {
    font-size: 1rem;
}

/* Enhanced Stat Cards */
.stat-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(31, 38, 135, 0.25);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
    pointer-events: none;
}

.stat-card.primary { border-top: 4px solid #4e73df; }
.stat-card.warning { border-top: 4px solid #f6c23e; }
.stat-card.success { border-top: 4px solid #1cc88a; }
.stat-card.info { border-top: 4px solid #36b9cc; }

.stat-card-content {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
}

.stat-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    background: rgba(78, 115, 223, 0.1);
}

.stat-card.primary .stat-icon-wrapper { 
    background: rgba(78, 115, 223, 0.1);
    color: #4e73df;
}

.stat-card.warning .stat-icon-wrapper {
    background: rgba(246, 194, 62, 0.1);
    color: #f6c23e;
}

.stat-card.success .stat-icon-wrapper {
    background: rgba(28, 200, 138, 0.1);
    color: #1cc88a;
}

.stat-card.info .stat-icon-wrapper {
    background: rgba(54, 185, 204, 0.1);
    color: #36b9cc;
}

.stat-info {
    flex-grow: 1;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.25rem;
    line-height: 1.2;
}

.stat-label {
    font-size: 1rem;
    font-weight: 600;
    color: #5a5c69;
    margin-bottom: 0.25rem;
}

.stat-sublabel {
    font-size: 0.875rem;
    color: #858796;
}

.stat-progress {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(0,0,0,0.05);
}

.progress {
    height: 4px;
    background: rgba(0,0,0,0.05);
    border-radius: 2px;
    overflow: hidden;
}

.stat-card.primary .progress-bar { background-color: #4e73df; }
.stat-card.warning .progress-bar { background-color: #f6c23e; }
.stat-card.success .progress-bar { background-color: #1cc88a; }
.stat-card.info .progress-bar { background-color: #36b9cc; }

/* Animation */
@keyframes progressAnimation {
    0% { width: 0; }
}

.progress-bar {
    animation: progressAnimation 1s ease-out;
}

/* Hover Effects */
.stat-card:hover .stat-icon-wrapper {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}

.stat-card:hover .stat-value {
    color: #000;
    transition: color 0.3s ease;
}

/* Modern Search and Filter Styles */
.search-filter-container {
    margin: 1.5rem 0;
}

.modern-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    padding: 2rem;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.modern-card:hover {
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
}

.search-wrapper, .filter-wrapper {
    position: relative;
    background: #f8f9fa;
    border-radius: 15px;
    transition: all 0.3s ease;
    overflow: hidden;
}

.search-wrapper:focus-within, .filter-wrapper:focus-within {
    background: #fff;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
}

.search-icon, .filter-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 1.2rem;
    z-index: 2;
    transition: color 0.3s ease;
}

.search-wrapper:focus-within .search-icon,
.filter-wrapper:focus-within .filter-icon {
    color: #0d6efd;
}

.search-input, .filter-select {
    height: 58px;
    padding-left: 55px;
    padding-right: 50px;
    border: 2px solid transparent;
    border-radius: 15px;
    font-size: 1rem;
    background: transparent;
    transition: all 0.3s ease;
    width: 100%;
}

.search-input:focus, .filter-select:focus {
    border-color: #0d6efd;
    box-shadow: none;
    outline: none;
}

.search-clear {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #6c757d;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

.search-clear:hover {
    background: rgba(108, 117, 125, 0.1);
    color: #dc3545;
}

.search-btn {
    height: 58px;
    border-radius: 15px;
    font-weight: 600;
    font-size: 1rem;
    background: linear-gradient(45deg, #0d6efd, #0a58ca);
    border: none;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(13, 110, 253, 0.3);
}

.clear-btn {
    height: 45px;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.9rem;
}

.clear-btn:hover {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
    transform: translateY(-2px);
}

/* Filter Select Styling */
.filter-select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 20px center;
    background-size: 12px;
    padding-right: 50px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .modern-card {
        padding: 1.5rem;
    }
    
    .search-wrapper, .filter-wrapper {
        margin-bottom: 1rem;
    }
    
    .search-btn {
        margin-top: 0.5rem;
    }
}

/* Animation for focus states */
@keyframes focusAnimation {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

.search-wrapper:focus-within, .filter-wrapper:focus-within {
    animation: focusAnimation 0.3s ease;
}

.pagination {
    gap: 5px;
}

.page-link {
    border-radius: 8px;
    padding: 8px 16px;
    color: #0d6efd;
    border: none;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.page-link:hover {
    background: #e9ecef;
    color: #0a58ca;
    transform: translateY(-2px);
}

.page-item.active .page-link {
    background: #0d6efd;
    color: white;
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
}

.page-item.disabled .page-link {
    background: #f8f9fa;
    color: #6c757d;
    opacity: 0.5;
}
</style>

    <script>
function updateOrderStatus(orderId) {
    Swal.fire({
        title: 'Update Order Status',
        input: 'select',
        inputOptions: {
            'pending': 'Pending',
            'processing': 'Processing',
            'completed': 'Completed',
            'cancelled': 'Cancelled'
        },
        inputPlaceholder: 'Select status',
        showCancelButton: true,
        confirmButtonText: 'Update',
        showLoaderOnConfirm: true,
        preConfirm: (status) => {
            return fetch(`update_order_status.php?id=${orderId}&status=${status}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message)
                    }
                    return data
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`)
                })
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Success!',
                text: 'Order status has been updated.',
                icon: 'success'
            }).then(() => {
                location.reload();
        });
        }
    });
}

function markAsCompleted(orderId) {
    Swal.fire({
        title: 'Mark Order as Completed?',
        text: "This will update the order status to completed",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, complete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `update_order_status.php?id=${orderId}&status=completed`;
        }
    });
}

function shipOrder(orderId) {
    Swal.fire({
        title: 'Ship this order?',
        text: "This will mark the order as shipped",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, ship it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `ship_order.php?id=${orderId}`;
        }
    });
}

function deleteOrder(orderId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete_order.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id';
            input.value = orderId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Clear search input functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.search-input');
    const searchClear = document.querySelector('.search-clear');
    
    if (searchInput && searchClear) {
        searchInput.addEventListener('input', function() {
            searchClear.style.display = this.value ? 'flex' : 'none';
        });
        
        searchClear.addEventListener('click', function() {
            searchInput.value = '';
            searchClear.style.display = 'none';
            searchInput.focus();
        });
            }
        });
    </script>

</div> <!-- Close main-content div from admin_header.php -->
</body>
</html>

