<?php
// Assuming order_id is passed as a GET parameter
if (!isset($_GET['order_id'])) {
    die("Order ID not provided.");
}

$order_id = $_GET['order_id'];

// Fetch order details from the database
include 'db_connection.php';
$stmt = $conn->prepare("SELECT total_price, status FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$stmt->bind_result($total_price, $status);
$stmt->fetch();
$stmt->close();

if (!$total_price) {
    die("Order not found.");
}

// Generate the PDF link
$pdf_link = "generate_pdf.php?order_id=" . urlencode($order_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Order Success</h1>
        <p>Thank you for your order!</p>
        <p>Your order ID is: <?php echo htmlspecialchars($order_id); ?></p>
        <p>Total Amount: $<?php echo number_format($total_price, 2); ?></p>
        <p>Status: <?php echo htmlspecialchars($status); ?></p>

        <!-- PDF Download Button (will be auto-triggered) -->
        <a href="<?php echo $pdf_link; ?>" class="btn btn-success" id="pdfDownloadBtn" style="display:none;">Download Receipt</a>

        <a href="index.php" class="btn btn-primary">Go to Home</a>
    </div>

    <script>
        // Trigger the download automatically when the page loads
        window.onload = function() {
            // Redirect to the PDF download
            window.location.href = "<?php echo $pdf_link; ?>";
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
