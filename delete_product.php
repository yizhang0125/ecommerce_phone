<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $_POST['id'];

        if (is_numeric($id)) {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Product deleted successfully!";
                    header("Location: products_section.php");
                    exit(); // Ensure no further code is executed
                } else {
                    echo "Error deleting record: " . $stmt->error;
                }
                $stmt->close();
            } else {
                echo "Error preparing statement: " . $conn->error;
            }
        } else {
            echo "Invalid ID specified.";
        }
    } else {
        echo "No ID specified.";
    }
} else {
    echo "Invalid request method.";
}
?>
