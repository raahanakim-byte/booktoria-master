<?php
include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

if(isset($_POST['update_cart'])){
   $cart_id = $_POST['cart_id'];
   $cart_quantity = $_POST['cart_quantity'];
   mysqli_query($conn, "UPDATE `cart` SET quantity = '$cart_quantity' WHERE id = '$cart_id'") or die('query failed');
   $message[] = 'cart quantity updated!';
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   mysqli_query($conn, "DELETE FROM `cart` WHERE id = '$delete_id'") or die('query failed');
   header('location:cart.php');
}

if(isset($_GET['delete_all'])){
   mysqli_query($conn, "DELETE FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
   header('location:cart.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>BookNook - Shopping Cart</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/home.css">
   <link rel="stylesheet" href="css/cart.css">
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

   <!-- Header -->
   <header>
      <div class="container header-content">
         <a href="home.php" class="logo">
            <i class="fas fa-book-open"></i>
            <span>BookNook</span>
         </a>
         
         <nav id="main-nav">
            <ul>
               <li><a href="home.php">Home</a></li>
               <li><a href="shop.php">Shop</a></li>
               <li><a href="orders.php">Orders</a></li>
               <li><a href="thrift_list.php">Thrift Books</a></li>
               <li><a href="contact.php">Contact</a></li>
            </ul>
         </nav>
         
         <div class="header-actions">
            <i class="fas fa-search" id="search-btn"></i>
            <a href="cart.php" class="cart-icon active">
               <i class="fas fa-shopping-cart" id="cart-btn"></i>
               <span class="cart-count">
                  <?php 
                  $cart_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
                  $cart_data = mysqli_fetch_assoc($cart_count);
                  echo $cart_data['count'];
                  ?>
               </span>
            </a>
            <i class="fas fa-user" id="user-btn"></i>
            <div class="mobile-menu-btn" id="menu-btn">
               <i class="fas fa-bars"></i>
            </div>
         </div>
      </div>
   </header>

   <!-- Breadcrumb Section -->
   <section class="breadcrumb">
      <div class="container">
         <div class="breadcrumb-content">
            <h1>Shopping Cart</h1>
            <div class="breadcrumb-nav">
               <a href="home.php">
                  <i class="fas fa-home"></i> Home
               </a>
               <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
               <span class="breadcrumb-current">Cart</span>
            </div>
            <div class="breadcrumb-stats">
               <div class="breadcrumb-stat">
                  <i class="fas fa-shopping-cart"></i>
                  <span>
                     <?php 
                     $cart_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
                     $cart_data = mysqli_fetch_assoc($cart_count);
                     echo $cart_data['count'] . ' Items';
                     ?>
                  </span>
               </div>
            </div>
         </div>
      </div>
   </section>

   <!-- Cart Section -->
   <section class="cart-section">
      <div class="container">
         <div class="cart-header">
            <h2 class="section-title">Your Cart Items</h2>
            <?php
            $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
            if(mysqli_num_rows($select_cart) > 0){
            ?>
            <a href="cart.php?delete_all" class="clear-cart-btn" onclick="return confirm('Are you sure you want to clear your entire cart?');">
               <i class="fas fa-trash-alt"></i>
               Clear Cart
            </a>
            <?php } ?>
         </div>

         <div class="cart-content">
            <!-- Cart Items -->
            <div class="cart-items">
               <?php
               $grand_total = 0;
               $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
               if(mysqli_num_rows($select_cart) > 0){
                  while($fetch_cart = mysqli_fetch_assoc($select_cart)){   
                     $sub_total = $fetch_cart['quantity'] * $fetch_cart['price'];
                     $grand_total += $sub_total;
               ?>
               <div class="cart-item">
                  <div class="item-image">
                     <img src="uploaded_img/<?php echo $fetch_cart['image']; ?>" alt="<?php echo $fetch_cart['name']; ?>">
                     <?php if($fetch_cart['type'] == 'thrift'){ ?>
                        <div class="item-badge thrift-badge">
                           <i class="fas fa-recycle"></i>
                           Thrift
                        </div>
                     <?php } ?>
                  </div>
                  
                  <div class="item-details">
                     <h3 class="item-title"><?php echo $fetch_cart['name']; ?></h3>
                     <p class="item-price">Rs. <?php echo $fetch_cart['price']; ?>/-</p>
                     
                     <form action="" method="post" class="quantity-form">
                        <input type="hidden" name="cart_id" value="<?php echo $fetch_cart['id']; ?>">
                        <div class="quantity-controls">
                           <label>Quantity:</label>
                           <div class="quantity-input-group">
                              <button type="button" class="quantity-btn minus" onclick="decreaseQuantity(this)">
                                 <i class="fas fa-minus"></i>
                              </button>
                              <input type="number" min="1" name="cart_quantity" value="<?php echo $fetch_cart['quantity']; ?>" class="quantity-input">
                              <button type="button" class="quantity-btn plus" onclick="increaseQuantity(this)">
                                 <i class="fas fa-plus"></i>
                              </button>
                           </div>
                           <button type="submit" name="update_cart" class="update-btn">
                              <i class="fas fa-sync-alt"></i>
                              Update
                           </button>
                        </div>
                     </form>
                     
                     <div class="item-subtotal">
                        Subtotal: <span>Rs. <?php echo $sub_total; ?>/-</span>
                     </div>
                  </div>
                  
                  <div class="item-actions">
                     <a href="cart.php?delete=<?php echo $fetch_cart['id']; ?>" class="delete-item" onclick="return confirm('Remove this item from cart?');">
                        <i class="fas fa-trash"></i>
                     </a>
                  </div>
               </div>
               <?php
                  }
               } else {
                  echo '
                  <div class="empty-cart">
                     <div class="empty-icon">
                        <i class="fas fa-shopping-cart"></i>
                     </div>
                     <h3>Your Cart is Empty</h3>
                     <p>Looks like you haven\'t added any books to your cart yet.</p>
                     <a href="shop.php" class="btn">Start Shopping</a>
                  </div>
                  ';
               }
               ?>
            </div>

               <div class="cart-actions">
                  <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
                  <?php if($grand_total > 0): ?>
                     <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                  <?php endif; ?>
               </div>

            <!-- Cart Summary -->
            <?php if(mysqli_num_rows($select_cart) > 0){ ?>
            <div class="cart-summary">
               <div class="summary-card">
                  <h3 class="summary-title">Order Summary</h3>
                  
                  <div class="summary-row">
                     <span>Subtotal</span>
                     <span>Rs. <?php echo $grand_total; ?>/-</span>
                  </div>
                  
                  <div class="summary-row">
                     <span>Shipping</span>
                     <span class="free-shipping">Free</span>
                  </div>
                  
                  <div class="summary-row">
                     <span>Tax</span>
                     <span>Rs. 0/-</span>
                  </div>
                  
                  <div class="summary-divider"></div>
                  
                  <div class="summary-row total">
                     <span>Total Amount</span>
                     <span class="total-amount">Rs. <?php echo $grand_total; ?>/-</span>
                  </div>
                  
                  <div class="summary-actions">
                     <a href="shop.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Continue Shopping
                     </a>
                     <a href="checkout.php" class="btn <?php echo ($grand_total > 1)?'':'disabled'; ?>">
                        <i class="fas fa-credit-card"></i>
                        Proceed to Checkout
                     </a>
                  </div>
                  
                  <div class="security-notice">
                     <i class="fas fa-shield-alt"></i>
                     <span>Secure checkout guaranteed</span>
                  </div>
               </div>
               
               <div class="benefits-card">
                  <h4>Benefits of Shopping with Us</h4>
                  <ul class="benefits-list">
                     <li><i class="fas fa-shipping-fast"></i> Free shipping on orders over Rs. 1000</li>
                     <li><i class="fas fa-undo-alt"></i> Easy 30-day return policy</li>
                     <li><i class="fas fa-lock"></i> Secure payment processing</li>
                     <li><i class="fas fa-headset"></i> 24/7 customer support</li>
                  </ul>
               </div>
            </div>
            <?php } ?>
         </div>
      </div>
   </section>

   <!-- Newsletter -->
   <section class="newsletter">
      <div class="container">
         <h2>Stay Updated</h2>
         <p>Subscribe to our newsletter for exclusive deals and new arrivals.</p>
         <form class="newsletter-form">
            <input type="email" placeholder="Your email address" class="newsletter-input" required>
            <button type="submit" class="btn btn-secondary">Subscribe</button>
         </form>
      </div>
   </section>

   <!-- Footer -->
   <footer>
      <div class="container">
         <div class="footer-content">
            <div class="footer-column">
               <h3>BookNook</h3>
               <p>Your favorite online bookstore with carefully curated selections for every type of reader.</p>
              
            </div>
            
            <div class="footer-column">
               <h3>Shop</h3>
               <ul class="footer-links">
                  <li><a href="shop.php">All Books</a></li>
                  <li><a href="shop.php?genre=Fiction">Fiction</a></li>
                  <li><a href="shop.php?genre=Non-Fiction">Non-Fiction</a></li>
                  <li><a href="thrift_list.php">Thrift Books</a></li>
                  <li><a href="shop.php">New Arrivals</a></li>
               </ul>
            </div>
            
            <div class="footer-column">
               <h3>Help</h3>
               <ul class="footer-links">
                  <li><a href="#">Shipping Info</a></li>
                  <li><a href="#">Returns</a></li>
                  <li><a href="#">FAQ</a></li>
                  <li><a href="contact.php">Contact Us</a></li>
                  <li><a href="#">Privacy Policy</a></li>
               </ul>
            </div>
            
            <div class="footer-column">
               <h3>Contact</h3>
               <ul class="footer-links">
                  <li><i class="fas fa-map-marker-alt"></i> 123 Book Street, Readville</li>
                  <li><i class="fas fa-phone"></i> (555) 123-4567</li>
                  <li><i class="fas fa-envelope"></i> hello@booknook.com</li>
               </ul>
            </div>
         </div>
         
         <div class="footer-bottom">
            <p>&copy; 2023 BookNook. All rights reserved.</p>
         </div>
      </div>
   </footer>

   <script>
      // Mobile menu toggle
      document.getElementById('menu-btn').addEventListener('click', function() {
         document.getElementById('main-nav').classList.toggle('active');
      });

      // Close mobile menu when clicking outside
      document.addEventListener('click', function(event) {
         const nav = document.getElementById('main-nav');
         const menuBtn = document.getElementById('menu-btn');
         
         if (!nav.contains(event.target) && !menuBtn.contains(event.target)) {
            nav.classList.remove('active');
         }
      });

      // Message auto-remove
      const messages = document.querySelectorAll('.message');
      messages.forEach(message => {
         setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
               message.remove();
            }, 300);
         }, 5000);
      });

      // Quantity controls
      function increaseQuantity(button) {
         const input = button.parentElement.querySelector('.quantity-input');
         input.value = parseInt(input.value) + 1;
      }

      function decreaseQuantity(button) {
         const input = button.parentElement.querySelector('.quantity-input');
         if (parseInt(input.value) > 1) {
            input.value = parseInt(input.value) - 1;
         }
      }

      // Update button animation
      document.querySelectorAll('.update-btn').forEach(btn => {
         btn.addEventListener('click', function() {
            this.classList.add('updating');
            setTimeout(() => {
               this.classList.remove('updating');
            }, 1000);
         });
      });
   </script>
</body>
</html>