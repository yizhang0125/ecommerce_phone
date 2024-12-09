<?php
require 'db_connection.php';

if (isset($_POST['id'], $_POST['name'], $_POST['description'], $_POST['price'], $_POST['stock_quantity'], $_POST['category_id'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $category_id = $_POST['category_id'];

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['name'] != '') {
        $image = 'uploads/' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image);

        $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ?, image = ? WHERE id = ?");
        $stmt->bind_param("issdisi", $category_id, $name, $description, $price, $stock_quantity, $image, $id);
    } else {
        $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ? WHERE id = ?");
        $stmt->bind_param("issdii", $category_id, $name, $description, $price, $stock_quantity, $id);
    }

    $stmt->execute();
    header("Location: admin_dashboard.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-5">Update Product</h2>

        <?php
        // Include database connection
        require 'db_connection.php';

        // Fetch the product details from the database
        if (isset($_GET['id'])) {
            $product_id = $_GET['id'];
            $stmt = $conn->prepare("SELECT id, name, description, price, stock_quantity, category_id, image FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();

            if ($product):
        ?>

        <form method="post" action="edit_product.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">

            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <?php
                    // Fetch categories from the database
                    $stmt = $conn->prepare("SELECT id, name FROM categories");
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()):
                        $selected = ($row['id'] == $product['category_id']) ? 'selected' : '';
                        echo "<option value=\"{$row['id']}\" $selected>{$row['name']}</option>";
                    endwhile;
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Product Image (Optional)</label>
                <input type="file" class="form-control" id="image" name="image">
                <?php if ($product['image']): ?>
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image" class="img-thumbnail mt-2" style="max-width: 200px;">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Update Product</button>
        </form>

        <?php
            else:
                echo "<p class='text-danger'>Product not found.</p>";
            endif;
        }
        ?>

        <p class="mt-3">
            <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

