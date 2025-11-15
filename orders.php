<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location:login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle cancel order
if(isset($_POST['cancel_order'])){
    $order_id = intval($_POST['order_id']);
    mysqli_query($conn, "UPDATE orders SET payment_status='cancelled' 
                         WHERE id='$order_id' AND user_id='$user_id' AND payment_status='pending'");
    $_SESSION['order_success'] = "Order #$order_id has been cancelled.";
    header('location:orders.php');
    exit;
}

// Fetch orders
$orders_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE user_id='$user_id' ORDER BY id DESC") 
                or die('Query failed: ' . mysqli_error($conn));
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

<?php if(isset($_SESSION['order_success'])): ?>
    <div class="message success">
        <span><?= htmlspecialchars($_SESSION['order_success']); ?></span>
        <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
    </div>
    <?php unset($_SESSION['order_success']); ?>
<?php endif; ?>

<?php if(mysqli_num_rows($orders_query) > 0): ?>
<div class="orders-list">

<?php while($order = mysqli_fetch_assoc($orders_query)): ?>

    <?php
    $products_clean = [];
    $order_items_json = $order['order_items'] ?? '';
    if (!empty($order_items_json)) {
        $items = json_decode($order_items_json, true);
        if(is_array($items)){
            foreach($items as $item){
                $products_clean[] = [
                    'name' => $item['name'] ?? '',
                    'qty'  => $item['quantity'] ?? 1,
                    'type' => $item['type'] ?? 'normal'
                ];
            }
        }
    }
    ?>

    <div class="order-card">
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
                <div class="order-total">Rs. <?= number_format($order['total_price']); ?>/-</div>
            </div>
        </div>

        <div class="order-body">
            <div class="detail-block">
                <h4><i class="fas fa-map-marker-alt"></i> Shipping Address</h4>
                <p><?= htmlspecialchars($order['address']); ?></p>
            </div>

            <div class="detail-block">
                <h4><i class="fas fa-book"></i> Items</h4>
                <button class="toggle-items-btn">
                    View Items <i class="fas fa-chevron-down"></i>
                </button>
                <ul class="items-list">
                    <?php foreach($products_clean as $p): ?>
                        <li>
                            <span class="item-name"><?= htmlspecialchars($p['name']); ?></span>
                            <span class="item-qty">x<?= $p['qty']; ?></span>
                            <?php if($p['type'] === 'thrift'): ?>
                                <span class="badge-thrift">Thrift</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if($order['payment_status'] === 'pending'): ?>
            <div class="detail-block">
                <form action="" method="post" onsubmit="return confirm('Cancel this order?');">
                    <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                    <button type="submit" name="cancel_order" class="btn btn-danger">Cancel Order</button>
                </form>
            </div>
            <?php endif; ?>

        </div>
    </div>

<?php endwhile; ?>

</div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-shopping-bag"></i>
        <h3>No Orders Yet</h3>
        <p>You haven't placed any orders yet.</p>
        <a href="shop.php" class="btn">Start Shopping</a>
    </div>
<?php endif; ?>

</div>
</section>
</div>

<script>
function toggleOrder(header) {
    header.parentElement.classList.toggle('expanded');
}
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
