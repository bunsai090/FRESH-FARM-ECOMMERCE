<?php
session_start();
require_once '../connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$response = ['status' => 'error', 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['address_id'])) {
        throw new Exception('Address ID is required');
    }
    
    $user_id = $_SESSION['user_id'];
    $address_id = $data['address_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // First, remove default status from all addresses
    $stmt = $conn->prepare("UPDATE delivery_addresses SET is_default = 0 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Then set the selected address as default
    $stmt = $conn->prepare("UPDATE delivery_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $address_id, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update default address');
    }
    
    // Commit transaction
    $conn->commit();
    
    $response = [
        'status' => 'success',
        'message' => 'Default address updated successfully'
    ];
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?> 