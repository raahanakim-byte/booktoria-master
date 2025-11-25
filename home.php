<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';
include 'recommendations.php';

// Determine if user is logged in
$user_id = $_SESSION['user_id'] ?? null;

// Get recommended books if user is logged in
$recommended_books = [];
if ($user_id) {
    $recommended_books = getRecommendedBooks($conn, $user_id);
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $product_quantity = $_POST['product_quantity'];

    if ($user_id) {
        // Logged-in user: store cart in database
        $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');

        if (mysqli_num_rows($check_cart_numbers) > 0) {
            $message[] = 'Already added to cart!';
        } else {
            mysqli_query($conn, "INSERT INTO `cart`(user_id, name, price, quantity, image) VALUES('$user_id', '$product_name', '$product_price', '$product_quantity', '$product_image')") or die('query failed');
            $message[] = 'Product added to cart!';
        }
    } else {
        // Guest: store cart in session
        $_SESSION['guest_cart'][$product_name] = [
            'name' => $product_name,
            'price' => $product_price,
            'image' => $product_image,
            'quantity' => $product_quantity
        ];
        $message[] = 'Added to cart (guest mode)!';
    }
}

// Fetch 6 latest products
$select_products = mysqli_query($conn, "SELECT * FROM `products` ORDER BY id DESC LIMIT 6") or die('query failed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Booktoria - Discover Your Next Read</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/home.css">
   <link rel="stylesheet" href="css/sidebar.css">
</head>
<body>
   <!-- Display Messages -->
   <?php
   if(isset($message)){
      foreach($message as $msg){
         echo '
         <div class="message">
            <span>'.$msg.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
   ?>

   <?php include 'header.php'; ?>

   <main class="main-content">
      <!-- Hero Section -->
      <section class="hero">
         <div class="hero-overlay"></div>
         <div class="container hero-container">
            <div class="hero-content">
               <h1>Discover Your Next Favorite Read</h1>
               <p>Explore thousands of books handpicked for passionate readers like you — from classics to new releases.</p>
               <div class="hero-buttons">
                  <a href="shop.php" class="btn primary">Browse Collection</a>
                  <a href="#" class="btn outline">Join Book Club</a>
               </div>
            </div>
         </div>
      </section>

      <!-- Latest Arrivals Section -->
      <section class="featured-products">
         <div class="container">
            <h2 class="section-title">Latest Arrivals</h2>
            <div class="products-grid">
               <?php
               if(mysqli_num_rows($select_products) > 0){
                  while($fetch_product = mysqli_fetch_assoc($select_products)){
               ?>
               <form action="" method="post" class="product-card">
                  <div class="card-image">
                     <img src="uploaded_img/<?php echo $fetch_product['image']; ?>" alt="<?php echo $fetch_product['name']; ?>">
                  </div>
                  <div class="card-body">
                     <h3><?php echo $fetch_product['name']; ?></h3>
                     <p class="author">by <?php echo $fetch_product['author']; ?></p>
                     <div class="meta">
                        <span class="genre"><?php echo $fetch_product['genre']; ?></span>
                        <?php if(!empty($fetch_product['location'])): ?>
                        <span class="location"><i class="fas fa-map-marker-alt"></i> <?php echo $fetch_product['location']; ?></span>
                        <?php endif; ?>
                     </div>
                     <div class="price">Rs. <?php echo $fetch_product['price']; ?>/-</div>

                     <input type="hidden" name="product_name" value="<?php echo $fetch_product['name']; ?>">
                     <input type="hidden" name="product_price" value="<?php echo $fetch_product['price']; ?>">
                     <input type="hidden" name="product_image" value="<?php echo $fetch_product['image']; ?>">
                     <div class="stock-info">
                        <i class="fas fa-cubes"></i>
                        <span>
                           <?php
                           $product_name = mysqli_real_escape_string($conn, $fetch_product['name']);
                           $bookQuantity = mysqli_query($conn, "SELECT `stock` FROM `products` WHERE `name` = '$product_name'");
                              if ($bookQuantity && mysqli_num_rows($bookQuantity) > 0) {
                                 $row = mysqli_fetch_assoc($bookQuantity);
                                 echo $row['stock'];  
                              }
                                          
                           ?>
                        </span>
                     </div>

                     <div class="card-buttons">
                        <a href="view_book.php?id=<?php echo $fetch_product['id']; ?>" class="btn-view">View Book</a>
                     <?php if($user_id): ?>
                        <button type="submit" name="add_to_cart" class="btn-cart">Add to Cart</button>
                     <?php else: ?>
                        <button type="button" class="btn-cart guest-add">Add to Cart</button>
                     <?php endif; ?>

                     </div>
                  </div>
               </form>
               <?php
                  }
               } else {
                  echo '<p class="empty">No products added yet!</p>';
               }
               ?>
            </div>

            <div class="load-more" style="margin-top: 3rem; text-align: center;">
               <a href="shop.php" class="btn btn-outline">View All Products</a>
            </div>
         </div>
      </section>

      <!-- Recommended Section (for logged-in users only) -->
      <?php if($user_id && !empty($recommended_books)): ?>
      <section class="recommended">
         <div class="container">
            <h2 class="section-title">Recommended For You</h2>
            <div class="products-grid">
               <?php foreach ($recommended_books as $book): ?>
                  <form action="" method="post" class="product-card">
                     <div class="card-image">
                        <img src="uploaded_img/<?php echo $book['image']; ?>" alt="<?php echo $book['name']; ?>">
                     </div>
                     <div class="card-body">
                        <h3><?php echo $book['name']; ?></h3>
                        <p class="author">by <?php echo $book['author']; ?></p>
                        <div class="meta">
                           <span class="genre"><?php echo $book['genre']; ?></span>
                           <?php if(!empty($book['location'])): ?>
                              <span class="location"><i class="fas fa-map-marker-alt"></i> <?php echo $book['location']; ?></span>
                           <?php endif; ?>
                        </div>
                        <div class="price">Rs. <?php echo $book['price']; ?>/-</div>
                        <div class="product-actions">
                           <div class="stock-info">
                              <i class="fas fa-cubes"></i>
                              <span>
                                 <?php
                                 $product_name = mysqli_real_escape_string($conn, $book['name']);
                                 $bookQuantity = mysqli_query($conn, "SELECT `stock` FROM `products` WHERE `name` = '$product_name'");
                                    if ($bookQuantity && mysqli_num_rows($bookQuantity) > 0) {
                                       $row = mysqli_fetch_assoc($bookQuantity);
                                       echo $row['stock'];  
                                    }
                                                
                                 ?>
                              </span>
                           </div>
                           <input type="hidden" name="product_name" value="<?php echo $book['name']; ?>">
                           <input type="hidden" name="product_price" value="<?php echo $book['price']; ?>">
                           <input type="hidden" name="product_image" value="<?php echo $book['image']; ?>">
                           <button type="submit" name="add_to_cart" class="btn-cart">Add to Cart</button>
                        </div>
                     </div>
                  </form>
               <?php endforeach; ?>
            </div>
         </div>
      </section>
      <?php endif; ?>
   </main>
      <!-- Login Modal -->
   <div id="loginModal" class="modal">
      <div class="modal-content">
         <span class="close">&times;</span>
         <h3>Please Login to Add to Cart</h3>
         <p>Login to continue shopping and save your cart.</p>
         <a href="login.php" class="btn">Login</a>
         <a href="register.php" class="btn btn-outline">Register</a>
      </div>
   </div>


   <script>
       // Guest add to cart triggers modal
      const loginModal = document.getElementById('loginModal');
      const closeBtn = document.querySelector('.modal .close');

      document.querySelectorAll('.guest-add').forEach(btn => {
         btn.addEventListener('click', () => {
            loginModal.style.display = 'block';
         });
      });

      closeBtn.addEventListener('click', () => {
         loginModal.style.display = 'none';
      });

      window.addEventListener('click', (e) => {
         if(e.target == loginModal){
            loginModal.style.display = 'none';
         }
      });
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
            setTimeout(() => { message.remove(); }, 300);
         }, 5000);
      });
   </script>
</body>
</html>
