<?php
include 'config.php';
session_start();

// Ensure user logged in
if (!isset($_SESSION['user_id'])) {
    header('location:login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch orders
$orders_query = mysqli_query(
    $conn,
    "SELECT * FROM `orders` WHERE user_id = '$user_id' ORDER BY id DESC"
) or die('Query failed: ' . mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Orders - Booktoria</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/home.css">
   <link rel="stylesheet" href="css/orders.css">
   <link rel="stylesheet" href="css/sidebar.css">
</head>

<body>

<?php include 'header.php'; ?>

<div class="main-content">

<section class="orders-section">
   <div class="container">

      <h1 class="page-title"><i class="fas fa-box"></i> My Orders</h1>

      <!-- Success Message -->
      <?php if(isset($_SESSION['order_success'])): ?>
         <div class="message success">
            <span><?= htmlspecialchars($_SESSION['order_success']); ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         <?php unset($_SESSION['order_success']); ?>
      <?php endif; ?>

      <!-- Orders Available -->
      <?php if(mysqli_num_rows($orders_query) > 0): ?>

      <div class="orders-list">

         <?php while($order = mysqli_fetch_assoc($orders_query)): ?>

            <?php
               // Parse products string into structured array
               $products_raw = explode(',', $order['total_products']);
               $products_clean = [];

               foreach ($products_raw as $p) {
                   $item = trim($p);
                   // pattern: Name (2)
                   if (preg_match('/^(.*)\((\d+)\)$/', $item, $m)) {
                       $products_clean[] = [
                           'name' => trim($m[1]),
                           'qty'  => intval($m[2])
                       ];
                   }
               }
            ?>

            <div class="order-card">

               <!-- Header -->
               <div class="order-header" onclick="toggleOrder(this)">
                  <div class="order-info">
                     <h3>Order #<?= $order['id']; ?></h3>
                     <p><i class="fas fa-calendar"></i> <?= htmlspecialchars($order['placed_on']); ?></p>
                     <p><i class="fas fa-wallet"></i> <?= ucwords(str_replace('_',' ', $order['method'])); ?></p>
                  </div>

                  <div class="order-status">
                     <span class="status-label status-<?= $order['payment_status']; ?>">
                        <?= ucfirst($order['payment_status']); ?>
                     </span>
                     <div class="order-total">Rs. <?= $order['total_price']; ?>/-</div>
                  </div>
               </div>

               <!-- Body -->
               <div class="order-body">

                  <div class="detail-block">
                     <h4><i class="fas fa-map-marker-alt"></i> Shipping Address</h4>
                     <p><?= htmlspecialchars($order['address']); ?></p>
                  </div>

                  <div class="detail-block">
                     <h4><i class="fas fa-book"></i> Items</h4>

                     <!-- Expand Button -->
                     <button class="toggle-items-btn">
                         View Items <i class="fas fa-chevron-down"></i>
                     </button>

                     <!-- Expandable List -->
                     <ul class="items-list">
                        <?php foreach ($products_clean as $p): ?>
                           <li>
                              <span class="item-name"><?= htmlspecialchars($p['name']); ?></span>
                              <span class="item-qty">x<?= $p['qty']; ?></span>
                           </li>
                        <?php endforeach; ?>
                     </ul>
                  </div>

               </div>

            </div>

         <?php endwhile; ?>

      </div>

      <?php else: ?>

         <!-- Empty State -->
         <div class="empty-state">
            <i class="fas fa-shopping-bag"></i>
            <h3>No Orders Yet</h3>
            <p>You haven't placed any orders yet.</p>
            <a href="shop.php" class="btn">Start Shopping</a>
         </div>

      <?php endif; ?>

   </div>
</section>

</div> <!-- end main content -->

<script>
// Expand order card body
function toggleOrder(header) {
    header.parentElement.classList.toggle('expanded');
}

// Expand items list
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".toggle-items-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            btn.nextElementSibling.classList.toggle("show");
        });
    });
});
</script>

</body>
</html>
