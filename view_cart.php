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
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Your Shopping Cart</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                    <td>$<?php echo htmlspecialchars($row['price']); ?></td>
                    <td>
                        <a href="update_cart.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <form method="post" action="remove_cart.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php
                    $total += $row['quantity'] * $row['price'];
                endwhile;
                ?>
                <tr>
                    <td colspan="3" class="text-end"><strong>Shipping Fee</strong></td>
                    <td>$<?php echo number_format($shipping_fee, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total</strong></td>
                    <td>$<?php echo number_format($total + $shipping_fee, 2); ?></td>
                </tr>
            </tbody>
        </table>
        <a href="check_out_process.php" class="btn btn-primary">Proceed to Checkout</a>
        <a href="index.php" class="btn btn-secondary">Back to Shop</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
