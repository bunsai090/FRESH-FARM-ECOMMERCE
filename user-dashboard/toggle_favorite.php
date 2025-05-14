<?php
session_start();
header('Content-Type: application/json');
require_once '../connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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

if (!$product_id) {
    echo json_encode([
        'status' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

try {
    // Check if the product is already in favorites
    $checkStmt = $conn->prepare("SELECT favorite_id FROM favorites WHERE user_id = ? AND product_id = ?");
    $checkStmt->bind_param("ii", $user_id, $product_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Product is already in favorites, so remove it
        $deleteStmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
        $deleteStmt->bind_param("ii", $user_id, $product_id);
        $deleteStmt->execute();
        
        $message = "Removed from favorites";
        $is_favorite = false;
    } else {
        // Product is not in favorites, so add it
        $insertStmt = $conn->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        $insertStmt->bind_param("ii", $user_id, $product_id);
        $insertStmt->execute();
        
        $message = "Added to favorites";
        $is_favorite = true;
    }
    
    // Get updated favorite count
    $countStmt = $conn->prepare("SELECT COUNT(*) as favorite_count FROM favorites WHERE user_id = ?");
    $countStmt->bind_param("i", $user_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $favoriteCount = $countResult->fetch_assoc()['favorite_count'];
    
    echo json_encode([
        'status' => true,
        'message' => $message,
        'is_favorite' => $is_favorite,
        'favorite_count' => $favoriteCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Error updating favorites: ' . $e->getMessage()
    ]);
}
?> 