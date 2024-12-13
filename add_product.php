<?php
require 'db_connection.php';

// Start the session
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['name'], $_POST['description'], $_POST['price'], $_POST['stock_quantity'], $_POST['category_id'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock_quantity = $_POST['stock_quantity'];
        $category_id = $_POST['category_id'];

        // Image upload handling
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $image = 'uploads/' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image)) {
                // Image uploaded successfully
            } else {
                echo "Failed to upload image.<br>";
                $image = ''; // No image uploaded
            }
        }

        // Check if the category_id exists
        $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();

            // Insert new product into the database
            $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, stock_quantity, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdis", $category_id, $name, $description, $price, $stock_quantity, $image);
            if ($stmt->execute()) {
                // Set success message and redirect to products_section.php
                $_SESSION['success_message'] = "Product added successfully!";
                header("Location: products_section.php");
                exit();
            } else {
                echo "Failed to add product: " . $stmt->error;
            }
        } else {
            echo "Invalid category ID.";
        }
    } else {
        echo "Required fields are missing.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Add New Product</h2>



        <!-- Product Addition Form -->
        <form method="post" action="add_product.php" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" required></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" required>
            </div>
            <div class="mb-3">
                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <!-- Populate categories dynamically from the database -->
                    <option value="1">Phone</option>
                    <option value="2">Accessories</option>
                    <!-- You can fetch these options dynamically from the database -->
                </select>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Product Image</label>
                <input type="file" class="form-control" id="image" name="image">
            </div>
            <button type="submit" class="btn btn-primary">Add Product</button>
        </form>

        <p class="mt-3">
            <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
