<?php
session_start();
include 'config.php';

$user_id = $_SESSION['user_id'] ?? null;
$count = 0;

if($user_id){
    $res = mysqli_query($conn,"SELECT SUM(quantity) AS total FROM cart WHERE user_id='$user_id'");
    $row = mysqli_fetch_assoc($res);
    $count = (int)$row['total'];
}

echo json_encode(['count'=>$count]);
