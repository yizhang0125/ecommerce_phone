<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in, if not, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Get user information (e.g., username)
$user_name = '';
$user_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $user_name = $user_data['name'];
}

// Number of products per page
$products_per_page = 6;

// Get current page from URL, default to 1 if not set
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start_from = ($current_page - 1) * $products_per_page;

// Get search term from URL
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch total number of products with search term
$search_query = "%" . $search_term . "%";
$total_products_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE name LIKE ?");
$total_products_stmt->bind_param("s", $search_query);
$total_products_stmt->execute();
$total_products_result = $total_products_stmt->get_result();
$total_products = $total_products_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $products_per_page);

// Fetch products from the database with search term
$stmt = $conn->prepare("SELECT id, name, price, description, image, stock_quantity FROM products WHERE name LIKE ? LIMIT ?, ?");
$stmt->bind_param("sii", $search_query, $start_from, $products_per_page);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .carousel-item img {
            max-width: 900px; /* Increase the width further */
            max-height: 500px; /* Increase the height further */
            object-fit: contain; /* Ensure the image fits without cropping */
            margin: 0 auto; /* Center the image horizontally */
            display: block; /* Center the image */
        }
        .carousel-caption {
            background: rgba(0, 0, 0, 0.5);
            padding: 10px;
            border-radius: 5px;
        }
        .card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .card-body {
            flex: 1 0 auto;
        }
        .logo {
            width: 200px;
            height: auto;
            margin-right: 2px;
        }
        .header-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-lg">
    <div class="container">
        <!-- Navbar Brand (No Logo) -->
        <a class="navbar-brand fw-bold" href="#">E-Commerce Site</a>

        <!-- Toggle Button for Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Cart Icon with Larger Size -->
                <li class="nav-item">
                    <a class="nav-link text-white d-flex align-items-center" href="view_cart.php">
                        <i class="bi bi-cart-fill" style="font-size: 1.5rem; margin-right: 10px;"></i> Cart
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Order History Icon with Larger Size -->
                    <li class="nav-item">
                        <a class="nav-link text-white d-flex align-items-center" href="order_history.php">
                            <i class="bi bi-clock-history" style="font-size: 1.5rem; margin-right: 10px;"></i> Order History
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white d-flex align-items-center" href="user_account.php">
                            <i class="bi bi-person-circle me-2"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-transparent text-white ms-2 px-4 py-2" href="user_logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light me-2 px-4 py-2" href="user_login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light px-4 py-2" href="user_register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>

        </div>
    </div>
</nav>


<div class="container mt-5">
    <div class="header-container">
        <img src="uploads/logo.webp" alt="Logo" class="logo">
        <h1>Shop Our Products</h1>
    </div>

    <div class="container mt-4">
    <!-- Success Message (if any) -->
    <?php
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success" role="alert">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']); // Remove message after displaying it
    }
    ?>

    <!-- Search Bar -->
    <div class="mb-4">
        <form method="get" action="" class="d-flex justify-content-center">
            <input type="text" name="search" class="form-control me-2" placeholder="Search for products" aria-label="Search" value="<?php echo htmlspecialchars($search_term); ?>">
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
    </div>

<!-- Slideshow -->
<div id="carouselExampleAutoplaying" class="carousel slide mt-4" data-bs-ride="carousel" data-bs-interval="3000"> <!-- 3000ms interval -->
    <div class="carousel-inner text-center"> <!-- Add text-center for centering -->
        <?php
        $carousel_images = [
            ['path' => 'uploads/iphone15.webp', 'caption' => 'iPhone 15 - The Future of Smartphones'],
            ['path' => 'uploads/magsafe.jpg', 'caption' => 'MagSafe - A New Way to Charge'],
            ['path' => 'uploads/apple-watch.webp', 'caption' => 'Apple Watch - Your Health Companion']
        ];
        foreach ($carousel_images as $index => $image): ?>
            <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                <img src="<?php echo htmlspecialchars($image['path']); ?>" class="d-block" alt="Slide <?php echo $index + 1; ?>">
                <div class="carousel-caption d-none d-md-block">
                    <h5><?php echo htmlspecialchars($image['caption']); ?></h5>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

    <!-- Products -->
    <div class="row">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <img src="<?php echo htmlspecialchars($row['image'] ?: 'https://via.placeholder.com/150'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                        <p class="card-text"><strong>$<?php echo htmlspecialchars($row['price']); ?></strong></p>
                        <p class="card-text">Available Stock: <?php echo htmlspecialchars($row['stock_quantity']); ?></p>
                        <form method="post" action="add_cart.php" class="mt-auto">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <div class="mb-3">
                                <label for="quantity<?php echo $row['id']; ?>" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity<?php echo $row['id']; ?>" name="quantity" min="1" value="1" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- Pagination controls -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo ($current_page - 1); ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo ($current_page + 1); ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
