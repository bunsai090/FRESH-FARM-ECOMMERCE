<?php
// Start session and include database connection
session_start();
require_once '../connect.php';

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
    // If table doesn't exist or other error, keep count at 0
    error_log("Error getting order count: " . $e->getMessage());
}

// Check if favorites table exists, if not create it
try {
    $conn->query("SELECT 1 FROM favorites LIMIT 1");
} catch (Exception $e) {
    // Create favorites table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS favorites (
        favorite_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )";
    $conn->query($createTableSQL);
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
    // If table doesn't exist or other error, keep count at 0
    error_log("Error getting favorite count: " . $e->getMessage());
}

// Get user's favorited products
$favoritedProducts = [];
try {
    $favoriteProductsStmt = $conn->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    $favoriteProductsStmt->bind_param("i", $user_id);
    $favoriteProductsStmt->execute();
    $favoriteResult = $favoriteProductsStmt->get_result();
    while ($row = $favoriteResult->fetch_assoc()) {
        $favoritedProducts[] = $row['product_id'];
    }
} catch (Exception $e) {
    error_log("Error getting favorited products: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmFresh Dashboard</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/user.css">
    <script src="js/search.js" defer></script>
</head>
<body>
    <!-- Glass effect header -->
    <header class="header">
        <div class="logo">
            <i class="fa-solid fa-leaf"></i>
            FarmFresh
        </div>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search products...">
            <div class="search-suggestions" id="searchSuggestions"></div>
        </div>
        <nav class="nav-menu">
            <a href="#"><i class="fa-solid fa-house"></i> Home</a>
            <a href="#"><i class="fa-solid fa-apple-whole"></i> Fruits</a>
            <a href="#"><i class="fa-solid fa-carrot"></i> Vegetables</a>
            <a href="#"><i class="fa-solid fa-cow"></i> Dairy</a>
            <a href="#"><i class="fa-solid fa-drumstick-bite"></i> Meat</a>
            <a href="#"><i class="fa-solid fa-seedling"></i> Organic</a>
            <a href="#"><i class="fa-solid fa-bread-slice"></i> Bakery</a>
            <a href="#" class="cart"><i class="fa-solid fa-cart-shopping"></i></a>
        </nav>
    </header>

    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="toggle-container">
                <button class="toggle-btn">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
            </div>
            <div class="user-info">
                <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../assets/images/default-avatar.jpg'; ?>" 
                     alt="User Avatar" class="user-avatar">
                <h3><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h3>
                <p><?php echo $user['email']; ?></p>
                <div class="user-stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo $orderCount; ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $favoriteCount; ?></div>
                        <div class="stat-label">Favorites</div>
                    </div>
                </div>
            </div>
            <ul class="menu-items">
                <li><a href="#"><i class="fa-solid fa-bag-shopping"></i> My Orders</a></li>
                <li><a href="#"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a href="#"><i class="fa-solid fa-credit-card"></i> Payment Methods</a></li>
                <li><a href="#"><i class="fa-solid fa-location-dot"></i> Delivery Address</a></li>
                <li><a href="#"><i class="fa-solid fa-heart"></i> Favorites</a></li>
                <li><a href="#"><i class="fa-solid fa-gear"></i> Account Settings</a></li>
                <li><a href="#"><i class="fa-solid fa-phone"></i> Contact Us</a></li>
                <li><a href="#" onclick="showLogoutModal(); return false;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <h2 class="section-title">All Products</h2>
            <div class="products-grid">
                <?php
                // Number of products per page
                $products_per_page = 12;
                
                // Get current page
                $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $offset = ($current_page - 1) * $products_per_page;

                // Get total number of products
                $total_query = "SELECT COUNT(*) as total FROM products";
                $total_result = $conn->query($total_query);
                $total_products = $total_result->fetch_assoc()['total'];
                $total_pages = ceil($total_products / $products_per_page);

                // Get products for current page with random ordering
                $query = "SELECT * FROM products ORDER BY RAND() LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $products_per_page, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    while ($product = $result->fetch_assoc()) {
                        // Determine status class and badge text
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
                             data-product-id="<?php echo htmlspecialchars($product['id']); ?>" 
                             data-stock="<?php echo htmlspecialchars($product['stock']); ?>" 
                             data-unit="<?php echo htmlspecialchars($product['unit']); ?>">
                            <span class="stock-badge"><?php echo $badgeText; ?></span>
                            <img src="../<?php echo $product['image_path']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                            <div class="product-info">
                                <span class="product-category"><?php echo $product['category']; ?></span>
                                <h3 class="product-title"><?php echo $product['name']; ?></h3>
                                <p class="product-price">
                                    ₱<?php echo number_format($product['price'], 2); ?>
                                    <span class="product-unit">/ <?php echo $product['unit']; ?></span>
                                </p>
                                <p class="product-description"><?php echo $product['description']; ?></p>
                                <p class="stock-status">
                                    <span class="stock-indicator"></span>
                                    <?php 
                                    if ($product['stock'] > 0) {
                                        echo $product['stock'] . ' ' . $product['unit'] . 's available';
                                    } else {
                                        echo 'Currently unavailable';
                                    }
                                    ?>
                                </p>
                                <div class="product-actions">
                                    <button class="cart-btn" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                        <i class="fa-solid fa-cart-plus"></i>
                                        <?php echo ($product['stock'] > 0) ? 'Add to Cart' : 'Out of Stock'; ?>
                                    </button>
                                    <button class="favorite-btn <?php echo in_array($product['id'], $favoritedProducts) ? 'active' : ''; ?>" title="<?php echo in_array($product['id'], $favoritedProducts) ? 'Remove from Favorites' : 'Add to Favorites'; ?>">
                                        <i class="fa-solid fa-heart"></i>
                                    </button>
                                    <button class="buy-btn" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                        <i class="fa-solid fa-bolt"></i>
                                        Buy Now
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <!-- Add pagination controls -->
                    <div class="pagination">
                        <?php if ($total_pages > 1): ?>
                            <button 
                                onclick="window.location.href='?page=1'"
                                class="pagination-btn"
                                <?php echo $current_page == 1 ? 'disabled' : ''; ?>
                            >
                                <i class="fas fa-angle-double-left"></i>
                            </button>
                            
                            <button 
                                onclick="window.location.href='?page=<?php echo max(1, $current_page - 1); ?>'"
                                class="pagination-btn"
                                <?php echo $current_page == 1 ? 'disabled' : ''; ?>
                            >
                                <i class="fas fa-angle-left"></i>
                            </button>

                            <?php
                            // Show page numbers
                            for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
                                echo '<button 
                                    onclick="window.location.href=\'?page=' . $i . '\'"
                                    class="pagination-btn ' . ($current_page == $i ? 'active' : '') . '"
                                >' . $i . '</button>';
                            }
                            ?>

                            <button 
                                onclick="window.location.href='?page=<?php echo min($total_pages, $current_page + 1); ?>'"
                                class="pagination-btn"
                                <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>
                            >
                                <i class="fas fa-angle-right"></i>
                            </button>
                            
                            <button 
                                onclick="window.location.href='?page=<?php echo $total_pages; ?>'"
                                class="pagination-btn"
                                <?php echo $current_page == $total_pages ? 'disabled' : ''; ?>
                            >
                                <i class="fas fa-angle-double-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php
                } else {
                    echo '<p class="no-products">No products available at this time.</p>';
                }
                ?>
            </div>
        </main>
    </div>

    <!-- Add the logout modal HTML at the end of the body -->
    <div class="modal-overlay" id="logoutModal">
        <div class="logout-modal">
            <h2>Logout Confirmation</h2>
            <p>Are you sure you want to logout from your account?</p>
            <div class="modal-buttons">
                <button class="modal-btn cancel-btn" onclick="hideLogoutModal()">Cancel</button>
                <button class="modal-btn confirm-btn" onclick="confirmLogout()">Logout</button>
            </div>
        </div>
    </div>

    <!-- Buy Now Modal -->
    <div class="buy-modal-overlay" id="buyModal">
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
            </div>
            <div class="buy-modal-actions">
                <button class="modal-btn cancel-buy-btn">Cancel</button>
                <button class="modal-btn confirm-buy-btn">Confirm Purchase</button>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        const toggleBtn = document.querySelector('.toggle-btn');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Save sidebar state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Check saved sidebar state on page load
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
            
            // Handle initial state for mobile
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
            }
        });

        // Add scroll event listener for header effects
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 0) {
                header.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
            } else {
                header.style.boxShadow = 'none';
            }
        });

        // Initialize cart functionality
        document.querySelectorAll('.cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Add cart functionality here
                alert('Product added to cart!');
            });
        });

        function showLogoutModal() {
            document.getElementById('logoutModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
        }

        function hideLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
        }

        function confirmLogout() {
            window.location.href = '../logout.php';
        }

        // Close modal if clicking outside
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideLogoutModal();
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideLogoutModal();
            }
        });

        // Initialize Buy Now functionality
        document.querySelectorAll('.buy-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const productCard = this.closest('.product-card');
                const productData = {
                    id: productCard.dataset.productId,
                    name: productCard.querySelector('.product-title').textContent,
                    price: productCard.querySelector('.product-price').textContent,
                    image: productCard.querySelector('.product-image').src,
                    stock: parseInt(productCard.dataset.stock),
                    unit: productCard.dataset.unit
                };
                
                showBuyModal(productData);
            });
        });

        // Prevent Buy Now and Add to Cart buttons from triggering card click
        document.querySelectorAll('.cart-btn, .buy-btn, .favorite-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        const buyModal = document.getElementById('buyModal');
        const buyModalClose = buyModal.querySelector('.buy-modal-close');
        const cancelBuyBtn = buyModal.querySelector('.cancel-buy-btn');
        const confirmBuyBtn = buyModal.querySelector('.confirm-buy-btn');
        const quantityInput = document.getElementById('quantityInput');
        const decreaseQuantityBtn = document.getElementById('decreaseQuantity');
        const increaseQuantityBtn = document.getElementById('increaseQuantity');

        // Function to show modal
        function showBuyModal(productData) {
            document.getElementById('previewImage').src = productData.image;
            document.getElementById('previewTitle').textContent = productData.name;
            document.getElementById('previewPrice').textContent = productData.price;
            document.getElementById('stockInfo').textContent = `Available: ${productData.stock} ${productData.unit}s`;
            
            buyModal.style.display = 'block';
            setTimeout(() => {
                buyModal.querySelector('.buy-modal').classList.add('active');
            }, 10);

            // Reset quantity
            quantityInput.value = 1;
            quantityInput.max = productData.stock;
        }

        // Function to hide modal
        function hideBuyModal() {
            buyModal.querySelector('.buy-modal').classList.remove('active');
            setTimeout(() => {
                buyModal.style.display = 'none';
            }, 300);
        }

        // Quantity controls
        decreaseQuantityBtn.addEventListener('click', () => {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        increaseQuantityBtn.addEventListener('click', () => {
            const currentValue = parseInt(quantityInput.value);
            const maxValue = parseInt(quantityInput.max);
            if (currentValue < maxValue) {
                quantityInput.value = currentValue + 1;
            }
        });

        // Quantity input validation
        quantityInput.addEventListener('change', () => {
            let value = parseInt(quantityInput.value);
            const max = parseInt(quantityInput.max);
            
            if (isNaN(value) || value < 1) value = 1;
            if (value > max) value = max;
            
            quantityInput.value = value;
        });

        // Close modal events
        buyModalClose.addEventListener('click', hideBuyModal);
        cancelBuyBtn.addEventListener('click', hideBuyModal);
        buyModal.addEventListener('click', (e) => {
            if (e.target === buyModal) {
                hideBuyModal();
            }
        });

        // Handle confirm purchase
        confirmBuyBtn.addEventListener('click', function() {
            const productId = document.querySelector('.buy-modal').dataset.productId;
            const quantity = quantityInput.value;
            
            // Here you can redirect to checkout or process the purchase
            window.location.href = `checkout.php?product_id=${productId}&quantity=${quantity}&buy_now=true`;
        });

        // Initialize favorite buttons
        document.querySelectorAll('.favorite-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent event bubbling
                
                const productCard = this.closest('.product-card');
                const productId = productCard.dataset.productId;
                
                // Toggle favorite
                fetch('../toggle_favorite.php', {
                    method: 'POST',
                    body: JSON.stringify({ product_id: productId }),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        // Update button appearance
                        this.classList.toggle('active', data.is_favorite);
                        
                        // Update ONLY the favorite count in sidebar
                        const favoriteCountElement = document.querySelector('.user-stats .stat:nth-child(2) .stat-number');
                        if (favoriteCountElement) {
                            favoriteCountElement.textContent = data.favorite_count;
                        }
                        
                        // Show notification
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message || 'Error updating favorites', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error updating favorites', 'error');
                });
            });
        });

        // Add notification function
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            
            // Add to notification container (create if doesn't exist)
            let container = document.getElementById('notification-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notification-container';
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 1000;
                `;
                document.body.appendChild(container);
            }
            
            container.appendChild(notification);
            
            // Remove notification after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add this to your existing JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchSuggestions = document.getElementById('searchSuggestions');
            let debounceTimer;

            // Function to show loading state
            function showLoading() {
                searchSuggestions.innerHTML = `
                    <div class="suggestion-item loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        Searching...
                    </div>
                `;
                searchSuggestions.classList.add('active');
            }

            // Function to highlight matching text
            function highlightMatch(text, query) {
                if (!query) return text;
                const regex = new RegExp(`(${query})`, 'gi');
                return text.replace(regex, '<span class="highlight">$1</span>');
            }

            // Function to create suggestion item
            function createSuggestionItem(product, query) {
                return `
                    <div class="suggestion-item" data-product-id="${product.id}">
                        <img src="${product.image_path}" alt="${product.name}" onerror="this.src='../assets/images/default-product.jpg'">
                        <div class="item-details">
                            <div class="item-name">${highlightMatch(product.name, query)}</div>
                            <div class="item-category">${product.category}</div>
                        </div>
                        <div class="item-price">₱${product.price.toFixed(2)}</div>
                    </div>
                `;
            }

            // Function to fetch suggestions
            function fetchSuggestions(query) {
                showLoading();
                
                // Make an AJAX call to get suggestions
                fetch(`../get_suggestions.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (Array.isArray(data) && data.length > 0) {
                            const suggestionsHtml = data
                                .map(product => createSuggestionItem(product, query))
                                .join('');
                            searchSuggestions.innerHTML = suggestionsHtml;
                        } else {
                            searchSuggestions.innerHTML = `
                                <div class="suggestion-item no-results">
                                    <i class="fas fa-search"></i>
                                    No products found for "${query}"
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching suggestions:', error);
                        searchSuggestions.innerHTML = `
                            <div class="suggestion-item error">
                                <i class="fas fa-exclamation-circle"></i>
                                Error fetching suggestions
                            </div>
                        `;
                    });
            }

            // Input event listener with debounce
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();
                
                if (query.length >= 2) {
                    debounceTimer = setTimeout(() => {
                        fetchSuggestions(query);
                    }, 300); // Debounce delay of 300ms
                } else {
                    searchSuggestions.classList.remove('active');
                }
            });

            // Close suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                    searchSuggestions.classList.remove('active');
                }
            });

            // Handle suggestion click
            searchSuggestions.addEventListener('click', function(e) {
                const suggestionItem = e.target.closest('.suggestion-item');
                if (suggestionItem) {
                    const productId = suggestionItem.dataset.productId;
                    // Navigate to product page or handle the selection
                    window.location.href = `product.php?id=${productId}`;
                }
            });

            // Focus event to show suggestions again if input has value
            searchInput.addEventListener('focus', function() {
                const query = this.value.trim();
                if (query.length >= 2) {
                    fetchSuggestions(query);
                }
            });
        });
    </script>
</body>
</html>

  
