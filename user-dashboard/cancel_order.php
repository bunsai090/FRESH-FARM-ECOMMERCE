<?php
session_start();
require_once '../connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit();
}

$order_id = $data['order_id'];
$user_id = $_SESSION['user_id'];

// Check if order exists and belongs to user
$checkStmt = $conn->prepare("SELECT status FROM orders WHERE order_id = ? AND user_id = ?");
$checkStmt->bind_param("ii", $order_id, $user_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit();
}

$order = $result->fetch_assoc();

if ($order['status'] !== 'pending') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Only pending orders can be cancelled']);
    exit();
}

// Update order status to cancelled
$updateStmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ?");
$updateStmt->bind_param("ii", $order_id, $user_id);

if ($updateStmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Order cancelled successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Error cancelling order']);
} 