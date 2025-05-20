<?php
// Start session and include database connection
session_start();
require_once '../connect.php';

// Handle logout action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['action']) && $data['action'] === 'logout') {
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
        exit();
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user's order count
$orderCount = 0;
try {
    $orderStmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
    $orderStmt->bind_param("i", $user_id);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    $orderCount = $orderResult->fetch_assoc()['order_count'];
} catch (Exception $e) {
    error_log("Error getting order count: " . $e->getMessage());
}

// Get user's favorites count
$favoriteCount = 0;
try {
    $favoriteStmt = $conn->prepare("SELECT COUNT(*) as favorite_count FROM favorites WHERE user_id = ?");
    $favoriteStmt->bind_param("i", $user_id);
    $favoriteStmt->execute();
    $favoriteResult = $favoriteStmt->get_result();
    $favoriteCount = $favoriteResult->fetch_assoc()['favorite_count'];
} catch (Exception $e) {
    error_log("Error getting favorite count: " . $e->getMessage());
}

// Handle profile update
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $phone = !empty($_POST['phone']) ? $_POST['phone'] : null;
    $birthDate = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;

    // Handle file upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $tempname = $_FILES['profile_image']['tmp_name'];
            $folder = "../uploads/profile_images/";
            
            // Create directory if it doesn't exist
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }

            // Delete previous profile image if exists
            if (!empty($user['profile_image']) && file_exists('../' . $user['profile_image'])) {
                unlink('../' . $user['profile_image']);
            }
            
            // Generate unique filename
            $new_filename = "profile_" . $user_id . "_" . time() . "." . $filetype;
            $filepath = $folder . $new_filename;
            
            if (move_uploaded_file($tempname, $filepath)) {
                // Update database with new image path
                $relative_path = "uploads/profile_images/" . $new_filename;
                $updateStmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                $updateStmt->bind_param("si", $relative_path, $user_id);
                $updateStmt->execute();
            }
        }
    }

    // Update user information - update both phone and phone_number fields for compatibility
    $updateStmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, phone_number = ?, birth_date = ? WHERE user_id = ?");
    $updateStmt->bind_param("sssssi", $firstName, $lastName, $phone, $phone, $birthDate, $user_id);
    
    if ($updateStmt->execute()) {
        $message = "Profile updated successfully!";
        $messageType = "success";
        
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    } else {
        $message = "Error updating profile. Please try again.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - FarmFresh</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/user.css">
    <link rel="stylesheet" href="../css/favorite.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="toggle-container">
                <button class="toggle-btn" id="sidebarToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <a href="user.php" class="back-button">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
            </div>
            <div class="user-info">
                <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../assets/images/default-avatar.jpg'; ?>" 
                     alt="User Avatar" class="user-avatar">
                <h3><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h3>
                <p><?php echo $user['email']; ?></p>
                <div class="user-stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo isset($orderCount) ? $orderCount : '0'; ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo isset($favoriteCount) ? $favoriteCount : '0'; ?></div>
                        <div class="stat-label">Favorites</div>
                    </div>
                </div>
            </div>
            <ul class="menu-items">
                <li><a href="orders.php"><i class="fa-solid fa-bag-shopping"></i> My Orders</a></li>
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a href="payment.php"><i class="fa-solid fa-credit-card"></i> Payment Methods</a></li>
                <li><a href="address.php"><i class="fa-solid fa-location-dot"></i> Delivery Address</a></li>
                <li><a href="favorite.php" class="active"><i class="fa-solid fa-heart"></i> Favorites</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Account Settings</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-phone"></i> Contact Us</a></li>
                <li><a href="#" onclick="showLogoutModal(); return false;" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-wrapper">
                <h1 class="page-title">My Favorites</h1>
                <div class="products-grid">
                    <?php
                    // Get user's favorite products with product details
                    $query = "SELECT p.*, f.favorite_id 
                             FROM favorites f 
                             JOIN products p ON f.product_id = p.id 
                             WHERE f.user_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        while ($product = $result->fetch_assoc()) {
                            $statusClass = '';
                            $badgeText = '';
                            if ($product['stock'] <= 0) {
                                $statusClass = 'out-of-stock';
                                $badgeText = 'Out of Stock';
                            } elseif ($product['stock'] <= 15) {
                                $statusClass = 'low-stock';
                                $badgeText = 'Low Stock';
                            } else {
                                $statusClass = 'in-stock';
                                $badgeText = 'In Stock';
                            }
                            ?>
                            <div class="product-card <?php echo $statusClass; ?>" 
                                 data-product-id="<?php echo $product['id']; ?>"
                                 data-stock="<?php echo $product['stock']; ?>"
                                 data-unit="<?php echo $product['unit']; ?>">
                                <button class="remove-favorite" onclick="removeFavorite(<?php echo $product['id']; ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <div class="product-image-container">
                                    <img src="../<?php echo $product['image_path']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                                    <span class="stock-badge"><?php echo $badgeText; ?></span>
                                    <span class="category-badge"><?php echo strtoupper($product['category']); ?></span>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title"><?php echo $product['name']; ?></h3>
                                    <div class="price-container">
                                        <span class="price-symbol">₱</span>
                                        <span class="price-amount"><?php echo number_format($product['price'], 2); ?></span>
                                        <span class="price-unit">/ <?php echo $product['unit']; ?></span>
                                    </div>
                                    <p class="product-description"><?php echo $product['description']; ?></p>
                                    <div class="stock-info">
                                        <?php echo $product['stock']; ?> <?php echo $product['unit'] . ($product['stock'] > 1 ? 's' : ''); ?> available
                                    </div>
                                    <div class="product-actions">
                                        <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>)" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                            <i class="fa-solid fa-cart-shopping"></i>
                                            ADD TO CART
                                        </button>
                                        <button class="buy-now-btn" 
                                            onclick="showBuyModal({
                                                id: <?php echo $product['id']; ?>,
                                                name: '<?php echo addslashes($product['name']); ?>',
                                                price: <?php echo $product['price']; ?>,
                                                image: '../<?php echo $product['image_path']; ?>',
                                                stock: <?php echo $product['stock']; ?>,
                                                unit: '<?php echo $product['unit']; ?>'
                                            })" 
                                            <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                            <i class="fa-solid fa-bolt"></i>
                                            BUY NOW
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="empty-favorites">
                            <i class="fa-solid fa-heart"></i>
                            <h3>No favorites yet</h3>
                            <p>Start adding your favorite products to your collection!</p>
                            <a href="user.php" class="shop-now-btn">
                                <i class="fa-solid fa-store"></i>
                                Shop Now
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Logout Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="logout-modal">
            <h2>Logout Confirmation</h2>
            <p>Are you sure you want to logout from your account?</p>
            <div class="modal-buttons">
                <button class="modal-btn cancel-btn" onclick="hideLogoutModal()">Cancel</button>
                <button class="modal-btn confirm-btn" onclick="confirmLogout(this)">
                    <span class="btn-text">Logout</span>
                    <span class="loading-spinner" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Buy Now Modal -->
    <div class="buy-modal-overlay modal-overlay" id="buyModal">
        <div class="buy-modal">
            <div class="buy-modal-header">
                <h2 class="buy-modal-title">Buy Now</h2>
                <button class="buy-modal-close">&times;</button>
            </div>
            <div class="buy-modal-content">
                <div class="product-preview">
                    <img src="" alt="" class="preview-image" id="previewImage">
                    <div class="preview-details">
                        <h3 class="preview-title" id="previewTitle"></h3>
                        <p class="preview-price" id="previewPrice"></p>
                    </div>
                </div>
                <div class="quantity-selector">
                    <button class="quantity-btn" id="decreaseQuantity">-</button>
                    <input type="number" class="quantity-input" id="quantityInput" value="1" min="1">
                    <button class="quantity-btn" id="increaseQuantity">+</button>
                </div>
                <p class="stock-info" id="stockInfo"></p>

                <!-- Delivery Address Section -->
                <div class="delivery-section">
                    <h3>Delivery Address</h3>
                    <div id="selectedAddress" class="selected-address">
                        <p class="no-address-msg" style="display: none;">No default address selected</p>
                    </div>
                </div>
            </div>
            <div class="buy-modal-actions">
                <button class="modal-btn cancel-buy-btn">CANCEL</button>
                <button class="modal-btn confirm-buy-btn">CONFIRM PURCHASE</button>
            </div>
        </div>
    </div>

    <!-- Address Selection Modal -->
    <div class="selection-modal-overlay modal-overlay" id="addressSelectionModal">
        <div class="selection-modal">
            <div class="selection-modal-header">
                <h2>Select Delivery Address</h2>
                <button class="close-modal" onclick="hideAddressSelection()">&times;</button>
            </div>
            <div class="selection-modal-content" id="addressList">
                <!-- Address list will be populated here -->
            </div>
        </div>
    </div>

    <!-- Payment Selection Modal -->
    <div class="selection-modal-overlay modal-overlay" id="paymentSelectionModal">
        <div class="selection-modal">
            <div class="selection-modal-header">
                <h2>Select Payment Method</h2>
                <button class="close-modal" onclick="hidePaymentSelection()">&times;</button>
            </div>
            <div class="selection-modal-content" id="paymentList">
                <!-- Payment method list will be populated here -->
            </div>
        </div>
    </div>

    <script>
        // Add to Cart Function
        function addToCart(productId) {
            fetch('../add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    // Show success message
                    alert(data.message);
                    // Update cart count if needed
                    // You can add cart count update logic here
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding to cart');
            });
        }

        // Modal Control Functions
        function showModal(modalId) {
            const modalOverlay = document.getElementById(modalId);
            if (modalOverlay) {
                modalOverlay.classList.add('visible');
                // The inner modal animation is handled by CSS:
                // .modal-overlay.visible .logout-modal,
                // .modal-overlay.visible .buy-modal, etc.
            }
        }

        function hideModal(modalId) {
            const modalOverlay = document.getElementById(modalId);
            if (modalOverlay) {
                modalOverlay.classList.remove('visible');
            }
        }

        // Buy Now Modal Functions
        const buyModalOverlay = document.getElementById('buyModal');
        const buyModalClose = buyModalOverlay.querySelector('.buy-modal-close');
        const cancelBuyBtn = buyModalOverlay.querySelector('.cancel-buy-btn');
        const confirmBuyBtn = buyModalOverlay.querySelector('.confirm-buy-btn');
        const quantityInput = document.getElementById('quantityInput');
        const decreaseQuantityBtn = document.getElementById('decreaseQuantity');
        const increaseQuantityBtn = document.getElementById('increaseQuantity');
        let currentBuyProductId = null;
        let selectedAddressId = null;

        function showBuyModal(productData) {
            showModal('buyModal');
            document.getElementById('previewImage').src = productData.image;
            document.getElementById('previewTitle').textContent = productData.name;
            document.getElementById('previewPrice').textContent = `₱${parseFloat(productData.price).toFixed(2)}`;
            document.getElementById('stockInfo').textContent = `Available: ${productData.stock} ${productData.unit}${productData.stock > 1 ? 's' : ''}`;
            quantityInput.value = 1;
            quantityInput.max = productData.stock;
            currentBuyProductId = productData.id;
            loadDefaultAddress();
        }

        function loadDefaultAddress() {
            fetch('get_addresses.php')
            .then(response => response.json())
            .then(data => {
                const selectedAddressDiv = document.getElementById('selectedAddress');
                if (data.status === 'success' && data.addresses.length > 0) {
                    const defaultAddress = data.addresses.find(addr => addr.is_default) || data.addresses[0];
                    selectedAddressId = defaultAddress.id;
                    selectedAddressDiv.innerHTML = `
                        <div class="address-info">
                            <p class="address-type">${defaultAddress.address_type} Address</p>
                            <p class="recipient">${defaultAddress.recipient_name}</p>
                            <p class="address-line">${defaultAddress.street}</p>
                            <p class="address-line">${defaultAddress.city}, ${defaultAddress.region}</p>
                            <p class="address-line">Philippines ${defaultAddress.postal_code}</p>
                            <p class="phone">Phone: ${defaultAddress.phone_number}</p>
                        </div>
                    `;
                    selectedAddressDiv.querySelector('.no-address-msg').style.display = 'none';
                } else {
                    selectedAddressDiv.querySelector('.no-address-msg').style.display = 'block';
                }
            });
        }

        function hideBuyModal() {
            hideModal('buyModal');
        }

        decreaseQuantityBtn.addEventListener('click', () => {
            let value = parseInt(quantityInput.value);
            if (value > 1) quantityInput.value = value - 1;
        });

        increaseQuantityBtn.addEventListener('click', () => {
            let value = parseInt(quantityInput.value);
            let max = parseInt(quantityInput.max);
            if (value < max) quantityInput.value = value + 1;
        });

        quantityInput.addEventListener('change', () => {
            let value = parseInt(quantityInput.value);
            let max = parseInt(quantityInput.max);
            if (isNaN(value) || value < 1) value = 1;
            if (value > max) value = max;
            quantityInput.value = value;
        });

        buyModalClose.addEventListener('click', hideBuyModal);
        cancelBuyBtn.addEventListener('click', hideBuyModal);
        buyModalOverlay.addEventListener('click', (e) => {
            if (e.target === buyModalOverlay) hideBuyModal();
        });

        confirmBuyBtn.addEventListener('click', function() {
            if (!selectedAddressId) {
                alert('No default delivery address found. Please set up a default address in your profile.');
                return;
            }
            
            const quantity = quantityInput.value;
            window.location.href = `checkout.php?product_id=${currentBuyProductId}&quantity=${quantity}&address_id=${selectedAddressId}&buy_now=true`;
        });

        // Close modals on click outside (for selection modals)
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('selection-modal-overlay') && e.target.classList.contains('visible')) {
                e.target.classList.remove('visible');
            }
        });

        // Remove favorite functionality
        function removeFavorite(productId) {
            if (confirm('Are you sure you want to remove this item from your favorites?')) {
                fetch('../toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        const productCard = document.querySelector(`.product-card[data-product-id="${productId}"]`);
                        productCard.remove();
                        
                        const favoriteCountElement = document.querySelector('.user-stats .stat:nth-child(2) .stat-number');
                        if (favoriteCountElement) {
                            favoriteCountElement.textContent = data.favorite_count;
                        }

                        if (data.favorite_count === 0) {
                            const productsGrid = document.querySelector('.products-grid');
                            productsGrid.innerHTML = `
                                <div class="empty-favorites">
                                    <i class="fa-solid fa-heart"></i>
                                    <h3>No favorites yet</h3>
                                    <p>Start adding your favorite products to your collection!</p>
                                    <a href="user.php" class="shop-now-btn">
                                        <i class="fa-solid fa-store"></i>
                                        Shop Now
                                    </a>
                                </div>
                            `;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while removing the item from favorites');
                });
            }
        }

        // Logout Modal Functions
        const logoutModalOverlay = document.getElementById('logoutModal');

        function showLogoutModal() {
            showModal('logoutModal');
        }

        function hideLogoutModal() {
            hideModal('logoutModal');
        }

        function confirmLogout(button) {
            const btnText = button.querySelector('.btn-text');
            const spinner = button.querySelector('.loading-spinner');

            btnText.style.display = 'none';
            spinner.style.display = 'inline-block';
            button.disabled = true;

            fetch('', { // POST to the same page
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'logout' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Redirect to login page or home page after logout
                    window.location.href = '../index.php';
                } else {
                    alert('Logout failed: ' + (data.message || 'Unknown error'));
                    btnText.style.display = 'inline-block';
                    spinner.style.display = 'none';
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                alert('An error occurred during logout.');
                btnText.style.display = 'inline-block';
                spinner.style.display = 'none';
                button.disabled = false;
            });
        }

        // Address Selection Modal Functions
        function showAddressSelection() { // Assuming you might need this
            showModal('addressSelectionModal');
        }
        function hideAddressSelection() {
            hideModal('addressSelectionModal');
        }

        // Payment Selection Modal Functions
        function showPaymentSelection() { // Assuming you might need this
            showModal('paymentSelectionModal');
        }
        function hidePaymentSelection() {
            hideModal('paymentSelectionModal');
        }

        // Existing sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const toggleIcon = sidebarToggle.querySelector('i');

        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.classList.remove('fa-bars');
                toggleIcon.classList.add('fa-times');
            } else {
                toggleIcon.classList.remove('fa-times');
                toggleIcon.classList.add('fa-bars');
            }
            
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        sidebarToggle.addEventListener('click', toggleSidebar);

        // Check saved sidebar state
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                toggleSidebar();
            }
        });
    </script>

    <style>
        /* Buy Modal Styles */
        .delivery-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .delivery-section h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #333;
        }

        .selected-address {
            margin-bottom: 10px;
        }

        .address-info {
            padding: 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        .address-type {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .address-line {
            color: #666;
            margin: 2px 0;
        }

        .buy-modal-actions {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            border-top: 1px solid #eee;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cancel-buy-btn {
            background: #f1f1f1;
            color: #333;
        }

        .confirm-buy-btn {
            background: #ff6b6b;
            color: white;
        }

        .cancel-buy-btn:hover {
            background: #e4e4e4;
        }

        .confirm-buy-btn:hover {
            background: #ff5252;
        }

        /* MODAL STYLES - REVISED FOR VISIBILITY & ANIMATION */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0,0,0,0.7); /* Base background for overlay */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;

            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.3s ease-out, visibility 0s linear 0.3s;
        }

        .modal-overlay.visible {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transition: opacity 0.3s ease-out, visibility 0s linear 0s;
        }

        /* Inner modal content styling (logout, buy, selection) */
        .logout-modal,
        .buy-modal,
        .selection-modal .selection-modal-content { /* Target content part if selection-modal is just a wrapper */
            /* Common styling for inner modals if any (e.g., background, border-radius) */
            /* Specifics are already in favorite.css or below */
            opacity: 0;
            transform: scale(0.9);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            transition-delay: 0.05s; /* Slight delay for content to pop after overlay fades in */
        }
        
        /* If .selection-modal is the direct animated child of .selection-modal-overlay */
        .selection-modal-overlay.visible .selection-modal {
             opacity: 1;
             transform: scale(1);
        }
        /* If .selection-modal-content is the animated child */
         .selection-modal-overlay.visible .selection-modal .selection-modal-content {
             opacity: 1;
             transform: scale(1);
         }


        .modal-overlay.visible .logout-modal,
        .modal-overlay.visible .buy-modal {
            opacity: 1;
            transform: scale(1);
            margin: auto; /* Use shorthand for centering in both axes within the flex container */
        }
        
        /* Ensure the backdrop-filter fix from before is still applied when modals are visible */
        .modal-overlay.visible {
            backdrop-filter: none !important; /* Kept from before */
            -webkit-backdrop-filter: none !important; /* Kept from before */
        }

        /* Remove all payment-related styles that might cause conflict (original block) */
        /* .payment-section, */ /* Keeping this commented if it's a section not a modal */
        /* .selection-modal-overlay, */ /* Handled by generic .modal-overlay now */
        /* .selection-modal { */ /* Handled by specific or generic inner modal styles */
            /* display: none; */ /* Original line, no longer needed with visibility approach */
        /* } */
        
        /* Fix for blur issues - AGGRESSIVE OVERRIDES (original block) */
        /* ... (Your existing aggressive overrides for product cards, text, etc. remain untouched below) ... */
        /* ... these should not conflict with the new modal logic ... */


        /* Ensure modal overlays do not cause blur (Kept) */
        .buy-modal-overlay, 
        .modal-overlay,
        .selection-modal-overlay { /* This might be redundant if all use .modal-overlay class */
            /* backdrop-filter: none !important; */ /* Moved to .modal-overlay.visible */
            /* -webkit-backdrop-filter: none !important; */ /* Moved to .modal-overlay.visible */
            /* background: rgba(0,0,0,0.7) !important; */ /* Already on .modal-overlay */
        }

        /* === Product Card and Image Overrides - HIGHEST PRIORITY === */
        body .container .main-content .products-grid .product-card, /* High specificity for user.css structure */
        .product-card { /* General override */
            transition: none !important; 
            transform: none !important; /* Ensure card itself is not transformed */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15) !important; /* Base shadow, slightly adjusted for visibility */
            will-change: auto !important;
        }

        body .container .main-content .products-grid .product-card:hover,
        .product-card:hover {
            transform: none !important; /* NO transform on hover */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25) !important; /* Hover shadow, slightly adjusted */
        }

        /* Image and its container - ABSOLUTELY NO TRANSFORMS OR FILTERS */
        .product-card .product-image-container {
            overflow: visible !important; 
            background: transparent !important;
            transform: none !important;
            filter: none !important;
            transition: none !important;
        }

        .product-card .product-image-container .product-image, /* Targeting image inside its container */
        .product-image { /* General override for product-image class */
            filter: none !important;
            -webkit-filter: none !important;
            image-rendering: crisp-edges !important;
            image-rendering: pixelated !important;  
            transform: none !important; /* CRITICAL: Prevent any scaling */
            transition: none !important; /* CRITICAL: No transitions on images */
            opacity: 1 !important; /* Ensure full opacity */
            outline: 1px solid transparent !important; /* Helps some browsers with rendering */
            backface-visibility: hidden !important; /* May help with rendering artifacts */
            -webkit-backface-visibility: hidden !important;
        }
        
        /* Ensure NO pseudo-elements on image/container are causing blur */
        .product-card .product-image-container::before,
        .product-card .product-image-container::after,
        .product-card .product-image::before,
        .product-card .product-image::after {
            display: none !important;
        }
        
        /* Remove any lingering image overlay effects (Kept) */
        .product-card .image-overlay {
            display: none !important;
        }

        /* Text rendering (Kept) */
        body {
            text-rendering: optimizeLegibility !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
            font-smooth: never !important; 
            -webkit-font-smoothing: none !important;
        }
        
        /* Main layout elements - Remove transitions if they cause widespread blur during sidebar toggle */
        /* Comment these out if sidebar animation becomes too jerky and is not the source of persistent card blur */
        /*
        .sidebar, .main-content {
            transition: none !important;
        }
        */

        /* Styles for Shop Now button and Empty Favorites (Kept) */
        .shop-now-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: #45935b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s ease; 
            will-change: background-color;
        }
        
        .shop-now-btn:hover {
            background: #367347;
        }
        
        .empty-favorites {
            text-align: center;
            padding: 3rem 1rem;
            color: #fff;
        }
        
        .empty-favorites i {
            font-size: 3rem;
            color: #45935b;
            margin-bottom: 1rem;
        }
        
        .empty-favorites h3 {
            color: #fff;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .empty-favorites p {
            color: #888;
            margin-bottom: 1.5rem;
        }

        /* Ensure logout modal overlay uses flex for centering - REMOVING THIS BLOCK */
        /*
        #logoutModal {
            align-items: center;
            justify-content: center;
        }
        */
    </style>
</body>
</html> 