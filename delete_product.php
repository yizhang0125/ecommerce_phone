<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $product_id = $_POST['id'];
    
    try {
        // First get the image path to delete the file
        $query = "SELECT image FROM products WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        // Delete the image file if it exists
        if ($product && $product['image'] && file_exists($product['image'])) {
            unlink($product['image']);
        }
        
        // Delete the product from database
        $delete_query = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Product deleted successfully!";
        } else {
            throw new Exception("Error deleting product from database");
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
    
    header('Location: products_section.php');
    exit();
} else {
    header('Location: products_section.php');
    exit();
}
?>
