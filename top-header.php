<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
?>

<!-- Top Header Navigation -->
<header>
   <div class="container header-content">
      <div class="header-left">
         <div class="mobile-menu-btn" id="menu-btn">
            <i class="fas fa-bars"></i>
         </div>
         <div class="header-search">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search books..." class="search-input">
         </div>
      </div>
      
      <div class="header-center">
         <a href="home.php" class="header-logo">
            <i class="fas fa-book-open"></i>
            <span>Booktoria</span>
         </a>
      </div>
      
      <div class="header-right">
         <div class="header-actions">
            <a href="cart.php" class="header-cart">
               <i class="fas fa-shopping-cart"></i>
               <span class="cart-badge">
                  <?php 
                  $cart_count = 0;
                  if (isset($_SESSION['user_id'])) {
                     include 'config.php';
                     $user_id = $_SESSION['user_id'];
                     $res = mysqli_query($conn, "SELECT * FROM cart WHERE user_id='$user_id'");
                     $cart_count = mysqli_num_rows($res);
                  }
                  echo $cart_count;
                  ?>
               </span>
            </a>
            <div class="user-dropdown">
               <div class="user-avatar">
                  <i class="fas fa-user-circle"></i>
               </div>
               <div class="dropdown-content">
                  <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?></span>
                  <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                  <a href="orders.php"><i class="fas fa-box"></i> Orders</a>
                  <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
               </div>
            </div>
         </div>
      </div>
   </div>
</header>