<?php
ob_clean();
session_start();
require_once '../connect.php';

// Ensure proper content type
header('Content-Type: application/json');

try {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login first');
    }

    // Check payment methods limit
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payment_methods WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    if ($count >= 3) {
        throw new Exception('Maximum number of payment methods (3) reached');
    }

    // Get JSON input
    $jsonData = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required fields based on payment method
    if (empty($jsonData['method'])) {
        throw new Exception('Payment method is required');
    }

    $user_id = $_SESSION['user_id'];
    $method = $jsonData['method'];
    
    // Validate fields based on payment method
    switch ($method) {
        case 'cod':
            if (empty($jsonData['delivery_address']) || empty($jsonData['contact_number']) || empty($jsonData['address_id'])) {
                throw new Exception('Missing required fields for COD');
            }
            
            // Check if COD already exists for this address
            $stmt = $conn->prepare("SELECT id FROM payment_methods WHERE user_id = ? AND type = 'cod' AND additional_data LIKE ?");
            $addressPattern = '%"address_id":"' . $jsonData['address_id'] . '"%';
            $stmt->bind_param("is", $user_id, $addressPattern);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('A Cash on Delivery payment method already exists for this address');
            }
            
            $account_number = $jsonData['contact_number'];
            $masked_number = substr($account_number, 0, 4) . str_repeat('*', strlen($account_number) - 8) . substr($account_number, -4);
            break;
            
        case 'credit-card':
            if (empty($jsonData['card_number'])) {
                throw new Exception('Card number is required');
            }
            if (empty($jsonData['card_type'])) {
                throw new Exception('Card type is required');
            }
            if (!preg_match('/^\d{16}$/', $jsonData['card_number'])) {
                throw new Exception('Card number must be 16 digits');
            }
            
            // Check if card number already exists
            $stmt = $conn->prepare("SELECT id FROM payment_methods WHERE user_id = ? AND type = 'credit-card' AND account_number = ?");
            $stmt->bind_param("is", $user_id, $jsonData['card_number']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('This credit card is already registered');
            }
            
            $account_number = $jsonData['card_number'];
            $masked_number = str_repeat('*', strlen($account_number) - 4) . substr($account_number, -4);
            $additional_data = json_encode([
                'card_type' => $jsonData['card_type']
            ]);
            break;
            
        case 'gcash':
        case 'maya':
            if (empty($jsonData['account_number'])) {
                throw new Exception('Account number is required');
            }
            
            // Check if account number already exists for this e-wallet type
            $stmt = $conn->prepare("SELECT id FROM payment_methods WHERE user_id = ? AND type = ? AND account_number = ?");
            $stmt->bind_param("iss", $user_id, $method, $jsonData['account_number']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('This ' . strtoupper($method) . ' account is already registered');
            }
            
            $account_number = $jsonData['account_number'];
            $masked_number = '09' . str_repeat('*', 5) . substr($account_number, -4);
            break;
            
        default:
            throw new Exception('Invalid payment method');
    }

    // Check if first payment method
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payment_methods WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_default = $result->fetch_assoc()['count'] == 0 ? 1 : 0;

    // Insert payment method
    $stmt = $conn->prepare("INSERT INTO payment_methods (user_id, type, account_number, masked_number, is_default, additional_data) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $method, $account_number, $masked_number, $is_default, $additional_data);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to add payment method: ' . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Payment method added successfully'
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    error_log("Payment method error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
exit();
?>