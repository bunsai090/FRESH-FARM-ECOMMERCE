<?php
session_start();
require_once '../connect.php';

header('Content-Type: application/json');

// Get the search query
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (strlen($query) >= 2) {
    try {
        // Prepare the SQL query with wildcards for partial matching
        $sql = "SELECT id, name, category, price, image_path 
                FROM products 
                WHERE LOWER(name) LIKE LOWER(?) 
                OR LOWER(category) LIKE LOWER(?) 
                LIMIT 8";
        
        $searchTerm = "%" . $query . "%";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $suggestions = array();
        
        while ($row = $result->fetch_assoc()) {
            // Handle image path
            if (!empty($row['image_path'])) {
                // Remove any leading slashes and ensure proper path
                $row['image_path'] = ltrim($row['image_path'], '/');
                // Add the relative path prefix if needed
                if (!str_starts_with($row['image_path'], 'assets/')) {
                    $row['image_path'] = '../assets/products/' . $row['image_path'];
                } else {
                    $row['image_path'] = '../' . $row['image_path'];
                }
            } else {
                $row['image_path'] = '../assets/images/default-product.jpg';
            }
            
            // Format the price
            $row['price'] = number_format(floatval($row['price']), 2);
            $suggestions[] = $row;
        }
        
        echo json_encode($suggestions);
        
    } catch (Exception $e) {
        error_log("Search suggestion error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Error fetching suggestions: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([]);
}
?> 