<?php
session_start();
require 'db_connection.php';

// Get cart items and total
$user_id = $_SESSION['user_id'];
$cart_items = [];
$total = 0;

$stmt = $conn->prepare("
    SELECT c.id, c.quantity, p.name, p.price, p.image 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $total += $row['price'] * $row['quantity'];
}

// Get shipping fee
$stmt = $conn->prepare("SELECT shipping_fee FROM settings WHERE id = 1");
$stmt->execute();
$shipping_result = $stmt->get_result();
$shipping_fee = 0;
if ($shipping_result->num_rows > 0) {
    $shipping_fee = $shipping_result->fetch_assoc()['shipping_fee'];
}

$grand_total = $total + $shipping_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - PhoneStore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --secondary: #6366f1;
            --light: #f5f3ff;
            --dark: #1e1b4b;
            --surface: #ffffff;
            --border: #e5e7eb;
        }

        body {
            background-color: var(--light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .checkout-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .checkout-step {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .step-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .step-number {
            width: 35px;
            height: 35px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .step-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }

        .form-control {
            border: 2px solid var(--border);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .order-summary {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            position: sticky;
            top: 2rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .payment-method {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .payment-option {
            flex: 1;
            padding: 1rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option:hover {
            border-color: var(--primary);
            background: var(--light);
        }

        .payment-option.active {
            border-color: var(--primary);
            background: var(--light);
        }

        .payment-option i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .bank-details {
            display: none;
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: var(--light);
            border-radius: 10px;
            border: 2px solid var(--border);
        }

        .bank-details.show {
            display: block;
        }

        .bank-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--border);
        }

        .bank-info:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .copy-btn {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            padding: 0.2rem 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .copy-btn:hover {
            color: var(--secondary);
        }

        .place-order-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            width: 100%;
            font-weight: 600;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        @media (max-width: 768px) {
            .checkout-container {
                margin: 1rem auto;
            }
            
            .checkout-step {
                padding: 1.5rem;
            }
        }

        /* Cookie Consent Banner */
        .cookie-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            display: none;
        }

        .cookie-banner.show {
            display: block;
        }

        .cookie-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .cookie-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .cookie-btn {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-weight: 500;
        }

        .cookie-accept {
            background: var(--primary);
            color: white;
        }

        .cookie-decline {
            background: #e5e7eb;
            color: #374151;
        }

        /* Credit Card Form Styles */
        #credit-card-form .form-control {
            padding: 0.75rem;
            height: 45px;
            background: white;
        }

        .StripeElement {
            height: 100%;
        }

        .StripeElement--focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .StripeElement--invalid {
            border-color: #ef4444;
        }

        .payment-details {
            background: var(--light);
            border-radius: 10px;
            margin-top: 1.5rem;
            border: 2px solid var(--border);
        }

        .bank-info {
            background: white;
            padding: 1rem;
            border-radius: 8px;
        }

        .ewallet-options .form-check {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .ewallet-options .form-check:hover {
            background: var(--light);
        }

        .ewallet-options .form-check-label {
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="checkout-container">
        <div class="checkout-header">
            <h1><i class="bi bi-credit-card me-2"></i>Checkout</h1>
        </div>

        <div class="row">
            <div class="col-md-8">
                <form id="checkoutForm" action="order_processing.php" method="POST">
                    <!-- Shipping Information -->
                    <div class="checkout-step">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <h2 class="step-title">Shipping Information</h2>
                        </div>
                        <input type="hidden" name="payment_method" value="credit_card">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Shipping Address</label>
                                <input type="text" class="form-control" name="shipping_address" 
                                       placeholder="Enter your street address" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="shipping_city" 
                                       placeholder="Enter your city" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">State</label>
                                <input type="text" class="form-control" name="shipping_state" 
                                       placeholder="Enter your state" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" name="shipping_zip" 
                                       placeholder="ZIP code" maxlength="5" required>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-step">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <h2 class="step-title">Payment Method</h2>
                        </div>
                        <div class="payment-method">
                            <div class="payment-option active">
                                <i class="bi bi-credit-card"></i>
                                <div>Visa Card</div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-md-4">
                <div class="order-summary">
                    <h3 class="mb-4">Order Summary</h3>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="text-muted">Qty: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="fw-bold">
                                $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="summary-item">
                        <div>Subtotal</div>
                        <div>$<?php echo number_format($total, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div>Shipping</div>
                        <div>$<?php echo number_format($shipping_fee, 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="fw-bold">Total</div>
                        <div class="fw-bold">$<?php echo number_format($grand_total, 2); ?></div>
                    </div>

                    <button type="submit" form="checkoutForm" class="place-order-btn">
                        Continue to Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="cookie-banner" id="cookieBanner">
        <div class="cookie-content">
            <div>
                This site uses cookies for payment processing and essential functionality. 
                Third-party cookies may be required for PayPal payments.
            </div>
            <div class="cookie-buttons">
                <button class="cookie-btn cookie-accept" onclick="acceptCookies()">Accept All</button>
                <button class="cookie-btn cookie-decline" onclick="declineCookies()">Essential Only</button>
            </div>
        </div>
    </div>

    <!-- Bank Transfer Details -->
    <div id="bank-details" class="payment-details" style="display: none;">
        <div class="p-4">
            <h5>Bank Transfer Information</h5>
            <div class="bank-info mt-3">
                <div class="d-flex justify-content-between mb-2">
                    <span>Bank Name:</span>
                    <strong>ABC Bank</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Account Number:</span>
                    <strong>1234-5678-9012</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Account Name:</span>
                    <strong>PhoneStore</strong>
                </div>
            </div>
            <div class="alert alert-info mt-3">
                <p>Please transfer exact amount: $<?php echo number_format($grand_total, 2); ?></p>
                <p class="mb-0">After making the transfer, your order will be processed once payment is confirmed.</p>
            </div>
        </div>
    </div>

    <script>
        // Cookie Consent Management
        function checkCookieConsent() {
            return localStorage.getItem('cookieConsent');
        }

        function showCookieBanner() {
            if (!checkCookieConsent()) {
                document.getElementById('cookieBanner').classList.add('show');
            }
        }

        function acceptCookies() {
            localStorage.setItem('cookieConsent', 'accepted');
            document.getElementById('cookieBanner').classList.remove('show');
            // Reload PayPal if it's selected
            const paymentMethod = document.getElementById('payment_method').value;
            if (paymentMethod === 'paypal') {
                loadPayPalScript();
            }
        }

        function declineCookies() {
            localStorage.setItem('cookieConsent', 'declined');
            document.getElementById('cookieBanner').classList.remove('show');
            // Disable PayPal option if cookies are declined
            const paypalOption = document.querySelector('.payment-option[onclick*="paypal"]');
            if (paypalOption) {
                paypalOption.style.opacity = '0.5';
                paypalOption.style.cursor = 'not-allowed';
                paypalOption.onclick = () => {
                    alert('PayPal requires third-party cookies to function. Please accept cookies to use PayPal.');
                };
            }
        }

        // Show cookie banner on page load
        document.addEventListener('DOMContentLoaded', showCookieBanner);

        // PayPal Integration
        function loadPayPalScript() {
            // Check cookie consent before loading PayPal
            if (checkCookieConsent() !== 'accepted') {
                document.getElementById('paypal-button-container').innerHTML = 
                    '<div class="alert alert-warning">Please accept cookies to use PayPal payment.</div>';
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://www.paypal.com/sdk/js?client-id=AYSq3RDGsmBLJE-otTkBtM-jBRd1TCQwFf9RGfwddNXWz0uFU9ztymylOhRS&currency=USD';
            script.async = true;
            script.onload = function() {
                if (typeof paypal !== 'undefined') {
                    paypal.Buttons({
                        createOrder: function(data, actions) {
                            // Validate form first
                            const form = document.getElementById('checkoutForm');
                            if (!form.checkValidity()) {
                                form.reportValidity();
                                return Promise.reject(new Error('Please fill in all required fields'));
                            }

                            const formData = new FormData(form);
                            
                            return fetch('save_order.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(orderData => {
                                if (!orderData.success) {
                                    throw new Error(orderData.message || 'Failed to create order');
                                }
                                
                                return actions.order.create({
                                    purchase_units: [{
                                        amount: {
                                            value: orderData.amount.toString()
                                        },
                                        description: 'Order #' + orderData.orderNumber
                                    }]
                                });
                            });
                        },
                        onApprove: function(data, actions) {
                            return actions.order.capture().then(function(details) {
                                return fetch('update_order_payment.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        orderId: details.purchase_units[0].description.split('#')[1],
                                        paypalDetails: details
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        window.location.href = 'order_confirmation.php?order_id=' + data.orderId;
                                    } else {
                                        throw new Error(data.message || 'Payment update failed');
                                    }
                                });
                            });
                        },
                        onError: function(err) {
                            console.error('PayPal Error:', err);
                            alert('There was an error processing your payment. Please try again.');
                        }
                    }).render('#paypal-button-container')
                    .catch(function(error) {
                        console.error('PayPal render error:', error);
                        document.getElementById('paypal-button-container').innerHTML = 
                            '<div class="alert alert-danger">PayPal payment is currently unavailable. Please try another payment method.</div>';
                    });
                }
            };
            script.onerror = function() {
                console.error('PayPal SDK failed to load');
                document.getElementById('paypal-button-container').innerHTML = 
                    '<div class="alert alert-danger">PayPal payment is currently unavailable. Please try another payment method.</div>';
            };
            document.body.appendChild(script);
        }

        // Validate ZIP code input
        document.querySelector('input[name="shipping_zip"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 5);
        });

        // Handle form submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const button = document.querySelector('.place-order-btn');
            
            // Validate form
            if (!this.checkValidity()) {
                this.reportValidity();
                return;
            }

            // Additional validation
            const zipCode = document.querySelector('input[name="shipping_zip"]').value;
            if (!/^\d{5}$/.test(zipCode)) {
                alert('Please enter a valid 5-digit ZIP code');
                return;
            }

            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            // Submit the form to order_processing.php
            this.submit();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
