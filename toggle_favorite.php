<?php
session_start();
header('Content-Type: application/json');
require_once 'connect.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the start of the request
error_log("Toggle favorite request started");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode([
        'status' => false,
        'message' => 'Please login to add favorites'
    ]);
    exit;
}

// Get the product ID from POST request
$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;
$user_id = $_SESSION['user_id'];

error_log("User ID: " . $user_id);
error_log("Product ID: " . $product_id);

if (!$product_id) {
    error_log("Product ID is missing");
    echo json_encode([
        'status' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

try {
    // Log database connection status
    error_log("Database connection status: " . ($conn ? "Connected" : "Not connected"));

    // Check if the product is already in favorites
    $checkStmt = $conn->prepare("SELECT favorite_id FROM favorites WHERE user_id = ? AND product_id = ?");
    if (!$checkStmt) {
        throw new Exception("Failed to prepare check statement: " . $conn->error);
    }
    
    $checkStmt->bind_param("ii", $user_id, $product_id);
    if (!$checkStmt->execute()) {
        throw new Exception("Failed to execute check statement: " . $checkStmt->error);
    }
    
    $result = $checkStmt->get_result();
    error_log("Check query executed. Found rows: " . $result->num_rows);
    
    if ($result->num_rows > 0) {
        // Product is already in favorites, so remove it
        error_log("Removing from favorites");
        $deleteStmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
        if (!$deleteStmt) {
            throw new Exception("Failed to prepare delete statement: " . $conn->error);
        }
        
        $deleteStmt->bind_param("ii", $user_id, $product_id);
        if (!$deleteStmt->execute()) {
            throw new Exception("Failed to execute delete statement: " . $deleteStmt->error);
        }
        
        $message = "Removed from favorites";
        $is_favorite = false;
    } else {
        // Product is not in favorites, so add it
        error_log("Adding to favorites");
        $insertStmt = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        if (!$insertStmt) {
            throw new Exception("Failed to prepare insert statement: " . $conn->error);
        }
        
        $insertStmt->bind_param("ii", $user_id, $product_id);
        if (!$insertStmt->execute()) {
            throw new Exception("Failed to execute insert statement: " . $insertStmt->error);
        }
        
        $message = "Added to favorites";
        $is_favorite = true;
    }
    
    // Get ONLY the favorites count
    $countStmt = $conn->prepare("SELECT COUNT(*) as favorite_count FROM favorites WHERE user_id = ?");
    if (!$countStmt) {
        throw new Exception("Failed to prepare count statement: " . $conn->error);
    }
    
    $countStmt->bind_param("i", $user_id);
    if (!$countStmt->execute()) {
        throw new Exception("Failed to execute count statement: " . $countStmt->error);
    }
    
    $countResult = $countStmt->get_result();
    $favoriteCount = $countResult->fetch_assoc()['favorite_count'];
    error_log("New favorite count: " . $favoriteCount);
    
    echo json_encode([
        'status' => true,
        'message' => $message,
        'is_favorite' => $is_favorite,
        'favorite_count' => $favoriteCount
    ]);
    
} catch (Exception $e) {
    error_log("Error in toggle_favorite.php: " . $e->getMessage());
    echo json_encode([
        'status' => false,
        'message' => 'Error updating favorites: ' . $e->getMessage()
    ]);
}
?> 