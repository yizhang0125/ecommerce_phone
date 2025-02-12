<?php 
session_start();
require 'db_connection.php';

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Redirect to the login page if not logged in
    header('Location: admin_login.php');
    exit();
}

// Fetch admin's name from database
$admin_id = $_SESSION['admin_id'];
$admin_stmt = $conn->prepare("SELECT name FROM admins WHERE id = ?");
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();

if ($admin_result->num_rows > 0) {
    $admin_data = $admin_result->fetch_assoc();
    $admin_name = $admin_data['name'];
} else {
    $admin_name = 'Admin'; // Default fallback
}

// Fetch payment details from the database
$stmt = $conn->prepare("SELECT 
    payments.id, 
    payments.card_number, 
    payments.card_holder, 
    payments.exp_date, 
    payments.payment_status, 
    payments.created_at, 
    orders.id AS order_id, 
    orders.status AS order_status,
    users.name AS user_name,
    users.email AS user_email
    FROM payments
    JOIN orders ON payments.order_id = orders.id
    JOIN users ON orders.user_id = users.id
    ORDER BY payments.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

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
                            <i class="bi bi-credit-card me-2"></i>Payment Details
                        </h1>
                        <p class="header-subtitle">View all payment transactions</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="container-fluid">
        <div class="modern-card">
            <div class="table-responsive">
                <table class="table modern-table">
                    <thead class="table-light">
                        <tr>
                            <th>Payment ID</th>
                            <th>Order Details</th>
                            <th>Customer</th>
                            <th>Payment Info</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($row['id']); ?></td>
                                <td>
                                    <div class="order-info">
                                        <div class="order-number">Order #<?php echo htmlspecialchars($row['order_id']); ?></div>
                                        <span class="status-badge <?php echo strtolower($row['order_status']); ?>">
                                            <?php echo htmlspecialchars($row['order_status']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name"><?php echo htmlspecialchars($row['user_name']); ?></div>
                                        <div class="customer-email"><?php echo htmlspecialchars($row['user_email']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="payment-info">
                                        <div class="card-number">**** **** **** <?php echo htmlspecialchars(substr($row['card_number'], -4)); ?></div>
                                        <div class="card-holder"><?php echo htmlspecialchars($row['card_holder']); ?></div>
                                        <div class="exp-date">Exp: <?php echo htmlspecialchars($row['exp_date']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($row['payment_status']); ?>">
                                        <?php echo htmlspecialchars($row['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <div><?php echo date('M j, Y', strtotime($row['created_at'])); ?></div>
                                        <div class="time"><?php echo date('g:i A', strtotime($row['created_at'])); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_order.php?id=<?php echo $row['order_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="View Order">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.modern-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    padding: 2rem;
    margin-bottom: 2rem;
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
    padding: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.modern-table td {
    padding: 1rem;
    vertical-align: middle;
}

.status-badge {
    padding: 0.5em 1em;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge.completed {
    background: #d1e7dd;
    color: #0f5132;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.failed {
    background: #f8d7da;
    color: #842029;
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

/* Hover effect for table rows */
.modern-table tbody tr {
    transition: all 0.3s ease;
}

.modern-table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.order-info, .customer-info, .payment-info, .date-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.order-number {
    font-weight: 600;
    color: #2c3e50;
}

.customer-name {
    font-weight: 600;
    color: #2c3e50;
}

.customer-email {
    font-size: 0.85rem;
    color: #6c757d;
}

.card-number {
    font-family: monospace;
    font-weight: 600;
}

.card-holder {
    font-size: 0.9rem;
}

.exp-date {
    font-size: 0.85rem;
    color: #6c757d;
}

.time {
    font-size: 0.85rem;
    color: #6c757d;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.action-buttons .btn {
    padding: 0.4rem 0.6rem;
}

.status-badge.processing {
    background: #cfe2ff;
    color: #084298;
}

.status-badge.completed {
    background: #d1e7dd;
    color: #0f5132;
}

.status-badge.cancelled {
    background: #f8d7da;
    color: #842029;
}
</style>

<?php 
// Free result set and close statement
$result->free();
$stmt->close();
$conn->close();
?>

</div> <!-- Close main-content div from admin_header.php -->
</body>
</html>
