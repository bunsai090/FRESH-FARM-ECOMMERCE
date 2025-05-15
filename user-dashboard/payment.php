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

// Get user's payment methods
$paymentMethods = [];
try {
    $paymentStmt = $conn->prepare("SELECT * FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC");
    $paymentStmt->bind_param("i", $user_id);
    $paymentStmt->execute();
    $paymentResult = $paymentStmt->get_result();
    while ($row = $paymentResult->fetch_assoc()) {
        $paymentMethods[] = $row;
    }
} catch (Exception $e) {
    error_log("Error getting payment methods: " . $e->getMessage());
}

// Handle payment method actions (add, remove, set default)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add new payment method
                $account_number = $_POST['account_number'];
                $payment_type = $_POST['payment_type'];
                
                $addStmt = $conn->prepare("INSERT INTO payment_methods (user_id, payment_type, account_number, is_default) VALUES (?, ?, ?, 0)");
                $addStmt->bind_param("iss", $user_id, $payment_type, $account_number);
                
                if ($addStmt->execute()) {
                    $message = "Payment method added successfully!";
                    $messageType = "success";
                } else {
                    $message = "Error adding payment method.";
                    $messageType = "error";
                }
                break;

            case 'remove':
                // Remove payment method
                if (isset($_POST['payment_id'])) {
                    $payment_id = $_POST['payment_id'];
                    $removeStmt = $conn->prepare("DELETE FROM payment_methods WHERE id = ? AND user_id = ?");
                    $removeStmt->bind_param("ii", $payment_id, $user_id);
                    
                    if ($removeStmt->execute()) {
                        $message = "Payment method removed successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error removing payment method.";
                        $messageType = "error";
                    }
                }
                break;

            case 'set_default':
                // Set default payment method
                if (isset($_POST['payment_id'])) {
                    $payment_id = $_POST['payment_id'];
                    
                    // First, remove default from all payment methods
                    $updateStmt = $conn->prepare("UPDATE payment_methods SET is_default = 0 WHERE user_id = ?");
                    $updateStmt->bind_param("i", $user_id);
                    $updateStmt->execute();
                    
                    // Set new default
                    $defaultStmt = $conn->prepare("UPDATE payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
                    $defaultStmt->bind_param("ii", $payment_id, $user_id);
                    
                    if ($defaultStmt->execute()) {
                        $message = "Default payment method updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating default payment method.";
                        $messageType = "error";
                    }
                }
                break;
        }
        
        // Refresh payment methods
        $paymentStmt->execute();
        $paymentResult = $paymentStmt->get_result();
        $paymentMethods = [];
        while ($row = $paymentResult->fetch_assoc()) {
            $paymentMethods[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Methods - FarmFresh</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/payment.css">
    <script src="js/shared.js" defer></script>
    <style>
        :root {
            --primary-color: #3b7a57;
            --secondary-color: #2c5a3f;
            --text-color: #333;
        }

        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #333;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* Container */
        .container {
            display: flex;
            min-height: 100vh;
            padding: 0;
            margin: 0;
            width: 100%;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            transition: all 0.3s ease;
            z-index: 999;
            padding-top: 15px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .sidebar.collapsed {
            left: -280px;
        }

        .toggle-container {
            position: fixed;
            left: 280px;
            top: 20px;
            z-index: 1002;
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .toggle-container {
            left: 0;
        }

        .toggle-btn {
            background: var(--primary-color, #3b7a57);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .toggle-btn:hover {
            background: var(--secondary-color, #2c5a3f);
            width: 45px;
        }

        .toggle-btn i {
            font-size: 20px;
            transition: transform 0.3s ease;
        }

        /* User Info Styles */
        .user-info {
            text-align: center;
            padding: 1.5rem 1rem;
            border-bottom: 1px solid #eee;
            margin-top: 20px;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 0.75rem;
            object-fit: cover;
            border: 3px solid var(--primary-color, #3b7a57);
            padding: 2px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .user-info h3 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
            color: var(--text-color, #333);
        }

        .user-info p {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .user-stats {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-weight: 600;
            color: var(--primary-color, #3b7a57);
            font-size: 1.1rem;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.25rem;
        }

        /* Menu Items Styles */
        .menu-items {
            list-style: none;
            padding: 0.75rem;
        }

        .menu-items li {
            margin-bottom: 0.25rem;
        }

        .menu-items a {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 0.75rem;
            color: var(--text-color, #333);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .menu-items a:hover {
            background-color: rgba(59, 122, 87, 0.1);
            color: var(--primary-color, #3b7a57);
        }

        .menu-items i {
            width: 16px;
            text-align: center;
            font-size: 0.95rem;
        }

        .menu-items a.active {
            background-color: rgba(59, 122, 87, 0.1);
            color: var(--primary-color, #3b7a57);
            font-weight: 500;
        }

        .menu-items .logout-link {
            color: #ff4444;
            margin-top: 0.5rem;
        }

        .menu-items .logout-link:hover {
            background-color: rgba(255, 68, 68, 0.1);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 280px;
            transition: all 0.3s ease;
            min-height: 100vh;
            background: #f5f5f5;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            margin: 0 auto;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .logout-modal {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            animation: modalFadeIn 0.3s ease;
            position: relative;
            z-index: 1001;
        }

        /* Payment Methods Styles */
        .payment-container {
            padding: 2rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .payment-header {
            margin-bottom: 2rem;
        }

        .payment-header h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .payment-header p {
            color: #666;
        }

        .payment-methods-list {
            margin-bottom: 2rem;
        }

        .payment-method-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }

        .payment-method-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .payment-method-icon {
            width: 40px;
            height: 40px;
            object-fit: contain;
            padding: 5px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .payment-method-details h4 {
            margin: 0;
            color: #333;
        }

        .payment-method-details p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .payment-method-actions {
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: #4CAF50;
            color: white;
        }

        .remove-btn {
            background: #f44336;
            color: white;
        }

        .default-btn {
            background: #2196F3;
            color: white;
        }

        .add-payment-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border: 2px dashed #ddd;
            border-radius: 8px;
            cursor: pointer;
            color: #666;
            transition: all 0.3s ease;
        }

        .add-payment-btn:hover {
            border-color: #4CAF50;
            color: #4CAF50;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            color: #4CAF50;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
        }

        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
        }

        .payment-type-select {
            position: relative;
        }

        .payment-type-select::after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #666;
        }

        .badge {
            background: #4CAF50;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 8px;
        }

        .payment-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-option {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .payment-option.selected {
            border-color: #4CAF50;
            background: #F1F8E9;
        }

        .payment-option img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }

        .payment-option p {
            margin: 0;
            color: #333;
            font-weight: 500;
        }

        .payment-form {
            display: none;
        }

        .payment-form.active {
            display: block;
        }

        .form-group input[type="tel"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .form-group input[type="tel"]:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .modal-header h3 {
            margin: 0;
            color: #1B5E20;
            font-size: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }

        .submit-btn {
            width: 100%;
            padding: 0.75rem;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background: #45a049;
        }

        #addPaymentModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        #addPaymentModal.show {
            display: flex;
        }

        .modal-content {
            transform: translateY(0);
            opacity: 1;
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .sidebar {
                left: -250px;
            }
            .sidebar.active,
            .sidebar.collapsed {
                left: 0;
            }
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group.half {
            flex: 1;
        }

        #credit-card-form input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        #credit-card-form input:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
    </style>
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
                <li><a href="user.php"><i class="fa-solid fa-bag-shopping"></i> My Orders</a></li>
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a href="payment.php"><i class="fa-solid fa-credit-card"></i> Payment Methods</a></li>
                <li><a href="address.php"><i class="fa-solid fa-location-dot"></i> Delivery Address</a></li>
                <li><a href="#"><i class="fa-solid fa-heart"></i> Favorites</a></li>
                <li><a href="#"><i class="fa-solid fa-gear"></i> Account Settings</a></li>
                <li><a href="#"><i class="fa-solid fa-phone"></i> Contact Us</a></li>
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

            <div class="payment-container">
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="payment-header">
                    <h2>Payment Methods</h2>
                    <p>Manage your payment methods</p>
                </div>

                <div class="payment-methods-list">
                    <?php foreach ($paymentMethods as $method): ?>
                        <div class="payment-method-card">
                            <div class="payment-method-info">
                                <img src="../assets/images/<?php echo strtolower($method['payment_type']); ?>.png" 
                                     alt="<?php echo $method['payment_type']; ?>" 
                                     class="payment-method-icon">
                                <div class="payment-method-details">
                                    <h4><?php echo $method['payment_type']; ?></h4>
                                    <p>Account: <?php echo substr($method['account_number'], 0, 4) . ' **** ' . substr($method['account_number'], -4); ?></p>
                                    <?php if ($method['is_default']): ?>
                                        <span class="badge">Default</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="payment-method-actions">
                                <?php if (!$method['is_default']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="set_default">
                                        <input type="hidden" name="payment_id" value="<?php echo $method['id']; ?>">
                                        <button type="submit" class="action-btn default-btn">Set as Default</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="payment_id" value="<?php echo $method['id']; ?>">
                                    <button type="submit" class="action-btn remove-btn">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="add-payment-btn" onclick="showAddPaymentModal()">
                        <i class="fas fa-plus"></i>
                        <span>Link New Payment Method</span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Payment Modal -->
    <div id="addPaymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Link New Payment Method</h3>
                <button class="modal-close" onclick="hideAddPaymentModal()">&times;</button>
            </div>
            <form method="POST" id="addPaymentForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="payment_type" id="selected_payment_type">
                
                <div class="payment-options">
                    <div class="payment-option" data-type="GCash" onclick="selectPaymentType('GCash')">
                        <img src="../assets/gcash.png" alt="GCash">
                        <p>GCash</p>
                    </div>
                    <div class="payment-option" data-type="Maya" onclick="selectPaymentType('Maya')">
                        <img src="../assets/maya.png" alt="Maya">
                        <p>Maya</p>
                    </div>
                    <div class="payment-option" data-type="Credit Card" onclick="selectPaymentType('Credit Card')">
                        <img src="../assets/credit.png" alt="Credit Card">
                        <p>Credit Card</p>
                    </div>
                </div>

                <div id="gcash-form" class="payment-form">
                    <div class="form-group">
                        <label for="gcash_number">GCash Mobile Number</label>
                        <input type="tel" 
                               id="gcash_number" 
                               name="account_number" 
                               pattern="09[0-9]{9}"
                               placeholder="09XX XXX XXXX"
                               maxlength="11"
                               required>
                    </div>
                </div>

                <div id="maya-form" class="payment-form">
                    <div class="form-group">
                        <label for="maya_number">Maya Account Number</label>
                        <input type="tel" 
                               id="maya_number" 
                               name="account_number" 
                               pattern="09[0-9]{9}"
                               placeholder="09XX XXX XXXX"
                               maxlength="11"
                               required>
                    </div>
                </div>

                <div id="credit-card-form" class="payment-form">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" 
                               id="card_number" 
                               name="account_number" 
                               pattern="[0-9]{16}"
                               placeholder="XXXX XXXX XXXX XXXX"
                               maxlength="16"
                               required>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Link Payment Method</button>
            </form>
        </div>
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

    <script>
        // Sidebar toggle functionality
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

        // Check saved sidebar state on page load
        document.addEventListener('DOMContentLoaded', () => {
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed) {
                toggleSidebar();
            }
            
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

        // Payment Modal Functions
        function showAddPaymentModal() {
            const modal = document.getElementById('addPaymentModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            selectPaymentType('GCash');
        }

        function hideAddPaymentModal() {
            const modal = document.getElementById('addPaymentModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const addPaymentModal = document.getElementById('addPaymentModal');
            if (event.target === addPaymentModal) {
                hideAddPaymentModal();
            }
        });

        // Prevent event bubbling for modal content
        document.querySelectorAll('.modal-content').forEach(modal => {
            modal.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideAddPaymentModal();
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

        // Payment type selection
        function selectPaymentType(type) {
            // Reset all options
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelectorAll('.payment-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Select clicked option
            const selectedOption = document.querySelector(`.payment-option[data-type="${type}"]`);
            selectedOption.classList.add('selected');
            
            // Show corresponding form
            const formId = type.toLowerCase().replace(' ', '-') + '-form';
            document.getElementById(formId).classList.add('active');
            document.getElementById('selected_payment_type').value = type;
            
            // Reset and update form requirements
            const gcashInput = document.getElementById('gcash_number');
            const mayaInput = document.getElementById('maya_number');
            const cardNumberInput = document.getElementById('card_number');
            
            // Disable all inputs first
            [gcashInput, mayaInput, cardNumberInput].forEach(input => {
                if (input) input.required = false;
            });
            
            // Enable required inputs based on selected type
            switch(type) {
                case 'GCash':
                    if (gcashInput) gcashInput.required = true;
                    break;
                case 'Maya':
                    if (mayaInput) mayaInput.required = true;
                    break;
                case 'Credit Card':
                    if (cardNumberInput) cardNumberInput.required = true;
                    break;
            }
        }

        // Format credit card number as user types
        document.getElementById('card_number')?.addEventListener('input', function(e) {
            let number = e.target.value.replace(/\D/g, '');
            if (number.length > 16) {
                number = number.substr(0, 16);
            }
            e.target.value = number;
        });

        // Initialize payment modal with GCash selected by default
        document.addEventListener('DOMContentLoaded', function() {
            showAddPaymentModal = function() {
                document.getElementById('addPaymentModal').style.display = 'flex';
                selectPaymentType('GCash');
            };
        });
    </script>
</body>
</html>

