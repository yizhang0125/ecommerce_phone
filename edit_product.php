<?php
session_start();
require 'db_connection.php';

// Get product details
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        $_SESSION['error_message'] = "Product not found!";
        header('Location: products_section.php');
        exit();
    }
}

// Get all categories for dropdown
$categories_query = "SELECT * FROM categories";
$categories_result = $conn->query($categories_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock_quantity = $_POST['stock_quantity'] ?? 0;
    $category_id = $_POST['category_id'] ?? '';
    $status = $stock_quantity > 0 ? 'available' : 'sold out';
    $update_successful = false;
    $error_message = '';

    try {
        // Handle image upload if new image is selected
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "uploads/";
            $image = $_FILES['image']['name'];
            $target_file = $target_dir . basename($image);
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

            // Delete old image if exists
            if ($product['image'] && file_exists($product['image'])) {
                unlink($product['image']);
            }

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $db_image_path = "uploads/" . $image;
            } else {
                throw new Exception("Error uploading image");
            }
        }

        // Update product in database
        $query = "UPDATE products SET 
                 name = ?, 
                 description = ?, 
                 price = ?, 
                 stock_quantity = ?, 
                 category_id = ?, 
                 status = ?";
        
        $params = [$name, $description, $price, $stock_quantity, $category_id, $status];
        $types = "ssdiis";

        if (isset($db_image_path)) {
            $query .= ", image = ?";
            $params[] = $db_image_path;
            $types .= "s";
        }

        $query .= " WHERE id = ?";
        $params[] = $product_id;
        $types .= "i";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Product updated successfully!";
            header('Location: products_section.php');
            exit();
        } else {
            throw new Exception("Error updating product");
        }

    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Include header after all redirects
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
                            <i class="bi bi-pencil-square me-2"></i>Edit Product
                        </h1>
                        <p class="header-subtitle">Update product information</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Form -->
    <div class="container-fluid">
        <div class="modern-card">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="edit-product-form">
                <div class="row g-4">
                    <!-- Left Column -->
                    <div class="col-md-8">
                        <div class="form-section">
                            <h5 class="section-title">Basic Information</h5>
                            
                            <!-- Product Name -->
                            <div class="mb-4">
                                <label class="form-label">Product Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                    <input type="text" 
                                           name="name" 
                                           class="form-control" 
                                           value="<?php echo htmlspecialchars($product['name']); ?>" 
                                           required>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label class="form-label">Description</label>
                                <textarea name="description" 
                                          class="form-control" 
                                          rows="5" 
                                          required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>

                            <!-- Price and Stock -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" 
                                                   name="price" 
                                                   class="form-control" 
                                                   step="0.01" 
                                                   min="0" 
                                                   value="<?php echo $product['price']; ?>" 
                                                   required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">Stock Quantity</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-box"></i></span>
                                            <input type="number" 
                                                   name="stock_quantity" 
                                                   class="form-control" 
                                                   min="0" 
                                                   value="<?php echo $product['stock_quantity']; ?>" 
                                                   required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="mb-4">
                                <label class="form-label">Category</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-folder"></i></span>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php while($category = $categories_result->fetch_assoc()): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-4">
                        <div class="form-section">
                            <h5 class="section-title">Product Image</h5>
                            <div class="image-upload-container">
                                <div class="current-image mb-3">
                                    <label class="form-label">Current Image</label>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="Current product image" 
                                         class="img-fluid rounded">
                                </div>
                                <div class="new-image">
                                    <label class="form-label">Upload New Image</label>
                                    <input type="file" 
                                           name="image" 
                                           class="form-control" 
                                           accept="image/jpeg,image/png,image/gif,image/webp">
                                    <small class="text-muted">Leave empty to keep current image</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="col-12">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>Save Changes
                            </button>
                            <a href="products_section.php" class="btn btn-light ms-2">
                                <i class="bi bi-x-lg me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Add your existing styles from products_section.php */
.modern-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 1.5rem;
    height: 100%;
}

.section-title {
    color: #344767;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.input-group {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.input-group-text {
    background: white;
    border: 1px solid #e9ecef;
    padding: 0.75rem 1rem;
    color: #6c757d;
}

.form-control, .form-select {
    border: 1px solid #e9ecef;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    background: white;
}

.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
}

.image-upload-container {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
}

.current-image img {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.form-actions {
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(45deg, #0d6efd, #0a58ca);
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
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

</div> <!-- Close main-content div from admin_header.php -->
</body>
</html>
