<?php
session_start();
require 'db_connection.php';

// Check if user is an admin
if (!isset($_SESSION['admin_id'])) {
    die("You are not authorized to access this page.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the shipping fee from the form
    $shipping_fee = $_POST['shipping_fee'];

    // Update or set the shipping fee in the database
    $stmt = $conn->prepare("UPDATE settings SET shipping_fee = ? WHERE id = 1");
    $stmt->bind_param("d", $shipping_fee);
    if ($stmt->execute()) {
        $message = "Shipping fee updated successfully!";
    } else {
        $message = "Error updating shipping fee.";
    }
    $stmt->close();
}

// Check if settings with id = 1 exist
$stmt = $conn->prepare("SELECT shipping_fee FROM settings WHERE id = 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the current shipping fee
    $current_shipping_fee = $result->fetch_assoc()['shipping_fee'];
} else {
    // If no row exists with id = 1, insert a default row
    $stmt = $conn->prepare("INSERT INTO settings (id, shipping_fee) VALUES (1, 5.00)"); // Default shipping fee is 5.00
    $stmt->execute();
    $current_shipping_fee = 5.00; // Set the default shipping fee
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Shipping Fee</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin-top: 50px;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            width: 100%;
        }
        .alert {
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2 class="text-center mb-4">Set Shipping Fee</h2>

        <?php if (isset($message)): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="shipping_fee" class="form-label">Shipping Fee ($):</label>
                <input type="number" name="shipping_fee" id="shipping_fee" class="form-control" step="0.01" value="<?php echo htmlspecialchars($current_shipping_fee); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Shipping Fee</button>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
