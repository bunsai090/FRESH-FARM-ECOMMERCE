<?php
ob_start();
session_start();
require_once '../connect.php';

header('Content-Type: application/json');

function sendJSON($data) {
    ob_clean();
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    sendJSON(['success' => false, 'message' => 'Please login first']);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid request data');
    }

    $user_id = $_SESSION['user_id'];
    $conn->begin_transaction();

    switch ($input['action']) {
        case 'save':
            // Validate required fields
            $required = ['address_type', 'recipient_name', 'street_address', 'city', 'region', 'postal_code', 'phone_number'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("$field is required");
                }
            }

            // Check address limit
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM delivery_addresses WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['count'];

            if ($count >= 3 && empty($input['address_id'])) {
                throw new Exception('Maximum number of addresses (3) reached');
            }

            if (!empty($input['address_id'])) {
                // Update existing address
                $stmt = $conn->prepare("UPDATE delivery_addresses SET address_type = ?, recipient_name = ?, 
                    street_address = ?, city = ?, region = ?, postal_code = ?, phone_number = ? 
                    WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sssssssii", $input['address_type'], $input['recipient_name'], 
                    $input['street_address'], $input['city'], $input['region'], $input['postal_code'], 
                    $input['phone_number'], $input['address_id'], $user_id);
            } else {
                // Insert new address
                $is_default = $count == 0 ? 1 : 0;
                $stmt = $conn->prepare("INSERT INTO delivery_addresses (user_id, address_type, recipient_name, 
                    street_address, city, region, postal_code, phone_number, is_default) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssssi", $user_id, $input['address_type'], $input['recipient_name'], 
                    $input['street_address'], $input['city'], $input['region'], $input['postal_code'], 
                    $input['phone_number'], $is_default);
            }

            if (!$stmt->execute()) {
                throw new Exception('Failed to save address');
            }
            break;

        case 'remove':
            if (empty($input['address_id'])) {
                throw new Exception('Address ID is required');
            }

            // Check if it's the default address
            $stmt = $conn->prepare("SELECT is_default FROM delivery_addresses WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $input['address_id'], $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Address not found');
            }

            $address = $result->fetch_assoc();
            if ($address['is_default']) {
                throw new Exception('Cannot remove default address. Please set another address as default first.');
            }

            $stmt = $conn->prepare("DELETE FROM delivery_addresses WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $input['address_id'], $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to remove address');
            }
            break;

        case 'set_default':
            if (empty($input['address_id'])) {
                throw new Exception('Address ID is required');
            }

            // Remove default from all addresses
            $stmt = $conn->prepare("UPDATE delivery_addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Set new default
            $stmt = $conn->prepare("UPDATE delivery_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $input['address_id'], $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to set default address');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

    $conn->commit();
    sendJSON(['success' => true, 'message' => 'Operation completed successfully']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}