<?php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

// Get cart items and calculate total
$cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
$grand_total = 0;
$cart_items = [];

if(mysqli_num_rows($cart_query) > 0){
   while($cart_item = mysqli_fetch_assoc($cart_query)){
      $cart_items[] = $cart_item;
      $grand_total += ($cart_item['price'] * $cart_item['quantity']);
   }
} else {
   header('location:cart.php');
   exit;
}

// Get user details
$user_query = mysqli_query($conn, "SELECT * FROM `users` WHERE id = '$user_id'") or die('query failed');
$user_data = mysqli_fetch_assoc($user_query);

// Handle form submission
if(isset($_POST['place_order'])){
   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $number = mysqli_real_escape_string($conn, $_POST['number']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $method = mysqli_real_escape_string($conn, $_POST['method']);
   $address = mysqli_real_escape_string($conn, $_POST['address']);
   
   // Validate required fields
   if(empty($number) || empty($method) || empty($address)){
      $message[] = 'Please fill in all required fields!';
   } else {
      // Prepare order data
      $total_products = [];
      foreach($cart_items as $item){
         $total_products[] = $item['name'] . ' (' . $item['quantity'] . ')';
      }
      $total_products_str = implode(', ', $total_products);
      $placed_on = date('Y-m-d H:i:s');
      
      // Insert order
      $order_query = "INSERT INTO `orders` (user_id, name, number, email, method, address, total_products, total_price, placed_on, payment_status) 
                     VALUES('$user_id', '$name', '$number', '$email', '$method', '$address', '$total_products_str', '$grand_total', '$placed_on', 'pending')";
      
      if(mysqli_query($conn, $order_query)){
         // Clear cart
         mysqli_query($conn, "DELETE FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
         
         $_SESSION['order_success'] = "Order placed successfully! Total: Rs. " . $grand_total . "/-";
         header('location:orders.php');
         exit;
      } else {
         $message[] = 'Failed to place order. Please try again.';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>BookNook - Checkout</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/home.css">
   <link rel="stylesheet" href="css/checkout.css">
   <link rel="stylesheet" href="css/sidebar.css">

</head>
<body>
   <!-- Display Messages -->
   <?php
   if(isset($message)){
      foreach($message as $message){
         echo '
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
   ?>

   <?php include 'header.php'?>

   <!-- Breadcrumb Section -->
   <section class="breadcrumb">
      <div class="container">
         <div class="breadcrumb-content">
            <h1>Checkout</h1>
            <div class="breadcrumb-nav">
               <a href="home.php">
                  <i class="fas fa-home"></i> Home
               </a>
               <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
               <a href="cart.php">Cart</a>
               <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
               <span class="breadcrumb-current">Checkout</span>
            </div>
         </div>
      </div>
   </section>

   <!-- Checkout Section -->
   <section class="checkout-section">
      <div class="container">
         <div class="checkout-content">
            <!-- Order Summary -->
            <div class="order-summary">
               <h2>Order Summary</h2>
               <div class="order-items">
                  <?php foreach($cart_items as $item): ?>
                     <div class="order-item">
                        <img src="uploaded_img/<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                        <div class="item-details">
                           <h4><?php echo $item['name']; ?></h4>
                           <p>Quantity: <?php echo $item['quantity']; ?></p>
                           <p class="item-price">Rs. <?php echo $item['price']; ?>/- each</p>
                        </div>
                        <div class="item-total">
                           Rs. <?php echo $item['price'] * $item['quantity']; ?>/-
                        </div>
                     </div>
                  <?php endforeach; ?>
               </div>
               <div class="order-total">
                  <div class="total-line grand-total">
                     <span>Total Amount:</span>
                     <span>Rs. <?php echo $grand_total; ?>/-</span>
                  </div>
               </div>
            </div>
            
            <!-- Checkout Form -->
            <div class="checkout-form">
               <h2>Shipping & Payment Details</h2>
               <form action="" method="post">
                  <div class="form-row">
                     <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo $user_data['name']; ?>" required>
                     </div>
                     
                     <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo $user_data['email']; ?>" required>
                     </div>
                  </div>
                  
                  <div class="form-group">
                     <label for="number">Phone Number *</label>
                     <input type="text" id="number" name="number" placeholder="Enter your phone number" required>
                  </div>
                  
                  <div class="form-group">
                     <label for="address">Shipping Address *</label>
                     <textarea id="address" name="address" placeholder="Enter your complete shipping address including street, city, and postal code" rows="4" required></textarea>
                  </div>
                  
                  <div class="form-group">
                     <label for="method">Payment Method *</label>
                     <select id="method" name="method" required>
                        <option value="">Select Payment Method</option>
                        <option value="cash_on_delivery">Cash on Delivery</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="paypal">PayPal</option>
                     </select>
                  </div>
                  
                  <div class="form-actions">
                     <a href="cart.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                     </a>
                     <button type="submit" name="place_order" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Place Order
                     </button>
                  </div>
               </form>
            </div>
         </div>
      </div>
   </section>

   <script>
      // Mobile menu toggle
      document.getElementById('menu-btn').addEventListener('click', function() {
         document.getElementById('main-nav').classList.toggle('active');
      });

      // Payment method change
      document.getElementById('method').addEventListener('change', function() {
         const method = this.value;
         if(method === 'cash_on_delivery') {
            alert('Note: Payment will be collected when your order is delivered.');
         }
      });
   </script>
</body>
</html>