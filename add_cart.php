<?php
session_start();
require 'db_connection.php';

if (isset($_POST['product_id'], $_POST['quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $user_id = $_SESSION['user_id'];

    // Check if the product has enough stock
    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if ($product['stock_quantity'] >= $quantity) {
        // Check if the product is already in the cart
        $stmt = $conn->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Update quantity if already in cart
            $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        } else {
            // Insert new item into cart
            $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        }

        // Execute the query to add/update the cart
        $stmt->execute();

        // Reduce the stock quantity in the products table
        $new_stock_quantity = $product['stock_quantity'] - $quantity;
        $stmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_stock_quantity, $product_id);
        $stmt->execute();

        $_SESSION['message'] = "Product added to cart and stock updated!";
    } else {
        $_SESSION['error'] = "Not enough stock available!";
    }

    // Redirect to view_cart.php
    header("Location: view_cart.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add to Cart</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <h2 class="text-center mt-5">Add to Cart</h2>
                <form method="post" action="add_to_cart.php">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product ID</label>
                        <input type="text" class="form-control" id="product_id" name="product_id" required>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Add to Cart</button>
                </form>
                <p class="text-center mt-3">
                    <a href="view_cart.php">View Cart</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (Optional, for interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

