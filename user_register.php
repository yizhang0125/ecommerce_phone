<?php
session_start();
require 'db_connection.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = "Email already registered.";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success_message = "Registration successful! Please login.";
                header("refresh:2;url=user_login.php");
            } else {
                $error_message = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | PhoneStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #6366f1;
            --dark: #1e1b4b;
            --light: #f5f3ff;
            --text: #4b5563;
        }

        body {
            min-height: 100vh;
            background: var(--light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #f5f3ff 0%, #ffffff 100%);
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            position: relative;
        }

        .register-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h1 {
            color: var(--dark);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: #666;
            margin-bottom: 0;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            background: white;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.2);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .input-group {
            margin-bottom: 1rem;
        }

        .toggle-password {
            border-color: #e5e7eb;
            background: white;
        }

        .toggle-password:hover {
            background: #f9fafb;
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        /* Add animation styles */
        /* Animated Bubbles */
        .bubbles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .bubble {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            animation: bubbleFloat 15s infinite;
            opacity: 0;
        }

        .bubble:nth-child(1) { width: 80px; height: 80px; left: 10%; animation-delay: 0s; }
        .bubble:nth-child(2) { width: 60px; height: 60px; left: 30%; animation-delay: -2s; }
        .bubble:nth-child(3) { width: 90px; height: 90px; left: 50%; animation-delay: -4s; }
        .bubble:nth-child(4) { width: 70px; height: 70px; left: 70%; animation-delay: -6s; }
        .bubble:nth-child(5) { width: 85px; height: 85px; left: 90%; animation-delay: -8s; }

        @keyframes bubbleFloat {
            0% {
                transform: translateY(120vh) scale(0);
                opacity: 0;
            }
            50% {
                opacity: 0.2;
            }
            100% {
                transform: translateY(-20vh) scale(1);
                opacity: 0;
            }
        }

        /* Animated Waves */
        .waves {
            position: absolute;
            width: 100%;
            height: 100%;
            transform: rotate(-45deg) scale(1.5);
        }

        .wave {
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            opacity: 0.1;
            border-radius: 40%;
            animation: waveRotate 15s linear infinite;
        }

        .wave:nth-child(1) {
            animation-duration: 15s;
        }

        .wave:nth-child(2) {
            animation-duration: 20s;
            opacity: 0.05;
        }

        .wave:nth-child(3) {
            animation-duration: 25s;
            opacity: 0.075;
        }

        @keyframes waveRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Glowing Dots */
        .dots {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .dot {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--primary);
            border-radius: 50%;
            animation: dotGlow 4s infinite;
        }

        .dot:nth-child(1) { top: 20%; left: 20%; animation-delay: 0s; }
        .dot:nth-child(2) { top: 40%; left: 60%; animation-delay: -1s; }
        .dot:nth-child(3) { top: 60%; left: 40%; animation-delay: -2s; }
        .dot:nth-child(4) { top: 80%; left: 80%; animation-delay: -3s; }

        @keyframes dotGlow {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(2); opacity: 0.1; }
        }

        /* Banner and Form Styles */
        .login-banner {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 4rem;
            position: relative;
            color: white;
            min-height: 600px;
            display: flex;
            flex-direction: column;
        }

        .banner-content {
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 3rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .banner-header {
            animation: textAppear 0.8s ease-out 0.6s backwards;
        }

        .banner-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .banner-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .features-list {
            margin-top: auto;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .login-form {
            flex: 1;
            padding: 4rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
            }

            .login-banner {
                padding: 3rem 2rem;
                min-height: auto;
            }

            .login-form {
                padding: 3rem 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <div class="bubbles">
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
            <div class="bubble"></div>
        </div>
        <div class="waves">
            <div class="wave"></div>
            <div class="wave"></div>
            <div class="wave"></div>
        </div>
        <div class="dots">
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
            <div class="dot"></div>
        </div>
    </div>

    <div class="login-container">
        <!-- Banner Section -->
        <div class="login-banner">
            <div class="banner-pattern"></div>
            <div class="banner-content">
                <div class="brand-logo">
                    <i class="bi bi-phone"></i>
                    PhoneStore
                </div>
                <div class="banner-header">
                    <h2>Join Our Community!</h2>
                    <p>Create an account to start shopping for premium phones and accessories with exclusive member benefits.</p>
                </div>
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="feature-text">
                            Secure account protection
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-gift"></i>
                        </div>
                        <div class="feature-text">
                            Exclusive member discounts
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-bell"></i>
                        </div>
                        <div class="feature-text">
                            New arrival notifications
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Section -->
        <div class="login-form">
            <div class="form-header">
                <h1>Create Account</h1>
                <p>Fill in your details to get started</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" 
                           class="form-control" 
                           name="name" 
                           placeholder="Enter your full name"
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" 
                           class="form-control" 
                           name="email" 
                           placeholder="Enter your email"
                           required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               placeholder="Create a password"
                               minlength="6"
                               required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               name="confirm_password" 
                               placeholder="Confirm your password"
                               required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                </button>
            </form>

            <div class="form-footer">
                Already have an account?
                <a href="user_login.php">Sign in</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    </script>
</body>
</html>
