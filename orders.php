<?php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

// Get user's orders
$orders_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE user_id = '$user_id' ORDER BY id DESC") or die('query failed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>BookNook - My Orders</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/home.css">
   <link rel="stylesheet" href="css/orders.css">
</head>
<body>
   <!-- Header (same as shop.php) -->

   <section class="orders-section">
      <div class="container">
         <h1>My Orders</h1>
         
         <?php if(isset($_SESSION['order_success'])): ?>
            <div class="message success">
               <span><?php echo $_SESSION['order_success']; ?></span>
               <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
            <?php unset($_SESSION['order_success']); ?>
         <?php endif; ?>
         
         <?php if(mysqli_num_rows($orders_query) > 0): ?>
            <div class="orders-list">
               <?php while($order = mysqli_fetch_assoc($orders_query)): ?>
                  <div class="order-card">
                     <div class="order-header">
                        <div class="order-info">
                           <h3>Order #<?php echo $order['id']; ?></h3>
                           <p>Placed on: <?php echo $order['placed_on']; ?></p>
                           <p>Method: <?php echo ucwords(str_replace('_', ' ', $order['method'])); ?></p>
                        </div>
                        <div class="order-status">
                           <span class="status-badge <?php echo $order['payment_status']; ?>">
                              <?php echo ucfirst($order['payment_status']); ?>
                           </span>
                           <div class="order-total">Rs. <?php echo $order['total_price']; ?>/-</div>
                        </div>
                     </div>
                     
                     <div class="order-details">
                        <div class="shipping-info">
                           <h4>Shipping Address</h4>
                           <p><?php echo $order['address']; ?></p>
                        </div>
                        
                        <div class="order-items-summary">
                           <h4>Order Items</h4>
                           <p><?php echo $order['total_products']; ?></p>
                        </div>
                     </div>
                  </div>
               <?php endwhile; ?>
            </div>
         <?php else: ?>
            <div class="empty-state">
               <i class="fas fa-shopping-bag"></i>
               <h3>No Orders Yet</h3>
               <p>You haven't placed any orders yet.</p>
               <a href="shop.php" class="btn">Start Shopping</a>
            </div>
         <?php endif; ?>
      </div>
   </section>
</body>
</html>