<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'config.php';
include 'recommendations.php';

$user_id = $_SESSION['user_id'] ?? null;
$message = [];

// --- Handle add to cart ---
if(isset($_POST['add_to_cart'])){
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_price = $_POST['product_price'];
    $product_image = $_POST['product_image'];
    $product_quantity = (int)$_POST['product_quantity'];

    if($user_id){
        $check = mysqli_query($conn, "SELECT * FROM cart WHERE user_id='$user_id' AND name='$product_name'");
        if(mysqli_num_rows($check) > 0){
            $message[] = 'Already in cart!';
        } else {
            mysqli_query($conn, "INSERT INTO cart(user_id, name, price, quantity, image) VALUES('$user_id','$product_name','$product_price','$product_quantity','$product_image')");
            $message[] = 'Added to cart!';
        }
    } else {
        if(!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
        if(isset($_SESSION['guest_cart'][$product_id])){
            $message[] = 'Already in cart!';
        } else {
            $_SESSION['guest_cart'][$product_id] = [
                'id' => $product_id,
                'name' => $product_name,
                'price' => $product_price,
                'image' => $product_image,
                'quantity' => $product_quantity,
                'type' => 'normal'
            ];
            $message[] = 'Added to cart (guest mode)!';
        }
    }
}

// --- Handle remove single item ---
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    if($user_id){
        mysqli_query($conn,"DELETE FROM cart WHERE id='$id'");
    } else {
        unset($_SESSION['guest_cart'][$id]);
    }
    header('Location: cart.php');
    exit;
}

// --- Handle clear cart ---
if(isset($_GET['delete_all'])){
    if($user_id){
        mysqli_query($conn,"DELETE FROM cart WHERE user_id='$user_id'");
    } else {
        $_SESSION['guest_cart'] = [];
    }
    header('Location: cart.php');
    exit;
}

// --- Fetch cart items ---
$cart_items = [];
$grand_total = 0;

if($user_id){
    $result = mysqli_query($conn,"SELECT * FROM cart WHERE user_id='$user_id'");
    while($row = mysqli_fetch_assoc($result)){
        $row['sub_total'] = $row['price'] * $row['quantity'];
        $grand_total += $row['sub_total'];
        $cart_items[] = $row;
    }
} else {
    if(isset($_SESSION['guest_cart'])){
        foreach($_SESSION['guest_cart'] as $id => $item){
            $item['sub_total'] = $item['price'] * $item['quantity'];
            $grand_total += $item['sub_total'];
            $item['id'] = $id;
            $cart_items[] = $item;
        }
    }
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
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <p>Price: Rs. <?= number_format($item['price']) ?>/-</p>
                    <p>Quantity: <?= $item['quantity'] ?></p>
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
