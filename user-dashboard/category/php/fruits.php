<?php
// Include the database connection file
require_once __DIR__ . '/../../../connect.php';

define('LOW_STOCK_THRESHOLD', 15); // Define Low Stock threshold

// Now you can use $conn (or your connection variable) to interact with the database
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fruits</title>
    <link rel="stylesheet" href="../css/fruits.css">
</head>
<body>
    <header>
        <a href="/fresh1/user-dashboard/user.php" class="nav-button">Back to Home</a>
        <h1 class="category-title">Fruits</h1>
        <a href="/fresh1/user-dashboard/cart/cart.php" class="nav-button">Go to Cart</a>
    </header>

    <main class="product-grid">
        <?php
        // Fetch fruits from the database
        $sql = "SELECT id, name, description, price, image_path, stock, status FROM products WHERE category = 'Fruits'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            // Loop through fruits and display them
            while ($fruit = $result->fetch_assoc()) {
                $isOutOfStock = ($fruit['status'] === 'Out of Stock' || $fruit['stock'] <= 0);
                
                // Determine if product should be displayed as "Low Stock"
                $isEffectivelyLowStock = !$isOutOfStock &&
                                         ($fruit['status'] === 'Low Stock' ||
                                          ($fruit['stock'] > 0 && $fruit['stock'] <= LOW_STOCK_THRESHOLD));

                echo "<div class='product-card' data-product-id='" . htmlspecialchars($fruit['id']) . "'>";
                // Assuming image_path is relative to a base images directory, e.g., /fresh1/images/
                // Adjust the base path as per your actual image storage structure.
                $imagePath = '/fresh1/' . htmlspecialchars($fruit['image_path']);
                echo "<img src='" . $imagePath . "' alt='" . htmlspecialchars($fruit['name']) . "'>";
                echo "<h3>" . htmlspecialchars($fruit['name']) . "</h3>";
                echo "<p class='description'>" . htmlspecialchars($fruit['description']) . "</p>";

                // Display Status and Stock
                echo "<div class='product-status-info'>";
                if ($isOutOfStock) {
                    echo "<span class='status-badge status-out-of-stock'>Out of Stock</span>";
                } elseif ($isEffectivelyLowStock) {
                    echo "<span class='status-badge status-low-stock'>Low Stock</span>";
                    echo "<span class='stock-left'>(" . htmlspecialchars($fruit['stock']) . " left)</span>";
                } else { // In Stock (and not Out of Stock or Low Stock)
                    echo "<span class='status-badge status-in-stock'>In Stock</span>";
                    echo "<span class='stock-left'>(" . htmlspecialchars($fruit['stock']) . " available)</span>";
                }
                echo "</div>";

                echo "<p class='price' data-price='" . htmlspecialchars($fruit['price']) . "'>₱" . number_format($fruit['price'], 2) . "</p>";
                
                echo "<div class='quantity-selector'" . ($isOutOfStock ? " style='display:none;'" : "") . ">";
                echo "<button class='quantity-btn minus-btn'>-</button>";
                echo "<span class='quantity-value'>1</span>";
                echo "<button class='quantity-btn plus-btn'>+</button>";
                echo "</div>";
                echo "<p class='total-price'" . ($isOutOfStock ? " style='display:none;'" : "") . ">Total: ₱" . number_format($fruit['price'], 2) . "</p>";
                echo "<button class='add-to-cart-btn'" . ($isOutOfStock ? " disabled" : "") . ">" . ($isOutOfStock ? "Out of Stock" : "Add to Cart") . "</button>";
                echo "</div>";
            }
        } else {
            echo "<p style='text-align:center; grid-column: 1 / -1;'>No fruits found in this category.</p>";
        }
        // $conn->close(); // Consider closing connection at the end of script execution if not handled by connect.php
        ?>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.product-card').forEach(card => {
            const minusBtn = card.querySelector('.minus-btn');
            const plusBtn = card.querySelector('.plus-btn');
            const quantityValueEl = card.querySelector('.quantity-value');
            const totalPriceEl = card.querySelector('.total-price');
            const pricePerItem = parseFloat(card.querySelector('.price')?.dataset.price);
            const addToCartBtn = card.querySelector('.add-to-cart-btn');
            const productId = card.dataset.productId;

            // Only add event listeners if elements exist (i.e., not out of stock)
            if (minusBtn && plusBtn && quantityValueEl && totalPriceEl && addToCartBtn && !addToCartBtn.disabled) {
                minusBtn.addEventListener('click', () => {
                    let quantity = parseInt(quantityValueEl.textContent);
                    if (quantity > 1) {
                        quantity--;
                        quantityValueEl.textContent = quantity;
                        updateTotalPrice(quantity);
                    }
                });

                plusBtn.addEventListener('click', () => {
                    let quantity = parseInt(quantityValueEl.textContent);
                    quantity++;
                    quantityValueEl.textContent = quantity;
                    updateTotalPrice(quantity);
                });

                function updateTotalPrice(quantity) {
                    const newTotal = pricePerItem * quantity;
                    totalPriceEl.textContent = 'Total: ₱' + newTotal.toFixed(2);
                }

                addToCartBtn.addEventListener('click', () => {
                    const quantity = parseInt(quantityValueEl.textContent);
                    console.log(`Adding to cart: Product ID ${productId}, Quantity ${quantity}, Price ${pricePerItem}`);

                    fetch('/fresh1/user-dashboard/cart/add_to_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: quantity
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Add to cart response:', data);
                        if(data.success) {
                            alert(data.message || 'Item added to cart!');
                            // Optionally, update a cart icon/count on the page
                        } else {
                            alert(data.message || 'Failed to add item to cart.');
                        }
                    })
                    .catch((error) => {
                        console.error('Error adding to cart:', error);
                        alert('Error adding item to cart.');
                    });
                });
            }
        });
    });
    </script>

</body>
</html>



