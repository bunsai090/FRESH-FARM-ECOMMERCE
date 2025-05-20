<?php
// Start session and include database connection
session_start();
require_once '../connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Order ID is required']);
    exit();
}

$order_id = intval($_GET['order_id']);
$user_id = $_SESSION['user_id'];

try {
    // Query to get order details along with items
    $query = "
        SELECT 
            o.order_id, 
            o.order_date, 
            o.total_amount, 
            o.status,
            o.payment_method,
            o.shipping_address,
            o.notes,
            oi.quantity,
            oi.price,
            p.name as product_name,
            p.image_path,
            p.price as unit_price
        FROM 
            orders o
        LEFT JOIN 
            order_items oi ON o.order_id = oi.order_id
        LEFT JOIN 
            products p ON oi.product_id = p.id
        WHERE 
            o.order_id = ? AND o.user_id = ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Order not found or access denied']);
        exit();
    }

    $orderDetails = null;
    $items = [];

    while ($row = $result->fetch_assoc()) {
        if ($orderDetails === null) {
            $orderDetails = [
                'order_id' => $row['order_id'],
                'order_date' => $row['order_date'],
                'total_amount' => $row['total_amount'],
                'status' => $row['status'],
                'payment_method' => $row['payment_method'],
                'shipping_address' => $row['shipping_address'],
                'notes' => $row['notes']
            ];
        }

        $items[] = [
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'unit_price' => $row['unit_price'],
            'image_path' => $row['image_path']
        ];
    }

    $orderDetails['items'] = $items;

    // Return the order details as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'order' => $orderDetails]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to load order details: ' . $e->getMessage()]);
}
?> 