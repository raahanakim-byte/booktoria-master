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

/* ======================================================
   STOCK VALIDATION FUNCTION
====================================================== */
function checkStockBeforeAdd($conn, $product_name, $quantity) {
    $product_name = mysqli_real_escape_string($conn, $product_name);
    $stock_check = mysqli_query($conn, "SELECT stock FROM products WHERE name = '$product_name'");
    
    if(mysqli_num_rows($stock_check) > 0) {
        $product = mysqli_fetch_assoc($stock_check);
        $current_stock = $product['stock'];
        
        if($current_stock <= 0) {
            return 'Product is out of stock!';
        }
        
        if($quantity > $current_stock) {
            return "Only $current_stock items available in stock!";
        }
        
        return true;
    }
    
    return 'Product not found!';
}

// Handle add to cart - ONLY FOR LOGGED-IN USERS
if (isset($_POST['add_to_cart'])) {
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $product_quantity = (int)($_POST['product_quantity'] ?? 1);

    if ($user_id) {
        // FIRST: CHECK STOCK AVAILABILITY
        $stock_check = checkStockBeforeAdd($conn, $product_name, $product_quantity);
        
        if($stock_check !== true) {
            $message[] = $stock_check;
        } else {
            // Check if product already in cart
            $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');

            if (mysqli_num_rows($check_cart_numbers) > 0) {
                // ✅ CHECK IF THIS IS SECOND ATTEMPT
                if(isset($_SESSION['duplicate_attempt'][$product_name])) {
                    // Second attempt - REDIRECT TO CART
                    $_SESSION['info_message'] = 'This book is already in your cart!';
                    unset($_SESSION['duplicate_attempt'][$product_name]); // Clear the flag
                    header('location: cart.php');
                    exit;
                } else {
                    // First attempt - SHOW MESSAGE
                    $_SESSION['duplicate_attempt'][$product_name] = true; // Set flag
                    $message[] = 'This book is already in your cart! Click "Add to Cart" again to view your cart.';
                }
            } else {
                // New item - ADD TO CART
                mysqli_query($conn, "INSERT INTO `cart`(user_id, name, price, quantity, image) VALUES('$user_id', '$product_name', '$product_price', '$product_quantity', '$product_image')") or die('query failed');
                $message[] = 'Product added to cart!';
                
                // Clear any duplicate flags for this product
                if(isset($_SESSION['duplicate_attempt'][$product_name])) {
                    unset($_SESSION['duplicate_attempt'][$product_name]);
                }
            }
        }
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
   <style>
      .stock-info {
          display: flex;
          align-items: center;
          gap: 5px;
          margin: 8px 0;
          font-size: 14px;
      }
      
      .stock-info.out-of-stock {
          color: #dc3545;
          font-weight: bold;
      }
      
      .stock-info.low-stock {
          color: #ffc107;
          font-weight: bold;
      }
      
      .stock-info.in-stock {
          color: #28a745;
      }
      
      .btn-cart:disabled {
          background-color: #6c757d;
          cursor: not-allowed;
          opacity: 0.6;
      }
      
      .stock-badge {
          padding: 2px 8px;
          border-radius: 12px;
          font-size: 12px;
          font-weight: bold;
      }
      
      .badge-out-of-stock {
          background-color: #dc3545;
          color: white;
      }
      
      .badge-low-stock {
          background-color: #ffc107;
          color: black;
      }
   </style>
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
                     // Get current stock
                     $product_name_esc = mysqli_real_escape_string($conn, $fetch_product['name']);
                     $stock_query = mysqli_query($conn, "SELECT stock FROM products WHERE name = '$product_name_esc'");
                     $stock_data = mysqli_fetch_assoc($stock_query);
                     $current_stock = $stock_data['stock'] ?? 0;
                     
                     // Determine stock status
                     $is_out_of_stock = $current_stock <= 0;
                     $is_low_stock = $current_stock > 0 && $current_stock <= 5;
                     $stock_class = $is_out_of_stock ? 'out-of-stock' : ($is_low_stock ? 'low-stock' : 'in-stock');
                     $stock_message = $is_out_of_stock ? 'Out of Stock' : ($is_low_stock ? "Only $current_stock left!" : "$current_stock in stock");
               ?>
               <form action="" method="post" class="product-card">
                  <div class="card-image">
                     <img src="uploaded_img/<?php echo $fetch_product['image']; ?>" alt="<?php echo $fetch_product['name']; ?>">
                     <?php if($is_out_of_stock): ?>
                        <div class="stock-badge badge-out-of-stock">Out of Stock</div>
                     <?php elseif($is_low_stock): ?>
                        <div class="stock-badge badge-low-stock">Low Stock</div>
                     <?php endif; ?>
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

                     <div class="stock-info <?php echo $stock_class; ?>">
                        <i class="fas fa-cubes"></i>
                        <span><?php echo $stock_message; ?></span>
                     </div>

                     <input type="hidden" name="product_name" value="<?php echo $fetch_product['name']; ?>">
                     <input type="hidden" name="product_price" value="<?php echo $fetch_product['price']; ?>">
                     <input type="hidden" name="product_image" value="<?php echo $fetch_product['image']; ?>">
                     <input type="hidden" name="product_quantity" value="1">
                     
                     <div class="card-buttons">
                        <a href="view_book.php?id=<?php echo $fetch_product['id']; ?>" class="btn-view">View Book</a>
                     <?php if($user_id): ?>
                        <button type="submit" name="add_to_cart" class="btn-cart" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>
                           <?php echo $is_out_of_stock ? 'Out of Stock' : 'Add to Cart'; ?>
                        </button>
                     <?php else: ?>
                        <button type="button" class="btn-cart guest-add" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>
                           <?php echo $is_out_of_stock ? 'Out of Stock' : 'Add to Cart'; ?>
                        </button>
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
                  <?php
                  // Get current stock for recommended books
                  $product_name_esc = mysqli_real_escape_string($conn, $book['name']);
                  $stock_query = mysqli_query($conn, "SELECT stock FROM products WHERE name = '$product_name_esc'");
                  $stock_data = mysqli_fetch_assoc($stock_query);
                  $current_stock = $stock_data['stock'] ?? 0;
                  
                  // Determine stock status
                  $is_out_of_stock = $current_stock <= 0;
                  $is_low_stock = $current_stock > 0 && $current_stock <= 5;
                  $stock_class = $is_out_of_stock ? 'out-of-stock' : ($is_low_stock ? 'low-stock' : 'in-stock');
                  $stock_message = $is_out_of_stock ? 'Out of Stock' : ($is_low_stock ? "Only $current_stock left!" : "$current_stock in stock");
                  ?>
                  <form action="" method="post" class="product-card">
                     <div class="card-image">
                        <img src="uploaded_img/<?php echo $book['image']; ?>" alt="<?php echo $book['name']; ?>">
                        <?php if($is_out_of_stock): ?>
                           <div class="stock-badge badge-out-of-stock">Out of Stock</div>
                        <?php elseif($is_low_stock): ?>
                           <div class="stock-badge badge-low-stock">Low Stock</div>
                        <?php endif; ?>
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
                           <div class="stock-info <?php echo $stock_class; ?>">
                              <i class="fas fa-cubes"></i>
                              <span><?php echo $stock_message; ?></span>
                           </div>
                           <input type="hidden" name="product_name" value="<?php echo $book['name']; ?>">
                           <input type="hidden" name="product_price" value="<?php echo $book['price']; ?>">
                           <input type="hidden" name="product_image" value="<?php echo $book['image']; ?>">
                           <button type="submit" name="add_to_cart" class="btn-cart" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>
                              <?php echo $is_out_of_stock ? 'Out of Stock' : 'Add to Cart'; ?>
                           </button>
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