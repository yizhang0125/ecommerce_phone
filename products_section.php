<?php
session_start();
require 'db_connection.php';

// Fetch products from the database
$stmt = $conn->prepare("SELECT products.id, products.name, products.price, products.stock_quantity, categories.name AS category_name 
                        FROM products 
                        JOIN categories ON products.category_id = categories.id");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2>Products</h2>

            <!-- Display success message if available -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']); // Remove message after displaying
                    ?>
                </div>
            <?php endif; ?>

            <!-- Add Product Button -->
            <a href="add_product.php" class="btn btn-primary mb-3">Add Product</a>

            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                        <p class="card-text">
                            Price: $<?php echo htmlspecialchars($row['price']); ?><br>
                            Stock: <?php echo htmlspecialchars($row['stock_quantity']); ?><br>
                            Category: <?php echo htmlspecialchars($row['category_name']); ?>
                        </p>
                        <a href="edit_product.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-warning">Edit</a>
                        <form method="post" action="delete_product.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Back to Dashboard Button -->
            <a href="admin_dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
        </div>
    </div>
</div>

<!-- Bootstrap JS (Optional, for interactive components) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
