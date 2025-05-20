<?php
session_start();
require_once '../connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

try {
    // Get the raw POST data
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        throw new Exception('Address ID is required');
    }

    $user_id = $_SESSION['user_id'];
    $address_id = intval($data['id']);

    // Only allow deletion if the address belongs to the user
    $stmt = $conn->prepare("DELETE FROM delivery_addresses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $address_id, $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Address deleted successfully']);
    } else {
        throw new Exception('Failed to delete address or address not found');
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>