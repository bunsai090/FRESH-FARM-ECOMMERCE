<?php
require_once '../../../connect.php'; // Adjusted path to connect.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p>Please <a href='../../../login.php'>login</a> to view your cart.</p>"; // Adjusted link
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$grand_total = 0; 

// Fetch cart items for the user, including product stock
$sql = "SELECT c.cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image_path, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
        $grand_total += $row['price'] * $row['quantity']; 
    }
    $stmt->close();
} else {
    error_log("Error preparing statement: " . $conn->error);
    echo "<p>An error occurred while fetching your cart. Please try again later.</p>";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart</title>
    <link rel="stylesheet" href="../css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="cart-container">
        <h1><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h1>

        <?php if (empty($cart_items)): ?>
            <p class="empty-cart-message">Your cart is currently empty. <a href="../../../index.php">Continue shopping!</a></p>
        <?php else: ?>
            <form id="cartForm" action="../../checkout.php" method="POST">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th class="select-all-header"><input type="checkbox" id="selectAllCheckbox"></th>
                            <th>Product</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Available Stock</th> 
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr data-item-price="<?php echo htmlspecialchars($item['price']); ?>" data-item-quantity="<?php echo htmlspecialchars($item['quantity']); ?>" data-cart-id="<?php echo $item['cart_id']; ?>">
                                <td>
                                    <input type="checkbox" name="selected_items[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" class="item-checkbox">
                                </td>
                                <td class="product-image-cell">
                                    <?php
                                    $image_url = '../../../' . htmlspecialchars($item['image_path']);
                                    if (empty($item['image_path']) || !file_exists($_SERVER['DOCUMENT_ROOT'] . '/fresh1/' . $item['image_path'] )) {
                                        $image_url = '../../../assets/images/placeholder.jpg';
                                    }
                                    ?>
                                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-thumbnail">
                                </td>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['stock']); ?></td>
                                <td class="item-total-price">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                <td class="action-cell">
                                    <a href="remove_from_cart.php?cart_id=<?php echo $item['cart_id']; ?>" class="remove-btn" title="Remove item">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" style="text-align: right;"><strong>Selected Total:</strong></td>
                            <td colspan="2"><strong id="selectedTotalPriceValue">₱0.00</strong></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="cart-actions">
                    <a href="../../../index.php" class="continue-shopping-btn"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
                    <button type="submit" id="checkoutBtn" class="checkout-btn" disabled>
                        Proceed to Checkout <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        const selectedTotalPriceElement = document.getElementById('selectedTotalPriceValue');
        const checkoutBtn = document.getElementById('checkoutBtn');
        const cartForm = document.getElementById('cartForm');

        console.log('Cart JS Loaded. Items:', itemCheckboxes.length, 'SelectAll:', selectAllCheckbox);

        function updateSelectedTotalAndButtonState() {
            let currentSelectedTotal = 0;
            let anythingSelected = false;
            console.log('Updating total...');

            itemCheckboxes.forEach((checkbox, index) => {
                if (checkbox.checked) {
                    anythingSelected = true;
                    const row = checkbox.closest('tr');
                    const priceString = row.dataset.itemPrice;
                    const quantityString = checkbox.value; // Quantity is stored in the checkbox value
                    
                    const price = parseFloat(priceString);
                    const quantity = parseInt(quantityString);

                    console.log(`Item ${index + 1}: Checked. Price: ${price}, Quantity: ${quantity}`);

                    if (!isNaN(price) && !isNaN(quantity)) {
                        currentSelectedTotal += price * quantity;
                    } else {
                        console.error(`Item ${index + 1}: Invalid price or quantity. PriceStr: ${priceString}, QtyStr: ${quantityString}`);
                    }
                }
            });
            
            console.log('Total Selected:', currentSelectedTotal, 'Anything Selected:', anythingSelected);
            selectedTotalPriceElement.textContent = '₱' + currentSelectedTotal.toFixed(2);
            checkoutBtn.disabled = !anythingSelected;
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (event) => {
                console.log('Select All changed:', event.target.checked);
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = event.target.checked;
                });
                updateSelectedTotalAndButtonState();
            });
        }

        itemCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                console.log('Item checkbox changed. ID:', checkbox.name, 'Checked:', checkbox.checked);
                if (!checkbox.checked && selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }
                
                let allChecked = true;
                if(itemCheckboxes.length > 0){
                    itemCheckboxes.forEach(cb => {
                        if (!cb.checked) {
                            allChecked = false;
                        }
                    });
                } else {
                    allChecked = false; 
                }

                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                }
                updateSelectedTotalAndButtonState();
            });
        });

        if (cartForm) {
            cartForm.addEventListener('submit', (event) => {
                console.log('Cart form submitted');
                let anythingSelected = false;
                itemCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        anythingSelected = true;
                    }
                });
                if (!anythingSelected) {
                    console.log('Submission prevented: No items selected');
                    event.preventDefault();
                    alert('Please select at least one item to proceed to checkout.');
                }
            });
        }

        // Initial state update on page load
        console.log('Initial call to update total.');
        updateSelectedTotalAndButtonState();
    });
    </script>
</body>
</html>
