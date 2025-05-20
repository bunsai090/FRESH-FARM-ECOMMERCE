<?php
session_start();
require_once '../connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, email, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch order count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orderCount = $result->fetch_assoc()['count'];

// Fetch orders and their items
$orders = [];
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($order = $result->fetch_assoc()) {
    $order_id = $order['order_id']; // <-- fix here
    $item_stmt = $conn->prepare("SELECT oi.quantity, p.name, p.price, p.image_path FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $item_stmt->bind_param("i", $order_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    $items = [];
    while ($item = $item_result->fetch_assoc()) {
        $items[] = $item;
    }
    $order['items'] = $items;
    $orders[] = $order;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/user.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/orders.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="toggle-container">
                <button class="toggle-btn" id="sidebarToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            <div class="user-info">
                <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../assets/images/default-avatar.jpg'; ?>" 
                     alt="User Avatar" class="user-avatar">
                <h3><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h3>
                <p><?php echo $user['email']; ?></p>
                <div class="user-stats">
                    <div class="stat">
                        <div class="stat-number"><?php echo $orderCount ?? '0'; ?></div>
                        <div class="stat-label">Orders</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number"><?php echo $favoriteCount ?? '0'; ?></div>
                        <div class="stat-label">Favorites</div>
                    </div>
                </div>
            </div>
            <ul class="menu-items">
                <li><a href="orders.php" class="active"><i class="fa-solid fa-bag-shopping"></i> My Orders</a></li>
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a href="payment.php"><i class="fa-solid fa-credit-card"></i> Payment Methods</a></li>
                <li><a href="address.php"><i class="fa-solid fa-location-dot"></i> Delivery Address</a></li>
                <li><a href="favorite.php"><i class="fa-solid fa-heart"></i> Favorites</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear"></i> Account Settings</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-phone"></i> Contact Us</a></li>
                <li><a href="#" onclick="showLogoutModal(); return false;" class="logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
        <a href="user.php" class="back-button">
        <i class="fa fa-arrow-left" aria-hidden="true"></i>
    </a>

            <div class="orders-container">
                <div class="orders-header">
                    <h2>My Orders</h2>
                    <p>View and manage your recent orders</p>
                </div>

                <?php if (!empty($orders)): ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card">
                                <div class="order-card-header">
                                    <div class="order-meta">
                                        <span class="order-date-display"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                                        <span class="order-id-display">Order #<?php echo htmlspecialchars($order['order_code'] ?? $order['order_id']); ?></span>
                                    </div>
                                    <span class="order-status-badge status-<?php echo strtolower(htmlspecialchars($order['status'])); ?>">
                                        <?php echo strtoupper(htmlspecialchars($order['status'])); ?>
                                    </span>
                                </div>

                                <div class="order-items-grid">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="order-item-card">
                                            <img src="<?php echo '../' . htmlspecialchars($item['image_path'] ?? 'assets/images/default-product.png'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="order-item-image">
                                            <div class="order-item-info">
                                                <p class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></p>
                                                <p class="order-item-price">₱<?php echo number_format($item['price'], 2); ?>/unit</p> <!-- Assuming price is per unit -->
                                                <p class="order-item-qty">Qty: <?php echo (int)$item['quantity']; ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="order-card-footer">
                                    <div class="order-total-amount">
                                        <strong>Total: ₱<?php echo number_format($order['total_amount'], 2); ?></strong>
                                    </div>
                                    <div class="order-actions-new">
                                        <?php 
                                        $status_lower = strtolower($order['status']);
                                        
                                        // Reorder Button (if applicable)
                                        if ($status_lower === 'delivered' || $status_lower === 'completed'): 
                                        ?>
                                            <a href="#" class="btn-reorder"><i class="fa-solid fa-rotate-right"></i> Reorder</a>
                                        <?php endif; ?>

                                        <?php 
                                        // Cancel Order Button (if applicable)
                                        if ($status_lower === 'pending'): 
                                        ?>
                                            <a href="cancel-order.php?id=<?php echo urlencode($order['order_id']); ?>" class="btn-cancel-order-new" onclick="return confirm('Are you sure you want to cancel this order?');">
                                                <i class="fa fa-times"></i> Cancel Order
                                            </a>
                                        <?php endif; ?>

                                        <!-- View Details Button (always shown) -->
                                        <a href="order-details.php?id=<?php echo urlencode($order['order_id']); ?>" class="btn-view-details-new">
                                            <i class="fa fa-eye"></i> View Details
                                        </a>

                                        <?php 
                                        // Order Received Button (if applicable - e.g., status is 'shipped')
                                        // Confirm or change this status condition as needed
                                        if ($status_lower === 'shipped'): 
                                        ?>
                                            <a href="mark-as-received.php?id=<?php echo urlencode($order['order_id']); ?>" class="btn-order-received" onclick="return confirm('Confirm that you have received this order?');">
                                                <i class="fa-solid fa-check-circle"></i> Order Received
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-orders-message">
                        <i class="fa fa-box-open"></i>
                        <p>You have no orders yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
         // Sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const toggleIcon = sidebarToggle.querySelector('i');

        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Adjust profile container
            const profileContainer = document.querySelector('.profile-container');
            if (profileContainer) {
                if (sidebar.classList.contains('collapsed')) {
                    profileContainer.style.marginLeft = '0';
                } else {
                    profileContainer.style.marginLeft = '280px'; // or var(--sidebar-width) if using CSS variable
                }
            }
            
            // Toggle the icon between bars and times
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.classList.remove('fa-bars');
                toggleIcon.classList.add('fa-times');
            } else {
                toggleIcon.classList.remove('fa-times');
                toggleIcon.classList.add('fa-bars');
            }
            
            // Save sidebar state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        sidebarToggle.addEventListener('click', toggleSidebar);

        // Check saved sidebar state on page load
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                toggleSidebar();
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

        // Profile image upload handling
        const profileImageContainer = document.querySelector('.profile-image-container');
        const profileImageInput = document.getElementById('profileImageInput');
        const profileImagePreview = document.getElementById('profileImagePreview');

        profileImageContainer.addEventListener('click', () => {
            profileImageInput.click();
        });

        profileImageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    profileImagePreview.src = e.target.result;
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Add alert auto-hide
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }, 3000);
        });

        // Logout functionality
        function showLogoutModal() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }

        function hideLogoutModal() {
            const modal = document.getElementById('logoutModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        function confirmLogout(button) {
            if (!button) return;

            // Disable button and show loading state
            button.disabled = true;
            button.querySelector('.btn-text').style.display = 'none';
            button.querySelector('.loading-spinner').style.display = 'inline';

            // Get the current URL path
            const currentPath = window.location.pathname;
            // Get the directory path by removing the file name
            const directoryPath = currentPath.substring(0, currentPath.lastIndexOf('/'));
            
            fetch(currentPath, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'logout'
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Redirect to the index page (up one directory)
                    window.location.href = directoryPath + '/../index.php';
                } else {
                    throw new Error(data.message || 'Error during logout');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during logout. Please try again.');
                // Reset button state
                button.disabled = false;
                button.querySelector('.btn-text').style.display = 'inline';
                button.querySelector('.loading-spinner').style.display = 'none';
            });
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
    </script>
</body>
</html>