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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - FarmFresh</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/user.css">
    <link rel="stylesheet" href="../css/settings.css">
    <style>
        /* Page-specific overrides to fix layout issues */
        .main-content {
            margin-left: 280px !important;
            padding: 1rem 2rem !important;
            flex: 1 1 auto !important;
            max-width: calc(100% - 280px) !important;
            min-height: 100vh !important;
            box-sizing: border-box !important;
            overflow-x: visible !important;
            overflow-y: visible !important;
        }
        
        .main-content.expanded {
            margin-left: 0 !important;
            max-width: 100% !important;
        }
        
        .settings-container {
            width: 100% !important;
            max-width: 700px !important;
            margin: 20px auto !important;
            box-sizing: border-box !important;
        }
        
        .password-input-container {
            width: 100% !important;
            position: relative !important;
            display: block !important;
        }
        
        .password-input-container input {
            width: 100% !important;
            box-sizing: border-box !important;
        }
        
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
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
                <li><a href="payment.php"><i class="fa-solid fa-credit-card"></i> Payment Methods</a></li>
                <li><a href="address.php"><i class="fa-solid fa-location-dot"></i> Delivery Address</a></li>
                <li><a href="favorite.php"><i class="fa-solid fa-heart"></i> Favorites</a></li>
                <li><a href="#" class="active"><i class="fa-solid fa-gear"></i> Account Settings</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-phone"></i> Contact Us</a></li>
                <li><a href="#" onclick="showLogoutModal(); return false;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </aside>
        <main class="main-content" style="margin-top: -10px !important; padding-top: 0 !important;">
            <a href="user.php" class="back-button" style="background-color:rgb(0, 0, 0) !important; color: #3b7a57 !important; border: none !important; box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important; top: 10px !important; margin-top: 10px !important;">
                <i class="fa fa-arrow-left" aria-hidden="true"></i>
            </a>

            <div class="settings-container" style="background-color: #1e1e1e !important; border-radius: 16px !important; box-shadow: 0 8px 25px rgba(23, 10, 10, 0.25) !important; padding: 0 !important; width: 100% !important; max-width: 700px !important; margin: 15px auto !important; overflow: hidden !important;">
                <div class="tabs" style="display: flex !important; border-bottom: 1px solid #2d2d2d !important; background-color: #1a1a1a !important;">
                    <button class="tab-link active" onclick="openTab(event, 'security')" style="padding: 20px 25px !important; font-size: 16px !important; font-weight: 500 !important; color: #3b7a57 !important; background: none !important; border: none !important; cursor: pointer !important; position: relative !important; transition: color 0.3s !important; letter-spacing: 0.5px !important;">Security<span style="position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px; background-color: #3b7a57;"></span></button>
                    <button class="tab-link" onclick="openTab(event, 'notifications')" style="padding: 20px 25px !important; font-size: 16px !important; font-weight: 500 !important; color: #888 !important; background: none !important; border: none !important; cursor: pointer !important; position: relative !important; transition: color 0.3s !important; letter-spacing: 0.5px !important;">Notifications</button>
                </div>

                <div id="security" class="tab-content" style="display: block; padding: 35px !important; background-color: #1e1e1e !important; color: #f0f0f0 !important;">
                    <h3 style="font-size: 24px !important; color: #ffffff !important; margin-bottom: 30px !important; font-weight: 500 !important;">Change Password</h3>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>" 
                             style="padding: 12px 20px !important; border-radius: 8px !important; margin-bottom: 25px !important; 
                                   background-color: <?php echo $messageType === 'success' ? 'rgba(40, 167, 69, 0.1)' : 'rgba(220, 53, 69, 0.1)'; ?> !important;
                                   border: 2px solid <?php echo $messageType === 'success' ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.2)'; ?> !important;
                                   color: <?php echo $messageType === 'success' ? '#28a745' : '#dc3545'; ?> !important;
                                   display: flex !important; align-items: center !important; gap: 12px !important;">
                            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST" id="passwordChangeForm" style="width: 100% !important;">
                        <input type="hidden" name="form_type" value="password_change">
                        <div class="input-group" style="margin-bottom: 25px !important; width: 100% !important;">
                            <label for="current_password" style="display: block !important; margin-bottom: 10px !important; color: #999 !important; font-weight: 500 !important; font-size: 15px !important; letter-spacing: 0.2px !important;">Current Password</label>
                            <div class="password-input-container" style="position: relative !important; width: 100% !important;">
                                <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required style="width: 100% !important; padding: 14px 16px !important; font-size: 15px !important; border: none !important; border-radius: 8px !important; background: #2a2a2a !important; color: #f0f0f0 !important; box-sizing: border-box !important;">
                                <span class="password-toggle" style="position: absolute !important; right: 12px !important; top: 50% !important; transform: translateY(-50%) !important; color: #777 !important; cursor: pointer !important;"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                        <div class="input-group" style="margin-bottom: 25px !important; width: 100% !important;">
                            <label for="new_password" style="display: block !important; margin-bottom: 10px !important; color: #999 !important; font-weight: 500 !important; font-size: 15px !important; letter-spacing: 0.2px !important;">New Password</label>
                            <div class="password-input-container" style="position: relative !important; width: 100% !important;">
                                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required style="width: 100% !important; padding: 14px 16px !important; font-size: 15px !important; border: none !important; border-radius: 8px !important; background: #2a2a2a !important; color: #f0f0f0 !important; box-sizing: border-box !important;">
                                <span class="password-toggle" style="position: absolute !important; right: 12px !important; top: 50% !important; transform: translateY(-50%) !important; color: #777 !important; cursor: pointer !important;"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                        <div class="input-group" style="margin-bottom: 30px !important; width: 100% !important;">
                            <label for="confirm_new_password" style="display: block !important; margin-bottom: 10px !important; color: #999 !important; font-weight: 500 !important; font-size: 15px !important; letter-spacing: 0.2px !important;">Confirm New Password</label>
                            <div class="password-input-container" style="position: relative !important; width: 100% !important;">
                                <input type="password" id="confirm_new_password" name="confirm_new_password" placeholder="Confirm new password" required style="width: 100% !important; padding: 14px 16px !important; font-size: 15px !important; border: none !important; border-radius: 8px !important; background: #2a2a2a !important; color: #f0f0f0 !important; box-sizing: border-box !important;">
                                <span class="password-toggle" style="position: absolute !important; right: 12px !important; top: 50% !important; transform: translateY(-50%) !important; color: #777 !important; cursor: pointer !important;"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="padding: 14px 28px !important; font-size: 15px !important; font-weight: 500 !important; border-radius: 8px !important; cursor: pointer !important; transition: all 0.2s !important; border: none !important; background-color: #3b7a57 !important; color: #fff !important; letter-spacing: 0.5px !important;">Update Password</button>
                    </form>

                    <hr style="border: none !important; border-top: 1px solid #2d2d2d !important; margin: 40px 0 !important;">

                    <div class="danger-zone" style="margin-top: 20px !important;">
                        <h3 style="color: #e74c3c !important; font-size: 20px !important; margin-bottom: 20px !important; font-weight: 500 !important; letter-spacing: 0.2px !important;">Danger Zone</h3>
                        <button class="btn btn-danger" style="padding: 12px 24px !important; font-size: 15px !important; font-weight: 500 !important; border-radius: 8px !important; cursor: pointer !important; transition: all 0.2s !important; border: none !important; background-color: rgba(231, 76, 60, 0.2) !important; color: #e74c3c !important; letter-spacing: 0.5px !important;">Delete Account</button>
                    </div>
                </div>

                <div id="notifications" class="tab-content" style="display: none; padding: 35px !important; background-color: #1e1e1e !important; color: #f0f0f0 !important;">
                    <h3 style="font-size: 24px !important; color: #ffffff !important; margin-bottom: 30px !important; font-weight: 500 !important;">Notifications</h3>
                    <p style="color: #888 !important; font-size: 15px !important; line-height: 1.6 !important;">Notification settings will go here.</p>
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

        // Tab functionality
        function openTab(evt, tabName) {
            // Hide all tab contents
            var tabcontent = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }

            // Remove active class and indicator from all tab links
            var tablinks = document.getElementsByClassName("tab-link");
            for (var i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
                tablinks[i].style.color = "#888";
                
                // Remove any existing indicator spans
                var spans = tablinks[i].getElementsByTagName("span");
                while (spans.length > 0) {
                    spans[0].parentNode.removeChild(spans[0]);
                }
            }

            // Show the current tab and add active class to the button
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
            evt.currentTarget.style.color = "#3b7a57";
            
            // Add indicator to active tab
            var indicator = document.createElement("span");
            indicator.style.position = "absolute";
            indicator.style.bottom = "-1px";
            indicator.style.left = "0";
            indicator.style.width = "100%";
            indicator.style.height = "2px";
            indicator.style.backgroundColor = "#3b7a57";
            evt.currentTarget.appendChild(indicator);
        }

        // Password visibility toggle
        const passwordToggles = document.querySelectorAll('.password-toggle');
        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling;
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
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
            }         });     </script>/</body>
</html></html>