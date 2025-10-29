<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

// Fix: Assign session user_id before checking it
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
   header('location:login.php');
   exit();
}

if (isset($_POST['add_to_cart'])) {
   $thrift_id = $_POST['thrift_id'];

   // Fetch thrift book details
   $check_thrift = mysqli_query($conn, "SELECT * FROM thrift_products WHERE id = '$thrift_id'");
   if (mysqli_num_rows($check_thrift) > 0) {
      $thrift = mysqli_fetch_assoc($check_thrift);

      // Check if already in cart
      $already_carted = mysqli_query($conn, "SELECT * FROM cart WHERE thrift_id = '$thrift_id' AND user_id = '$user_id'");
      if (mysqli_num_rows($already_carted) == 0) {
         $name = $thrift['title'];
         $price = $thrift['price'];
         $image = $thrift['image'];

         $insert = mysqli_query($conn, "INSERT INTO cart(user_id, name, price, quantity, image, type, thrift_id)
            VALUES('$user_id', '$name', '$price', 1, '$image', 'thrift', '$thrift_id')");

         if ($insert) {
            echo "✅ Thrift book added to cart!";
         } else {
            echo "❌ Failed to add to cart.";
         }

      } else {
         echo "⚠️ Already in cart!";
      }
   } else {
      echo "❌ Invalid thrift book!";
   }
} else {
   echo "❌ Invalid access.";
}
?>
