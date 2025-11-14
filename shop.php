   <?php
   include 'config.php';
   include 'recommendations.php';

   session_start();

   $user_id = $_SESSION['user_id'];
   $recommended_books = getRecommendedBooks($conn, $user_id);

   if(!isset($user_id)){
      header('location:login.php');
   }



   if(isset($_POST['add_to_cart'])){

      $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
      $product_price = mysqli_real_escape_string($conn, $_POST['product_price']);
      $product_image = mysqli_real_escape_string($conn, $_POST['product_image']);
      $product_quantity = (int)$_POST['product_quantity'];

      $check_cart_numbers = mysqli_query($conn, "SELECT * FROM `cart` WHERE name = '$product_name' AND user_id = '$user_id'") or die('query failed');

      if(mysqli_num_rows($check_cart_numbers) > 0){
         $message[] = 'already added to cart!';
      }else{
         mysqli_query($conn, "INSERT INTO `cart`(user_id, name, price, quantity, image) VALUES('$user_id', '$product_name', '$product_price', '$product_quantity', '$product_image')") or die('query failed');
         $message[] = 'product added to cart!';
      }

   }

   // Get unique genres from database
   $genre_query = mysqli_query($conn, "SELECT DISTINCT genre FROM `products` ORDER BY genre") or die('query failed');
   $genres = [];
   while($genre_row = mysqli_fetch_assoc($genre_query)) {
      $genres[] = $genre_row['genre'];
   }

   // Handle filters and sorting
   $selected_genre = isset($_GET['genre']) ? mysqli_real_escape_string($conn, $_GET['genre']) : '';
   $sort_by = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'newest';
   $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

   // Pagination settings
   $limit = 12; // Number of products per page
   $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
   if ($page < 1) $page = 1;
   $offset = ($page - 1) * $limit;

   // Build the base query with proper escaping
   $query = "SELECT * FROM `products` WHERE 1=1";
   $count_query = "SELECT COUNT(*) as total FROM `products` WHERE 1=1";

   // Add search filter if provided
   if (!empty($search)) {
      $search_clean = mysqli_real_escape_string($conn, $search);
      $search_condition = " AND (name LIKE '%$search_clean%' OR author LIKE '%$search_clean%' OR genre LIKE '%$search_clean%')";
      $query .= $search_condition;
      $count_query .= $search_condition;
   }

   // Add genre filter if selected
   if (!empty($selected_genre)) {
      $genre_clean = mysqli_real_escape_string($conn, $selected_genre);
      $query .= " AND genre = '$genre_clean'";
      $count_query .= " AND genre = '$genre_clean'";
   }

   // Add sorting
   switch($sort_by) {
      case 'price-low':
         $query .= " ORDER BY price ASC";
         break;
      case 'price-high':
         $query .= " ORDER BY price DESC";
         break;
      case 'name':
         $query .= " ORDER BY name ASC";
         break;
      case 'newest':
      default:
         $query .= " ORDER BY id DESC";
         break;
   }

   // Add pagination
   $query .= " LIMIT $limit OFFSET $offset";

   // Get total count for pagination
   $count_result = mysqli_query($conn, $count_query);
   if (!$count_result) {
      die('Count query failed: ' . mysqli_error($conn));
   }
   $count_data = mysqli_fetch_assoc($count_result);
   $total_count = $count_data['total'];
   $total_pages = ceil($total_count / $limit);

   // Ensure page is within valid range
   if ($page > $total_pages && $total_pages > 0) {
      $page = $total_pages;
      $offset = ($page - 1) * $limit;
      // Rebuild query with corrected page
      $query = preg_replace('/LIMIT \d+ OFFSET \d+/', "LIMIT $limit OFFSET $offset", $query);
   }

   // Execute main query
   $select_products = mysqli_query($conn, $query);
   if (!$select_products) {
      die('Main query failed: ' . mysqli_error($conn));
   }

   // Build URL parameters for pagination
   function buildUrlParams($params) {
      $current_params = $_GET;
      unset($current_params['page']); // Remove page from current params
      $merged_params = array_merge($current_params, $params);
      return http_build_query($merged_params);
   }

   ?>

   <!DOCTYPE html>
   <html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Booktoria - Shop</title>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <link rel="stylesheet" href="css/home.css">
      <link rel="stylesheet" href="css/shop.css">
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
               <span>Booktoria</span>
            </a>
            
            <nav id="main-nav">
               <ul>
                  <li><a href="home.php">Home</a></li>
                  <li><a href="shop.php" class="active">Shop</a></li>
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

      <!-- Breadcrumb Section -->
      <section class="breadcrumb">
         <div class="container">
            <div class="breadcrumb-content">
               <h1>Our Book Collection</h1>
               <div class="breadcrumb-nav">
                  <a href="home.php">
                     <i class="fas fa-home"></i> Home
                  </a>
                  <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                  <span class="breadcrumb-current">Shop</span>
                  <?php if ($selected_genre): ?>
                     <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
                     <span class="breadcrumb-current"><?php echo htmlspecialchars($selected_genre); ?></span>
                  <?php endif; ?>
               </div>
               <div class="breadcrumb-stats">
                  <div class="breadcrumb-stat">
                     <i class="fas fa-book"></i>
                     <span><?php echo $total_count; ?> Books Total</span>
                  </div>
                  <?php if ($selected_genre): ?>
                     <div class="breadcrumb-stat">
                        <i class="fas fa-tag"></i>
                        <span><?php echo htmlspecialchars($selected_genre); ?></span>
                     </div>
                  <?php endif; ?>
                  <?php if (!empty($search)): ?>
                     <div class="breadcrumb-stat">
                        <i class="fas fa-search"></i>
                        <span>Search: "<?php echo htmlspecialchars($search); ?>"</span>
                     </div>
                  <?php endif; ?>
               </div>
            </div>
         </div>
      </section>

      <!-- All Products Section -->
      <section class="featured-products">
         <div class="container">
            <h2 class="section-title">
               <?php 
               if ($selected_genre) {
                  echo htmlspecialchars($selected_genre) . ' Books';
               } elseif (!empty($search)) {
                  echo 'Search Results';
               } else {
                  echo 'All Books';
               }
               ?>
               <span class="product-count">(Showing <?php echo mysqli_num_rows($select_products); ?> of <?php echo $total_count; ?> books)</span>
            </h2>
            
            <!-- Search and Filter Section -->
            <div class="shop-controls">
               <form method="GET" action="" class="filter-form">
                  <input type="hidden" name="page" value="1"> <!-- Reset to page 1 on filter -->
                  
                  <div class="search-box">
                     <input type="text" name="search" placeholder="Search by title, author, or genre..." value="<?php echo htmlspecialchars($search); ?>">
                     <button type="submit" class="search-btn" title="Search">
                        <i class="fas fa-search"></i>
                     </button>
                  </div>
                  
                  <div class="filter-options">
                     <div class="filter-group">
                        <div class="filter-label">Genre</div>
                        <select name="genre" onchange="this.form.submit()">
                           <option value="">All Genres</option>
                           <?php foreach($genres as $genre): ?>
                              <option value="<?php echo htmlspecialchars($genre); ?>" <?php echo $selected_genre == $genre ? 'selected' : ''; ?>>
                                 <?php echo htmlspecialchars($genre); ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     
                     <div class="filter-group">
                        <div class="filter-label">Sort By</div>
                        <select name="sort" onchange="this.form.submit()">
                           <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                           <option value="price-low" <?php echo $sort_by == 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                           <option value="price-high" <?php echo $sort_by == 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                           <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                        </select>
                     </div>
                  </div>
                  
                  <?php if ($selected_genre || $sort_by != 'newest' || !empty($search)): ?>
                  <div class="active-filters">
                     <?php if ($selected_genre): ?>
                        <div class="active-filter-tag">
                           <span>Genre: <?php echo htmlspecialchars($selected_genre); ?></span>
                           <a href="?<?php echo buildUrlParams(['genre' => '']); ?>" class="remove-filter" title="Remove genre filter">
                              <i class="fas fa-times"></i>
                           </a>
                        </div>
                     <?php endif; ?>
                     
                     <?php if (!empty($search)): ?>
                        <div class="active-filter-tag">
                           <span>Search: "<?php echo htmlspecialchars($search); ?>"</span>
                           <a href="?<?php echo buildUrlParams(['search' => '']); ?>" class="remove-filter" title="Clear search">
                              <i class="fas fa-times"></i>
                           </a>
                        </div>
                     <?php endif; ?>
                     
                     <?php if ($sort_by != 'newest'): ?>
                        <div class="active-filter-tag">
                           <span>Sort: 
                              <?php 
                              switch($sort_by) {
                                 case 'price-low': echo 'Price: Low to High'; break;
                                 case 'price-high': echo 'Price: High to Low'; break;
                                 case 'name': echo 'Name: A to Z'; break;
                                 default: echo 'Newest First';
                              }
                              ?>
                           </span>
                           <a href="?<?php echo buildUrlParams(['sort' => 'newest']); ?>" class="remove-filter" title="Reset sorting">
                              <i class="fas fa-times"></i>
                           </a>
                        </div>
                     <?php endif; ?>
                     
                     <a href="shop.php" class="clear-filters">
                        <i class="fas fa-times-circle"></i>
                        Clear All
                     </a>
                  </div>
                  <?php endif; ?>
               </form>
            </div>

            <!-- Quick Genre Filters -->
            <div class="genre-filters">
               <h3>Browse by Genre</h3>
               <div class="genre-tags">
                  <a href="shop.php" class="genre-tag <?php echo empty($selected_genre) ? 'active' : ''; ?>">All</a>
                  <?php foreach($genres as $genre): ?>
                     <a href="?<?php echo buildUrlParams(['genre' => $genre]); ?>" class="genre-tag <?php echo $selected_genre == $genre ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($genre); ?>
                     </a>
                  <?php endforeach; ?>
               </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
               <?php  
               if(mysqli_num_rows($select_products) > 0){
                  while($fetch_products = mysqli_fetch_assoc($select_products)){
               ?>
               <form class="product-card add-to-cart-form">
                  <img src="uploaded_img/<?php echo htmlspecialchars($fetch_products['image']); ?>" alt="<?php echo htmlspecialchars($fetch_products['name']); ?>" class="product-image">
                  <div class="product-info">
                     <h3 class="product-title"><?php echo htmlspecialchars($fetch_products['name']); ?></h3>
                     <p class="product-author">by <?php echo htmlspecialchars($fetch_products['author']); ?></p>
                     <div class="product-meta">
                        <span class="genre-tag"><?php echo htmlspecialchars($fetch_products['genre']); ?></span>
                        <?php if(!empty($fetch_products['location'])): ?>
                           <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($fetch_products['location']); ?></span>
                        <?php endif; ?>
                     </div>
                     <div class="product-price">Rs. <?php echo htmlspecialchars($fetch_products['price']); ?>/-</div>
                     <div class="product-actions">
                        <div name="product_quantity" class="quantity-input">
                     <div class="breadcrumb-stat">
                           <i class="fas fa-book"></i>
                           <span><?php
                              $product_name = mysqli_real_escape_string($conn, $fetch_products['name']);
                              $bookQuantity = mysqli_query($conn, "SELECT COUNT(*) AS total_books FROM `products` WHERE `name` = '$product_name'");
                              $row = mysqli_fetch_assoc($bookQuantity);
                              $totalBooks = $row['total_books'];
                              echo $totalBooks;
                              ?>
                              
                              </span>
                        </div>
                        </div>
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($fetch_products['name']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo htmlspecialchars($fetch_products['price']); ?>">
                        <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($fetch_products['image']); ?>">
                       <input type="submit" value="Add to Cart" name="add_to_cart" class="btn add-to-cart-btn">
                     </div>
                  </div>
               </form>
               <?php
                  }
               } else {
                  echo '<div class="empty-state"><p>No products found matching your criteria.</p><a href="shop.php" class="btn">Clear Filters</a></div>';
               }
               ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
               <?php if ($page > 1): ?>
                  <a href="?<?php echo buildUrlParams(['page' => $page - 1]); ?>" class="pagination-btn">
                     <i class="fas fa-chevron-left"></i> Previous
                  </a>
               <?php endif; ?>

               <div class="pagination-numbers">
                  <?php
                  $start_page = max(1, $page - 2);
                  $end_page = min($total_pages, $page + 2);
                  
                  if ($start_page > 1) {
                     echo '<a href="?' . buildUrlParams(['page' => 1]) . '" class="pagination-number">1</a>';
                     if ($start_page > 2) echo '<span class="pagination-ellipsis">...</span>';
                  }
                  
                  for ($i = $start_page; $i <= $end_page; $i++) {
                     $active_class = $i == $page ? 'active' : '';
                     echo '<a href="?' . buildUrlParams(['page' => $i]) . '" class="pagination-number ' . $active_class . '">' . $i . '</a>';
                  }
                  
                  if ($end_page < $total_pages) {
                     if ($end_page < $total_pages - 1) echo '<span class="pagination-ellipsis">...</span>';
                     echo '<a href="?' . buildUrlParams(['page' => $total_pages]) . '" class="pagination-number">' . $total_pages . '</a>';
                  }
                  ?>
               </div>

               <?php if ($page < $total_pages): ?>
                  <a href="?<?php echo buildUrlParams(['page' => $page + 1]); ?>" class="pagination-btn">
                     Next <i class="fas fa-chevron-right"></i>
                  </a>
               <?php endif; ?>
            </div>
            <?php endif; ?>
         </div>
      </section>

      <!-- Newsletter -->
      <section class="newsletter">
         <div class="container">
            <h2>Stay Updated</h2>
            <p>Get notified about new arrivals, exclusive deals, and reading recommendations.</p>
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
                  <div class="social-links">
                     <a href="#"><i class="fab fa-facebook-f"></i></a>
                     <a href="#"><i class="fab fa-twitter"></i></a>
                     <a href="#"><i class="fab fa-instagram"></i></a>
                     <a href="#"><i class="fab fa-pinterest"></i></a>
                  </div>
               </div>
               
               <div class="footer-column">
                  <h3>Shop</h3>
                  <ul class="footer-links">
                     <li><a href="shop.php">All Books</a></li>
                     <?php foreach($genres as $genre): ?>
                        <li><a href="?<?php echo buildUrlParams(['genre' => $genre]); ?>"><?php echo htmlspecialchars($genre); ?></a></li>
                     <?php endforeach; ?>
                     <li><a href="thrift_list.php">Thrift Books</a></li>
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

         document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('.add-to-cart-form');

  forms.forEach(form => {
    const button = form.querySelector('.add-to-cart-btn');
    button.addEventListener('click', () => {
      const formData = new FormData();
      formData.append('name', form.querySelector('[name="product_name"]').value);
      formData.append('price', form.querySelector('[name="product_price"]').value);
      formData.append('image', form.querySelector('[name="product_image"]').value);
      formData.append('quantity', form.querySelector('[name="product_quantity"]').value);

      fetch('add_to_cart.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        const messageBox = document.createElement('div');
        messageBox.className = 'message ' + (data.status === 'success' ? 'success' : 'error');
        messageBox.innerHTML = `<span>${data.message}</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i>`;
        document.body.appendChild(messageBox);
        setTimeout(() => messageBox.remove(), 4000);
      })
      .catch(err => console.error('AJAX error:', err));
    });
  });
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
               setTimeout(() => {
                  message.remove();
               }, 300);
            }, 5000);
         });
      </script>
   </body>
   </html>