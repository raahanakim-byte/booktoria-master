<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// Display info message from redirect
if(isset($_SESSION['info_message'])){
   echo '
   <div class="message info">
      <span>'.$_SESSION['info_message'].'</span>
      <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
   </div>
   ';
   unset($_SESSION['info_message']); // Clear the message after displaying
}
include 'config.php';
include 'recommendations.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = [];

/* ======================================================
   ADD TO CART (Supports normal + thrift)
====================================================== */

if(isset($_POST['add_to_cart'])){
    $product_id     = $_POST['product_id'];
    $product_name   = $_POST['product_name'];
    $product_price  = $_POST['product_price'];
    $product_image  = $_POST['product_image'];
    $product_qty    = (int)$_POST['product_quantity'];
    $type           = $_POST['type'] ?? 'normal';
    $thrift_id      = $_POST['thrift_id'] ?? null;

    // HANDLE DUPLICATES
    if($type === "thrift"){
        // Thrift: must check by thrift_id
        $check = mysqli_query($conn, 
            "SELECT * FROM cart 
             WHERE user_id='$user_id' AND type='thrift' AND thrift_id='$thrift_id'"
        );
    } else {
        // Normal: check by name
        $check = mysqli_query($conn, 
            "SELECT * FROM cart 
             WHERE user_id='$user_id' AND type='normal' AND name='$product_name'"
        );
    }

    if(mysqli_num_rows($check) > 0){
        $message[] = "Already in cart!";
    } else {
        mysqli_query($conn,
            "INSERT INTO cart(user_id,name,price,quantity,image,type,thrift_id)
             VALUES('$user_id','$product_name','$product_price','$product_qty',
                   '$product_image','$type','$thrift_id')"
        );
        $message[] = "Added to cart!";
    }
}
/* ======================================================
   UPDATE CART QUANTITY
====================================================== */
if(isset($_POST['update_qty_id'])){
    $id = $_POST['update_qty_id'];
    $qty = (int)$_POST['new_quantity'];

    if($qty > 0){
        mysqli_query($conn, 
            "UPDATE cart SET quantity='$qty' 
             WHERE id='$id' AND user_id='$user_id'"
        );
    }

    header("Location: cart.php");
    exit;
}

/* ======================================================
   DELETE SINGLE ITEM
====================================================== */
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn,"DELETE FROM cart WHERE id='$id' AND user_id='$user_id'");
    header("Location: cart.php");
    exit;
}

/* ======================================================
   CLEAR CART
====================================================== */
if(isset($_GET['delete_all'])){
    mysqli_query($conn,"DELETE FROM cart WHERE user_id='$user_id'");
    header("Location: cart.php");
    exit;
}

/* ======================================================
   FETCH CART ITEMS
====================================================== */
$cart_items = [];
$grand_total = 0;

$res = mysqli_query($conn,"SELECT * FROM cart WHERE user_id='$user_id' ORDER BY type DESC");

while($row = mysqli_fetch_assoc($res)){
    $row['sub_total'] = $row['price'] * $row['quantity'];
    $grand_total += $row['sub_total'];
    $cart_items[] = $row;
}

$cart_count = count($cart_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Booktoria - Cart</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/home.css">
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/cart.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="main-content">
    <h1>Shopping Cart</h1>

    <!-- Messages -->
    <?php foreach($message as $msg): ?>
        <div class="message">
            <span><?= htmlspecialchars($msg) ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove()"></i>
        </div>
    <?php endforeach; ?>

    <?php if($cart_count > 0): ?>
        <div class="cart-items">
            <?php foreach($cart_items as $item): ?>
            <div class="cart-item">

                <img src="uploaded_img/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-img">

                <div class="cart-details">
                    <h3>
                        <?= htmlspecialchars($item['name']) ?>
                        <?php if($item['type'] === 'thrift'): ?>
                            <span class="thrift-tag">Thrift</span>
                        <?php endif; ?>
                    </h3>

                    <p>Price: Rs. <?= number_format($item['price']) ?>/-</p>
                    <form action="cart.php" method="post" class="update-qty-form">
    <input type="hidden" name="update_qty_id" value="<?= $item['id'] ?>">

    <label>Quantity:</label>
    <input type="number" 
           name="new_quantity" 
           min="1" 
           value="<?= $item['quantity'] ?>" 
           class="quantity-input"
           onchange="this.form.submit()">
</form>

                    <p>Subtotal: Rs. <?= number_format($item['sub_total']) ?>/-</p>

                    <a href="cart.php?delete=<?= $item['id'] ?>" class="btn btn-danger">Remove</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <h2>Total: Rs. <?= number_format($grand_total) ?>/-</h2>

            <a href="cart.php?delete_all" class="btn btn-warning">Clear Cart</a>
            <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
        </div>

    <?php else: ?>
        <p>Your cart is empty. <a href="shop.php">Start shopping</a>.</p>
    <?php endif; ?>
</div>

</body>
</html>
