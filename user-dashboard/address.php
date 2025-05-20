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

// Fetch user addresses
$addresses = [];
try {
    $addressStmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
    $addressStmt->bind_param("i", $user_id);
    $addressStmt->execute();
    $addressResult = $addressStmt->get_result();
    
    while ($row = $addressResult->fetch_assoc()) {
        $addresses[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching addresses: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - FarmFresh</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/user.css">
    <link rel="stylesheet" href="../css/address.css">
    <style>
        .phone-input-container {
            position: relative;
        }
        
        .phone-format-hint {
            color: #666;
            font-size: 0.8em;
            margin-top: 4px;
            display: block;
        }
        
        .phone-error-message {
            color: #dc3545;
            font-size: 0.8em;
            margin-top: 4px;
            display: none;
        }
        
        input[type="tel"].invalid {
            border-color: #dc3545;
        }
        
        input[type="tel"].valid {
            border-color: #198754;
        }

        .email-input-container {
            position: relative;
            margin-bottom: 15px;
        }

        .readonly-input {
            background-color: #f8f9fa !important;
            border: 1px solid #ced4da !important;
            cursor: not-allowed;
            color: #495057 !important;
            opacity: 0.8;
        }

        .email-readonly-badge {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #e9ecef;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            color: #6c757d;
            pointer-events: none;
        }

        .email-readonly-badge i {
            margin-right: 4px;
        }

        /* Address styles */
        .address-container {
            margin: 20px;
        }

        .address-list {
            margin-top: 10px;
        }

        .address-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: #fff;
        }

        .address-type {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .address-details p {
            margin: 5px 0;
        }

        .address-actions {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .address-actions button {
            background: #333;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 1em;
            margin-left: 10px;
            transition: background 0.2s;
        }

        .address-actions button:hover {
            background: #444;
        }

        .set-default-btn {
            background: #3a7c4a;
        }

        .set-default-btn:hover {
            background: #2e5e38;
        }

        .edit-btn {
            background: #007bff;
        }

        .edit-btn:hover {
            background: #0056b3;
        }

        .remove-btn {
            background: #dc3545;
        }

        .remove-btn:hover {
            background: #bd2130;
        }

        .default-badge {
            background: #007bff;
            color: #fff;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .add-address-btn {
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 1em;
        }

        .add-address-btn i {
            margin-right: 5px;
        }

        /* Modal styles */
        .modal-overlay {
            background: rgba(30, 30, 30, 0.85);
            display: none;
            align-items: flex-start;
            justify-content: center;
            z-index: 1000;
            min-height: 100vh;
            overflow-y: auto;
        }

        .address-modal {
            background: #181818;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            width: 95%;
            max-width: 420px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
        }

        .address-modal h2 {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            color: #e0e0e0;
            font-size: 1rem;
            margin-bottom: 0.3rem;
            display: block;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.9rem 1rem;
            background: #232323;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            margin-bottom: 0.7rem;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

        .form-group input:focus,
        .form-group select:focus {
            background: #292929;
            outline: none;
            box-shadow: 0 0 0 2px #3a7c4a;
        }

        .form-group input::placeholder {
            color: #b0b0b0;
            opacity: 1;
        }

        .modal-buttons {
            margin-top: auto;
            position: sticky;
            bottom: 0;
            background: #181818;
            padding-bottom: 1rem;
            z-index: 2;
        }

        .cancel-btn {
            background: transparent;
            color: #fff;
            border: 1.5px solid #444;
            border-radius: 8px;
            padding: 0.7rem 0;
            font-size: 1.05rem;
            flex: 1;
            transition: background 0.2s, color 0.2s;
        }

        .cancel-btn:hover {
            background: #232323;
            color: #e0e0e0;
        }

        .save-btn {
            background: #3a7c4a;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 0;
            font-size: 1.05rem;
            font-weight: 600;
            flex: 1;
            transition: background 0.2s;
        }

        .save-btn:hover {
            background: #2e5e38;
        }

        @media (max-width: 500px) {
            .address-modal {
                padding: 1.2rem 0.5rem;
                max-width: 98vw;
                max-height: 98vh;
            }
        }

        #logoutModal {
            display: none;
            background: rgba(30, 30, 30, 0.85);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            min-height: 100vh;
            overflow-y: auto;
        }

        #logoutModal .logout-modal {
            background: #fff;
            color: #222;
            border-radius: 12px;
            padding: 2rem 1.5rem;
            max-width: 350px;
            margin: auto;
            text-align: center;
        }

        #logoutModal .logout-modal h2 {
            margin-bottom: 1.5rem;
            font-size: 1.6rem;
            font-weight: 700;
        }

        #logoutModal .modal-buttons {
            margin-top: auto;
            position: sticky;
            bottom: 0;
            background: #181818;
            padding-bottom: 1rem;
            z-index: 2;
        }

        #logoutModal .modal-btn {
            background: transparent;
            color: #fff;
            border: 1.5px solid #444;
            border-radius: 8px;
            padding: 0.7rem 0;
            font-size: 1.05rem;
            flex: 1;
            transition: background 0.2s, color 0.2s;
        }

        #logoutModal .cancel-btn:hover {
            background: #232323;
            color: #e0e0e0;
        }

        #logoutModal .confirm-btn {
            background: #3a7c4a;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 0;
            font-size: 1.05rem;
            font-weight: 600;
            flex: 1;
            transition: background 0.2s;
        }

        #logoutModal .confirm-btn:hover {
            background: #2e5e38;
        }

        /* Delete Confirmation Modal */
        .confirmation-overlay {
            background: rgba(30, 30, 30, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            min-height: 100vh;
            overflow-y: auto;
        }

        .confirmation-modal {
            background: #1E1E1E;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            width: 95%;
            max-width: 420px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            color: #fff;
            text-align: center;
        }

        .confirmation-modal h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .confirmation-modal p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .confirmation-buttons {
            display: flex;
            justify-content: space-between;
        }

        .confirm-cancel {
            background: transparent;
            color: #fff;
            border: 1.5px solid #444;
            border-radius: 8px;
            padding: 0.7rem 0;
            font-size: 1.05rem;
            flex: 1;
            transition: background 0.2s, color 0.2s;
            margin-right: 10px;
        }

        .confirm-cancel:hover {
            background: #232323;
            color: #e0e0e0;
        }

        .confirm-delete {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 0;
            font-size: 1.05rem;
            font-weight: 600;
            flex: 1;
            transition: background 0.2s;
            position: relative;
            overflow: hidden;
        }

        .confirm-delete:hover {
            background: #bd2130;
        }

        .confirm-delete .button-text {
            position: relative;
            z-index: 1;
        }

        .loading-spinner {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Success Message Modal */
        .success-overlay {
            background: rgba(30, 30, 30, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            min-height: 100vh;
            overflow-y: auto;
        }

        .success-modal {
            background: #1E1E1E;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            width: 95%;
            max-width: 420px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            color: #fff;
            text-align: center;
        }

        .success-icon {
            font-size: 3rem;
            color: #3a7c4a;
            margin-bottom: 1.5rem;
        }

        .success-modal h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .success-modal p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        .success-modal button {
            background: #3a7c4a;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 0;
            font-size: 1.05rem;
            font-weight: 600;
            flex: 1;
            transition: background 0.2s;
        }

        .success-modal button:hover {
            background: #2e5e38;
        }
    </style>
</head>
<body>

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
            <li><a href="orders.php"><i class="fa-solid fa-bag-shopping"></i> My Orders</a></li>
            <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
            <li><a href="payment.php"><i class="fa-solid fa-credit-card"></i> Payment Methods</a></li>
            <li><a href="address.php" class="active"><i class="fa-solid fa-location-dot"></i> Delivery Address</a></li>
            <li><a href="favorite.php"><i class="fa-solid fa-heart"></i> Favorites</a></li>
            <li><a href="settings.php" ><i class="fa-solid fa-gear"></i> Account Settings</a></li>
            <li><a href="contact.php"><i class="fa-solid fa-phone"></i> Contact Us</a></li>
            <li><a href="#" onclick="showLogoutModal(); return false;" class="logout-link">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a></li>
        </ul>
    </aside>

    <!-- Header with Back Button -->
    <header class="page-header">
        <a href="user.php" class="back-button">
            <i class="fa fa-arrow-left" aria-hidden="true"></i>
        </a>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="address-container">
            <h1>Your Delivery Addresses</h1>
            <p class="no-address-message" style="display:none;">You haven't saved any addresses yet.</p>

            <!-- Address List Container -->
            <div class="address-list" id="addressList">
                <?php foreach ($addresses as $address): ?>
                    <div class="address-card">
                        <div class="address-type">
                            <?php echo htmlspecialchars($address['address_type']); ?> Address
                            <?php if ($address['is_default']): ?>
                                <span class="default-badge">Default</span>
                            <?php endif; ?>
                        </div>
                        <div class="address-details">
                            <p><?php echo htmlspecialchars($address['recipient_name']); ?></p>
                            <p><?php echo htmlspecialchars($address['street']); ?></p>
                            <p><?php echo htmlspecialchars($address['city']) . ', ' . htmlspecialchars($address['region']); ?></p>
                            <p>Philippines <?php echo htmlspecialchars($address['postal_code']); ?></p>
                            <p>Phone: <?php echo htmlspecialchars($address['phone_number']); ?></p>
                        </div>
                        <div class="address-actions">
                            <?php if (!$address['is_default']): ?>
                                <button class="set-default-btn" onclick="setDefaultAddress(<?php echo $address['id']; ?>)">Set as Default</button>
                            <?php endif; ?>
                            <button class="edit-btn" onclick="editAddress(<?php echo $address['id']; ?>)">Edit</button>
                            <button class="remove-btn" onclick="deleteAddress(<?php echo $address['id']; ?>)">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Add New Address Button -->
            <button class="add-address-btn" onclick="showAddressModal()">
                <i class="fas fa-plus"></i> Add New Address
            </button>
        </div>

        <!-- Address Modal -->
        <div class="modal-overlay" id="addressModal">
            <div class="address-modal">
                <h2>Add New Address</h2>
                <form id="addressForm" onsubmit="saveAddress(event)">
                    <div class="form-group">
                        <label for="addressType">Address Type</label>
                        <select id="addressType" name="addressType" required>
                            <option value="">Select Type</option>
                            <option value="Home">Home</option>
                            <option value="Work">Work</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="recipientName">Recipient Name</label>
                        <input type="text" id="recipientName" name="recipientName" 
                               value="<?php echo $user['first_name'] . ' ' . $user['last_name']; ?>" 
                               readonly>
                    </div>

                    <div class="form-group">
                        <label for="phoneNumber">Phone Number</label>
                        <input type="tel" id="phoneNumber" name="phoneNumber" 
                               value="<?php echo isset($user['phone']) ? $user['phone'] : ''; ?>" 
                               pattern="\+63\s?[0-9]{3}\s?[0-9]{3}\s?[0-9]{4}" 
                               required>
                    </div>
                    <div class="form-group">
                        <label for="region">Region</label>
                        <select id="region" name="region" required onchange="loadCities()">
                            <option value="">Select Region</option>
                            <option value="NCR">National Capital Region (NCR)</option>
                            <option value="CAR">Cordillera Administrative Region (CAR)</option>
                            <option value="Region1">Region I (Ilocos Region)</option>
                            <option value="Region2">Region II (Cagayan Valley)</option>
                            <option value="Region3">Region III (Central Luzon)</option>
                            <option value="Region4A">Region IV-A (CALABARZON)</option>
                            <option value="Region4B">Region IV-B (MIMAROPA)</option>
                            <option value="Region5">Region V (Bicol Region)</option>
                            <option value="Region6">Region VI (Western Visayas)</option>
                            <option value="Region7">Region VII (Central Visayas)</option>
                            <option value="Region8">Region VIII (Eastern Visayas)</option>
                            <option value="Region9">Region IX (Zamboanga Peninsula)</option>
                            <option value="Region10">Region X (Northern Mindanao)</option>
                            <option value="Region11">Region XI (Davao Region)</option>
                            <option value="Region12">Region XII (SOCCSKSARGEN)</option>
                            <option value="Region13">Region XIII (Caraga)</option>
                            <option value="BARMM">Bangsamoro (BARMM)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="city">City/Municipality</label>
                        <select id="city" name="city" required onchange="loadBarangays()">
                            <option value="">Select City/Municipality</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="barangay">Barangay</label>
                        <select id="barangay" name="barangay" required>
                            <option value="">Select Barangay</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="street">Street Name/Purok</label>
                        <input type="text" id="street" name="street" required placeholder="Enter street name, purok, or house number">
                    </div>

                    <div class="form-group full-width">
                        <label for="postalCode">Postal Code</label>
                        <input type="text" id="postalCode" name="postalCode" required placeholder="Postal code will autofill" readonly style="background-color: #e9f7ef; color: #222; font-weight: 500;">
                    </div>

                    <div class="modal-buttons" style="grid-column: 1 / 3;">
                        <button type="button" class="cancel-btn" onclick="hideAddressModal()">Cancel</button>
                        <button type="submit" class="save-btn">Save Address</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="confirmationModal" class="confirmation-overlay">
            <div class="confirmation-modal">
                <div class="modal-content">
                    <h3>Delete Address</h3>
                    <p>Are you sure you want to delete this address? This action cannot be undone.</p>
                    <div class="confirmation-buttons">
                        <button class="confirm-cancel" onclick="hideModal('confirmationModal')">Cancel</button>
                        <button class="confirm-delete" onclick="confirmDelete()">
                            <span class="button-text">Delete</span>
                            <div class="loading-spinner"></div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <div id="successModal" class="modal-overlay">
            <div class="success-modal">
                <i class="fas fa-check-circle success-icon"></i>
                <p>Operation completed successfully!</p>
            </div>
        </div>
    </main>

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

        // Profile image upload handling (safe)
        const profileImageContainer = document.querySelector('.profile-image-container');
        const profileImageInput = document.getElementById('profileImageInput');
        const profileImagePreview = document.getElementById('profileImagePreview');
        if (profileImageContainer && profileImageInput && profileImagePreview) {
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
        }

        // Phone number formatting and validation (safe)
        const phoneInput = document.getElementById('phone');
        const phoneError = document.querySelector('.phone-error-message');
        if (phoneInput && phoneError) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value;
                if (!value.startsWith('+63')) {
                    value = '+63' + value.replace('+63', '');
                }
                let cleaned = value.replace(/[^\d+]/g, '');
                if (cleaned.length > 1) {
                    let formatted = cleaned.substring(0, 3);
                    if (cleaned.length > 3) {
                        formatted += ' ' + cleaned.substring(3, 6);
                    }
                    if (cleaned.length > 6) {
                        formatted += ' ' + cleaned.substring(6, 9);
                    }
                    if (cleaned.length > 9) {
                        formatted += ' ' + cleaned.substring(9, 13);
                    }
                    value = formatted;
                }
                e.target.value = value;
                const isValid = /^\+63\s?\d{3}\s?\d{3}\s?\d{4}$/.test(value);
                if (value.length > 3) {
                    if (!isValid) {
                        phoneError.style.display = 'block';
                        phoneError.textContent = 'Please enter a valid Philippine phone number';
                        phoneInput.classList.add('invalid');
                        phoneInput.classList.remove('valid');
                    } else {
                        phoneError.style.display = 'none';
                        phoneInput.classList.remove('invalid');
                        phoneInput.classList.add('valid');
                    }
                } else {
                    phoneError.style.display = 'none';
                    phoneInput.classList.remove('invalid');
                    phoneInput.classList.remove('valid');
                }
            });
            phoneInput.addEventListener('keydown', function(e) {
                const value = e.target.value;
                const selectionStart = this.selectionStart;
                if (e.key === 'Backspace' && selectionStart <= 3) {
                    e.preventDefault();
                }
            });
        }

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
                modal.style.display = 'flex';
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

        // Address handling functions
        function showAddressModal(address = null) {
            const modal = document.getElementById('addressModal');
            const form = document.getElementById('addressForm');
            const modalTitle = modal.querySelector('h2');
            if (!modal || !form) return;
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Reset form
            form.reset();
            
            // If editing, fill form and update title
            if (address) {
                modalTitle.textContent = 'Edit Address';
                form.addressType.value = address.address_type;
                form.recipientName.value = address.recipient_name;
                form.phoneNumber.value = address.phone_number;
                form.region.value = address.region;
                // Load cities and barangays async, then set values
                loadCitiesEdit(address.region, address.city, address.barangay);
                form.street.value = address.street;
                form.postalCode.value = address.postal_code;
                form.setAttribute('data-edit-id', address.id);
            } else {
                modalTitle.textContent = 'Add New Address';
                form.removeAttribute('data-edit-id');
            }
        }

        function hideAddressModal() {
            const modal = document.getElementById('addressModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Reset form if exists
                const form = modal.querySelector('form');
                if (form) {
                    form.reset();
                    form.removeAttribute('data-edit-id');
                }
            }
        }

        function saveAddress(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            
            const editId = form.getAttribute('data-edit-id');
            if (editId) {
                formData.append('edit_id', editId);
            }
            
            // Disable form submission while processing
            const submitButton = form.querySelector('button[type="submit"]');
            const cancelButton = form.querySelector('.cancel-btn');
            if (submitButton) submitButton.disabled = true;
            if (cancelButton) cancelButton.disabled = true;
            
            fetch('save_address.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    hideAddressModal();
                    loadAddresses();
                    form.reset();
                    showSuccessMessage(data.message || 'Address saved successfully');
                } else {
                    if (data.message === 'Maximum limit of 2 addresses reached') {
                        showErrorMessage('You can only add up to 2 addresses. Please delete an existing address first.');
                    } else {
                        showErrorMessage(data.message || 'Error saving address');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('An error occurred while saving the address.');
            })
            .finally(() => {
                // Re-enable form submission
                if (submitButton) submitButton.disabled = false;
                if (cancelButton) cancelButton.disabled = false;
            });
        }

        function loadAddresses() {
            fetch('get_addresses.php')
            .then(response => response.json())
            .then(data => {
                const addressList = document.getElementById('addressList');
                const noAddressMsg = document.querySelector('.no-address-message');
                const addBtn = document.querySelector('.add-address-btn');
                const container = document.querySelector('.address-container');
                
                if (!addressList || !noAddressMsg || !addBtn || !container) return;
                
                if (data.status === 'success') {
                    addressList.innerHTML = '';
                    
                    if (!data.addresses || data.addresses.length === 0) {
                        noAddressMsg.classList.add('visible');
                        container.classList.remove('limit-reached');
                        addBtn.classList.add('visible');
                    } else {
                        noAddressMsg.classList.remove('visible');
                        
                        // Handle address limit
                        if (data.addresses.length >= 2) {
                            container.classList.add('limit-reached');
                            addBtn.classList.remove('visible');
                        } else {
                            container.classList.remove('limit-reached');
                            addBtn.classList.add('visible');
                        }
                        
                        data.addresses.forEach(address => {
                            const addressCard = document.createElement('div');
                            addressCard.className = 'address-card';
                            addressCard.innerHTML = `
                                <div class="address-type">
                                    <strong>${address.address_type}</strong>
                                    ${address.is_default == 1 ? '<span class="default-badge">Default</span>' : ''}
                                </div>
                                <div class="address-details">
                                    <p>${address.recipient_name}</p>
                                    <p>${address.street}</p>
                                    <p>${address.city}, ${address.region}</p>
                                    <p>Philippines ${address.postal_code}</p>
                                    <p>Phone: ${address.phone_number}</p>
                                </div>
                                <div class="address-actions">
                                    ${address.is_default == 0 ? `<button class="set-default-btn" onclick="setDefaultAddress(${address.id})">Set as Default</button>` : ''}
                                    <button class="edit-btn" onclick="editAddress(${address.id})">Edit</button>
                                    <button class="remove-btn" onclick="deleteAddress(${address.id})">Remove</button>
                                </div>
                            `;
                            addressList.appendChild(addressCard);
                        });
                    }
                } else {
                    addressList.innerHTML = '';
                    noAddressMsg.classList.add('visible');
                    container.classList.remove('limit-reached');
                    addBtn.classList.add('visible');
                    showErrorMessage(data.message || 'Failed to load addresses');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('Failed to load addresses. Please try again.');
            });
        }

        function deleteAddress(addressId) {
            showDeleteConfirmation(addressId);
        }

        // Always call loadAddresses on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadAddresses();
            // Add modal close on outside click
            const addressModal = document.getElementById('addressModal');
            if (addressModal) {
                addressModal.addEventListener('click', (e) => {
                    if (e.target === e.currentTarget) {
                        hideAddressModal();
                    }
                });
            }
        });

        function loadCities() {
            const region = document.getElementById('region').value;
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');
            
            // Clear existing options
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            if (!region) return;

            // Show loading state
            citySelect.disabled = true;

            fetch(`get_cities.php?region=${region}`)
                .then(response => response.json()) // Add response parsing
                .then(data => {
                    citySelect.disabled = false;
                    if (data.status === 'success' && Array.isArray(data.cities)) {
                        data.cities.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.name;
                            citySelect.appendChild(option);
                        });
                    } else {
                        throw new Error('Invalid data format received');
                    }
                })
                .catch(error => {
                    console.error('Error loading cities:', error);
                    alert('Failed to load cities. Please try again.');
                    citySelect.disabled = false;
                });
        }

        function loadBarangays() {
            const city = document.getElementById('city').value;
            const barangaySelect = document.getElementById('barangay');
            const postalInput = document.getElementById('postalCode');
            
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            postalInput.value = '';

            if (!city) return;

            barangaySelect.disabled = true;

            fetch(`get_barangays.php?city=${city}`)
                .then(response => response.json())
                .then(data => {
                    barangaySelect.disabled = false;
                    if (data.status === 'success' && Array.isArray(data.barangays)) {
                        data.barangays.forEach(barangay => {
                            const option = document.createElement('option');
                            option.value = barangay.id;
                            option.textContent = barangay.name;
                            barangaySelect.appendChild(option);
                        });
                    }
                    // Always re-attach the event
                    barangaySelect.removeEventListener('change', autofillPostalCode);
                    barangaySelect.addEventListener('change', autofillPostalCode);
                })
                .catch(error => {
                    console.error('Error loading barangays:', error);
                    alert('Failed to load barangays. Please try again.');
                    barangaySelect.disabled = false;
                });
        }

        function autofillPostalCode() {
            const region = document.getElementById('region').value;
            const city = document.getElementById('city').value;
            const barangay = document.getElementById('barangay').value;
            const postalInput = document.getElementById('postalCode');

            if (region && city && barangay) {
                fetch(`get_postal.php?region=${encodeURIComponent(region)}&city=${encodeURIComponent(city)}&barangay=${encodeURIComponent(barangay)}`)
                    .then(response => response.json())
                    .then(data => {
                        postalInput.value = data.postal_code || '';
                    })
                    .catch(() => {
                        postalInput.value = '';
                    });
            } else {
                postalInput.value = '';
            }
        }

        function setDefaultAddress(addressId) {
            fetch('set_default_address.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ address_id: addressId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadAddresses();
                    showSuccessMessage('Default address updated successfully!');
                } else {
                    throw new Error(data.message || 'Error setting default address');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('An error occurred while setting the default address.');
            });
        }

        function editAddress(addressId) {
            // Fetch address details and open modal with pre-filled data
            fetch('get_addresses.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && Array.isArray(data.addresses)) {
                    const address = data.addresses.find(a => a.id == addressId);
                    if (address) {
                        showAddressModal(address);
                    }
                }
            });
        }

        function loadCitiesEdit(region, city, barangay) {
            const citySelect = document.getElementById('city');
            const barangaySelect = document.getElementById('barangay');
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            if (!region) return;
            fetch(`get_cities.php?region=${region}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && Array.isArray(data.cities)) {
                        data.cities.forEach(c => {
                            const option = document.createElement('option');
                            option.value = c.id;
                            option.textContent = c.name;
                            if (c.id == city) option.selected = true;
                            citySelect.appendChild(option);
                        });
                        // Now load barangays
                        fetch(`get_barangays.php?city=${city}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success' && Array.isArray(data.barangays)) {
                                    data.barangays.forEach(b => {
                                        const option = document.createElement('option');
                                        option.value = b.id;
                                        option.textContent = b.name;
                                        if (b.id == barangay) option.selected = true;
                                        barangaySelect.appendChild(option);
                                    });
                                }
                            });
                    }
                });
        }

        let addressToDelete = null;

        function showDeleteConfirmation(addressId) {
            addressToDelete = addressId;
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            addressToDelete = null;
        }

        function showSuccessMessage(message) {
            const modal = document.getElementById('successModal');
            const icon = modal.querySelector('.success-icon');
            const messageElement = modal.querySelector('p');
            
            // Set success styles
            icon.className = 'fas fa-check-circle success-icon';
            icon.style.color = '#45935b';
            messageElement.textContent = message;
            
            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Auto hide after 2 seconds
            setTimeout(() => {
                hideSuccessModal();
            }, 2000);
        }

        function hideSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function confirmDelete() {
            if (!addressToDelete) return;

            const button = document.querySelector('.confirm-delete');
            const buttonText = button.querySelector('.button-text');
            const loadingSpinner = button.querySelector('.loading-spinner');

            // Disable button and show loading state
            button.disabled = true;
            buttonText.style.display = 'none';
            loadingSpinner.style.display = 'block';

            fetch('delete_address.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: addressToDelete })
            })
            .then(response => response.json())
            .then(data => {
                hideModal('confirmationModal');
                if (data.status === 'success') {
                    loadAddresses();
                    showSuccessMessage('Address deleted successfully!');
                } else {
                    throw new Error(data.message || 'Error deleting address');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('An error occurred while deleting the address.');
            })
            .finally(() => {
                // Reset button state
                button.disabled = false;
                buttonText.style.display = 'inline';
                loadingSpinner.style.display = 'none';
                addressToDelete = null; // Reset the addressToDelete
            });
        }

        // Close modals on outside click
        window.addEventListener('click', function(e) {
            const deleteModal = document.getElementById('confirmationModal');
            const successModal = document.getElementById('successModal');
            
            if (e.target === deleteModal) {
                hideModal('confirmationModal');
            }
            if (e.target === successModal) {
                hideSuccessModal();
            }
        });

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideModal('confirmationModal');
                hideSuccessModal();
            }
        });

        // Add this new function for error modal
        function showErrorMessage(message) {
            const modal = document.getElementById('successModal');
            const icon = modal.querySelector('.success-icon');
            const messageElement = modal.querySelector('p');
            
            // Set error styles
            icon.className = 'fas fa-exclamation-circle success-icon';
            icon.style.color = '#dc3545';
            
            // Set error message
            messageElement.textContent = message;
            
            // Show modal
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                hideSuccessModal();
            }, 3000);
        }
    </script>
</body>
</html>