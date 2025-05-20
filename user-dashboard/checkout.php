<?php
session_start();
require_once '../connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize variables
$checkout_items = [];
$grand_total_checkout = 0;
$error = '';
$order_success = false;

// Get user info
$stmt_user = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// ---- START ITEM POPULATION ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source_data_for_items = null;
    $is_placing_order_now = isset($_POST['place_order']);

    if ($is_placing_order_now) {
        if (isset($_POST['selected_items_hidden']) && !empty($_POST['selected_items_hidden'])) {
            $source_data_for_items = json_decode($_POST['selected_items_hidden'], true);
            if (!is_array($source_data_for_items)) {
                $source_data_for_items = null;
                $error .= "Error retrieving items for order. Please try again.<br>";
            }
        }
    } elseif (isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
        $source_data_for_items = $_POST['selected_items'];
    }

    if (is_array($source_data_for_items) && !empty($source_data_for_items)) {
        foreach ($source_data_for_items as $cart_id_key => $quantity_val) {
            // Check if this is a direct purchase cart ID (string starting with 'direct_')
            if (is_string($cart_id_key) && strpos($cart_id_key, 'direct_') === 0) {
                // For direct purchases, extract the product ID and fetch the product
                $product_id = (int)substr($cart_id_key, 7); // Remove 'direct_' prefix
                $quantity = (int)$quantity_val;
                
                if ($product_id > 0 && $quantity > 0) {
                    $stmt_product = $conn->prepare("SELECT id as product_id, name, price, stock, image_path, unit FROM products WHERE id = ?");
                    $stmt_product->bind_param("i", $product_id);
                    $stmt_product->execute();
                    $result = $stmt_product->get_result();
                    
                    if ($product = $result->fetch_assoc()) {
                        if ($quantity > $product['stock']) {
                            $error .= htmlspecialchars($product['name']) . " is out of stock for the requested quantity ($quantity). Available: " . $product['stock'] . ". It has been removed from checkout.<br>";
                            continue;
                        }
                        $product['checkout_quantity'] = $quantity;
                        $product['cart_id'] = $cart_id_key; // Preserve the direct_ prefix
                        $checkout_items[] = $product;
                        $grand_total_checkout += $product['price'] * $product['checkout_quantity'];
                    } else {
                        $error .= "The selected product could not be found.<br>";
                    }
                    $stmt_product->close();
                }
                continue; // Skip regular cart processing for direct purchases
            }
            
            // Regular cart item processing
            $cart_id = (int)$cart_id_key;
            $quantity = (int)$quantity_val;

            if ($quantity <= 0) continue; // Skip if quantity is invalid

            $sql_item = "SELECT c.cart_id, c.product_id, c.quantity as cart_quantity, 
                                p.name, p.price, p.stock, p.image_path, p.unit
                         FROM cart c 
                         JOIN products p ON c.product_id = p.id 
                         WHERE c.cart_id = ? AND c.user_id = ?";
            $stmt_item = $conn->prepare($sql_item);
            if ($stmt_item) {
                $stmt_item->bind_param("ii", $cart_id, $user_id);
                $stmt_item->execute();
                $item_result = $stmt_item->get_result();
                if ($item = $item_result->fetch_assoc()) {
                    if ($quantity > $item['stock']) {
                        $error .= htmlspecialchars($item['name']) . " is out of stock for the requested quantity ($quantity). Available: " . $item['stock'] . ". It has been removed from checkout.<br>";
                        continue; // Skip this item, it will not be added to $checkout_items
                    }
                    $item['checkout_quantity'] = $quantity; 
                    $checkout_items[] = $item;
                    $grand_total_checkout += $item['price'] * $item['checkout_quantity'];
                } else {
                    $error .= "Invalid item (Cart ID: $cart_id) could not be retrieved. It might have been removed.<br>";
                }
                $stmt_item->close();
            } else {
                 $error .= "Database error preparing to fetch item details. Please try again later.<br>";
                 break; // Major DB error, stop processing items
            }
        }
        if (empty($checkout_items) && empty($error) && !empty($source_data_for_items) ) { 
             // All items from source had issues (e.g. all out of stock) but no DB error.
             $error .= "No valid items could be prepared for checkout from your selection. Please review your cart.<br>";
        }
    } elseif ($is_placing_order_now && empty($source_data_for_items) && empty($error)) {
        // This specific condition leads to the "session expired" type error if no previous error was set.
        $error = "Your session seems to have expired or there was an issue with the items. Please try selecting items from your cart again.<br>";
    } elseif (!$is_placing_order_now && empty($source_data_for_items) && isset($_POST['selected_items'])) {
         $error = "No items were selected from your cart. Please go back and select items to checkout.<br>";
    } elseif (!$is_placing_order_now && empty($source_data_for_items) && !empty($_POST) && !isset($_POST['selected_items'])){
        // Generic POST to checkout without expected data from cart and not placing order
        $error = "Invalid request to checkout. Please proceed from your cart.<br>";
    }
} 
// Handle "Buy Now" flow via GET parameters
elseif (isset($_GET['buy_now']) && $_GET['buy_now'] === 'true' && isset($_GET['product_id']) && isset($_GET['quantity'])) {
    $product_id = (int)$_GET['product_id'];
    $quantity = (int)$_GET['quantity'];
    
    if ($product_id > 0 && $quantity > 0) {
        // Fetch the product directly from database
        $stmt_product = $conn->prepare("SELECT id as product_id, name, price, stock, image_path, unit FROM products WHERE id = ?");
        $stmt_product->bind_param("i", $product_id);
        $stmt_product->execute();
        $result = $stmt_product->get_result();
        
        if ($product = $result->fetch_assoc()) {
            // Check if enough stock
            if ($quantity > $product['stock']) {
                $error .= htmlspecialchars($product['name']) . " is out of stock for the requested quantity ($quantity). Available: " . $product['stock'] . ".<br>";
            } else {
                // Create a checkout item in the same format as from cart
                $product['checkout_quantity'] = $quantity;
                // Set a cart_id to maintain compatibility with the rest of the code
                $product['cart_id'] = 'direct_' . $product_id; // Use a prefix to avoid conflicts with real cart IDs
                $checkout_items[] = $product;
                $grand_total_checkout = $product['price'] * $quantity;
            }
        } else {
            $error .= "The selected product could not be found.<br>";
        }
        $stmt_product->close();
    } else {
        $error .= "Invalid product or quantity specified.<br>";
    }
}
// ---- END ITEM POPULATION ----

// ---- START ADDRESS & PAYMENT FETCHING ----
$address_id_to_use = 0; // This will hold the ID of the address to be used/displayed

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['address_id'])) {
    $address_id_to_use = (int)$_POST['address_id'];
} elseif (isset($_GET['address_id'])) { // For initial load via GET if that's ever a scenario (less likely now)
    $address_id_to_use = (int)$_GET['address_id'];
}

$default_address = null;
try {
    if ($address_id_to_use > 0) {
        $stmt_addr = $conn->prepare("SELECT * FROM delivery_addresses WHERE id = ? AND user_id = ?");
        $stmt_addr->bind_param("ii", $address_id_to_use, $user_id);
        $stmt_addr->execute();
        $default_address = $stmt_addr->get_result()->fetch_assoc();
        $stmt_addr->close();
    }
    if (!$default_address) { // If no specific address ID provided or found, try default then first
        $stmt_addr_def = $conn->prepare("SELECT * FROM delivery_addresses WHERE user_id = ? AND is_default = 1");
        $stmt_addr_def->bind_param("i", $user_id);
        $stmt_addr_def->execute();
        $default_address = $stmt_addr_def->get_result()->fetch_assoc();
        $stmt_addr_def->close();
        if (!$default_address) {
            $stmt_addr_first = $conn->prepare("SELECT * FROM delivery_addresses WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt_addr_first->bind_param("i", $user_id);
            $stmt_addr_first->execute();
            $default_address = $stmt_addr_first->get_result()->fetch_assoc();
            $stmt_addr_first->close();
        }
    }
    if ($default_address) {
        $address_id_to_use = $default_address['id']; // Ensure this is set to the ID of the address being displayed
    }
} catch (Exception $e) {
    error_log("Address table error: " . $e->getMessage());
    $error .= "Could not load address information. Please try again.<br>";
}

$payment_methods = [];
try {
    $stmt_pay = $conn->prepare("SELECT * FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC");
    $stmt_pay->bind_param("i", $user_id);
    $stmt_pay->execute();
    $payment_methods = $stmt_pay->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_pay->close();
} catch (Exception $e) {
    error_log("Payment methods table error: " . $e->getMessage());
    $error .= "Could not load payment methods. Please try again.<br>";
}
// ---- END ADDRESS & PAYMENT FETCHING ----

// ---- START HANDLE ORDER SUBMISSION ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!empty($error)) {
        // Errors occurred during item population. Do not proceed.
        // $error messages will be displayed by the HTML part.
    } elseif (empty($checkout_items)) {
        // If item population resulted in empty checkout_items, but no specific error set above
        // (e.g. if source_data_for_items was empty and it wasn't the 'session expired' case)
        if(empty($error)) $error = "There are no valid items to checkout. Please return to your cart.<br>";
    } else {
        // Proceed with order placement validations
        $payment_method_id_posted = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
        // Use $address_id_to_use as it reflects the address selected/defaulted and potentially submitted by the form

        if (empty($address_id_to_use)) {
            $error .= "Please select or add a delivery address.<br>";
        }
        if (empty($payment_method_id_posted)) {
            $error .= "Please select a payment method.<br>";
        }

        if (empty($error)) { // If all preliminary checks pass, attempt transaction
            try {
                // Re-fetch address and payment for final validation before use, using IDs from the form submission
                $stmt_addr_val = $conn->prepare("SELECT * FROM delivery_addresses WHERE id = ? AND user_id = ?");
                $stmt_addr_val->bind_param("ii", $address_id_to_use, $user_id);
                $stmt_addr_val->execute();
                $final_address = $stmt_addr_val->get_result()->fetch_assoc();
                $stmt_addr_val->close();
                if (!$final_address) throw new Exception('Selected delivery address could not be validated.');

                $stmt_pay_val = $conn->prepare("SELECT * FROM payment_methods WHERE id = ? AND user_id = ?");
                $stmt_pay_val->bind_param("is", $payment_method_id_posted, $user_id); // payment_method_id could be string from form
                $stmt_pay_val->execute();
                $final_payment = $stmt_pay_val->get_result()->fetch_assoc();
                $stmt_pay_val->close();
                if (!$final_payment) throw new Exception('Selected payment method could not be validated.');

                $conn->begin_transaction();
                try {
                    $shipping_address_formatted = $final_address['recipient_name'] . "\n" .
                                              $final_address['street_address'] . "\n" .
                                              $final_address['city'] . ", " . $final_address['region'] . "\n" .
                                              "Philippines " . $final_address['postal_code'] . "\n" .
                                              "Phone: " . $final_address['phone_number'];

                    $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, address_id, payment_method_id) VALUES (?, ?, 'pending', ?, ?, ?, ?)");
                    $stmt_order->bind_param("idssii", $user_id, $grand_total_checkout, $shipping_address_formatted, $final_payment['type'], $final_address['id'], $final_payment['id']);
                    $stmt_order->execute();
                    $order_id = $stmt_order->insert_id;
                    $stmt_order->close();

                    foreach ($checkout_items as $item_to_order) {
                        $stmt_stock_check = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                        $stmt_stock_check->bind_param("i", $item_to_order['product_id']);
                        $stmt_stock_check->execute();
                        $current_product_stock_result = $stmt_stock_check->get_result();
                        if ($current_product_stock_row = $current_product_stock_result->fetch_assoc()){
                            $current_product_stock = $current_product_stock_row['stock'];
                             if ($current_product_stock < $item_to_order['checkout_quantity']) {
                                throw new Exception("Stock for " . htmlspecialchars($item_to_order['name']) . " changed. Not enough stock available.");
                            }
                        } else {
                             throw new Exception("Could not verify stock for " . htmlspecialchars($item_to_order['name']) . ".");
                        }
                        $stmt_stock_check->close();

                        $stmt_order_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                        $stmt_order_item->bind_param("iiid", $order_id, $item_to_order['product_id'], $item_to_order['checkout_quantity'], $item_to_order['price']);
                        $stmt_order_item->execute();
                        $stmt_order_item->close();

                        $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                        $stmt_update_stock->bind_param("iii", $item_to_order['checkout_quantity'], $item_to_order['product_id'], $item_to_order['checkout_quantity']);
                        if (!$stmt_update_stock->execute() || $stmt_update_stock->affected_rows === 0) {
                            throw new Exception('Failed to update product stock for ' . htmlspecialchars($item_to_order['name']) . '. Order rolled back.');
                        }
                        $stmt_update_stock->close();
                    }
                    
                    foreach ($checkout_items as $item_ordered) {
                        // Skip cart deletion for direct purchases (they aren't in the cart)
                        if (isset($item_ordered['cart_id']) && 
                            (is_string($item_ordered['cart_id']) && strpos($item_ordered['cart_id'], 'direct_') === 0)) {
                            continue; // Skip deletion for direct purchases
                        }
                        
                        $stmt_clear_cart = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
                        $stmt_clear_cart->bind_param("ii", $item_ordered['cart_id'], $user_id);
                        $stmt_clear_cart->execute();
                        $stmt_clear_cart->close();
                    }

                    $conn->commit();
                    $order_success = true;
                    $checkout_items = []; // Clear items after successful order to prevent re-display on success page if reloaded.
                    $grand_total_checkout = 0;

                } catch (Exception $e) {
                    $conn->rollback();
                    $error .= "Order processing failed: " . $e->getMessage() . "<br>";
                    error_log("Order transaction error: " . $e->getMessage());
                }
            } catch (Exception $e) {
                $error .= "Order setup error: " . $e->getMessage() . "<br>";
                error_log("Order setup error: " . $e->getMessage());
            }
        } // end if empty(error) for transaction
    } // end if can proceed with order placement (no item errors)
} // ---- END HANDLE ORDER SUBMISSION ----

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - FarmFresh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/user.css">
    <link rel="stylesheet" href="../css/checkout.css">
</head>
<body>
    <div class="checkout-container">
        <h1 class="checkout-title">Checkout</h1>
        <?php if ($order_success): ?>
            <div class="checkout-success">
                <i class="fa-solid fa-circle-check"></i> Your order has been placed successfully!
                <a href="orders.php" class="view-order-btn">View Orders</a>
            </div>
        <?php else: ?>
            <?php if (!empty($error)): // Display all accumulated errors ?>
                <div class="checkout-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($checkout_items)): // Only show form if there are items to checkout ?>
                <form method="post" class="checkout-form">
                    <input type="hidden" name="selected_items_hidden" value="<?php echo htmlspecialchars(json_encode(array_column($checkout_items, 'checkout_quantity', 'cart_id'))); ?>">
                    
                    <div class="checkout-summary-multiple">
                        <h3>Order Summary</h3>
                        <?php foreach ($checkout_items as $item): ?>
                            <div class="product-details-checkout">
                                <img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="product-info-checkout">
                                    <span class="product-name-checkout"><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['checkout_quantity']; ?>)</span>
                                    <span class="product-price-checkout">₱<?php echo number_format($item['price'] * $item['checkout_quantity'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="checkout-total-multiple">
                            <strong>Total: ₱<?php echo number_format($grand_total_checkout, 2); ?></strong>
                        </div>
                    </div>

                    <input type="hidden" name="address_id" value="<?php echo $address_id_to_use; // Use the ID of the address being displayed/used ?>">

                    <div class="delivery-section">
                        <div class="section-header">
                            <?php if (!$default_address): ?>
                                <a href="address.php?redirect=checkout" class="section-title no-data">
                                    Delivery Address <i class="fas fa-plus add-icon"></i>
                                </a>
                            <?php else: ?>
                                <div class="section-title">Delivery Address</div>
                            <?php endif; ?>
                            <a href="address.php?redirect=checkout&select=true" class="change-link">Change</a>
                        </div>
                        <?php if ($default_address): ?>
                            <div class="address-info">
                                <div class="address-type"><?php echo htmlspecialchars($default_address['address_type']); ?> - <?php echo htmlspecialchars($default_address['recipient_name']); ?></div>
                                <?php echo htmlspecialchars($default_address['street_address']); ?>, 
                                <?php echo htmlspecialchars($default_address['city']); ?>, <?php echo htmlspecialchars($default_address['region']); ?> <?php echo htmlspecialchars($default_address['postal_code']); ?><br>
                                <?php echo htmlspecialchars($default_address['phone_number']); ?>
                            </div>
                        <?php else: ?>
                            <p class="no-address-msg">No delivery address set. <a href="address.php?redirect=checkout">Add Address</a></p>
                        <?php endif; ?>
                    </div>

                    <div class="payment-section">
                        <div class="section-header">
                            <?php if (empty($payment_methods)): ?>
                                <a href="payment.php?redirect=checkout" class="section-title no-data">
                                    Payment Method <i class="fas fa-plus add-icon"></i>
                                </a>
                            <?php else: ?>
                                <div class="section-title">Payment Method</div>
                            <?php endif; ?>
                            <a href="payment.php?redirect=checkout&select=true" class="change-link">Change/Add</a>
                        </div>
                        <?php if (!empty($payment_methods)): ?>
                            <div class="payment-methods">
                                <?php foreach ($payment_methods as $method): ?>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="<?php echo $method['id']; ?>" 
                                            <?php 
                                            // Check if this method was POSTed or if it's the default and nothing was POSTed
                                            $checked = false;
                                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])){
                                                if ($_POST['payment_method'] == $method['id']) $checked = true;
                                            } elseif ($method['is_default']) {
                                                $checked = true;
                                            }
                                            if ($checked) echo 'checked'; 
                                            ?>
                                         required>
                                        <div class="payment-card">
                                            <img src="../assets/<?php echo strtolower($method['type']); ?>.png" alt="<?php echo $method['type']; ?>" class="payment-icon">
                                            <div class="payment-details">
                                                <span class="payment-type"><?php echo htmlspecialchars($method['type']); ?></span>
                                                <span class="payment-number"><?php echo htmlspecialchars($method['masked_number']); ?></span>
                                                <?php if ($method['is_default']): ?>
                                                    <span class="default-badge">Default</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-payment-msg">No payment methods found. <a href="payment.php?redirect=checkout">Add Payment</a></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="place_order" class="checkout-btn" <?php echo (empty($checkout_items) || !$default_address || empty($payment_methods) || !empty($error) ) ? 'disabled' : ''; ?>>
                        Place Order (₱<?php echo number_format($grand_total_checkout, 2); ?>)
                    </button>
                </form>
            <?php elseif (empty($error)): // No items and no error explicitly set from item population (e.g. direct GET access) ?>
                <div class="checkout-error">Your cart is empty or no items were selected for checkout. <a href='../category/php/cart.php'>Return to Cart</a></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <style>
    /* Basic styles for new multi-item summary */
    .checkout-summary-multiple {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .checkout-summary-multiple h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 1.2em;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    .product-details-checkout {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0; /* Lighter separator */
    }
    .product-details-checkout:last-child {
        margin-bottom: 0;
        border-bottom: none;
    }
    .product-details-checkout img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 6px;
    }
    .product-info-checkout {
        flex-grow: 1;
        display: flex;
        justify-content: space-between;
    }
    .product-name-checkout {
        font-size: 0.95em;
        color: #333;
    }
    .product-price-checkout {
        font-size: 0.95em;
        font-weight: 600;
        color: #28a745;
    }
    .checkout-total-multiple {
        text-align: right;
        font-size: 1.2em;
        font-weight: bold;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #ddd;
    }
    .checkout-total-multiple strong {
        color: #28a745;
    }
    .change-link {
        font-size: 0.85em;
        color: #007bff;
        text-decoration: none;
    }
    .change-link:hover {
        text-decoration: underline;
    }
    /* Original CSS from user below */
        .checkout-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .checkout-title {
            color: #2c5282;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2em;
        }

        .checkout-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .product-details {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .product-details img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-details h2 {
            margin: 0 0 10px 0;
            color: #2d3748;
        }

        .price {
            color: #48bb78;
            font-size: 1.2em;
            font-weight: 600;
        }

        .quantity-section {
            margin-top: 10px;
        }

        .stock-info {
            color: #718096;
            font-size: 0.9em;
        }

        .checkout-total {
            text-align: right;
            font-size: 1.25em;
            font-weight: 600;
            color: #2d3748;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .total-amount {
            color: #48bb78;
        }

        .delivery-section,
        .payment-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .section-title {
            color: #2d3748;
            margin: 0 0 15px 0;
        }

        .section-title.no-data {
            color: #48bb78;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        .section-title.no-data:hover {
            color: #38a169;
        }

        .section-title .add-icon {
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            background: #48bb78;
            color: white;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .section-title.no-data:hover .add-icon {
            background: #38a169;
            transform: scale(1.1);
        }

        .address-info {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .address-type {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .payment-methods {
            display: grid;
            gap: 15px;
        }

        .payment-option {
            display: block;
            cursor: pointer;
        }

        .payment-option input {
            display: none;
        }

        .payment-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .payment-option input:checked + .payment-card {
            border-color: #48bb78;
            background: #f0fff4;
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .payment-details {
            flex: 1;
        }

        .payment-type {
            display: block;
            font-weight: 600;
            color: #2d3748;
        }

        .payment-number {
            display: block;
            color: #718096;
            font-size: 0.9em;
        }

        .default-badge {
            display: inline-block;
            background: #48bb78;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: #48bb78;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .checkout-btn:hover:not(:disabled) {
            background: #38a169;
        }

        .checkout-btn:disabled {
            background: #a0aec0;
            cursor: not-allowed;
        }

        .checkout-success {
            text-align: center;
            padding: 30px;
            background: #f0fff4;
            border-radius: 8px;
            color: #2f855a;
        }

        .checkout-error {
            text-align: center;
            padding: 15px;
            background: #fff5f5;
            border-radius: 8px;
            color: #c53030;
            margin-bottom: 20px;
        }

        .view-order-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #48bb78;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .view-order-btn:hover {
            background: #38a169;
        }

        .no-address-msg,
        .no-payment-msg {
            color: #718096;
            text-align: center;
            padding: 15px;
        }

        .no-address-msg a,
        .no-payment-msg a {
            color: #48bb78;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .no-address-msg a:hover,
        .no-payment-msg a:hover {
            color: #38a169;
        }
    </style>
</body>
</html>