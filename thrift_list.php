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

/* ======================================================
   THRIFT STOCK VALIDATION FUNCTION
====================================================== */
function checkThriftStockBeforeAdd($conn, $thrift_id, $quantity) {
    $thrift_id = mysqli_real_escape_string($conn, $thrift_id);
    $stock_check = mysqli_query($conn, "SELECT stock, status FROM thrift_products WHERE id = '$thrift_id'");
    
    if(mysqli_num_rows($stock_check) > 0) {
        $product = mysqli_fetch_assoc($stock_check);
        $current_stock = $product['stock'];
        $status = $product['status'];
        
        // Check if product is available
        if($status !== 'available') {
            return 'This book is no longer available!';
        }
        
        if($current_stock <= 0) {
            return 'This thrift book is out of stock!';
        }
        
        if($quantity > $current_stock) {
            return "Only $current_stock items available!";
        }
        
        return true;
    }
    
    return 'Thrift book not found!';
}

// Handle add-to-cart
if (isset($_POST['add_to_cart'])) {
    $thrift_id = $_POST['thrift_id'];

    // Fetch thrift book details
    $check_thrift = mysqli_query($conn, "SELECT * FROM thrift_products WHERE id = '$thrift_id'") or die('query failed');
    if (mysqli_num_rows($check_thrift) > 0) {
        $thrift = mysqli_fetch_assoc($check_thrift);

        // FIRST: CHECK STOCK AVAILABILITY
        $stock_check = checkThriftStockBeforeAdd($conn, $thrift_id, 1);
        
        if($stock_check !== true) {
            $message[] = $stock_check;
        } else {
            // Check if already in cart
            $already_carted = mysqli_query($conn, "SELECT * FROM cart WHERE thrift_id = '$thrift_id' AND user_id = '$user_id'") or die('query failed');
            
            if (mysqli_num_rows($already_carted) > 0) {
                // ✅ CHECK IF THIS IS SECOND ATTEMPT
                if(isset($_SESSION['thrift_duplicate_attempt'][$thrift_id])) {
                    // Second attempt - REDIRECT TO CART
                    $_SESSION['info_message'] = 'This thrift book is already in your cart!';
                    unset($_SESSION['thrift_duplicate_attempt'][$thrift_id]); // Clear the flag
                    header('location: cart.php');
                    exit;
                } else {
                    // First attempt - SHOW MESSAGE
                    $_SESSION['thrift_duplicate_attempt'][$thrift_id] = true; // Set flag
                    $message[] = 'This thrift book is already in your cart! Click "Add to Cart" again to view your cart.';
                }
            } else {
                // New item - ADD TO CART
                $name = $thrift['title'];
                $price = $thrift['price'];
                $image = $thrift['image'];

                $insert = mysqli_query($conn, "INSERT INTO cart(user_id, name, price, quantity, image, type, thrift_id)
                    VALUES('$user_id', '$name', '$price', 1, '$image', 'thrift', '$thrift_id')") or die('query failed');

                if ($insert) {
                    $message[] = 'Thrift book added to cart!';
                    
                    // Clear any duplicate flags for this product
                    if(isset($_SESSION['thrift_duplicate_attempt'][$thrift_id])) {
                        unset($_SESSION['thrift_duplicate_attempt'][$thrift_id]);
                    }
                } else {
                    $message[] = 'Failed to add to cart.';
                }
            }
        }
    } else {
        $message[] = 'Invalid thrift book!';
    }
}

// Fetch thrift products with stock and status check
$select_products = mysqli_query($conn, "SELECT * FROM thrift_products WHERE status = 'available' ORDER BY posted_on DESC") or die('Query failed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booktoria - Thrift Books</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/shop.css">
    <link rel="stylesheet" href="css/thrift.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <style>
        /* Stock Management Styles */
        .thrift-stock-info {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 8px 0;
            font-size: 14px;
        }
        
        .thrift-stock-info.out-of-stock {
            color: #dc3545;
            font-weight: bold;
        }
        
        .thrift-stock-info.low-stock {
            color: #ffc107;
            font-weight: bold;
        }
        
        .thrift-stock-info.in-stock {
            color: #28a745;
        }
        
        .btn-thrift:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .thrift-stock-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        
        .thrift-badge-out-of-stock {
            background-color: #dc3545;
            color: white;
        }
        
        .thrift-badge-low-stock {
            background-color: #ffc107;
            color: black;
        }
        
        .thrift-badge-sold {
            background-color: #6c757d;
            color: white;
        }
        
        .thrift-image-container {
            position: relative;
        }
    </style>
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

   <?php include "header.php"; ?>

   <!-- Main Content -->
   <main class="main-content">
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
               <div class="thrift-top-actions">
                  <a href="add_thrift_book.php" class="btn btn-add-thrift">
                     <i class="fas fa-plus-circle"></i> Sell Your Book
                  </a>
               </div>
               <div class="breadcrumb-stats">
                  <div class="breadcrumb-stat">
                     <i class="fas fa-recycle"></i>
                     <span><?php echo mysqli_num_rows($select_products); ?> Pre-loved Books Available</span>
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
                       // Determine stock status
                       $current_stock = $product['stock'] ?? 1; // Default to 1 for thrift items
                       $status = $product['status'] ?? 'available';
                       
                       $is_out_of_stock = $current_stock <= 0 || $status !== 'available';
                       $is_low_stock = $current_stock > 0 && $current_stock <= 2;
                       $stock_class = $is_out_of_stock ? 'out-of-stock' : ($is_low_stock ? 'low-stock' : 'in-stock');
                       $stock_message = $is_out_of_stock ? 'Not Available' : ($is_low_stock ? "Only $current_stock left!" : "Available");
               ?>
               <form action="" method="post" class="thrift-card">
                  <div class="thrift-badge">
                     <i class="fas fa-recycle"></i>
                     Pre-loved
                  </div>
                  
                  <div class="thrift-image-container">
                     <img src="uploaded_thrift_img/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" class="thrift-image">
                     
                     <?php if($is_out_of_stock): ?>
                        <div class="thrift-stock-badge thrift-badge-out-of-stock">Sold Out</div>
                     <?php elseif($is_low_stock): ?>
                        <div class="thrift-stock-badge thrift-badge-low-stock">Low Stock</div>
                     <?php endif; ?>
                     
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
                     
                     <div class="thrift-stock-info <?php echo $stock_class; ?>">
                        <i class="fas fa-cubes"></i>
                        <span><?php echo $stock_message; ?></span>
                     </div>
                     
                     <div class="thrift-price">
                        <span class="price">Rs. <?php echo htmlspecialchars($product['price']); ?></span>
                        <span class="price-note">(Pre-loved)</span>
                     </div>
                     
                     <div class="thrift-actions">
                        <input type="hidden" name="thrift_id" value="<?php echo $product['id']; ?>">
                        <button type="submit" name="add_to_cart" class="btn btn-thrift" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>
                           <i class="fas fa-cart-plus"></i>
                           <?php echo $is_out_of_stock ? 'Not Available' : 'Add to Cart'; ?>
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
                      <a href="add_thrift_book.php" class="btn btn-secondary">Sell Your Books</a>
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
   </main>

   <script>
      // Mobile menu toggle
      const toggle = document.getElementById('menu-toggle');
      if(toggle){
          toggle.addEventListener('click', () => {
              document.querySelector('.sidebar').classList.toggle('active');
          });
      }

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

      // Disable form submission for out-of-stock items
      document.querySelectorAll('form.thrift-card').forEach(form => {
         const submitBtn = form.querySelector('button[type="submit"]');
         if(submitBtn.disabled) {
            form.addEventListener('submit', (e) => {
               e.preventDefault();
            });
         }
      });
   </script>
</body>
</html>