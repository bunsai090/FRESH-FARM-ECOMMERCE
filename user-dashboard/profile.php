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
    <title>Profile - FarmFresh</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/user.css">
    <link rel="stylesheet" href="../css/profile.css">
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
                <li><a href="orders.php"><i class="fa-solid fa-bag-shopping"></i> My Orders</a></li>
                <li><a href="#" class="active"><i class="fa-solid fa-user"></i> Profile</a></li>
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

            <div class="profile-container">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <div class="profile-header">
                    <h2>My Profile</h2>
                    <p>Manage your profile information</p>
                </div>

                <form action="profile.php" method="POST" enctype="multipart/form-data" class="profile-form">
                    <div class="form-grid">
                        <div class="profile-image-section">
                            <div class="profile-image-container">
                                <img src="<?php echo !empty($user['profile_image']) ? '../' . $user['profile_image'] : '../assets/images/default-avatar.jpg'; ?>" 
                                     alt="Profile Image" id="profileImagePreview">
                                <div class="image-overlay">
                                    <i class="fas fa-camera"></i>
                                    <span>Change Photo</span>
                                </div>
                            </div>
                            <input type="file" name="profile_image" id="profileImageInput" accept="image/*" hidden>
                        </div>

                        <div class="form-fields">
                            <div class="input-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>

                            <div class="input-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>

                            <div class="input-group">
                                <label for="email">Email Address</label>
                                <div class="email-input-container">
                                    <input type="email" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                           readonly
                                           class="readonly-input">
                                    <div class="email-readonly-badge">
                                        <i class="fas fa-lock"></i> Cannot be changed
                                    </div>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="phone">Phone Number</label>
                                <div class="phone-input-container">
                                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? '+63'); ?>"
                                           placeholder="+63 XXX XXX XXXX" 
                                           pattern="\+63\s?\d{3}\s?\d{3}\s?\d{4}"
                                           maxlength="17"
                                           required>
                                    <small class="phone-format-hint">Format: +63 XXX XXX XXXX</small>
                                    <div class="phone-error-message"></div>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="birth_date">Birth Date</label>
                                <input type="date" id="birth_date" name="birth_date" 
                                       value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>"
                                       max="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="save-changes-btn">
                                    <i class="fas fa-save"></i>
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
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

        // Phone number formatting and validation
        const phoneInput = document.getElementById('phone');
        const phoneError = document.querySelector('.phone-error-message');

        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Ensure the number always starts with +63
            if (!value.startsWith('+63')) {
                value = '+63' + value.replace('+63', '');
            }
            
            // Remove all non-digit characters except the plus sign
            let cleaned = value.replace(/[^\d+]/g, '');
            
            // Format the number: +63 XXX XXX XXXX
            if (cleaned.length > 1) {
                let formatted = cleaned.substring(0, 3); // +63
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
            
            // Update input value
            e.target.value = value;
            
            // Validate the phone number
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

        // Prevent deletion of +63 prefix
        phoneInput.addEventListener('keydown', function(e) {
            const value = e.target.value;
            const selectionStart = this.selectionStart;
            
            // Prevent backspace from deleting +63 prefix
            if (e.key === 'Backspace' && selectionStart <= 3) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html> 