<?php
include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
   exit;
}

// Function to decrease product stock
function decreaseProductStock($conn, $product_name, $quantity) {
    $product_name = mysqli_real_escape_string($conn, $product_name);
    $quantity = intval($quantity);
    
    // Get current stock
    $product_query = mysqli_query($conn, "SELECT id, stock FROM `products` WHERE name = '$product_name'");
    if(mysqli_num_rows($product_query) > 0) {
        $product = mysqli_fetch_assoc($product_query);
        $current_stock = $product['stock'];
        $new_stock = max(0, $current_stock - $quantity);
        
        // Update stock
        mysqli_query($conn, "UPDATE `products` SET stock = '$new_stock' WHERE id = '{$product['id']}'");
        return true;
    }
    return false;
}

// Function to increase product stock
function increaseProductStock($conn, $product_name, $quantity) {
    $product_name = mysqli_real_escape_string($conn, $product_name);
    $quantity = intval($quantity);
    
    // Get current stock
    $product_query = mysqli_query($conn, "SELECT id, stock FROM `products` WHERE name = '$product_name'");
    if(mysqli_num_rows($product_query) > 0) {
        $product = mysqli_fetch_assoc($product_query);
        $current_stock = $product['stock'];
        $new_stock = $current_stock + $quantity;
        
        // Update stock
        mysqli_query($conn, "UPDATE `products` SET stock = '$new_stock' WHERE id = '{$product['id']}'");
        return true;
    }
    return false;
}

// Approve Order
if(isset($_POST['approve_order'])){
    $order_id = intval($_POST['order_id']);
    $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE id='$order_id'");
    
    if(mysqli_num_rows($order_query) > 0) {
        $order = mysqli_fetch_assoc($order_query);

        // Update status to completed
        mysqli_query($conn, "UPDATE `orders` SET payment_status='completed' WHERE id='$order_id'");

        // Decrease stock
        $total_products = $order['total_products'];
        preg_match_all('/([^(]+)\s*\((\d+)\)/x', $total_products, $matches, PREG_SET_ORDER);
        foreach($matches as $match) {
            $product_name = trim($match[1]);
            $quantity = (int)$match[2];
            decreaseProductStock($conn, $product_name, $quantity);
        }

        $_SESSION['success_message'] = "Order #$order_id approved and stock updated!";
    } else {
        $_SESSION['error_message'] = "Order not found!";
    }
    header('location:admin_orders.php');
    exit;
}

// Disapprove Order
if(isset($_POST['disapprove_order'])){
    $order_id = intval($_POST['order_id']);
    
    // Get order details to check if it was completed (to restore stock)
    $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE id='$order_id'");
    if(mysqli_num_rows($order_query) > 0) {
        $order = mysqli_fetch_assoc($order_query);
        
        // If order was completed, restore stock
        if($order['payment_status'] == 'completed') {
            $total_products = $order['total_products'];
            preg_match_all('/([^(]+)\s*\((\d+)\)/x', $total_products, $matches, PREG_SET_ORDER);
            foreach($matches as $match) {
                $product_name = trim($match[1]);
                $quantity = (int)$match[2];
                increaseProductStock($conn, $product_name, $quantity);
            }
        }
        
        mysqli_query($conn, "UPDATE `orders` SET payment_status='cancelled' WHERE id='$order_id'");
        $_SESSION['success_message'] = "Order #$order_id has been cancelled and stock restored!";
    } else {
        $_SESSION['error_message'] = "Order not found!";
    }
    header('location:admin_orders.php');
    exit;
}

// Update Order Status
if(isset($_POST['update_order'])){
   $order_update_id = intval($_POST['order_id']);
   $update_payment = mysqli_real_escape_string($conn, $_POST['update_payment']);
   $current_status = mysqli_real_escape_string($conn, $_POST['current_status']);
   
   // Get order details before updating
   $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE id = '$order_update_id'");
   if(mysqli_num_rows($order_query) > 0) {
       $order = mysqli_fetch_assoc($order_query);
       
       // Handle stock changes based on status transition
       if($current_status == 'completed' && $update_payment != 'completed') {
           // Restore stock if moving from completed to another status
           $total_products = $order['total_products'];
           preg_match_all('/([^(]+)\s*\((\d+)\)/x', $total_products, $matches, PREG_SET_ORDER);
           foreach($matches as $match) {
               $product_name = trim($match[1]);
               $quantity = (int)$match[2];
               increaseProductStock($conn, $product_name, $quantity);
           }
       } elseif($current_status != 'completed' && $update_payment == 'completed') {
           // Decrease stock if moving to completed
           $total_products = $order['total_products'];
           preg_match_all('/([^(]+)\s*\((\d+)\)/x', $total_products, $matches, PREG_SET_ORDER);
           foreach($matches as $match) {
               $product_name = trim($match[1]);
               $quantity = (int)$match[2];
               decreaseProductStock($conn, $product_name, $quantity);
           }
       }
       
       // Update payment status
       mysqli_query($conn, "UPDATE `orders` SET payment_status = '$update_payment' WHERE id = '$order_update_id'");
       $_SESSION['success_message'] = 'Order status updated successfully!';
   } else {
       $_SESSION['error_message'] = 'Order not found!';
   }
   header('location:admin_orders.php');
   exit;
}

// Delete Order
if(isset($_GET['delete'])){
   $delete_id = intval($_GET['delete']);
   
   // Get order details to restore stock if it was completed
   $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE id = '$delete_id'");
   if(mysqli_num_rows($order_query) > 0) {
       $order = mysqli_fetch_assoc($order_query);
       
       // Restore stock if order was completed
       if($order['payment_status'] == 'completed') {
           $total_products = $order['total_products'];
           preg_match_all('/([^(]+)\s*\((\d+)\)/x', $total_products, $matches, PREG_SET_ORDER);
           foreach($matches as $match) {
               $product_name = trim($match[1]);
               $quantity = (int)$match[2];
               increaseProductStock($conn, $product_name, $quantity);
           }
       }
       
       mysqli_query($conn, "DELETE FROM `orders` WHERE id = '$delete_id'");
       $_SESSION['success_message'] = "Order #$delete_id deleted successfully!";
   } else {
       $_SESSION['error_message'] = "Order not found!";
   }
   header('location:admin_orders.php');
   exit;
}

// Get order statistics with date filtering
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('-7 days'));
$month_start = date('Y-m-d', strtotime('-30 days'));

$total_orders = mysqli_query($conn, "SELECT COUNT(*) as total FROM `orders`")->fetch_assoc()['total'];
$pending_orders = mysqli_query($conn, "SELECT COUNT(*) as pending FROM `orders` WHERE payment_status = 'pending'")->fetch_assoc()['pending'];
$completed_orders = mysqli_query($conn, "SELECT COUNT(*) as completed FROM `orders` WHERE payment_status = 'completed'")->fetch_assoc()['completed'];
$cancelled_orders = mysqli_query($conn, "SELECT COUNT(*) as cancelled FROM `orders` WHERE payment_status = 'cancelled'")->fetch_assoc()['cancelled'];
$total_revenue = mysqli_query($conn, "SELECT SUM(total_price) as revenue FROM `orders` WHERE payment_status = 'completed'")->fetch_assoc()['revenue'] ?? 0;
$today_revenue = mysqli_query($conn, "SELECT SUM(total_price) as revenue FROM `orders` WHERE payment_status = 'completed' AND DATE(placed_on) = '$today'")->fetch_assoc()['revenue'] ?? 0;

// Fetch orders with search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where_conditions = [];
if(!empty($search)) {
    $where_conditions[] = "(o.name LIKE '%$search%' OR o.email LIKE '%$search%' OR o.number LIKE '%$search%' OR o.id = '$search')";
}
if(!empty($status_filter)) {
    $where_conditions[] = "o.payment_status = '$status_filter'";
}

$where_clause = '';
if(!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$select_orders = mysqli_query($conn, "
    SELECT o.*, u.name as user_name 
    FROM `orders` o 
    LEFT JOIN `users` u ON o.user_id = u.id 
    $where_clause 
    ORDER BY o.placed_on DESC
") or die('query failed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Order Management - Admin Panel</title>

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">

   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/admin_header.css">
   <link rel="stylesheet" href="css/admin_orders.css">

</head>
<body>
   
<?php include 'admin_header.php'; ?>

<div class="main-content">
   <section class="orders-section">
      <header class="section-header">
         <h1 class="title">Order Management</h1>
         <div class="order-stats">
            <div class="stat-item">
               <i class="fas fa-shopping-cart"></i>
               <div>
                  <span class="stat-number"><?php echo $total_orders; ?></span>
                  <span class="stat-label">Total Orders</span>
               </div>
            </div>
            <div class="stat-item">
               <i class="fas fa-clock"></i>
               <div>
                  <span class="stat-number"><?php echo $pending_orders; ?></span>
                  <span class="stat-label">Pending</span>
               </div>
            </div>
            <div class="stat-item">
               <i class="fas fa-check-circle"></i>
               <div>
                  <span class="stat-number"><?php echo $completed_orders; ?></span>
                  <span class="stat-label">Completed</span>
               </div>
            </div>
            <div class="stat-item">
               <i class="fas fa-money-bill-wave"></i>
               <div>
                  <span class="stat-number">Rs. <?php echo number_format($total_revenue); ?></span>
                  <span class="stat-label">Total Revenue</span>
               </div>
            </div>
            <div class="stat-item">
               <i class="fas fa-calendar-day"></i>
               <div>
                  <span class="stat-number">Rs. <?php echo number_format($today_revenue); ?></span>
                  <span class="stat-label">Today's Revenue</span>
               </div>
            </div>
            <div class="stat-item">
               <i class="fas fa-times-circle"></i>
               <div>
                  <span class="stat-number"><?php echo $cancelled_orders; ?></span>
                  <span class="stat-label">Cancelled</span>
               </div>
            </div>
         </div>
      </header>

      <!-- Orders Table -->
      <div class="orders-container">
         <div class="table-header">
            <h3>All Orders (<?php echo $total_orders; ?>)</h3>
            <div class="table-actions">
               <form method="GET" class="search-form">
                  <div class="search-box">
                     <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search orders...">
                     <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                     </button>
                  </div>
               </form>
               <div class="filter-options">
                  <form method="GET" class="filter-form">
                     <select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                     </select>
                     <?php if(!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                     <?php endif; ?>
                  </form>
               </div>
               <?php if(!empty($search) || !empty($status_filter)): ?>
                  <a href="admin_orders.php" class="btn btn-secondary">
                     <i class="fas fa-times"></i> Clear Filters
                  </a>
               <?php endif; ?>
            </div>
         </div>

         <div class="table-responsive">
            <table class="orders-table">
               <thead>
                  <tr>
                     <th>Order ID</th>
                     <th>Customer</th>
                     <th>Products</th>
                     <th>Amount</th>
                     <th>Date</th>
                     <th>Payment Method</th>
                     <th>Status</th>
                     <th>Actions</th>
                  </tr>
               </thead>
               <tbody>
                  <?php if(mysqli_num_rows($select_orders) > 0): ?>
                     <?php while($fetch_orders = mysqli_fetch_assoc($select_orders)): 
                        $status_class = 'status-' . $fetch_orders['payment_status'];
                        $is_completed = $fetch_orders['payment_status'] == 'completed';
                        $is_pending = $fetch_orders['payment_status'] == 'pending';
                     ?>
                     <tr class="order-row" data-status="<?php echo $fetch_orders['payment_status']; ?>">
                        <td class="order-id">#<?php echo $fetch_orders['id']; ?></td>
                        <td class="customer-info">
                           <div class="customer-name"><?php echo htmlspecialchars($fetch_orders['name']); ?></div>
                           <div class="customer-contact">
                              <small><?php echo htmlspecialchars($fetch_orders['email']); ?></small>
                              <br>
                              <small><?php echo htmlspecialchars($fetch_orders['number']); ?></small>
                           </div>
                        </td>
                        <td class="products-info">
                           <div class="products-count"><?php echo $fetch_orders['total_products']; ?></div>
                           <div class="user-id">User: <?php echo htmlspecialchars($fetch_orders['user_name'] ?? 'N/A'); ?></div>
                        </td>
                        <td class="order-amount">
                           <strong>Rs. <?php echo number_format($fetch_orders['total_price']); ?>/-</strong>
                        </td>
                        <td class="order-date">
                           <?php echo date('M j, Y', strtotime($fetch_orders['placed_on'])); ?>
                           <br>
                           <small><?php echo date('g:i A', strtotime($fetch_orders['placed_on'])); ?></small>
                        </td>
                        <td class="payment-method">
                           <span class="method-badge"><?php echo ucfirst($fetch_orders['method']); ?></span>
                        </td>
                        <td class="order-status">
                           <span class="status-badge <?php echo $status_class; ?>">
                              <i class="fas <?php 
                                 echo $is_completed ? 'fa-check-circle' : 
                                      ($is_pending ? 'fa-clock' : 'fa-times-circle'); 
                              ?>"></i>
                              <?php echo ucfirst($fetch_orders['payment_status']); ?>
                           </span>
                        </td>
                        <td class="order-actions">
                           <div class="action-buttons">
                              <button class="action-btn view-btn" data-order-id="<?= $fetch_orders['id']; ?>" title="View Details">
                                 <i class="fas fa-eye"></i>
                              </button>

                              <?php if($is_pending): ?>
                                 <button type="button" class="action-btn approve-btn" 
                                         onclick="approveOrder(<?= $fetch_orders['id']; ?>)" 
                                         title="Approve Order">
                                    <i class="fas fa-check-circle"></i>
                                 </button>
                                 <button type="button" class="action-btn disapprove-btn" 
                                         onclick="disapproveOrder(<?= $fetch_orders['id']; ?>)" 
                                         title="Cancel Order">
                                    <i class="fas fa-times-circle"></i>
                                 </button>
                              <?php endif; ?>

                              <button type="button" class="action-btn delete-btn" 
                                      onclick="confirmDelete(<?= $fetch_orders['id']; ?>)" 
                                      title="Delete Order">
                                 <i class="fas fa-trash"></i>
                              </button>
                           </div>
                        </td>
                     </tr>
                     <?php endwhile; ?>
                  <?php else: ?>
                     <tr>
                        <td colspan="8" class="empty-orders">
                           <div class="empty-state">
                              <i class="fas fa-shopping-cart"></i>
                              <h4>No Orders Found</h4>
                              <p><?php echo (!empty($search) || !empty($status_filter)) ? 'Try adjusting your search or filter criteria.' : 'No orders have been placed yet.'; ?></p>
                           </div>
                        </td>
                     </tr>
                  <?php endif; ?>
               </tbody>
            </table>
         </div>
      </div>
   </section>
</div>

<!-- Order Details Modal -->
<div id="order-details-modal" class="modal-overlay">
   <div class="modal-content">
      <div class="modal-header">
         <h3><i class="fas fa-receipt"></i> Order Details</h3>
         <button class="close-btn" id="close-details-modal">
            <i class="fas fa-times"></i>
         </button>
      </div>
      <div class="modal-body" id="order-details-content">
         <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading order details...</p>
         </div>
      </div>
   </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-overlay">
   <div class="modal-content delete-modal">
      <div class="modal-header">
         <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
         <button class="close-btn" id="close-delete-modal">
            <i class="fas fa-times"></i>
         </button>
      </div>
      <div class="modal-body">
         <div class="delete-icon">
            <i class="fas fa-trash"></i>
         </div>
         <h4>Delete Order?</h4>
         <p>Are you sure you want to delete order <strong id="delete-order-id"></strong>?</p>
         <div class="delete-warning">
            <i class="fas fa-exclamation-circle"></i>
            <span>This action cannot be undone and will permanently remove the order.</span>
         </div>
      </div>
      <div class="modal-actions">
         <form method="GET" id="delete-form" style="display: inline;">
            <input type="hidden" name="delete" id="delete-order-value">
            <button type="submit" class="btn btn-danger">
               <i class="fas fa-trash"></i> Yes, Delete Order
            </button>
         </form>
         <button type="button" id="cancel-delete" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
         </button>
      </div>
   </div>
</div>

<!-- Quick Action Forms (Hidden) -->
<form method="POST" id="approve-form" style="display: none;">
   <input type="hidden" name="order_id" id="approve-order-id">
   <input type="hidden" name="approve_order">
</form>

<form method="POST" id="disapprove-form" style="display: none;">
   <input type="hidden" name="order_id" id="disapprove-order-id">
   <input type="hidden" name="disapprove_order">
</form>

<script>
// Order management functions
function approveOrder(orderId) {
    if(confirm('Are you sure you want to approve order #' + orderId + '? This will decrease product stock.')) {
        document.getElementById('approve-order-id').value = orderId;
        document.getElementById('approve-form').submit();
    }
}

function disapproveOrder(orderId) {
    if(confirm('Are you sure you want to cancel order #' + orderId + '?')) {
        document.getElementById('disapprove-order-id').value = orderId;
        document.getElementById('disapprove-form').submit();
    }
}

function confirmDelete(orderId) {
    document.getElementById('delete-order-id').textContent = '#' + orderId;
    document.getElementById('delete-order-value').value = orderId;
    document.getElementById('delete-modal').classList.add('active');
}

// Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const modals = {
        details: document.getElementById('order-details-modal'),
        delete: document.getElementById('delete-modal')
    };

    const closeButtons = {
        details: document.getElementById('close-details-modal'),
        delete: document.getElementById('close-delete-modal'),
        cancelDelete: document.getElementById('cancel-delete')
    };

    // View order details
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            loadOrderDetails(orderId);
            modals.details.classList.add('active');
        });
    });

    // Close modals
    Object.entries(closeButtons).forEach(([key, btn]) => {
        if(btn) {
            btn.addEventListener('click', () => {
                Object.values(modals).forEach(modal => modal.classList.remove('active'));
            });
        }
    });

    // Close modals on overlay click
    Object.values(modals).forEach(modal => {
        modal.addEventListener('click', (e) => {
            if(e.target === modal) modal.classList.remove('active');
        });
    });

    // ESC key to close modals
    document.addEventListener('keydown', (e) => {
        if(e.key === 'Escape') {
            Object.values(modals).forEach(modal => modal.classList.remove('active'));
        }
    });
});

// Load order details via AJAX
function loadOrderDetails(orderId) {
    const content = document.getElementById('order-details-content');
    content.innerHTML = `
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading order details...</p>
        </div>
    `;

    // In a real implementation, you'd fetch this via AJAX
    // For now, we'll simulate with a timeout
    setTimeout(() => {
        content.innerHTML = `
            <div class="order-details">
                <div class="detail-section">
                    <h4>Order Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="label">Order ID:</span>
                            <span class="value">#${orderId}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Order Date:</span>
                            <span class="value">${new Date().toLocaleDateString()}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Total Amount:</span>
                            <span class="value">Rs. 0.00</span>
                        </div>
                    </div>
                </div>
                <div class="detail-section">
                    <h4>Customer Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="label">Name:</span>
                            <span class="value">Customer Name</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Email:</span>
                            <span class="value">customer@example.com</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Phone:</span>
                            <span class="value">+1234567890</span>
                        </div>
                    </div>
                </div>
                <div class="detail-section">
                    <h4>Order Items</h4>
                    <div class="order-items">
                        <p>Order items would be displayed here...</p>
                    </div>
                </div>
            </div>
        `;
    }, 1000);
}
</script>

</body>
</html>