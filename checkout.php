<?php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if(!$user_id){
    header('location:login.php');
    exit;
}

// Fetch cart items
$cart_query = mysqli_query($conn, "SELECT * FROM cart WHERE user_id='$user_id'");
$cart_items = [];
$grand_total = 0;
$total_thrift_items = 0;
while($item = mysqli_fetch_assoc($cart_query)){
    $item['sub_total'] = $item['price'] * $item['quantity'];
    $grand_total += $item['sub_total'];
    if($item['type']==='thrift') $total_thrift_items += $item['quantity'];
    $cart_items[] = $item;
}

if(empty($cart_items)){
    header('location:cart.php');
    exit;
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, email FROM users WHERE id='$user_id'"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout - BookNook</title>
<link rel="stylesheet" href="css/home.css">
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/checkout.css">
</head>
<body>
<?php include 'header.php'; ?>

<div class="checkout-container">
    <div class="checkout-content">
        <div class="order-summary">
            <h2>Order Summary</h2>
            <?php foreach($cart_items as $item): ?>
            <div class="summary-item">
                <img src="uploaded_img/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                <div>
                    <h4><?= htmlspecialchars($item['name']) ?>
                        <?php if($item['type']==='thrift'): ?><span class="badge-thrift">Thrift</span><?php endif; ?>
                    </h4>
                    <p><?= $item['quantity'] ?> × Rs. <?= number_format($item['price']) ?></p>
                </div>
                <strong>Rs. <?= number_format($item['sub_total']) ?></strong>
            </div>
            <?php endforeach; ?>
            <div class="summary-total">Total: Rs. <?= number_format($grand_total) ?></div>
        </div>

        <form method="post" action="initiate_khalti.php" class="checkout-form">
            <h2>Shipping Details</h2>
            <label>Full Name *</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            <label>Email *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <label>Phone Number *</label>
            <input type="text" name="phone" required>
            <label>Address *</label>
            <textarea name="address" required></textarea>

            <button type="submit" name="cod" class="btn-primary">Place Order (Cash on Delivery)</button>
            <button type="submit" name="khalti" class="btn-primary">Pay with Khalti</button>
        </form>
    </div>
</div>
</body>
</html>
