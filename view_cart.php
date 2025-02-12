<?php
session_start();
require 'db_connection.php';

// Get cart count from database
$cart_count = 0;
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

// Get cart items for the current user
$user_id = $_SESSION['user_id'];
$cart_items = [];
$total = 0;

$stmt = $conn->prepare("
    SELECT c.id, c.quantity, p.name, p.price, p.image, p.id as product_id 
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

// Fetch the shipping fee from settings table
$stmt = $conn->prepare("SELECT shipping_fee FROM settings WHERE id = 1");
$stmt->execute();
$shipping_result = $stmt->get_result();
$shipping_fee = 0; // Default value for shipping fee
if ($shipping_result->num_rows > 0) {
    $shipping_fee = $shipping_result->fetch_assoc()['shipping_fee'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4338ca;
            --secondary: #6366f1;
            --light: #f5f3ff;
            --dark: #1e1b4b;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .cart-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .cart-item {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .item-price {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quantity-btn {
            background: var(--light);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quantity-btn:hover {
            background: var(--primary);
            color: white;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 2px solid var(--light);
            border-radius: 8px;
            padding: 0.3rem;
        }

        .remove-btn {
            color: #ef4444;
            border: none;
            background: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            color: #dc2626;
            transform: scale(1.1);
        }

        .cart-summary {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light);
        }

        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .checkout-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            width: 100%;
            font-weight: 600;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
        }

        .empty-cart i {
            font-size: 4rem;
            color: var(--secondary);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                text-align: center;
            }

            .quantity-control {
                justify-content: center;
                margin: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="cart-container">
        <div class="cart-header">
            <h1><i class="bi bi-cart3 me-2"></i>Your Shopping Cart</h1>
        </div>

        <div class="row">
            <div class="col-md-8">
                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="bi bi-cart-x"></i>
                        <h3>Your cart is empty</h3>
                        <p>Looks like you haven't added any items to your cart yet.</p>
                        <a href="index.php" class="btn btn-primary mt-3">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-cart-item="<?php echo $item['id']; ?>">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="item-image">
                            
                            <div class="item-details">
                                <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                            </div>

                            <div class="quantity-control">
                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                <input type="number" 
                                       class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1"
                                       data-cart-id="<?php echo $item['id']; ?>"
                                       onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                <button class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                            </div>

                            <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">
                                <i class="bi bi-trash fs-4"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="cart-summary">
                    <h3 class="mb-4">Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal">$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span id="shipping">$<?php echo number_format($shipping_fee, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <strong>Total</strong>
                        <strong id="total">$<?php echo number_format($total + $shipping_fee, 2); ?></strong>
                    </div>
                    <?php if (!empty($cart_items)): ?>
                        <button class="checkout-btn" onclick="window.location.href='check_out_process.php'">
                            Proceed to Checkout
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateQuantity(cartId, change) {
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_id: cartId,
                    change: change
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the cart count
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount && data.cartCount !== undefined) {
                        cartCount.textContent = data.cartCount;
                    }
                    
                    // Update the quantity input
                    const quantityInput = document.querySelector(`input[data-cart-id="${cartId}"]`);
                    if (quantityInput && data.newQuantity !== undefined) {
                        quantityInput.value = data.newQuantity;
                    }
                    
                    // Update the total price
                    if (data.newTotal !== undefined) {
                        document.getElementById('subtotal').textContent = `$${data.newTotal.toFixed(2)}`;
                        const shippingFee = parseFloat(document.getElementById('shipping').textContent.replace('$', ''));
                        document.getElementById('total').textContent = `$${(data.newTotal + shippingFee).toFixed(2)}`;
                    }

                    // If quantity is 0, remove the item
                    if (data.newQuantity === 0) {
                        const cartItem = document.querySelector(`[data-cart-item="${cartId}"]`);
                        if (cartItem) {
                            cartItem.remove();
                        }
                    }

                    // Refresh the page if cart is empty
                    if (data.cartEmpty) {
                        location.reload();
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function removeItem(cartId) {
            if (confirm('Are you sure you want to remove this item?')) {
                fetch('delete_cart_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cart_id: cartId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update cart count
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount && data.cartCount !== undefined) {
                            cartCount.textContent = data.cartCount;
                        }

                        // Remove the item from DOM
                        const cartItem = document.querySelector(`[data-cart-item="${cartId}"]`);
                        if (cartItem) {
                            cartItem.remove();
                        }

                        // Update totals
                        if (data.newTotal !== undefined) {
                            document.getElementById('subtotal').textContent = `$${data.newTotal.toFixed(2)}`;
                            const shippingFee = parseFloat(document.getElementById('shipping').textContent.replace('$', ''));
                            document.getElementById('total').textContent = `$${(data.newTotal + shippingFee).toFixed(2)}`;
                        }

                        // Refresh if cart is empty
                        if (data.cartEmpty) {
                            location.reload();
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
