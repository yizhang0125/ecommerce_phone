<?php
require_once 'admin_header.php';
require 'db_connection.php';

// At the top of your file, add this to get categories
$categories_query = "SELECT * FROM categories";
$categories_result = $conn->query($categories_query);

// Create uploads directory if it doesn't exist
if (!file_exists('uploads/')) {
    mkdir('uploads/', 0777, true);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock_quantity = $_POST['stock_quantity'] ?? 0;
    $category_id = $_POST['category_id'] ?? '';
    $status = 'available';

    // Validate required fields
    if (empty($name) || empty($description) || empty($price) || empty($stock_quantity) || empty($category_id)) {
        $error_message = "All fields are required.";
    } else {
        // Handle image upload with better error checking
        $target_dir = "uploads/";
        $image = $_FILES['image']['name'];  // Just the original filename
        $db_image_path = "uploads/" . $image;  // Will be like: uploads/apple-watch.webp
        $target_file = $target_dir . $image;   // Full path for upload

        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Check if directory is writable
        if (!is_writable($target_dir)) {
            $error_message = "Error: Upload directory is not writable";
        } else {
            // Check if image file is a actual image or fake image
            if(isset($_FILES["image"]) && $_FILES["image"]["tmp_name"] != "") {
                $check = getimagesize($_FILES["image"]["tmp_name"]);
                if($check !== false) {
                    // Allow certain file formats
                    if($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif" || $imageFileType == "webp" ) {
                        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                            $query = "INSERT INTO products (name, description, price, stock_quantity, category_id, image, status) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("ssdiiss", $name, $description, $price, $stock_quantity, $category_id, $db_image_path, $status);
                            
                            if ($stmt->execute()) {
                                $success_message = "Product added successfully!";
                            } else {
                                $error_message = "Error adding product: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $error_message = "Sorry, there was an error uploading your file. Error: " . error_get_last()['message'];
                        }
                    } else {
                        $error_message = "Sorry, only JPG, JPEG, PNG, GIF & WebP files are allowed.";
                    }
                } else {
                    $error_message = "File is not an image.";
                }
            } else {
                $error_message = "Please select an image to upload.";
            }
        }
    }
}
?>

<div class="dashboard-content">
    <!-- Header Section with Gradient Background -->
    <div class="content-header-wrapper mb-4">
        <div class="content-header p-4">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-12">
                        <h1 class="header-title">
                            <i class="bi bi-plus-circle me-2"></i>Add Product
                        </h1>
                        <p class="header-subtitle">Add new products to your inventory</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Product Form -->
    <div class="container-fluid">
        <div class="modern-card">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="product-form">
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
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="5" required></textarea>
                            </div>

                            <!-- Price and Category -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" name="price" class="form-control" 
                                                step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">Stock Quantity</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-box"></i></span>
                                            <input type="number" name="stock_quantity" class="form-control" 
                                                min="0" required>
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
                                            <option value="<?php echo $category['id']; ?>">
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
                                <div class="image-preview" id="imagePreview">
                                    <i class="bi bi-cloud-upload"></i>
                                    <p>Click or drag image here</p>
                                </div>
                                <input type="file" 
                                    name="image" 
                                    class="form-control" 
                                    accept="image/jpeg,image/png,image/gif,image/webp" 
                                    required 
                                    id="imageInput">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-12">
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-2"></i>Add Product
                            </button>
                            <button type="reset" class="btn btn-light ms-2">
                                <i class="bi bi-x-lg me-2"></i>Reset Form
                            </button>
                        </div>
                    </div>
                </div>
            </form>
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

textarea.form-control {
    min-height: 120px;
}

.image-upload-container {
    background: white;
    border: 2px dashed #dee2e6;
    border-radius: 15px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.image-upload-container:hover {
    border-color: #0d6efd;
    background: #f8f9fa;
}

.image-preview {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    padding: 1rem;
}

.image-preview i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.image-preview img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    object-fit: contain;
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

.btn-light {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.btn-light:hover {
    background: #e9ecef;
}

.alert {
    border-radius: 12px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
    border: none;
    display: flex;
    align-items: center;
}

.alert-success {
    background: #d1e7dd;
    color: #0f5132;
}

.alert-danger {
    background: #f8d7da;
    color: #842029;
}

.form-label {
    font-weight: 500;
    color: #344767;
    margin-bottom: 0.5rem;
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

<script>
document.querySelector('.image-upload-container').addEventListener('click', function() {
    document.getElementById('imageInput').click();
});

document.getElementById('imageInput').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    const file = e.target.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        preview.innerHTML = `<img src="${e.target.result}" class="img-fluid">`;
    }

    if (file) {
        reader.readAsDataURL(file);
    }
});
</script>

</div>
</body>
</html>
