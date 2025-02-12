<?php
require_once 'admin_header.php';
require 'db_connection.php';

// Get search term and filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Pagination
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Search condition
$search_condition = '';
$params = [];
$types = '';

if (!empty($search)) {
    $search_condition = "WHERE name LIKE ? OR description LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
    $types = 'ss';
} else {
    $search_condition = "WHERE 1=1";
}

// Add stock filter condition
if (!empty($filter)) {
    switch ($filter) {
        case 'low_stock':
            $search_condition .= " AND stock_quantity > 0 AND stock_quantity <= 10";
            break;
        case 'out_stock':
            $search_condition .= " AND stock_quantity = 0";
            break;
        case 'in_stock':
            $search_condition .= " AND stock_quantity > 10";
            break;
    }
}

// Get total number of products
$count_query = "SELECT COUNT(*) as total FROM products $search_condition";
if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_products = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_products = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_products / $items_per_page);

// Fetch products
$query = "SELECT * FROM products $search_condition ORDER BY id DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $items_per_page;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="dashboard-content">
    <!-- Header Section with Gradient Background -->
    <div class="content-header-wrapper mb-4">
        <div class="content-header p-4">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-12 col-md-6">
                        <h1 class="header-title">
                            <i class="bi bi-box-seam me-2"></i>Products
                        </h1>
                        <p class="header-subtitle">Manage your product inventory</p>
                    </div>
                    <div class="col-12 col-md-6 text-md-end">
                        <a href="add_product.php" class="btn btn-primary btn-lg add-product-btn">
                            <i class="bi bi-plus-lg me-2"></i>Add New Product
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards with Glass Effect -->
    <div class="container-fluid mb-4">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="glass-card primary">
                    <div class="glass-card-content">
                        <div class="glass-card-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="glass-card-info">
                            <h3><?php echo number_format($total_products); ?></h3>
                            <p>Total Products</p>
                        </div>
                    </div>
                    <div class="glass-card-footer">
                        <small>Updated just now</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="glass-card success">
                    <div class="glass-card-content">
                        <div class="glass-card-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="glass-card-info">
                            <h3><?php 
                                $in_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity > 0")->fetch_assoc()['count'];
                                echo number_format($in_stock); 
                            ?></h3>
                            <p>In Stock</p>
                        </div>
                    </div>
                    <div class="glass-card-footer">
                        <small>Active products</small>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="glass-card danger">
                    <div class="glass-card-content">
                        <div class="glass-card-icon">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                        <div class="glass-card-info">
                            <h3><?php 
                                $low_stock = $conn->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10")->fetch_assoc()['count'];
                                echo number_format($low_stock); 
                            ?></h3>
                            <p>Low Stock</p>
                        </div>
                    </div>
                    <div class="glass-card-footer">
                        <small>Needs attention</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Search and Filter Section -->
    <div class="search-filter-container mb-4">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-6">
                <div class="modern-search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="search" 
                           class="form-control form-control-lg" 
                           placeholder="Search products..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <select name="filter" class="form-select form-select-lg modern-select">
                    <option value="">All Products</option>
                    <option value="in_stock" <?php echo $filter === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                    <option value="low_stock" <?php echo $filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="out_stock" <?php echo $filter === 'out_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary btn-lg w-100 modern-button">
                    <i class="bi bi-search me-2"></i>Search
                </button>
            </div>
        </form>
    </div>

    <!-- Products Table with Modern Design -->
    <div class="modern-card">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['success_message']; 
                    unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
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

        <div class="table-responsive">
            <table class="table modern-table">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">ID</th>
                        <th>Image</th>
                        <th>Product Details</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="text-center">#<?php echo $row['id']; ?></td>
                        <td>
                            <div class="product-img-wrapper">
                                <?php 
                                $image_path = $row['image'];
                                // Check if the image path already includes 'uploads/'
                                if (strpos($image_path, 'uploads/') === false) {
                                    $image_path = 'uploads/' . $image_path;
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                     alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                     class="product-thumbnail">
                            </div>
                        </td>
                        <td>
                            <div class="product-info">
                                <h5 class="product-name mb-1"><?php echo htmlspecialchars($row['name']); ?></h5>
                                <p class="product-description mb-0">
                                    <?php 
                                    echo strlen($row['description']) > 50 ? 
                                         substr(htmlspecialchars($row['description']), 0, 50) . '...' : 
                                         htmlspecialchars($row['description']); 
                                    ?>
                                </p>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="price-tag">$<?php echo number_format($row['price'], 2); ?></div>
                        </td>
                        <td class="text-center">
                            <span class="stock-badge <?php echo $row['stock_quantity'] < 10 ? 'low-stock' : ''; ?>">
                                <?php echo $row['stock_quantity']; ?> units
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="status-badge <?php echo $row['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                <?php echo $row['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="action-buttons">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-info" 
                                        onclick="viewProduct(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                <a href="edit_product.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="Edit Product">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteProduct(<?php echo $row['id']; ?>)"
                                        title="Delete Product">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Enhanced Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

    <style>
    /* Modern Header Styling */
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

    /* Glass Card Effect */
    .glass-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.18);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(31, 38, 135, 0.2);
    }

    .glass-card.primary { border-left: 4px solid #4e73df; }
    .glass-card.success { border-left: 4px solid #1cc88a; }
    .glass-card.danger { border-left: 4px solid #e74a3b; }

    .glass-card-content {
            display: flex;
            align-items: center;
        gap: 1.5rem;
    }

    .glass-card-icon {
        font-size: 2.5rem;
        opacity: 0.8;
        color: #4e73df;
        }

    .glass-card.success .glass-card-icon { color: #1cc88a; }
    .glass-card.danger .glass-card-icon { color: #e74a3b; }

    .glass-card-info h3 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        color: #2c3e50;
    }

    .glass-card-info p {
        margin: 0.25rem 0 0;
        color: #6c757d;
        font-size: 1rem;
    }

    .glass-card-footer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0,0,0,0.1);
        color: #6c757d;
        }

    /* Modern Search Box */
    .modern-search-box {
        position: relative;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }

    .modern-search-box input {
        border: none;
        padding-left: 3rem;
        height: 3.5rem;
        border-radius: 12px;
    }

    .modern-search-box .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 1.2rem;
    }

    /* Modern Select */
    .modern-select {
        height: 3.5rem;
        border-radius: 12px;
        border: none;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    }

    /* Modern Button */
    .modern-button {
        height: 3.5rem;
        border-radius: 12px;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        }

    .modern-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    }

    /* Modern Card */
    .modern-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        padding: 1.5rem;
    }

    /* Modern Table */
    .modern-table {
        margin: 0;
    }

    .modern-table th {
        background: #f8f9fa;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        padding: 1rem;
        border-bottom: 2px solid #e9ecef;
    }

    .modern-table td {
        padding: 1rem;
        vertical-align: middle;
        }

    /* Add Product Button */
    .add-product-btn {
        padding: 0.8rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        background: rgba(255,255,255,0.2);
        border: 2px solid rgba(255,255,255,0.3);
        color: white;
        backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

    .add-product-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-2px);
        }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .header-title {
            font-size: 2rem;
        }
        
        .glass-card {
            margin-bottom: 1rem;
        }
        
        .modern-search-box,
        .modern-select,
        .modern-button {
            margin-bottom: 1rem;
        }
    }

    /* Product Table Styling */
    .product-img-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        overflow: hidden;
        background: #f8f9fa;
        }

    .product-thumbnail {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-info {
        max-width: 300px;
    }

    .product-name {
        font-size: 1rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.25rem;
            }

    .product-description {
        font-size: 0.875rem;
        color: #6c757d;
    }

    .price-tag {
        font-weight: 600;
        color: #2c3e50;
        font-size: 1.1rem;
    }

    .stock-badge {
        padding: 0.5em 1em;
        border-radius: 20px;
        background: #e9ecef;
        font-size: 0.875rem;
    }

    .stock-badge.low-stock {
        background: #fff3cd;
        color: #856404;
    }

    .status-badge {
        padding: 0.5em 1em;
        border-radius: 20px;
        font-size: 0.875rem;
    }

    .status-badge.in-stock {
        background: #d4edda;
        color: #155724;
            }

    .status-badge.out-of-stock {
        background: #f8d7da;
        color: #721c24;
            }

    .action-buttons .btn {
        margin: 0 0.25rem;
        }
    </style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function deleteProduct(productId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete_product.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id';
            input.value = productId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<!-- Product View Modal -->
<div class="modal fade" id="productViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="product-view-image">
                            <img src="" alt="Product Image" id="modalProductImage">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="product-view-details">
                            <h3 id="modalProductName"></h3>
                            <div class="category-badge" id="modalProductCategory"></div>
                            <div class="price-tag" id="modalProductPrice"></div>
                            <div class="stock-info" id="modalProductStock"></div>
                            <div class="description" id="modalProductDescription"></div>
                        </div>
                    </div>
        </div>
    </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Update these styles for the modal image */
.product-view-image {
    width: 100%;
    height: 400px; /* Increased height */
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 1rem;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-view-image img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* Changed from cover to contain */
    padding: 1rem;
}

/* Update modal size */
.modal-dialog {
    max-width: 800px; /* Increased width */
}

.modal-body {
    padding: 2rem;
}

/* Update the product details styles */
.product-view-details {
    padding: 1rem;
}

.product-view-details h3 {
    font-size: 1.8rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.category-badge {
    display: inline-block;
    padding: 0.5em 1em;
    background: #e9ecef;
    border-radius: 20px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
}

.price-tag {
    font-size: 2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 1.5rem;
}

.stock-info {
    margin-bottom: 1.5rem;
    padding: 0.7em 1.2em;
    border-radius: 20px;
    display: inline-block;
    font-weight: 600;
    font-size: 1.1rem;
}

.stock-info.in-stock {
    background: #d1e7dd;
    color: #0f5132;
}

.stock-info.low-stock {
    background: #fff3cd;
    color: #856404;
}

.stock-info.out-of-stock {
    background: #f8d7da;
    color: #842029;
}

.description {
    color: #6c757d;
    line-height: 1.8;
    font-size: 1rem;
    font-weight: normal;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    margin-top: 1.5rem;
}

.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.modal-header {
    border-bottom: 2px solid #e9ecef;
    padding: 1.5rem;
}

.modal-header .modal-title {
    font-size: 1.4rem;
    font-weight: 600;
    color: #2c3e50;
}

.modal-footer {
    border-top: 2px solid #e9ecef;
    padding: 1.5rem;
}

/* Keep your existing image styles */
.product-view-image {
    width: 100%;
    height: 400px;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 1rem;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-view-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 1rem;
}

.modal-dialog {
    max-width: 800px;
}

.modal-body {
    padding: 2rem;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function viewProduct(product) {
    // Fix image path if needed
    let imagePath = product.image;
    if (imagePath && !imagePath.includes('uploads/')) {
        imagePath = 'uploads/' + imagePath;
    }
    
    // Update modal content
    document.getElementById('modalProductImage').src = imagePath;
    document.getElementById('modalProductName').textContent = product.name;
    document.getElementById('modalProductCategory').textContent = product.category_name;
    document.getElementById('modalProductPrice').textContent = '$' + parseFloat(product.price).toFixed(2);

    // Set stock status
    const stockElement = document.getElementById('modalProductStock');
    if (product.stock_quantity > 10) {
        stockElement.className = 'stock-info in-stock';
        stockElement.textContent = 'In Stock (' + product.stock_quantity + ' units)';
    } else if (product.stock_quantity > 0) {
        stockElement.className = 'stock-info low-stock';
        stockElement.textContent = 'Low Stock (' + product.stock_quantity + ' units)';
            } else {
        stockElement.className = 'stock-info out-of-stock';
        stockElement.textContent = 'Out of Stock';
            }
    
    document.getElementById('modalProductDescription').textContent = product.description;

    // Show modal
    const productModal = new bootstrap.Modal(document.getElementById('productViewModal'));
    productModal.show();
}
</script>

</div> <!-- Close main-content div from admin_header.php -->
</body>
</html>
