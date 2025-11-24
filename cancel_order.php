<?php
include 'config.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header('location:login.php');
    exit;
}

if(isset($_POST['order_id'])){
    $order_id = intval($_POST['order_id']);
    $user_id = $_SESSION['user_id'];

    // Only cancel pending orders
    mysqli_query($conn, "UPDATE orders SET payment_status='cancelled' 
                         WHERE id='$order_id' AND user_id='$user_id' AND payment_status='pending'") 
    or die('Query failed: ' . mysqli_error($conn));

    $_SESSION['order_success'] = "Order #$order_id has been cancelled.";
}

header('location:orders.php');
exit;
?>
