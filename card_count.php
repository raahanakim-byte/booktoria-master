<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$count = 0;

if ($user_id) {
    $res = mysqli_query($conn, "SELECT * FROM cart WHERE user_id='$user_id'");
    $count = mysqli_num_rows($res);
}

echo json_encode(['count' => $count]);
