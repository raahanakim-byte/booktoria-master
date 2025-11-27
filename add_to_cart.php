<?php
include 'config.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    echo "❌ login_required";
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'];

echo "🔍 DEBUG START<br>";
echo "User ID: $user_id<br>";
echo "Product ID: $product_id<br>";

// 1️⃣ Get product info
$stmt = $conn->prepare("SELECT id, name, price, image, stock FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if(!$product) {
    echo "❌ invalid_product";
    exit;
}

echo "Product: " . $product['name'] . "<br>";
echo "Stock: " . $product['stock'] . "<br>";

// 2️⃣ Check if item already in cart
$check_cart = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND name = ?");
$check_cart->bind_param("is", $user_id, $product['name']);
$check_cart->execute();
$cart_result = $check_cart->get_result();

echo "Cart entries found: " . $cart_result->num_rows . "<br>";

if($cart_result->num_rows > 0) {
    // Product exists in cart
    $cart_row = $cart_result->fetch_assoc();
    echo "🛒 Current quantity in cart: " . $cart_row['quantity'] . "<br>";
    
    // Calculate new quantity
    $new_quantity = $cart_row['quantity'] + 1;
    echo "🔄 Trying to update to quantity: " . $new_quantity . "<br>";
    
    // Update the quantity
    $update_cart = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND name = ?");
    $update_cart->bind_param("iss", $new_quantity, $user_id, $product['name']);
    
    if($update_cart->execute()) {
        echo "✅ SUCCESS: Quantity updated to $new_quantity<br>";
        echo "updated";
    } else {
        echo "❌ UPDATE FAILED: " . $conn->error . "<br>";
        echo "update_failed";
    }
} else {
    // Product not in cart - insert new
    echo "🆕 Product not in cart - inserting new<br>";
    $insert = $conn->prepare("INSERT INTO cart (user_id, name, price, quantity, image, type) VALUES (?, ?, ?, 1, ?, 'normal')");
    $insert->bind_param("isss", $user_id, $product['name'], $product['price'], $product['image']);
    
    if($insert->execute()) {
        echo "✅ SUCCESS: New product added to cart<br>";
        echo "added";
    } else {
        echo "❌ INSERT FAILED: " . $conn->error . "<br>";
        echo "insert_failed";
    }
}

// Final verification
echo "<br>🔍 FINAL CHECK:<br>";
$final_check = $conn->prepare("SELECT name, quantity FROM cart WHERE user_id = ? AND name = ?");
$final_check->bind_param("is", $user_id, $product['name']);
$final_check->execute();
$final_result = $final_check->get_result();
$final_row = $final_result->fetch_assoc();

echo "Final quantity for '" . $final_row['name'] . "': " . $final_row['quantity'] . "<br>";
?>