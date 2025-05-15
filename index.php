<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmFresh - Fresh Produce Delivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/index.css">
</head>
<body>
    <!-- Header with Logo on left, Sign Up/Login on right -->
    <div class="header">
        <div class="logo">FarmFresh 🌱</div>
        <div class="auth-buttons">
            <a id="cart-icon" href="/cart.html" class="cart-button">
                <span id="cart-count" class="cart-count" style="display: none;">0</span>
            </a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="auth-button logout">Logout</a>
            <?php else: ?>
                <a href="#" class="auth-button login">Login</a>
                <a href="#" class="auth-button sign-up">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Auth Modal -->
    <div id="authModal" class="modal-overlay">
      <div class="auth-container">
        <!-- Left Panel -->
        <div class="auth-left">
          <h1>Fresh Farm</h1>
          <h2>Welcome to Fresh Farm</h2>
          <p>Join our community of fresh produce lovers and enjoy farm-to-table goodness delivered to your doorstep!</p>
          <ul>
            <li>✔ Fresh, organic products</li>
            <li>✔ Fast and reliable delivery</li>
            <li>✔ Earn loyalty points with every purchase</li>
            <li>✔ Support local farmers</li>
          </ul>
        </div>

        <!-- Right Panel -->
        <div class="auth-right">
          <span class="close-btn" id="closeModal">&times;</span>
          <div class="auth-tabs">
            <button id="showLogin" class="active-tab">Login</button>
            <button id="showSignup">Sign Up</button>
          </div>

          <!-- Login Form -->
          <form id="loginForm" class="auth-form" method="post">
            <h2>Welcome Back!</h2>
            <p>Sign in to access your account</p>
            <div id="login-error" style="display: none;"></div>
            <div id="login-success" style="display: none;"></div>
            
            <div class="input-group">
              <input type="email" name="email" id="login-email" placeholder="Email address" required>
            </div>
            
            <div class="input-group">
              <input type="password" name="password" id="login-password" placeholder="Password" required>
            </div>
            
            <label><input type="checkbox" name="remember"> Remember me</label>
            
            <div class="form-bottom">
              <a href="#">Forgot password?</a>
            </div>
            
            <button type="submit" class="submit-button">Login</button>
          </form>

          <!-- Signup Form -->
          <form id="signupForm" class="auth-form" style="display: none;" method="post">
            <h2 class="signup-title">Create Account</h2>
            <p class="signup-subtitle">Join our community of fresh produce lovers</p>
            <div id="signup-error" style="display: none;"></div>
            <div id="signup-success" style="display: none;"></div>

            <div class="name-fields">
              <div class="input-group">
                <input type="text" name="first_name" id="first_name" placeholder="First Name" required>
              </div>
              <div class="input-group">
                <input type="text" name="last_name" id="last_name" placeholder="Last Name" required>
              </div>
            </div>

            <div class="input-group">
              <input type="email" name="email" id="email" placeholder="Email address" required>
            </div>

            <div class="input-group">
              <input type="tel" name="phone_number" id="phone_number" placeholder="Phone Number" required>
            </div>

            <div class="input-group">
              <input type="password" name="password" id="password" placeholder="Password" required>
            </div>

            <div class="input-group">
              <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
            </div>

            <button type="submit" class="signup-button">Create Account</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Navigation Categories -->
    <div class="nav-categories">
        <a href="/index.html" class="category">Home</a>
        <a href="/fruits-category/fruits.html" class="category">Fruits 🍎</a>
        <a href="/vegatable-category/vegetable.html" class="category">Vegetables 🥦</a>
        <a href="/dairy-category/dairy.html" class="category">Dairy 🥛</a>
        <a href="/meat-category/meat.html" class="category">Meat 🥩</a>
        <a href="/organic-category/organic.html" class="category">Organic 🌿</a>
        <a href="/bakery-category/bakery.html" class="category">Bakery 🍞</a>
    </div>
    
    <!-- Hero Banner -->
    <div class="hero-banner">
        <h1 class="hero-title">Fresh Produce Delivered to Your Doorstep</h1>
    </div>
    
    <!-- Featured Products Section -->
    <div class="featured-section">
        <h2 class="featured-title">Featured Products 🍎 🥦 🥛 🍗 🌿 🍞</h2>
        <!-- Featured products would go here -->
    </div>

    <!-- Bottom Product Carousel Section -->
    <section class="bottom-carousel-section">
      <h2 class="section-title">Explore Our Products</h2>
      <div class="carousel-container">
          <div class="main-carousel" id="bottom-product-carousel">
              <!-- Products will be loaded dynamically from the database -->
              <div id="loading-indicator" class="loading">
                  <i class="fas fa-spinner fa-spin"></i> Loading products...
              </div>
          </div>
          <button class="carousel-control prev" id="prev-btn"><i class="fas fa-chevron-left"></i></button>
          <button class="carousel-control next" id="next-btn"><i class="fas fa-chevron-right"></i></button>
      </div>
    </section>

    <!-- Cart notification container -->
    <div id="cart-notification"></div>

    <script>
      // Modal functionality
      const modal = document.getElementById('authModal');
      const closeModal = document.getElementById('closeModal');
      const loginBtn = document.querySelector('.login');
      const signupBtn = document.querySelector('.sign-up');
      const loginForm = document.getElementById('loginForm');
      const signupForm = document.getElementById('signupForm');
      const showLogin = document.getElementById('showLogin');
      const showSignup = document.getElementById('showSignup');
      
      // Show modal
      loginBtn.addEventListener('click', (e) => {
        e.preventDefault();
        showAuthModal('login');
      });
      
      signupBtn.addEventListener('click', (e) => {
        e.preventDefault();
        showAuthModal('signup');
      });
      
      // Close modal
      closeModal.onclick = () => modal.style.display = 'none';
      window.onclick = (e) => {
        if (e.target == modal) modal.style.display = 'none';
      }
      
      // Tab switching
      showLogin.onclick = () => {
        loginForm.style.display = 'flex';
        signupForm.style.display = 'none';
        showLogin.classList.add('active-tab');
        showSignup.classList.remove('active-tab');
      };
      
      showSignup.onclick = () => {
        loginForm.style.display = 'none';
        signupForm.style.display = 'flex';
        showSignup.classList.add('active-tab');
        showLogin.classList.remove('active-tab');
      };
      
      // Bottom Product Carousel with Database Connection
      document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('bottom-product-carousel');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        let itemWidth;
        let currentPosition = 0;
        let autoScrollInterval;
        let isTransitioning = false;
        
        // Check if user is logged in (function to be implemented)
        function isUserLoggedIn() {
          <?php
          if (session_status() === PHP_SESSION_NONE) {
              session_start();
          }
          echo isset($_SESSION['user_id']) ? 'return true;' : 'return false;';
          ?>
        }
        
        // Fetch products from database
        fetchProducts();
        
        // Function to fetch products from the database
        function fetchProducts() {
          fetch('user-dashboard/get_random_products.php')
            .then(response => response.json())
            .then(data => {
              if (data.status) {
                // Remove loading indicator
                const loadingIndicator = document.getElementById('loading-indicator');
                if (loadingIndicator) {
                  loadingIndicator.remove();
                }
                
                // Populate carousel with products
                data.products.forEach(product => {
                  const item = createProductItem(product);
                  carousel.appendChild(item);
                });
                
                // Set up carousel after products are loaded
                setupCarousel();
              } else {
                // Show error message if no products
                carousel.innerHTML = `<div class="error-message">
                  <i class="fas fa-exclamation-circle"></i> ${data.message}
                </div>`;
              }
            })
            .catch(error => {
              console.error('Error fetching products:', error);
              carousel.innerHTML = `<div class="error-message">
                <i class="fas fa-exclamation-circle"></i> Failed to load products. Please try again later.
              </div>`;
            });
        }
        
        // Function to create a product carousel item
        function createProductItem(product) {
          const item = document.createElement('div');
          item.className = 'carousel-item';
          
          // Create product image with fallback
          const img = document.createElement('img');
          img.src = product.image;
          img.alt = product.name;
          img.onerror = function() {
            this.src = '/assets/images/placeholder.jpg';
          };
          
          // Create product info container
          const info = document.createElement('div');
          info.className = 'product-info';
          
          // Create product name
          const name = document.createElement('h3');
          name.textContent = product.name;
          
          // Create product price
          const price = document.createElement('p');
          price.className = 'price';
          price.textContent = product.priceDisplay;
          
          // Create add to cart button
          const button = document.createElement('button');
          button.className = 'add-to-cart';
          button.textContent = 'Add to Cart';
          button.dataset.id = product.id;
          button.addEventListener('click', function(e) {
            e.preventDefault();
            handleAddToCart(product.id);
          });
          
          // Assemble the product item
          info.appendChild(name);
          info.appendChild(price);
          info.appendChild(button);
          
          item.appendChild(img);
          item.appendChild(info);
          
          return item;
        }
        
        // Function to handle add to cart with auth check
        function handleAddToCart(productId) {
          if (isUserLoggedIn()) {
            // User is logged in, proceed with adding to cart
            addToCart(productId);
          } else {
            // User is not logged in, show auth modal
            showAuthModal('login');
            
            // Store the product ID to add after login
            sessionStorage.setItem('pendingCartItem', productId);
            
            // Add a message to login/signup forms
            const loginMessage = document.createElement('div');
            loginMessage.className = 'auth-cart-message';
            loginMessage.innerHTML = '<i class="fas fa-shopping-cart"></i> Please login to add items to your cart';
            
            // Remove existing messages if any
            const existingLoginMsg = loginForm.querySelector('.auth-cart-message');
            const existingSignupMsg = signupForm.querySelector('.auth-cart-message');
            
            if (existingLoginMsg) existingLoginMsg.remove();
            if (existingSignupMsg) existingSignupMsg.remove();
            
            // Add new messages at the beginning of the forms
            loginForm.insertBefore(loginMessage.cloneNode(true), loginForm.firstChild);
            signupForm.insertBefore(loginMessage.cloneNode(true), signupForm.firstChild);
          }
        }
        
        // Function to show auth modal
        function showAuthModal(tab = 'login') {
          modal.style.display = 'flex';
          
          if (tab === 'login') {
            loginForm.style.display = 'flex';
            signupForm.style.display = 'none';
            showLogin.classList.add('active-tab');
            showSignup.classList.remove('active-tab');
          } else {
            loginForm.style.display = 'none';
            signupForm.style.display = 'flex';
            showSignup.classList.add('active-tab');
            showLogin.classList.remove('active-tab');
          }
        }
        
        // Function to add product to cart
        function addToCart(productId) {
          // AJAX request to add item to cart
          fetch('add_to_cart.php', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId }),
            headers: { 'Content-Type': 'application/json' }
          })
          .then(response => response.json())
          .then(data => {
            if (data.status) {
              showCartNotification(data.message);
              updateCartIndicator(data.cart.totalItems);
            } else {
              showCartNotification(data.message || 'Failed to add product to cart', 'error');
            }
          })
          .catch(error => {
            console.error('Error adding to cart:', error);
            showCartNotification('An error occurred while adding to cart', 'error');
          });
        }
        
        // Function to show cart notification
        function showCartNotification(message, type = 'success') {
          // Check if notification container exists, create if not
          let notifContainer = document.getElementById('cart-notification');
          
          // Create notification
          const notification = document.createElement('div');
          notification.className = `notification ${type}`;
          notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
          `;
          
          // Add to container
          notifContainer.appendChild(notification);
          
          // Auto remove after 3 seconds
          setTimeout(() => {
            notification.classList.add('hide');
            setTimeout(() => {
              notification.remove();
            }, 300);
          }, 3000);
        }
        
        // Function to update cart indicator
        function updateCartIndicator(count) {
          // Find cart count indicator
          let cartCount = document.getElementById('cart-count');
          
          // Update count
          cartCount.textContent = count;
          cartCount.style.display = count > 0 ? 'flex' : 'none';
        }
        
        // Function to set up carousel after products are loaded
        function setupCarousel() {
          const items = document.querySelectorAll('.carousel-item');
          if (items.length === 0) return;
          
          // Get the actual width including gap
          itemWidth = items[0].offsetWidth + 15; // 15px is the gap in CSS
          
          // Clone items for infinite loop
          cloneItems();
          
          // Start auto-scroll
          startAutoScroll();
          
          // Add event listeners
          setupEventListeners();
        }
        
        // Clone items for infinite loop
        function cloneItems() {
          const items = document.querySelectorAll('.carousel-item');
          const originalItems = Array.from(items);
          
          // Clone all items and append to end
          originalItems.forEach(item => {
            const clone = item.cloneNode(true);
            
            // Re-add event listeners to cloned buttons
            const button = clone.querySelector('.add-to-cart');
            if (button) {
              const productId = button.dataset.id;
              button.addEventListener('click', function(e) {
                e.preventDefault();
                handleAddToCart(productId);
              });
            }
            
            carousel.appendChild(clone);
          });
          
          // Set initial position to show original items
          currentPosition = 0;
          carousel.style.transform = `translateX(${currentPosition}px)`;
        }
        
        // Move carousel
        function moveCarousel(direction) {
          if (isTransitioning) return;
          isTransitioning = true;
          
          const items = document.querySelectorAll('.carousel-item');
          const totalItems = items.length / 2; // Divide by 2 because we cloned all items
          
          if (direction === 'next') {
            currentPosition -= itemWidth;
          } else {
            currentPosition += itemWidth;
          }
          
          carousel.style.transition = 'transform 0.5s ease';
          carousel.style.transform = `translateX(${currentPosition}px)`;
          
          // Reset position for infinite loop
          setTimeout(() => {
            // If we've scrolled past all original items
            if (currentPosition <= -itemWidth * totalItems) {
              carousel.style.transition = 'none';
              currentPosition = 0;
              carousel.style.transform = `translateX(${currentPosition}px)`;
            }
            
            // If we've scrolled back before the first item
            else if (currentPosition > 0) {
              carousel.style.transition = 'none';
              currentPosition = -itemWidth * (totalItems - 1);
              carousel.style.transform = `translateX(${currentPosition}px)`;
            }
            
            setTimeout(() => {
              isTransitioning = false;
            }, 50);
          }, 500);
        }
        
        // Auto-scroll
        function startAutoScroll() {
          clearInterval(autoScrollInterval);
          autoScrollInterval = setInterval(() => {
            moveCarousel('next');
          }, 3000);
        }
        
        // Set up event listeners
        function setupEventListeners() {
          // Manual navigation
          prevBtn.addEventListener('click', () => {
            clearInterval(autoScrollInterval);
            moveCarousel('prev');
            startAutoScroll();
          });
          
          nextBtn.addEventListener('click', () => {
            clearInterval(autoScrollInterval);
            moveCarousel('next');
            startAutoScroll();
          });
          
          // Pause on hover
          carousel.addEventListener('mouseenter', () => {
            clearInterval(autoScrollInterval);
          });
          
          carousel.addEventListener('mouseleave', () => {
            startAutoScroll();
          });
        }
      });

      // Modified login form processing to handle adding products to cart after login
      document.addEventListener('DOMContentLoaded', function() {
        // Get the login form
        const loginForm = document.getElementById('loginForm');
        
        // Add submit event listener
        loginForm.addEventListener('submit', function(event) {
          // Prevent the default form submission
          event.preventDefault();
          
          // Get form data
          const formData = new FormData(loginForm);
          
          // Create error and success message elements
          const errorElement = document.getElementById('login-error');
          const successElement = document.getElementById('login-success');
          
          // Reset messages
          errorElement.style.display = 'none';
          successElement.style.display = 'none';
          
          // Send AJAX request
          fetch('login_process.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.status) {
              // Success
              successElement.textContent = data.message;
              successElement.style.display = 'block';
              
              // Dispatch userLoggedIn event to handle pending cart items
              const loginEvent = new Event('userLoggedIn');
              document.dispatchEvent(loginEvent);
              
              // Redirect after successful login
              setTimeout(function() {
                window.location.href = 'user-dashboard/user.php'; // Redirect to profile page
              }, 1500);
            } else {
              // Error
              errorElement.textContent = data.message;
              errorElement.style.display = 'block';
            }
          })
          .catch(error => {
            errorElement.textContent = 'An error occurred. Please try again later.';
            errorElement.style.display = 'block';
            console.error('Error:', error);
          });
        });
      });

      // Modified signup form processing to handle adding products to cart after signup
      document.addEventListener('DOMContentLoaded', function() {
        // Get the signup form
        const signupForm = document.getElementById('signupForm');
        
        // Add submit event listener
        signupForm.addEventListener('submit', function(event) {
          // Prevent the default form submission
          event.preventDefault();
          
          // Get form data
          const formData = new FormData(signupForm);
          
          // Create error and success message elements
          const errorElement = document.getElementById('signup-error');
          const successElement = document.getElementById('signup-success');
          
          // Reset messages
          errorElement.style.display = 'none';
          successElement.style.display = 'none';
          
          // Form validation
          const password = formData.get('password');
          const confirmPassword = formData.get('confirm_password');
          
          if (password !== confirmPassword) {
            errorElement.textContent = 'Passwords do not match!';
            errorElement.style.display = 'block';
            return;
          }
          
          // Send AJAX request
          fetch('signup_process.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.status) {
              // Success
              successElement.textContent = data.message;
              successElement.style.display = 'block';
              
              // Dispatch userLoggedIn event to handle pending cart items
              const loginEvent = new Event('userLoggedIn');
              document.dispatchEvent(loginEvent);
              
              // Reset form
              signupForm.reset();
              
              // Redirect after successful signup
              setTimeout(function() {
                window.location.href = ''; // Redirect to profile page
              }, 2000);
            } else {
              // Error
              errorElement.textContent = data.message;
              errorElement.style.display = 'block';
            }
          })
          .catch(error => {
            errorElement.textContent = 'An error occurred. Please try again later.';
            errorElement.style.display = 'block';
            console.error('Error:', error);
          });
        });
      });

      // Add event listener for successful login to handle pending cart items
      document.addEventListener('userLoggedIn', function() {
        // Check if there was a pending cart item
        const pendingItemId = sessionStorage.getItem('pendingCartItem');
        
        if (pendingItemId) {
          // Add the pending item to cart
          setTimeout(() => {
            addToCart(pendingItemId);
            
            // Clear the pending item
            sessionStorage.removeItem('pendingCartItem');
            
            // Close modal
            document.getElementById('authModal').style.display = 'none';
          }, 500);
        }
      });


      // Function to show auth modal
function showAuthModal(tab = 'login') {
  const modal = document.getElementById('authModal');
  const loginForm = document.getElementById('loginForm');
  const signupForm = document.getElementById('signupForm');
  const showLogin = document.getElementById('showLogin');
  const showSignup = document.getElementById('showSignup');
  
  modal.style.display = 'flex';
  
  if (tab === 'login') {
    loginForm.style.display = 'flex';
    signupForm.style.display = 'none';
    showLogin.classList.add('active-tab');
    showSignup.classList.remove('active-tab');
  } else {
    loginForm.style.display = 'none';
    signupForm.style.display = 'flex';
    showSignup.classList.add('active-tab');
    showLogin.classList.remove('active-tab');
  }
}

// Event listeners for login/signup buttons
document.querySelector('.login').addEventListener('click', function(e) {
  e.preventDefault();
  showAuthModal('login');
});

document.querySelector('.sign-up').addEventListener('click', function(e) {
  e.preventDefault();
  showAuthModal('signup');
});
    </script>
</body>
</html>