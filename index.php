<?php
session_start();
require 'db_connection.php';

// Add this near the top of your file after session_start()
$cart_count = 0;
// Get cart count from database
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_stmt = $conn->prepare("
        SELECT SUM(quantity) as total_items 
        FROM cart 
        WHERE user_id = ?
    ");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    if ($cart_result->num_rows > 0) {
        $cart_count = (int)$cart_result->fetch_assoc()['total_items'];
    }
}

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

// Initialize search term
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination setup
$items_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Get total number of items
$count_query = "SELECT COUNT(*) as total FROM products WHERE stock_quantity > 0";
$count_result = $conn->query($count_query);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Search functionality
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Base query
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

// Add search condition if search term exists
if (!empty($search_query)) {
    $search_term = "%{$search_query}%";
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
}

// Add category filter if selected
if ($category_id > 0) {
    $query .= " AND p.category_id = ?";
}

$query .= " ORDER BY p.id DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);

if (!empty($search_query) && $category_id > 0) {
    $stmt->bind_param("ssi", $search_term, $search_term, $category_id);
} elseif (!empty($search_query)) {
    $stmt->bind_param("ss", $search_term, $search_term);
} elseif ($category_id > 0) {
    $stmt->bind_param("i", $category_id);
}

$stmt->execute();
$products = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhoneStore - Premium Mobile Devices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --secondary: #6366f1;
            --dark: #1e1b4b;
            --light: #f5f3ff;
            --text: #4b5563;
            --surface: #ffffff;
            --accent: #818cf8;
            --border: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--light);
            color: var(--text);
            line-height: 1.6;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 30px;
            padding: 4rem 2rem;
            margin-bottom: 4rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, 
                                      rgba(255, 255, 255, 0.1), 
                                      transparent 50%);
            opacity: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: #94a3b8;
            margin-bottom: 2rem;
        }

        /* Search Section */
        .search-container {
            max-width: 800px;
            margin: -2rem auto 4rem;
            padding: 0 1rem;
            position: relative;
            z-index: 10;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            background: var(--surface);
            padding: 0.8rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .search-input {
            flex: 1;
            border: 2px solid var(--border);
            padding: 1.2rem 1.5rem;
            border-radius: 15px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1);
        }

        .search-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1.2rem 2.5rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }

        /* Carousel Section */
        .carousel-container {
            max-width: 800px;
            margin: 0 auto 5rem;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            background: var(--surface);
            position: relative;
        }

        .carousel-item {
            height: 400px;
            background: var(--surface);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .carousel-item img {
            max-height: 300px;
            width: auto;
            max-width: 80%;
            object-fit: contain;
            margin: 0 auto;
            display: block;
            transition: transform 0.5s ease;
        }

        .carousel-caption {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            border-radius: 10px;
            padding: 1rem 1.5rem;
            position: absolute;
            left: 5%;
            right: auto;
            bottom: 2rem;
            text-align: left;
            max-width: 80%;
            width: auto;
        }

        .carousel-caption h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.2rem;
        }

        /* Products Section */
        .products-section {
            padding: 4rem 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -1rem;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2.5rem;
            padding: 1rem;
        }

        .product-card {
            background: var(--surface);
            border-radius: 25px;
            overflow: hidden;
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .product-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 300px;
            object-fit: contain;
            background: #f8fafc;
            padding: 2rem;
            transition: transform 0.4s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.1);
        }

        .product-content {
            padding: 2.5rem;
        }

        .product-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .product-description {
            color: var(--text);
            margin-bottom: 1.5rem;
            font-size: 1rem;
            line-height: 1.7;
        }

        .product-price {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-stock {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--light);
            color: var(--primary);
            border-radius: 100px;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        .quantity-input {
            width: 100%;
            padding: 1.2rem;
            border: 2px solid var(--border);
            border-radius: 15px;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .add-to-cart {
            width: 100%;
            padding: 1.2rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
        }

        .add-to-cart:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            margin: 4rem 0;
        }

        .page-link {
            padding: 1.2rem 1.8rem;
            border: 2px solid var(--border);
            border-radius: 15px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .page-link:hover,
        .page-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2);
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .search-form {
                flex-direction: column;
            }

            .search-button {
                width: 100%;
            }

            .carousel-item {
                height: 300px;
            }

            .carousel-item img {
                max-height: 250px;
            }

            .carousel-caption {
                padding: 0.75rem 1rem;
                bottom: 1.5rem;
                left: 1rem;
                max-width: 90%;
            }

            .carousel-caption h5 {
                font-size: 1rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .product-card {
                max-width: 400px;
                margin: 0 auto;
            }
        }

        @media (max-width: 576px) {
            .carousel-caption {
                padding: 0.5rem 0.75rem;
                bottom: 1rem;
                left: 0.75rem;
                max-width: 85%;
            }

            .carousel-caption h5 {
                font-size: 0.875rem;
            }
        }

        .floating-cart {
            position: fixed;
            bottom: 40px;
            right: 40px;
            z-index: 1000;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #2196F3;
            color: white;
            border-radius: 50px;
            padding: 0 25px 0 20px;
            height: 60px;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
            transition: all 0.3s ease;
        }

        .floating-cart:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(33, 150, 243, 0.6);
            background: #1976D2;
        }

        .floating-cart svg {
            width: 32px;
            height: 32px;
            color: white;
            transition: all 0.3s ease;
            stroke-width: 2;
        }

        .cart-count {
            font-size: 18px;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .floating-cart {
                bottom: 20px;
                right: 20px;
                height: 55px;
                padding: 0 20px 0 18px;
            }

            .floating-cart svg {
                width: 28px;
                height: 28px;
            }

            .cart-count {
                font-size: 16px;
            }
        }

        /* Add these styles */
        .search-section {
            padding: 2rem;
            margin-bottom: 2rem;
            background: var(--surface);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .search-form {
            display: flex;
            gap: 1rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .search-input {
            flex: 1;
            padding: 1rem 1.5rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .search-button {
            padding: 1rem 2rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .search-button:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }

        .category-select {
            padding: 1rem 1.5rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            min-width: 150px;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }

            .search-button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="main-container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="hero-title">Welcome to PhoneStore</h1>
            <p class="hero-subtitle">Discover the Latest in Mobile Technology</p>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <form class="search-form" method="GET" action="index.php">
                <select name="category" class="category-select">
                    <option value="0">All Categories</option>
                    <?php
                    $cat_stmt = $conn->prepare("SELECT id, name FROM categories");
                    $cat_stmt->execute();
                    $categories = $cat_stmt->get_result();
                    while ($category = $categories->fetch_assoc()) {
                        $selected = ($category_id == $category['id']) ? 'selected' : '';
                        echo "<option value='{$category['id']}' {$selected}>{$category['name']}</option>";
                    }
                    ?>
                </select>
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search products..."
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-button">
                    <i class="bi bi-search"></i>
                    Search
                </button>
            </form>
        </div>

        <!-- Carousel Section -->
        <div class="carousel-container">
            <div id="carouselExampleAutoplaying" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                <div class="carousel-inner">
                    <?php
                    $carousel_images = [
                        [
                            'path' => 'uploads/iphone15.webp',
                            'fallback' => 'uploads/placeholder.jpg',
                            'caption' => 'iPhone 15 - The Future of Smartphones'
                        ],
                        [
                            'path' => 'uploads/magsafe1.jpg',
                            'fallback' => 'uploads/placeholder.jpg',
                            'caption' => 'MagSafe - A New Way to Charge'
                        ],
                        [
                            'path' => 'uploads/apple-watch.webp',
                            'fallback' => 'uploads/placeholder.jpg',
                            'caption' => 'Apple Watch - Your Health Companion'
                        ],
                        [
                            'path' => 'uploads/AirPods.png',
                            'fallback' => 'uploads/placeholder.jpg',
                            'caption' => 'AirPods - Wireless Audio Freedom'
                        ],
                        [
                            'path' => 'uploads/car-usb.jpg',
                            'fallback' => 'uploads/placeholder.jpg',
                            'caption' => 'Car USB Charger - Power On the Go'
                        ]
                    ];
                    foreach ($carousel_images as $index => $image): 
                        $image_path = file_exists($image['path']) ? $image['path'] : $image['fallback'];
                    ?>
                        <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                 alt="<?php echo htmlspecialchars($image['caption']); ?>">
                            <div class="carousel-caption">
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
        </div>

        <!-- Products Section -->
        <div class="products-section">
            <h2 class="section-title">Our Products</h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo $product['image']; ?>" 
                             class="product-image" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="height: 200px; object-fit: contain; padding: 1rem;">
                        
                        <div class="product-content">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                            <p class="product-stock">Available Stock: <?php echo $product['stock_quantity']; ?> units</p>
                            
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <form action="add_cart.php" method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="number" name="quantity" value="1" min="1" 
                                           max="<?php echo $product['stock_quantity']; ?>" 
                                           class="form-control w-25">
                                    <button type="submit" class="btn btn-primary">Add to Cart</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <a href="view_cart.php" class="floating-cart">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        <span class="cart-count"><?php echo $cart_count; ?></span>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to animate item to cart
        function animateToCart(productImage, button) {
            // Create flying item element
            const flyingItem = document.createElement('div');
            const rect = productImage.getBoundingClientRect();
            const cartIcon = document.querySelector('.floating-cart');
            
            // Set initial position and style
            flyingItem.style.cssText = `
                position: fixed;
                z-index: 9999;
                left: ${rect.left}px;
                top: ${rect.top}px;
                width: 50px;
                height: 50px;
                opacity: 1;
                pointer-events: none;
                transition: all 1.5s cubic-bezier(0.25, 0.1, 0.25, 1);
                background-image: url('${productImage.src}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                border-radius: 50%;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                border: 2px solid white;
            `;
            
            document.body.appendChild(flyingItem);
            
            // Trigger animation
            setTimeout(() => {
                const cartRect = cartIcon.getBoundingClientRect();
                flyingItem.style.transform = `translate(
                    ${cartRect.left - rect.left}px,
                    ${cartRect.top - rect.top}px
                ) scale(0.3)`;
                flyingItem.style.opacity = '0';
                flyingItem.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';
            }, 100);
            
            // Clean up
            setTimeout(() => {
                document.body.removeChild(flyingItem);
            }, 1600);
        }

        // Function to fetch current cart count from server
        function fetchCartCount() {
            fetch('get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.cartCount !== undefined) {
                        updateCartCount(data.cartCount);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Function to update cart count
        function updateCartCount(count) {
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = count;
                
                // Add animation
                cartCount.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    cartCount.style.transform = 'scale(1)';
                }, 200);
            }
        }

        // Refresh cart count when page loads
        document.addEventListener('DOMContentLoaded', fetchCartCount);
        
        // Refresh cart count periodically (every 30 seconds)
        setInterval(fetchCartCount, 30000);

        // Add event listeners to all add to cart forms
        document.querySelectorAll('form[action="add_cart.php"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                // Get the product image and button
                const productCard = this.closest('.product-card');
                const productImage = productCard.querySelector('.product-image');
                const button = this.querySelector('button[type="submit"]');
                
                fetch('add_cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the cart count immediately
                        if (data.cartCount !== undefined) {
                            updateCartCount(data.cartCount);
                        }
                        
                        // Animate item to cart
                        animateToCart(productImage, button);
                        
                        // Show success message
                        const originalText = button.textContent;
                        button.textContent = 'Added to Cart!';
                        button.style.backgroundColor = '#22c55e';
                        
                        // Reset button after delay
                        setTimeout(() => {
                            button.textContent = originalText;
                            button.style.backgroundColor = '';
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error message
                    button.textContent = 'Error! Try Again';
                    button.style.backgroundColor = '#ef4444';
                    setTimeout(() => {
                        button.textContent = 'Add to Cart';
                        button.style.backgroundColor = '';
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>
