<?php
require_once 'admin_header.php';
require 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('location: admin_login.php');
    exit();
}

$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';  // Default to 'Admin' if not set

// Get current shipping fee from settings table
$query = "SELECT shipping_fee FROM settings WHERE id = 1";
$result = $conn->query($query);
$shipping_fee = $result->fetch_assoc()['shipping_fee'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_fee = $_POST['shipping_fee'];
    $update_query = "UPDATE settings SET shipping_fee = ? WHERE id = 1";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('d', $new_fee);
    
    if ($stmt->execute()) {
        $shipping_fee = $new_fee;
        $success_message = "Shipping fee updated successfully!";
    } else {
        $error_message = "Error updating shipping fee.";
    }
    $stmt->close();
}
?>

<div class="dashboard-content">
    <!-- Header Section -->
    <div class="content-header-wrapper mb-4">
        <div class="content-header p-4">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-12">
                        <h1 class="header-title">
                            <i class="bi bi-truck me-2"></i>Shipping Fee Settings
                        </h1>
                        <p class="header-subtitle">Manage global shipping fee</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipping Fee Card -->
    <div class="container-fluid">
        <div class="modern-card">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="shipping-fee-form">
                <div class="mb-4">
                    <label class="form-label">Global Shipping Fee</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" 
                               name="shipping_fee" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($shipping_fee); ?>" 
                               step="0.01" 
                               min="0" 
                               required>
                    </div>
                    <div class="form-text">This fee will be applied to all orders.</div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>Update Shipping Fee
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.modern-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    padding: 2rem;
    margin-bottom: 2rem;
}

.shipping-fee-form {
    max-width: 500px;
}

.input-group {
    border-radius: 12px;
    overflow: hidden;
}

.input-group-text {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
}

.form-control {
    border: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
    font-size: 1.1rem;
}

.btn-primary {
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    background: linear-gradient(45deg, #0d6efd, #0a58ca);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
}

.alert {
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
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

</div>
</body>
</html>
