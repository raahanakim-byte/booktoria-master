<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'config.php';
include 'recommendations.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// redirect admins to admin page:
if (isset($_SESSION['user'])) {
    header('Location: home.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$recommended_books = getRecommendedBooks($conn, $user_id);

if(isset($_POST['add_to_cart'])){

   $product_name = $_POST['product_name'];
   $product_price = $_POST['product_price'];
   $product_image = $_POST['product_image'];
   $product_quantity = $_POST['product_quantity'];

   $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');

   if(mysqli_num_rows($check_cart_numbers) > 0){
      $message[] = 'already added to cart!';
   }else{
      mysqli_query($conn, "INSERT INTO `cart`(user_id, name, price, quantity, image) VALUES('$user_id', '$product_name', '$product_price', '$product_quantity', '$product_image')") or die('query failed');
      $message[] = 'product added to cart!';
   }

}

// Fetch 6 latest products from the database
$select_products = mysqli_query($conn, "SELECT * FROM `products` ORDER BY id DESC LIMIT 6") or die('query failed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>BookNook - Discover Your Next Read</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/home.css">
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
            <a href="cart.php" class="cart-icon">
               <i class="fas fa-shopping-cart" id="cart-btn"></i>
            </a>
            <i class="fas fa-user" id="user-btn"></i>
            <div class="mobile-menu-btn" id="menu-btn">
               <i class="fas fa-bars"></i>
            </div>
         </div>
      </div>
   </header>

   <!-- Hero Section -->
   <section class="hero">
      <div class="container">
         <div class="hero-content">
            <h1>Discover Your Next Favorite Read</h1>
            <p>Explore our curated collection of books from around the world. From bestsellers to hidden gems, we have something for every reader.</p>
            <div class="hero-buttons">
               <a href="shop.php" class="btn">Browse Collection</a>
               <a href="#" class="btn btn-outline">Join Book Club</a>
            </div>
         </div>
      </div>
   </section>

   <!-- Featured Products -->
   <section class="featured-products">
      <div class="container">
         <h2 class="section-title">Latest Arrivals</h2>
         <div class="products-grid">
            <?php
            if(mysqli_num_rows($select_products) > 0){
               while($fetch_product = mysqli_fetch_assoc($select_products)){
            ?>
            <form action="" method="post" class="product-card">
               <img src="uploaded_img/<?php echo $fetch_product['image']; ?>" alt="<?php echo $fetch_product['name']; ?>" class="product-image">
               <div class="product-info">
                  <h3 class="product-title"><?php echo $fetch_product['name']; ?></h3>
                  <p class="product-author">by <?php echo $fetch_product['author']; ?></p>
                  <div class="product-meta">
                     <span><?php echo $fetch_product['genre']; ?></span>
                     <?php if(!empty($fetch_product['location'])): ?>
                        <span><i class="fas fa-map-marker-alt"></i> <?php echo $fetch_product['location']; ?></span>
                     <?php endif; ?>
                  </div>
                  <div class="product-price">Rs. <?php echo $fetch_product['price']; ?>/-</div>
                  <div class="product-actions">
                     <input type="number" min="1" name="product_quantity" value="1" class="quantity-input">
                     <input type="hidden" name="product_name" value="<?php echo $fetch_product['name']; ?>">
                     <input type="hidden" name="product_price" value="<?php echo $fetch_product['price']; ?>">
                     <input type="hidden" name="product_image" value="<?php echo $fetch_product['image']; ?>">
                     <input type="submit" value="Add to Cart" name="add_to_cart" class="btn">
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

   <!-- Features -->
   <section class="features">
      <div class="container">
         <h2 class="section-title">Why Choose BookNook</h2>
         <div class="features-grid">
            <div class="feature-card">
               <div class="feature-icon">
                  <i class="fas fa-shipping-fast"></i>
               </div>
               <h3 class="feature-title">Free Shipping</h3>
               <p class="feature-text">Free delivery on orders over Rs. 1000. Fast and reliable shipping to your doorstep.</p>
            </div>
            
            <div class="feature-card">
               <div class="feature-icon">
                  <i class="fas fa-undo-alt"></i>
               </div>
               <h3 class="feature-title">Easy Returns</h3>
               <p class="feature-text">Not satisfied? Return your books within 30 days for a full refund.</p>
            </div>
            
            <div class="feature-card">
               <div class="feature-icon">
                  <i class="fas fa-headset"></i>
               </div>
               <h3 class="feature-title">24/7 Support</h3>
               <p class="feature-text">Our customer service team is always here to help with any questions.</p>
            </div>
            
            <div class="feature-card">
               <div class="feature-icon">
                  <i class="fas fa-award"></i>
               </div>
               <h3 class="feature-title">Curated Selection</h3>
               <p class="feature-text">Hand-picked books by our expert team to ensure quality and diversity.</p>
            </div>
         </div>
      </div>
   </section>

   <!-- Recommended For You -->
   <section class="recommended">
      <div class="container">
         <h2 class="section-title">Recommended For You</h2>
         <div class="products-grid">
            <?php if (!empty($recommended_books)): ?>
               <?php foreach ($recommended_books as $book): ?>
                  <form action="" method="post" class="product-card">
                     <img src="uploaded_img/<?php echo $book['image']; ?>" alt="<?php echo $book['name']; ?>" class="product-image">
                     <div class="product-info">
                        <h3 class="product-title"><?php echo $book['name']; ?></h3>
                        <p class="product-author">by <?php echo $book['author']; ?></p>
                        <div class="product-meta">
                           <span><?php echo $book['genre']; ?></span>
                           <?php if(!empty($book['location'])): ?>
                              <span><i class="fas fa-map-marker-alt"></i> <?php echo $book['location']; ?></span>
                           <?php endif; ?>
                        </div>
                        <div class="product-price">Rs. <?php echo $book['price']; ?>/-</div>
                        <div class="product-actions">
                           <input type="number" min="1" name="product_quantity" value="1" class="quantity-input">
                           <input type="hidden" name="product_name" value="<?php echo $book['name']; ?>">
                           <input type="hidden" name="product_price" value="<?php echo $book['price']; ?>">
                           <input type="hidden" name="product_image" value="<?php echo $book['image']; ?>">
                           <input type="submit" value="Add to Cart" name="add_to_cart" class="btn">
                        </div>
                     </div>
                  </form>
               <?php endforeach; ?>
            <?php else: ?>
               <div class="empty-recommendations">
                  <p>No personalized recommendations available yet.</p>
                  <p>Start browsing our collection to get personalized book suggestions!</p>
                  <a href="shop.php" class="btn">Browse Books</a>
               </div>
            <?php endif; ?>
         </div>
      </div>
   </section>

   <!-- Newsletter -->
   <section class="newsletter">
      <div class="container">
         <h2>Join Our Reading Community</h2>
         <p>Subscribe to our newsletter for exclusive deals, new releases, and reading recommendations delivered to your inbox.</p>
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

      // Add smooth scrolling for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
         anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
               target.scrollIntoView({
                  behavior: 'smooth',
                  block: 'start'
               });
            }
         });
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
   </script>
</body>
</html>