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
    // Get current stock
    $product_query = mysqli_query($conn, "SELECT stock FROM `products` WHERE name = '$product_name'");
    if(mysqli_num_rows($product_query) > 0) {
        $product = mysqli_fetch_assoc($product_query);
        $current_stock = $product['stock'];
        $new_stock = max(0, $current_stock - $quantity);
        
        // Update stock
        mysqli_query($conn, "UPDATE `products` SET stock = '$new_stock' WHERE name = '$product_name'");
        return true;
    }
    return false;
}

if(isset($_POST['update_order'])){
   $order_update_id = $_POST['order_id'];
   $update_payment = $_POST['update_payment'];
   $current_status = $_POST['current_status'];
   
   // Get order details before updating
   $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE id = '$order_update_id'");
   $order = mysqli_fetch_assoc($order_query);
   
   // Update payment status
   mysqli_query($conn, "UPDATE `orders` SET payment_status = '$update_payment' WHERE id = '$order_update_id'") or die('query failed');
   
   // If status changed from pending to completed, decrease stock
   if($current_status == 'pending' && $update_payment == 'completed') {
       $total_products = $order['total_products'];
       
       // Parse the products string (format: name (quantity))
       preg_match_all('/([^(]+)\s*\((\d+)\)/x', $total_products, $matches, PREG_SET_ORDER);
       
       foreach($matches as $match) {
           $product_name = trim($match[1]);
           $quantity = (int)$match[2];
           decreaseProductStock($conn, $product_name, $quantity);
       }
       
       $message[] = 'Payment status updated and stock decreased!';
   } else {
       $message[] = 'Payment status has been updated!';
   }
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   mysqli_query($conn, "DELETE FROM `orders` WHERE id = '$delete_id'") or die('query failed');
   header('location:admin_orders.php');
   exit;
}

// Get order statistics
$total_orders = mysqli_query($conn, "SELECT COUNT(*) as total FROM `orders`")->fetch_assoc()['total'];
$pending_orders = mysqli_query($conn, "SELECT COUNT(*) as pending FROM `orders` WHERE payment_status = 'pending'")->fetch_assoc()['pending'];
$completed_orders = mysqli_query($conn, "SELECT COUNT(*) as completed FROM `orders` WHERE payment_status = 'completed'")->fetch_assoc()['completed'];
$total_revenue = mysqli_query($conn, "SELECT SUM(total_price) as revenue FROM `orders` WHERE payment_status = 'completed'")->fetch_assoc()['revenue'] ?? 0;

// Fetch orders
$select_orders = mysqli_query($conn, "SELECT * FROM `orders` ORDER BY id DESC") or die('query failed');
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
      <!-- Messages -->
      <?php if(isset($message)): ?>
         <?php foreach($message as $msg): ?>
            <div class="admin-message success">
               <span><?php echo $msg; ?></span>
               <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
         <?php endforeach; ?>
      <?php endif; ?>

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
                  <span class="stat-label">Revenue</span>
               </div>
            </div>
         </div>
      </header>

      <!-- Orders Table -->
      <div class="orders-container">
         <div class="table-header">
            <h3>All Orders (<?php echo $total_orders; ?>)</h3>
            <div class="table-actions">
               <div class="search-box">
                  <input type="text" id="order-search" placeholder="Search orders...">
                  <i class="fas fa-search"></i>
               </div>
               <div class="filter-options">
                  <select id="status-filter">
                     <option value="">All Status</option>
                     <option value="pending">Pending</option>
                     <option value="completed">Completed</option>
                  </select>
               </div>
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
                        $status_class = $fetch_orders['payment_status'] == 'completed' ? 'status-completed' : 'status-pending';
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
                           <div class="user-id">User ID: <?php echo $fetch_orders['user_id']; ?></div>
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
                              <?php echo ucfirst($fetch_orders['payment_status']); ?>
                           </span>
                        </td>
                        <td class="order-actions">
                           <div class="action-buttons">
                              <button class="action-btn view-btn" data-order-id="<?php echo $fetch_orders['id']; ?>" title="View Details">
                                 <i class="fas fa-eye"></i>
                              </button>
                              <button class="action-btn edit-btn" data-order-id="<?php echo $fetch_orders['id']; ?>" title="Edit Order">
                                 <i class="fas fa-edit"></i>
                              </button>
                              <a href="admin_orders.php?delete=<?php echo $fetch_orders['id']; ?>" 
                                 class="action-btn delete-btn" 
                                 title="Delete Order"
                                 onclick="return confirm('Are you sure you want to delete order #<?php echo $fetch_orders['id']; ?>?');">
                                 <i class="fas fa-trash"></i>
                              </a>
                           </div>
                        </td>
                     </tr>
                     <?php endwhile; ?>
                  <?php else: ?>
                     <tr>
                        <td colspan="8" class="empty-orders">
                           <div class="empty-state">
                              <i class="fas fa-shopping-cart"></i>
                              <h4>No Orders Yet</h4>
                              <p>No orders have been placed yet.</p>
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
         <!-- Order details will be loaded here via AJAX -->
      </div>
   </div>
</div>

<!-- Edit Order Modal -->
<div id="edit-order-modal" class="modal-overlay">
   <div class="modal-content">
      <div class="modal-header">
         <h3><i class="fas fa-edit"></i> Update Order Status</h3>
         <button class="close-btn" id="close-edit-modal">
            <i class="fas fa-times"></i>
         </button>
      </div>
      <form action="" method="post" class="modal-form">
         <input type="hidden" name="order_id" id="edit_order_id">
         <input type="hidden" name="current_status" id="current_status">
         
         <div class="form-group">
            <label for="update_payment">Payment Status</label>
            <select name="update_payment" id="update_payment" class="form-control" required>
               <option value="pending">Pending</option>
               <option value="completed">Completed</option>
            </select>
         </div>
         
         <div class="form-actions">
            <button type="submit" name="update_order" class="btn btn-primary">
               <i class="fas fa-save"></i> Update Status
            </button>
            <button type="button" id="cancel-edit" class="btn btn-secondary">
               <i class="fas fa-times"></i> Cancel
            </button>
         </div>
      </form>
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
         <p>Are you sure you want to delete order <strong id="delete-order-id"></strong>? This action cannot be undone.</p>
      </div>
      <div class="modal-actions">
         <a href="#" id="confirm-delete" class="btn btn-danger">
            <i class="fas fa-trash"></i> Yes, Delete Order
         </a>
         <button type="button" id="cancel-delete" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
         </button>
      </div>
   </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const orderDetailsModal = document.getElementById('order-details-modal');
    const editOrderModal = document.getElementById('edit-order-modal');
    const deleteModal = document.getElementById('delete-modal');
    const orderSearch = document.getElementById('order-search');
    const statusFilter = document.getElementById('status-filter');
    const orderRows = document.querySelectorAll('.order-row');

    // Search functionality
    if(orderSearch) {
        orderSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            orderRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Filter functionality
    if(statusFilter) {
        statusFilter.addEventListener('change', function() {
            const filterValue = this.value;
            orderRows.forEach(row => {
                if(!filterValue || row.getAttribute('data-status') === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // View order details
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            // In a real implementation, you'd fetch order details via AJAX
            // For now, we'll show a placeholder
            document.getElementById('order-details-content').innerHTML = `
                <div class="order-details-placeholder">
                    <i class="fas fa-receipt"></i>
                    <p>Order details for #${orderId} would be loaded here.</p>
                    <p>This would include complete product list, customer address, etc.</p>
                </div>
            `;
            orderDetailsModal.classList.add('active');
        });
    });

    // Edit order
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const orderRow = this.closest('.order-row');
            const currentStatus = orderRow.getAttribute('data-status');
            
            document.getElementById('edit_order_id').value = orderId;
            document.getElementById('current_status').value = currentStatus;
            document.getElementById('update_payment').value = currentStatus;
            
            editOrderModal.classList.add('active');
        });
    });

    // Delete order with confirmation
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.closest('tr').querySelector('.order-id').textContent;
            
            document.getElementById('delete-order-id').textContent = orderId;
            document.getElementById('confirm-delete').href = this.href;
            deleteModal.classList.add('active');
        });
    });

    // Modal close functionality
    function setupModal(modal, closeBtn, cancelBtn = null) {
        closeBtn.addEventListener('click', () => modal.classList.remove('active'));
        if(cancelBtn) {
            cancelBtn.addEventListener('click', () => modal.classList.remove('active'));
        }
        modal.addEventListener('click', (e) => {
            if(e.target === modal) modal.classList.remove('active');
        });
    }

    // Setup modals
    setupModal(
        orderDetailsModal,
        document.getElementById('close-details-modal')
    );
    
    setupModal(
        editOrderModal,
        document.getElementById('close-edit-modal'),
        document.getElementById('cancel-edit')
    );
    
    setupModal(
        deleteModal,
        document.getElementById('close-delete-modal'),
        document.getElementById('cancel-delete')
    );

    // ESC key to close modals
    document.addEventListener('keydown', (e) => {
        if(e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });

    // Auto-hide messages after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.admin-message').forEach(msg => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 300);
        });
    }, 5000);
});
</script>

</body>
</html>