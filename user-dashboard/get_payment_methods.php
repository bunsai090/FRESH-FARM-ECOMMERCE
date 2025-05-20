<?php
session_start();
require_once '../connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$response = ['status' => 'error', 'message' => '', 'payment_methods' => []];

try {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT pm.*, da.street_address, da.city, da.region, da.postal_code, da.phone_number 
                           FROM payment_methods pm 
                           LEFT JOIN delivery_addresses da ON pm.delivery_address_id = da.id 
                           WHERE pm.user_id = ? 
                           ORDER BY pm.is_default DESC, pm.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $payment_methods = [];
    while ($row = $result->fetch_assoc()) {
        // Format the payment type
        $type = $row['type'];
        $display_type = $type;
        $badge_type = '';
        
        if ($type === 'gcash' || $type === 'maya') {
            $display_type = 'E-Wallet';
            $badge_type = strtoupper($type);
        } elseif ($type === 'cod') {
            $display_type = 'Cash on Delivery';
            $badge_type = 'COD';
            // Format the masked number for COD to show address
            if ($row['street_address']) {
                $row['masked_number'] = "{$row['street_address']}, {$row['city']}, {$row['region']}\nContact: {$row['phone_number']}";
            }
        }
        
        $payment_methods[] = [
            'id' => $row['id'],
            'type' => $type,
            'display_type' => $display_type,
            'badge_type' => $badge_type,
            'masked_number' => $row['masked_number'],
            'is_default' => $row['is_default']
        ];
    }
    
    $response = [
        'status' => 'success',
        'message' => 'Payment methods loaded successfully',
        'payment_methods' => $payment_methods
    ];
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?> 