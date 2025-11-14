<?php

include 'config.php'
session_start();

if(!isset($_SESSION['user_id'])) {
    echo "login_required";
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

// 2️⃣ Get product info and stock
$stmt = $conn->prepare("SELECT name, price, image, stock FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if(!$product) {
    echo "invalid_product";
    exit;
}

if($product['stock'] <= 0) {
    echo "out_of_stock";
    exit;
}

// 3️⃣ Check if item already in cart
$check_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND name = ?");
$check_cart->bind_param("is", $user_id, $product['name']);
$check_cart->execute();
$cart_result = $check_cart->get_result();

if($cart_result->num_rows > 0) {
    // Already in cart → update quantity (if stock allows)
    $cart_row = $cart_result->fetch_assoc();
    if($cart_row['quantity'] < $product['stock']) {
        $update_cart = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND name = ?");
        $update_cart->bind_param("is", $user_id, $product['name']);
        $update_cart->execute();
        echo "updated";
    } else {
        echo "stock_limit";
    }
} else {
    // Not in cart → insert
    $insert = $conn->prepare("INSERT INTO cart (user_id, name, price, quantity, image, type) VALUES (?, ?, ?, 1, ?, 'normal')");
    $insert->bind_param("isis", $user_id, $product['name'], $product['price'], $product['image']);
    $insert->execute();
    echo "added";
}
?>

