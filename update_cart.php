<?php
// Check if a session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db_connection.php';

if (isset($_POST['product_id'], $_POST['quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("iii", $quantity, $user_id, $product_id);
    $stmt->execute();

    // Redirect to cart page after updating
    header("Location: view_cart.php");
    exit(); // Ensure no further code is executed after redirect
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Cart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="text-center mt-5">Update Cart</h2>
                <form method="post" action="update_cart.php">
                    <?php
                    // No need to call session_start() again here
                    require 'db_connection.php';

                    $user_id = $_SESSION['user_id'];
                    $stmt = $conn->prepare("SELECT cart.product_id, cart.quantity, products.name, products.price FROM cart 
                                            JOIN products ON cart.product_id = products.id WHERE cart.user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()):
                    ?>
                        <div class="mb-3">
                            <label for="product_quantity_<?php echo $row['product_id']; ?>" class="form-label">
                                <?php echo htmlspecialchars($row['name']); ?> (Price: $<?php echo htmlspecialchars($row['price']); ?>)
                            </label>
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($row['product_id']); ?>">
                            <input type="number" class="form-control" id="product_quantity_<?php echo $row['product_id']; ?>" name="quantity" min="1" value="<?php echo htmlspecialchars($row['quantity']); ?>" required>
                        </div>
                    <?php endwhile; ?>

                    <button type="submit" class="btn btn-primary w-100">Update Cart</button>
                </form>
                <p class="text-center mt-3">
                    <a href="view_cart.php" class="btn btn-secondary">Back to Cart</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (Optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
