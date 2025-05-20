<?php
session_start();
require_once '../connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$response = ['status' => 'error', 'message' => '', 'addresses' => []];

try {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT * FROM delivery_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $addresses = [];
    while ($row = $result->fetch_assoc()) {
        $addresses[] = [
            'id' => $row['id'],
            'address_type' => $row['address_type'],
            'recipient_name' => $row['recipient_name'],
            'street' => $row['street_address'],
            'city' => $row['city'],
            'region' => $row['region'],
            'postal_code' => $row['postal_code'],
            'phone_number' => $row['phone_number'],
            'is_default' => $row['is_default']
        ];
    }
    
    $response = [
        'status' => 'success',
        'message' => 'Addresses loaded successfully',
        'addresses' => $addresses,
        'limit_reached' => count($addresses) >= 2,
        'max_addresses' => 2,
        'current_count' => count($addresses)
    ];
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?> 