<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

$user_id = $_SESSION['user_id'] ?? null; 

if (!isset($user_id)) {
    header('location:login.php');
    exit();
}

// Handle add-to-cart
if (isset($_POST['add_to_cart'])) {
    $thrift_id = $_POST['thrift_id'];

    // Fetch thrift book details
    $check_thrift = mysqli_query($conn, "SELECT * FROM thrift_products WHERE id = '$thrift_id'") or die('query failed');
    if (mysqli_num_rows($check_thrift) > 0) {
        $thrift = mysqli_fetch_assoc($check_thrift);

        // Check if already in cart
        $already_carted = mysqli_query($conn, "SELECT * FROM cart WHERE thrift_id = '$thrift_id' AND user_id = '$user_id'") or die('query failed');
        if (mysqli_num_rows($already_carted) > 0) {
            $message[] = '⚠️ Already in cart!';
        } else {
            $name = $thrift['title'];
            $price = $thrift['price'];
            $image = $thrift['image'];

            $insert = mysqli_query($conn, "INSERT INTO cart(user_id, name, price, quantity, image, type, thrift_id)
                VALUES('$user_id', '$name', '$price', 1, '$image', 'thrift', '$thrift_id')") or die('query failed');

            if ($insert) {
                $message[] = '✅ Thrift book added to cart!';
            } else {
                $message[] = '❌ Failed to add to cart.';
            }
        }
    } else {
        $message[] = '❌ Invalid thrift book!';
    }
}

// Fetch thrift products
$select_products = mysqli_query($conn, "SELECT * FROM thrift_products ORDER BY posted_on DESC") or die('Query failed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookNook - Thrift Books</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/thrift.css">
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
               <li><a href="thrift_list.php" class="active">Thrift Books</a></li>
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

   <!-- Breadcrumb Section -->
   <section class="breadcrumb">
      <div class="container">
         <div class="breadcrumb-content">
            <h1>Thrift Books Marketplace</h1>
            <div class="breadcrumb-nav">
               <a href="home.php">
                  <i class="fas fa-home"></i> Home
               </a>
               <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
               <span class="breadcrumb-current">Thrift Books</span>
            </div>
            <div class="breadcrumb-stats">
               <div class="breadcrumb-stat">
                  <i class="fas fa-recycle"></i>
                  <span><?php echo mysqli_num_rows($select_products); ?> Pre-loved Books</span>
               </div>
               <div class="breadcrumb-stat">
                  <i class="fas fa-leaf"></i>
                  <span>Eco-friendly Shopping</span>
               </div>
            </div>
         </div>
      </div>
   </section>

   <!-- Thrift Books Section -->
   <section class="thrift-section">
      <div class="container">
         <div class="section-header">
            <h2 class="section-title">Pre-loved Books</h2>
            <p class="section-subtitle">Discover amazing deals on quality second-hand books. Give books a second life!</p>
         </div>

         <!-- Thrift Books Grid -->
         <div class="thrift-grid">
            <?php
            if (mysqli_num_rows($select_products) > 0) {
                while ($product = mysqli_fetch_assoc($select_products)) {
            ?>
            <form action="" method="post" class="thrift-card">
               <div class="thrift-badge">
                  <i class="fas fa-recycle"></i>
                  Pre-loved
               </div>
               
               <div class="thrift-image-container">
                  <img src="uploaded_thrift_img/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="thrift-image">
                  <div class="condition-badge condition-<?php echo strtolower(str_replace(' ', '-', $product['condition'])); ?>">
                     <?php echo htmlspecialchars($product['condition']); ?>
                  </div>
               </div>
               
               <div class="thrift-info">
                  <h3 class="thrift-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                  <p class="thrift-author">by <?php echo htmlspecialchars($product['author']); ?></p>
                  
                  <div class="thrift-meta">
                     <div class="meta-item">
                        <i class="fas fa-bookmark"></i>
                        <span><?php echo htmlspecialchars($product['genre']); ?></span>
                     </div>
                     <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($product['location']); ?></span>
                     </div>
                  </div>
                  
                  <div class="thrift-price">
                     <span class="price">Rs. <?php echo htmlspecialchars($product['price']); ?></span>
                     <span class="price-note">(Pre-loved)</span>
                  </div>
                  
                  <div class="thrift-actions">
                     <input type="hidden" name="thrift_id" value="<?php echo $product['id']; ?>">
                     <button type="submit" name="add_to_cart" class="btn btn-thrift">
                        <i class="fas fa-cart-plus"></i>
                        Add to Cart
                     </button>
                  </div>
                  
                  <div class="thrift-footer">
                     <small class="posted-date">
                        <i class="far fa-clock"></i>
                        Posted <?php echo date("M j, Y", strtotime($product['posted_on'])); ?>
                     </small>
                  </div>
               </div>
            </form>
            <?php
                }
            } else {
                echo '
                <div class="empty-thrift">
                   <div class="empty-icon">
                      <i class="fas fa-book-open"></i>
                   </div>
                   <h3>No Thrift Books Available</h3>
                   <p>Be the first to list a pre-loved book and help build our thrift community!</p>
                   <a href="#" class="btn btn-secondary">Sell Your Books</a>
                </div>
                ';
            }
            ?>
         </div>

         <!-- Thrift Info Section -->
         <div class="thrift-benefits">
            <div class="benefits-grid">
               <div class="benefit-card">
                  <div class="benefit-icon">
                     <i class="fas fa-piggy-bank"></i>
                  </div>
                  <h4>Save Money</h4>
                  <p>Get quality books at a fraction of the original price</p>
               </div>
               
               <div class="benefit-card">
                  <div class="benefit-icon">
                     <i class="fas fa-leaf"></i>
                  </div>
                  <h4>Eco-Friendly</h4>
                  <p>Reduce waste by giving books a second life</p>
               </div>
               
               <div class="benefit-card">
                  <div class="benefit-icon">
                     <i class="fas fa-heart"></i>
                  </div>
                  <h4>Support Community</h4>
                  <p>Buy from fellow book lovers in your community</p>
               </div>
               
               <div class="benefit-card">
                  <div class="benefit-icon">
                     <i class="fas fa-gem"></i>
                  </div>
                  <h4>Hidden Gems</h4>
                  <p>Discover rare and out-of-print editions</p>
               </div>
            </div>
         </div>
      </div>
   </section>

   <!-- Newsletter -->
   <section class="newsletter">
      <div class="container">
         <h2>Love Thrift Books?</h2>
         <p>Subscribe to get notified when new pre-loved books are listed!</p>
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
                  <li><a href="shop.php">New Books</a></li>
                  <li><a href="thrift_list.php">Thrift Books</a></li>
                  <li><a href="shop.php?genre=Fiction">Fiction</a></li>
                  <li><a href="shop.php?genre=Non-Fiction">Non-Fiction</a></li>
                  <li><a href="shop.php">Bestsellers</a></li>
               </ul>
            </div>
            
            <div class="footer-column">
               <h3>Sell Books</h3>
               <ul class="footer-links">
                  <li><a href="#">Sell Your Books</a></li>
                  <li><a href="#">Pricing Guide</a></li>
                  <li><a href="#">Shipping Info</a></li>
                  <li><a href="#">Seller FAQ</a></li>
                  <li><a href="#">Community Guidelines</a></li>
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
            <p>&copy; 2023 BookNook. All rights reserved. | <a href="#">Thrift Books Community</a></p>
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
   </script>
</body>
</html>