<?php
session_start();
require_once '../connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get product info from GET or POST (single product checkout)
$product_id = 0;
$quantity = 1;

// Prefer POST if available (after form submission), else GET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
} else {
    $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
}

$product = null;
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
}

// Handle order submission
$order_success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $address = trim($_POST['address']);
    $payment_method = trim($_POST['payment_method']);

    // Get product info again for validation
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product || $product['stock'] < $quantity) {
        $error = "Sorry, not enough stock available.";
    } else {
        $total = $product['price'] * $quantity;

        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method) VALUES (?, ?, 'pending', ?, ?)");
        $stmt->bind_param("idss", $user_id, $total, $address, $payment_method);
        $stmt->execute();
        $order_id = $stmt->insert_id;

        // Insert order item
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $product['price']);
        $stmt->execute();

        // Update product stock
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $product_id);
        $stmt->execute();

        $order_success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - FarmFresh</title>
    <link rel="stylesheet" href="../css/user.css">
    <link rel="stylesheet" href="../css/checkout.css">
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-title">Checkout</div>
        <?php if ($order_success): ?>
            <div class="checkout-success">
                <i class="fa-solid fa-circle-check"></i> Your order has been placed successfully!
            </div>
        <?php elseif ($product): ?>
            <?php if ($error): ?>
                <div class="checkout-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" class="checkout-form">
                <div class="checkout-summary">
                    <div class="checkout-summary-details">
                        <img src="../<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div>
                            <div class="checkout-label"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div>₱<?php echo number_format($product['price'], 2); ?> / <?php echo htmlspecialchars($product['unit']); ?></div>
                            <div>Quantity: 
                                <input type="number" name="quantity" value="<?php echo $quantity; ?>" min="1" max="<?php echo $product['stock']; ?>" required>
                                <span style="font-size:0.95em;color:#888;">(Available: <?php echo $product['stock']; ?>)</span>
                            </div>
                        </div>
                    </div>
                    <div class="checkout-total">
                        Total: ₱<?php echo number_format($product['price'] * $quantity, 2); ?>
                    </div>
                </div>
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <div>
                    <label class="checkout-label">Delivery Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" required>
                </div>
                <div>
                    <label class="checkout-label">Payment Method</label>
                    <select name="payment_method" required>
                        <option value="">Select payment method</option>
                        <option value="COD">Cash on Delivery</option>
                        <option value="GCash">GCash</option>
                        <option value="Card">Credit/Debit Card</option>
                    </select>
                </div>
                <button type="submit" name="place_order" class="checkout-btn">Place Order</button>
            </form>
        <?php else: ?>
            <div class="checkout-error">Product not found.</div>
        <?php endif; ?>
    </div>
</body>
</html>