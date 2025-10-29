<?php
if(!isset($user_id) && isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include DB connection if not already done
@include 'config.php';

// Set $user_id safely
$user_id = $_SESSION['user_id'] ?? null;
?>
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
<script>
window.addEventListener('DOMContentLoaded', () => {
   const messages = document.querySelectorAll('.message');

   messages.forEach((msg) => {
      setTimeout(() => {
         msg.style.opacity = '0';
      }, 2000); // fade starts after 2 sec

      setTimeout(() => {
         msg.remove();
      }, 3000); // remove after 3 sec
   });
});
</script>


<header class="header">

   <div class="header-1">
      <div class="flex">
         <div class="share">
            <a href="#" class="fab fa-facebook-f"></a>
            <a href="#" class="fab fa-twitter"></a>
            <a href="#" class="fab fa-instagram"></a>
            <a href="#" class="fab fa-linkedin"></a>
         </div>
         <?php if(isset($_SESSION['user_id'])): ?>
            <p> Welcome, <strong><?php echo $_SESSION['user_name']; ?></strong> | <a href="logout.php">Logout</a> </p>
         <?php else: ?>
            <p> new <a href="login.php">login</a> | <a href="register.php">register</a> </p>
         <?php endif; ?>

      </div>
   </div>

   <div class="header-2">
      <div class="flex">
         <a href="home.php" class="logo">Booktoria</a>

         <nav class="navbar">
            <a href="home.php">HOME</a>
          
            <a href="shop.php">STORE</a>
            <a href="contact.php">CONTACT</a>
            <a href="orders.php">ORDERS</a>
            <a href="thrift_list.php">THRIFT BOOKS</a>
            <a href="my_thrift.php">MY THRIFT ITEMS</a>


         </nav>

         <div class="icons">
            <div id="menu-btn" class="fas fa-bars"></div>
            <a href="search_page.php" class="fas fa-search"></a>
            <div id="user-btn" class="fas fa-user"></div>
           <?php
           $cart_rows_number = 0;
           if ($user_id) {
            $select_cart_number = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
            $cart_rows_number = mysqli_num_rows($select_cart_number);
         }
         ?>

            <a href="cart.php"> <i class="fas fa-shopping-cart"></i> <span>(<?php echo $cart_rows_number; ?>)</span> </a>
         </div>

         <?php if(isset($user_id)): ?>
            <div class="user-box">
               <p>username : <span><?php echo $_SESSION['user_name']; ?></span></p>
               <p>email : <span><?php echo $_SESSION['user_email']; ?></span></p>
               <a href="logout.php" class="logout-btn">logout</a>
            </div>
         <?php endif; ?>

      </div>
   </div>

</header>