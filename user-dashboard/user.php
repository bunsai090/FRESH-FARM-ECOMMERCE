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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --primary-color: #3b7a57;
            --secondary-color: #2c5a3f;
            --text-color: #333;
            --bg-color: #f5f5f5;
        }

        body {
            background-color: var(--bg-color);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Glass effect header */
        .header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: var(--primary-color);
            padding: 0.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 50px;
        }

        .logo {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-bar {
            flex: 1;
            max-width: 500px;
            margin: 0 2rem;
        }

        .search-bar input {
            width: 100%;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.9);
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin: 0 auto;
            padding: 0.25rem 0.5rem;
            background: var(--primary-color);
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-menu i {
            font-size: 1rem;
        }

        /* Sidebar */
        .container {
            display: flex;
            position: relative;
        }

        .sidebar {
            width: 250px;
            background: white;
            height: 100vh;
            position: fixed;
            left: 0;
            transition: all 0.3s ease;
            z-index: 999;
            padding-top: 15px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar.collapsed {
            left: -250px;
        }

        .toggle-container {
            position: absolute;
            right: -40px;
            top: 350px;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .toggle-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .toggle-btn:hover {
            background: var(--secondary-color);
            width: 45px;
        }

        .toggle-btn i {
            transition: transform 0.3s ease;
        }

        .sidebar.collapsed .toggle-btn i {
            transform: rotate(180deg);
        }

        .user-info {
            text-align: center;
            padding: 2rem 1rem;
            border-bottom: 1px solid #eee;
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 1rem;
            object-fit: cover;
        }

        .user-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-weight: 600;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        .menu-items {
            list-style: none;
            padding: 1rem;
        }

        .menu-items li {
            margin-bottom: 0.5rem;
        }

        .menu-items a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.75rem 1rem;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .menu-items a:hover {
            background-color: rgba(59, 122, 87, 0.1);
            color: var(--primary-color);
        }

        .menu-items i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            color: var(--primary-color);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .product-price {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .product-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .cart-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .cart-btn:hover {
            background: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-container {
                right: -40px;
            }
        }

        /* Add these styles in the style section */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            backdrop-filter: blur(4px);
        }

        .logout-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 90%;
            max-width: 400px;
        }

        .logout-modal h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .logout-modal p {
            color: #666;
            margin-bottom: 2rem;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .modal-btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .confirm-btn {
            background: var(--primary-color);
            color: white;
        }

        .confirm-btn:hover {
            background: var(--secondary-color);
        }

        .cancel-btn {
            background: #f0f0f0;
            color: #333;
        }

        .cancel-btn:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <!-- Glass effect header -->
    <header class="header">
        <div class="logo">
            <i class="fa-solid fa-seedling"></i>
            FarmFresh
        </div>
        <div class="search-bar">
            <input type="text" placeholder="Search products...">
        </div>
        <nav class="nav-menu">
            <a href="#"><i class="fa-solid fa-house"></i> Home</a>
            <a href="#"><i class="fa-solid fa-apple-whole"></i> Fruits</a>
            <a href="#"><i class="fa-solid fa-carrot"></i> Vegetables</a>
            <a href="#"><i class="fa-solid fa-cow"></i> Dairy</a>
            <a href="#"><i class="fa-solid fa-drumstick-bite"></i> Meat</a>
            <a href="#"><i class="fa-solid fa-leaf"></i> Organic</a>
            <a href="#"><i class="fa-solid fa-bread-slice"></i> Bakery</a>
            <a href="#" class="cart"><i class="fa-solid fa-cart-shopping"></i></a>
            <a href="#" class="profile"><i class="fa-solid fa-user"></i></a>
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
            <h2 class="section-title">Featured Products</h2>
            <div class="products-grid">
                <?php
                // Get random products from database
                $query = "SELECT * FROM products WHERE status != 'Out of Stock' ORDER BY RAND() LIMIT 8";
                $result = $conn->query($query);

                while ($product = $result->fetch_assoc()) {
                    ?>
                    <div class="product-card">
                        <img src="../<?php echo $product['image_path']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                        <div class="product-info">
                            <h3 class="product-title"><?php echo $product['name']; ?></h3>
                            <p class="product-price">₱<?php echo number_format($product['price'], 2); ?> / <?php echo $product['unit']; ?></p>
                            <p class="product-description"><?php echo $product['description']; ?></p>
                            <button class="cart-btn">Add to Cart</button>
                        </div>
                    </div>
                    <?php
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
    </script>
</body>
</html>

  
