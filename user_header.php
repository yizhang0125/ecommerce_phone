<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Get user name if logged in
$user_name = '';
if ($is_logged_in) {
    require_once 'db_connection.php';
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $user_name = $row['name'];
    }
}

// Get cart count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhoneStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --dark: #1e1b4b;
            --light: #f5f3ff;
            --text: #4b5563;
            --success: #10b981;
            --surface: #ffffff;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--light);
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 1rem 0;
            box-shadow: 0 4px 30px rgba(99, 102, 241, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand i {
            font-size: 2.2rem;
            filter: drop-shadow(0 2px 8px rgba(255, 255, 255, 0.3));
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-link i {
            font-size: 1.2rem;
        }

        .nav-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-btn {
            padding: 0.6rem;
            border-radius: 8px;
            color: #64748b;
            background: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .nav-btn:hover {
            color: #2563eb;
            background: #eff6ff;
        }

        .nav-btn i {
            font-size: 1.2rem;
        }

        .cart-icon {
            margin-right: 0;
            margin-left: 0.5rem;
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .cart-icon:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--success);
            color: white;
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
            border: 2px solid white;
        }

        .btn-auth {
            padding: 0.8rem 1.8rem;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1rem;
            text-decoration: none;
        }

        .btn-login {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: white;
        }

        .btn-register {
            background: white;
            color: var(--primary);
            box-shadow: 0 4px 15px rgba(255, 255, 255, 0.2);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.3);
        }

        .btn-logout {
            padding: 0.8rem;
            color: white;
            background: rgba(239, 68, 68, 0.2);
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.3);
            color: white;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-btn {
            background: #eff6ff;
            padding: 0.4rem 1rem;
            border-radius: 25px;
            color: #2563eb;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-name {
            font-weight: 500;
        }

        .logout-btn:hover {
            color: #ef4444;
            background: #fef2f2;
        }

        .sign-in-btn {
            background: #2563eb;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 25px;
        }

        .sign-in-btn:hover {
            background: #1d4ed8;
            color: white;
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            color: #64748b;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        @media (max-width: 992px) {
            .navbar-collapse {
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                padding: 2rem;
                border-radius: 20px;
                margin-top: 1rem;
                box-shadow: 0 10px 40px rgba(99, 102, 241, 0.2);
            }

            .nav-buttons {
                flex-direction: column;
                width: 100%;
                gap: 0.5rem;
            }

            .btn-auth {
                width: 100%;
                justify-content: center;
            }

            .cart-icon {
                padding: 1.2rem;
                bottom: 20px;
                right: 20px;
            }

            .cart-icon i {
                font-size: 1.4rem;
            }
        }

        .dropdown-menu {
            margin-top: 0.5rem;
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 0.5rem;
            min-width: 220px;
            animation: dropdownFade 0.2s ease;
        }

        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            padding: 0.8rem 1rem;
            color: var(--text);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background: var(--light);
            color: var(--primary);
            transform: translateX(5px);
        }

        .dropdown-item i {
            font-size: 1.1rem;
            color: var(--primary);
        }

        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: var(--light);
        }

        .text-danger {
            color: #ef4444 !important;
        }

        .text-danger:hover {
            background: #fef2f2 !important;
            color: #ef4444 !important;
        }

        .text-danger i {
            color: #ef4444;
        }

        @media (max-width: 992px) {
            .dropdown-menu {
                width: 100%;
                margin-top: 0.5rem;
                position: static !important;
                transform: none !important;
            }
        }

        .user-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        @media (max-width: 992px) {
            .user-controls {
                flex-direction: column;
                width: 100%;
            }

            .user-controls .btn-auth {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-phone"></i>
                PhoneStore
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="bi bi-list text-white"></i>
            </button>

            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Main Navigation -->
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="order_history.php">
                                <i class="bi bi-box"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="payment_history.php">
                                <i class="bi bi-credit-card"></i> Payments
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Right Side Items -->
                <div class="nav-buttons">
                    <?php if ($is_logged_in): ?>
                        <div class="user-controls">
                            <a href="user_account.php" class="btn-auth btn-login">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                            </a>
                            <a href="logout.php" class="btn-auth btn-logout">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="user_login.php" class="btn-auth btn-login">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </a>
                        <a href="user_register.php" class="btn-auth btn-register">
                            <i class="bi bi-person-plus"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Add spacing for fixed navbar -->
    <div style="margin-top: 76px;"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 