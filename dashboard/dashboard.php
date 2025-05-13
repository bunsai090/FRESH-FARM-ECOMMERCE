<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Fresh Farm Admin - Dashboard</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Dashboard Styles */
    .sidebar {
      background-color: #343a40;
      color: #fff;
      min-height: 100vh;
      padding: 20px 0;
      transition: all 0.3s;
      position: fixed;
      z-index: 100;
    }
    
    .sidebar.active {
      margin-left: -250px;
    }
    
    .logo-area {
      padding: 10px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .nav-link {
      color: rgba(255, 255, 255, 0.8);
      padding: 10px 20px;
    }
    
    .nav-link:hover, .nav-link.active {
      color: #fff;
      background-color: rgba(255, 255, 255, 0.1);
    }
    
    .nav-link i {
      margin-right: 10px;
    }
    
    .content-area {
      transition: all 0.3s;
      padding: 20px;
    }
    
    .content-expanded {
      margin-left: 0;
    }
    
    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid #eee;
    }
    
    .menu-toggle {
      cursor: pointer;
      font-size: 1.2rem;
      margin-right: 10px;
    }
    
    .stats-card {
      padding: 20px;
      border-radius: 5px;
      color: #fff;
      text-align: center;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
    }
    
    .stats-card i {
      font-size: 3rem;
      margin-bottom: 10px;
    }
    
    .stats-card h3 {
      font-size: 1.8rem;
      margin-bottom: 5px;
    }
    
    .primary { background-color: #4e73df; }
    .info { background-color: #36b9cc; }
    .warning { background-color: #f6c23e; }
    .danger { background-color: #e74a3b; }
    
    .chart-container {
      position: relative;
      height: 300px;
    }
    
    .notification-icon {
      position: relative;
      cursor: pointer;
      margin-right: 15px;
      font-size: 1.2rem;
    }
    
    .notification-badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background-color: #e74a3b;
      color: white;
      border-radius: 50%;
      padding: 2px 6px;
      font-size: 0.7rem;
    }
    
    .user-area img {
      cursor: pointer;
    }
    
    @media (max-width: 992px) {
      .sidebar {
        margin-left: -250px;
      }
      .sidebar.active {
        margin-left: 0;
      }
      .content-area {
        margin-left: 0;
      }
      .content-expanded {
        margin-left: 250px;
      }
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-lg-3 col-xl-2 sidebar" id="sidebar">
        <div class="logo-area">
          <h3>Fresh Farm</h3>
        </div>
        <ul class="nav flex-column mt-4">
          <li class="nav-item"><a class="nav-link active" href="dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="./product.php"><i class="fas fa-carrot"></i> Products</a></li>
          <li class="nav-item"><a class="nav-link" href="categories.html"><i class="fas fa-tags"></i> Categories</a></li>
          <li class="nav-item"><a class="nav-link" href="orders.html"><i class="fas fa-shopping-cart"></i> Orders</a></li>
          <li class="nav-item"><a class="nav-link" href="users.html"><i class="fas fa-users"></i> Users</a></li>
          <li class="nav-item"><a class="nav-link" href="transactions.html"><i class="fas fa-money-bill-wave"></i> Transactions</a></li>
          <li class="nav-item"><a class="nav-link" href="delivery.html"><i class="fas fa-truck"></i> Delivery</a></li>
          <li class="nav-item"><a class="nav-link" href="settings.html"><i class="fas fa-cog"></i> Settings</a></li>
        </ul>
      </div>

      <!-- Main Content -->
      <div class="col-lg-9 col-xl-10 ms-auto content-area" id="content">
        <div class="top-bar mb-4">
          <div><span class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></span> Dashboard</div>
          <div class="user-area d-flex align-items-center">
            <div class="dropdown">
              <div class="notification-icon" id="notificationDropdown" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notificationCount">0</span>
              </div>
              <div class="dropdown-menu dropdown-menu-end" style="width: 320px;">
                <div class="p-3 border-bottom"><strong>Notifications</strong></div>
                <div id="notificationContainer">
                  <!-- Notifications will be loaded here -->
                </div>
              </div>
            </div>
            <img src="../assets/farmfresh.png" alt="Admin Profile" class="rounded-circle" height="36" width="36"/>
          </div>
        </div>

        <!-- Dashboard Cards -->
        <div class="row">
          <div class="col-md-3">
            <div class="stats-card primary">
              <i class="fas fa-shopping-cart"></i>
              <h3 id="totalOrders">0</h3>
              <p>Total Orders</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stats-card info">
              <i class="fa-solid fa-peso-sign"></i>
              <h3 id="totalRevenue">0</h3>
              <p>Total Revenue</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stats-card warning">
              <i class="fas fa-users"></i>
              <h3 id="activeUsers">0</h3>
              <p>Active Users</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stats-card danger">
              <i class="fas fa-carrot"></i>
              <h3 id="totalProducts">0</h3>
              <p>Total Products</p>
            </div>
          </div>
        </div>

        <!-- Charts -->
        <div class="row mt-4">
          <div class="col-lg-8">
            <div class="card">
              <div class="card-header d-flex justify-content-between">
                <span>Sales Overview</span>
                <div>
                  <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSalesModal">
                    <i class="fas fa-plus"></i> Add Sale
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="salesChart"></canvas>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="card">
              <div class="card-header d-flex justify-content-between">
                <span>Product Categories</span>
                <div>
                  <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus"></i> Add Category
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="chart-container">
                  <canvas id="categoryChart"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header d-flex justify-content-between">
                <span>Recent Orders</span>
                <div>
                  <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addOrderModal">
                    <i class="fas fa-plus"></i> New Order
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Products</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody id="recentOrdersTable">
                      <tr>
                        <td colspan="6" class="text-center">Loading orders...</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Quick Add Product -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <span>Quick Add Product</span>
              </div>
              <div class="card-body">
                <form id="addProductForm" action="process_product.php" method="POST" enctype="multipart/form-data">
                  <div class="row">
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="productName" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="productName" name="productName" required>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="mb-3">
                        <label for="productPrice" class="form-label">Price</label>
                        <input type="number" class="form-control" id="productPrice" name="productPrice" step="0.01" required>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="mb-3">
                        <label for="productQuantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="productQuantity" name="productQuantity" required>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="mb-3">
                        <label for="productCategory" class="form-label">Category</label>
                        <select class="form-select" id="productCategory" name="productCategory" required>
                          <option value="">Select Category</option>
                          <option value="1">Vegetables</option>
                          <option value="2">Fruits</option>
                          <option value="3">Dairy</option>
                          <option value="4">Meat</option>
                          <option value="5">Bakery</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                      <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Add</button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Sales Modal -->
  <div class="modal fade" id="addSalesModal" tabindex="-1" aria-labelledby="addSalesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addSalesModalLabel">Add Sales Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="process_sales.php" method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label for="saleDate" class="form-label">Date</label>
              <input type="date" class="form-control" id="saleDate" name="saleDate" required>
            </div>
            <div class="mb-3">
              <label for="saleAmount" class="form-label">Amount</label>
              <input type="number" class="form-control" id="saleAmount" name="saleAmount" step="0.01" required>
            </div>
            <div class="mb-3">
              <label for="saleProduct" class="form-label">Product</label>
              <select class="form-select" id="saleProduct" name="saleProduct" required>
                <option value="">Select Product</option>
                <option value="1">Organic Tomatoes</option>
                <option value="2">Fresh Lettuce</option>
                <option value="3">Free Range Eggs</option>
                <option value="4">Grass-Fed Beef</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="saleQuantity" class="form-label">Quantity</label>
              <input type="number" class="form-control" id="saleQuantity" name="saleQuantity" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Add Category Modal -->
  <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="process_category.php" method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label for="categoryName" class="form-label">Category Name</label>
              <input type="text" class="form-control" id="categoryName" name="categoryName" required>
            </div>
            <div class="mb-3">
              <label for="categoryDescription" class="form-label">Description</label>
              <textarea class="form-control" id="categoryDescription" name="categoryDescription" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label for="categoryIcon" class="form-label">Icon Class (Font Awesome)</label>
              <input type="text" class="form-control" id="categoryIcon" name="categoryIcon" placeholder="fas fa-carrot">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Add Order Modal -->
  <div class="modal fade" id="addOrderModal" tabindex="-1" aria-labelledby="addOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addOrderModalLabel">Create New Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="process_order.php" method="POST">
          <div class="modal-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="orderCustomer" class="form-label">Customer</label>
                <select class="form-select" id="orderCustomer" name="orderCustomer" required>
                  <option value="">Select Customer</option>
                  <option value="1">John Doe</option>
                  <option value="2">Jane Smith</option>
                  <option value="3">Robert Johnson</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="orderDate" class="form-label">Order Date</label>
                <input type="datetime-local" class="form-control" id="orderDate" name="orderDate" required>
              </div>
            </div>
            
            <div class="order-items mb-3">
              <label class="form-label">Order Items</label>
              <div class="table-responsive">
                <table class="table table-bordered" id="orderItemsTable">
                  <thead>
                    <tr>
                      <th>Product</th>
                      <th>Price</th>
                      <th>Quantity</th>
                      <th>Total</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <select class="form-select" name="orderItems[0][product]" required>
                          <option value="">Select Product</option>
                          <option value="1">Organic Tomatoes</option>
                          <option value="2">Fresh Lettuce</option>
                          <option value="3">Free Range Eggs</option>
                        </select>
                      </td>
                      <td>
                        <input type="number" class="form-control item-price" name="orderItems[0][price]" step="0.01" required>
                      </td>
                      <td>
                        <input type="number" class="form-control item-qty" name="orderItems[0][quantity]" min="1" value="1" required>
                      </td>
                      <td>
                        <input type="number" class="form-control item-total" name="orderItems[0][total]" step="0.01" readonly>
                      </td>
                      <td>
                        <button type="button" class="btn btn-sm btn-danger remove-item">Remove</button>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="5">
                        <button type="button" class="btn btn-sm btn-success" id="addItemBtn">Add Item</button>
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="orderStatus" class="form-label">Status</label>
                  <select class="form-select" id="orderStatus" name="orderStatus" required>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="paymentMethod" class="form-label">Payment Method</label>
                  <select class="form-select" id="paymentMethod" name="paymentMethod" required>
                    <option value="cash">Cash</option>
                    <option value="credit">Credit Card</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="e-wallet">E-Wallet</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="shippingAddress" class="form-label">Shipping Address</label>
                  <textarea class="form-control" id="shippingAddress" name="shippingAddress" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                  <label for="orderNotes" class="form-label">Order Notes</label>
                  <textarea class="form-control" id="orderNotes" name="orderNotes" rows="2"></textarea>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6 offset-md-6">
                <div class="d-flex justify-content-between mb-2">
                  <span>Subtotal:</span>
                  <span id="orderSubtotal">₱0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>Shipping:</span>
                  <span id="orderShipping">₱50.00</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>Tax (12%):</span>
                  <span id="orderTax">₱0.00</span>
                </div>
                <div class="d-flex justify-content-between fw-bold">
                  <span>Total:</span>
                  <span id="orderTotal">₱0.00</span>
                </div>
                <input type="hidden" name="orderSubtotalValue" id="orderSubtotalValue">
                <input type="hidden" name="orderShippingValue" id="orderShippingValue" value="50">
                <input type="hidden" name="orderTaxValue" id="orderTaxValue">
                <input type="hidden" name="orderTotalValue" id="orderTotalValue">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Create Order</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <script>
    // Toggle sidebar
    document.getElementById('menuToggle').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('active');
      document.getElementById('content').classList.toggle('content-expanded');
    });

    // Initialize charts with dummy data
    window.addEventListener('DOMContentLoaded', () => {
      // Sample data for charts
      initializeSalesChart();
      initializeCategoryChart();
      
      // Load some sample recent orders
      loadSampleOrders();
      
      // Set dummy values for stats cards
      document.getElementById('totalOrders').textContent = '145';
      document.getElementById('totalRevenue').textContent = '₱24,580';
      document.getElementById('activeUsers').textContent = '37';
      document.getElementById('totalProducts').textContent = '68';
      document.getElementById('notificationCount').textContent = '3';
      
      // Sample notifications
      const notificationContainer = document.getElementById('notificationContainer');
      notificationContainer.innerHTML = `
        <div class="p-3 border-bottom">
          <strong>New order received</strong>
          <div class="small text-muted">5 minutes ago</div>
        </div>
        <div class="p-3 border-bottom">
          <strong>Low stock alert: Organic Tomatoes</strong>
          <div class="small text-muted">1 hour ago</div>
        </div>
        <div class="p-3 border-bottom">
          <strong>Payment received from John Doe</strong>
          <div class="small text-muted">3 hours ago</div>
        </div>
      `;
      
      // Setup order form calculations
      setupOrderFormCalculations();
    });

    function initializeSalesChart() {
      const ctx = document.getElementById('salesChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
          datasets: [{
            label: 'Sales',
            data: [1250, 1900, 1700, 2100, 2400, 2800, 2600],
            backgroundColor: 'rgba(76, 175, 80, 0.2)',
            borderColor: '#4caf50',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: { y: { beginAtZero: true } }
        }
      });
    }

    function initializeCategoryChart() {
      const ctx = document.getElementById('categoryChart').getContext('2d');
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Vegetables', 'Fruits', 'Dairy', 'Meat', 'Others'],
          datasets: [{
            data: [42, 25, 15, 10, 8],
            backgroundColor: ['#4caf50', '#8bc34a', '#2196f3', '#ff9800', '#9e9e9e']
          }]
        },
        options: { 
          responsive: true, 
          maintainAspectRatio: false 
        }
      });
    }

    function loadSampleOrders() {
      const recentOrdersTable = document.getElementById('recentOrdersTable');
      recentOrdersTable.innerHTML = `
        <tr>
          <td>#ORD-2023-001</td>
          <td>John Doe</td>
          <td>Organic Tomatoes, Fresh Lettuce</td>
          <td>₱320.00</td>
          <td><span class="badge bg-success">Delivered</span></td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="viewOrder('ORD-2023-001')">View</button>
            <button class="btn btn-sm btn-secondary" onclick="printOrder('ORD-2023-001')">Print</button>
          </td>
        </tr>
        <tr>
          <td>#ORD-2023-002</td>
          <td>Jane Smith</td>
          <td>Free Range Eggs, Grass-Fed Beef</td>
          <td>₱580.00</td>
          <td><span class="badge bg-warning text-dark">Processing</span></td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="viewOrder('ORD-2023-002')">View</button>
            <button class="btn btn-sm btn-secondary" onclick="printOrder('ORD-2023-002')">Print</button>
          </td>
        </tr>
        <tr>
          <td>#ORD-2023-003</td>
          <td>Robert Johnson</td>
          <td>Fresh Milk, Organic Apples</td>
          <td>₱250.00</td>
          <td><span class="badge bg-info">Shipped</span></td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="viewOrder('ORD-2023-003')">View</button>
            <button class="btn btn-sm btn-secondary" onclick="printOrder('ORD-2023-003')">Print</button>
          </td>
        </tr>
      `;
    }
    
    function viewOrder(orderId) {
      alert('Viewing order: ' + orderId);
      // Redirect to order details page
      // window.location.href = 'order_details.php?id=' + orderId;
    }
    
    function printOrder(orderId) {
      alert('Printing order: ' + orderId);
      // Open print window
      // window.open('print_order.php?id=' + orderId, '_blank');
    }

    // Order form calculations
    function setupOrderFormCalculations() {
      const orderModal = document.getElementById('addOrderModal');
      if (!orderModal) return;
      
      // Add item button functionality
      const addItemBtn = document.getElementById('addItemBtn');
      addItemBtn.addEventListener('click', function() {
        const tbody = document.querySelector('#orderItemsTable tbody');
        const rowCount = tbody.children.length;
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
          <td>
            <select class="form-select" name="orderItems[${rowCount}][product]" required>
              <option value="">Select Product</option>
              <option value="1">Organic Tomatoes</option>
              <option value="2">Fresh Lettuce