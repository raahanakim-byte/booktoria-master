<?php
include 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in first.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$name = mysqli_real_escape_string($conn, $_POST['name']);
$price = (int)$_POST['price'];
$image = mysqli_real_escape_string($conn, $_POST['image']);
$quantity = (int)$_POST['quantity'];

$check = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id='$user_id' AND name='$name'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Already added to cart!']);
} else {
    mysqli_query($conn, "INSERT INTO `cart` (user_id, name, price, quantity, image) VALUES ('$user_id', '$name', '$price', '$quantity', '$image')") or die(mysqli_error($conn));
    echo json_encode(['status' => 'success', 'message' => 'Book added to cart!']);
}
?>
