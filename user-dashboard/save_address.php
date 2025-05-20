<?php

session_start();
require_once '../connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$response = ['status' => 'error', 'message' => ''];

try {
    $user_id = $_SESSION['user_id'];
    $data = $_POST;
    
    // Check if this is an edit or a new address
    if (isset($data['edit_id']) && !empty($data['edit_id'])) {
        // EDIT: Update existing address
        $stmt = $conn->prepare("UPDATE delivery_addresses SET address_type = ?, recipient_name = ?, street_address = ?, city = ?, barangay = ?, region = ?, postal_code = ?, phone_number = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param(
            "ssssssssii",
            $data['addressType'],
            $data['recipientName'],
            $data['street'],
            $data['city'],
            $data['barangay'],
            $data['region'],
            $data['postalCode'],
            $data['phoneNumber'],
            $data['edit_id'],
            $user_id
        );
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Address updated successfully'];
        } else {
            throw new Exception('Failed to update address');
        }
    } else {
        // Check existing address count
        $checkStmt = $conn->prepare("SELECT COUNT(*) as address_count FROM delivery_addresses WHERE user_id = ?");
        $checkStmt->bind_param("i", $user_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $addressCount = $result->fetch_assoc()['address_count'];
        
        if ($addressCount >= 2) {
            $response = ['status' => 'error', 'message' => 'Maximum limit of 2 addresses reached'];
            echo json_encode($response);
            exit;
        }
        
        // ADD: Insert new address
        $stmt = $conn->prepare("INSERT INTO delivery_addresses (user_id, address_type, recipient_name, street_address, city, barangay, region, postal_code, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "issssssss",
            $user_id,
            $data['addressType'],
            $data['recipientName'],
            $data['street'],
            $data['city'],
            $data['barangay'],
            $data['region'],
            $data['postalCode'],
            $data['phoneNumber']
        );
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Address saved successfully'];
        } else {
            throw new Exception('Failed to save address');
        }
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage(), 'debug' => $conn->error];
}

echo json_encode($response);

?>