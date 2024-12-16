<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and execute query to get admin data by email
    $stmt = $conn->prepare("SELECT id, name, password FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    // Check if an admin with the given email exists
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $name, $hashed_password);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Store admin info in session
            $_SESSION['admin_id'] = $id;
            $_SESSION['admin_name'] = $name;
            $_SESSION['login_success'] = "Welcome back, $name!";
            // Redirect to admin dashboard
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No account found with that email.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Page Background */
        body {
            background: linear-gradient(135deg, #ff7e5f, #feb47b); /* Warm gradient for eCommerce */
            font-family: 'Poppins', sans-serif; /* Modern font for clean design */
            color: #333;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Login Container Styling */
        .login-container {
            width: 100%;
            max-width: 450px;
            background: #ffffff; /* White box for contrast */
            padding: 40px 30px;
            border-radius: 12px; /* Rounded corners for modern feel */
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15); /* Soft shadow for depth */
            text-align: center;
        }

        /* Logo Styling */
        .logo {
            display: block;
            margin: 0 auto 20px;
            max-width: 200px; /* Larger logo size */
            height: auto;
        }

        /* Title Styling */
        .login-container h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333; /* Dark text for readability */
            margin-bottom: 25px;
        }

        /* Form Field Styling */
        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: #555; /* Subtle label color */
            text-align: left;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
            position: relative;
        }

        .password-toggle {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            display: flex; /* Ensures the icon remains visible */
            align-items: center;
            height: 100%;
        }
        
        input[type="password"]:focus {
            border-color: #ff7e5f;
            outline: none;
            box-shadow: 0 0 6px rgba(255, 126, 95, 0.5);
        }

        /* Button Styling */
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #ff7e5f, #feb47b);
            border: none;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #feb47b, #ff7e5f);
            box-shadow: 0 4px 10px rgba(255, 126, 95, 0.3);
            transform: scale(1.03);
        }

        /* Alert Styling */
        .alert {
            font-size: 14px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: rgba(255, 59, 59, 0.1);
            color: #ff3b3b;
            border: 1px solid #ff3b3b;
            text-align: left;
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .login-container {
                max-width: 90%;
                padding: 30px 20px;
            }

            .login-container h1 {
                font-size: 24px;
            }

            .btn-primary {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo -->
        <img src="uploads/logo.webp" alt="Admin Logo" class="logo">
        
        <!-- Title -->
        <h1>Admin Login</h1>
        
        <!-- Error Message -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="post" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="mb-3 position-relative">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                <i class="fas fa-eye password-toggle"></i>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <!-- Footer -->
        <div class="footer mt-3">© 2024 Your eCommerce Platform</div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript to toggle password visibility
        const passwordToggle = document.querySelector('.password-toggle');
        const passwordInput = document.querySelector('#password');

        passwordToggle.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });

        // Keep the eye icon visible when clicking anywhere on the input
        passwordInput.addEventListener('focus', function () {
            passwordToggle.style.display = 'flex';
        });

        passwordInput.addEventListener('blur', function () {
            if (!passwordInput.value) {
                passwordToggle.style.display = 'none';
            }
        });

    </script>
</body>
</html>


