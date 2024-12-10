<?php
session_start();
require 'db_connection.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];

// Fetch cart items
$stmt = $conn->prepare("SELECT cart.id, cart.quantity, products.name, products.price FROM cart 
                        JOIN products ON cart.product_id = products.id WHERE cart.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize total price
$total = 0;

// Fetch the shipping fee from settings table
$stmt = $conn->prepare("SELECT shipping_fee FROM settings WHERE id = 1");
$stmt->execute();
$shipping_result = $stmt->get_result();
$shipping_fee = 0; // Default value for shipping fee
if ($shipping_result->num_rows > 0) {
    $shipping_fee = $shipping_result->fetch_assoc()['shipping_fee'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Checkout</h1>
        <?php if ($result->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format($row['price'], 2)); ?></td>
                </tr>
                <?php
                    $total += $row['quantity'] * $row['price'];
                endwhile;
                ?>
                <tr>
                    <td colspan="2" class="text-end"><strong>Shipping Fee</strong></td>
                    <td>$<?php echo number_format($shipping_fee, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="text-end"><strong>Total</strong></td>
                    <td>$<?php echo number_format($total + $shipping_fee, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <form method="post" action="order_processing.php">
            <input type="hidden" name="total_price" value="<?php echo number_format($total + $shipping_fee, 2); ?>">
            
            <div class="mb-3">
                <label for="shipping_address" class="form-label">Shipping Address</label>
                <input type="text" class="form-control" id="shipping_address" name="shipping_address" required>
            </div>
            <div class="mb-3">
                <label for="shipping_city" class="form-label">City</label>
                <input type="text" class="form-control" id="shipping_city" name="shipping_city" required>
            </div>
            <div class="mb-3">
                <label for="shipping_state" class="form-label">State</label>
                <input type="text" class="form-control" id="shipping_state" name="shipping_state" required>
            </div>
            <div class="mb-3">
                <label for="shipping_zip" class="form-label">ZIP Code</label>
                <input type="text" class="form-control" id="shipping_zip" name="shipping_zip" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Proceed to Checkout</button>
        </form>
        <?php else: ?>
        <p>No items found in your cart.</p>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
