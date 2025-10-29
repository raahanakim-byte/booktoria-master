<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('location:login.php');
    exit;
}

$item_id = intval($_GET['id'] ?? 0);
if ($item_id <= 0) {
    header('location:my_thrift.php');
    exit;
}

// Fetch the existing thrift item details, make sure it belongs to user
$query = mysqli_query($conn, "SELECT * FROM thrift_products WHERE id = $item_id AND user_id = $user_id");
if (mysqli_num_rows($query) == 0) {
    header('location:my_thrift.php');
    exit;
}
$product = mysqli_fetch_assoc($query);

$message = [];

if (isset($_POST['update'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
    $price = floatval($_POST['price']);
    $condition = mysqli_real_escape_string($conn, $_POST['condition']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    $image = $product['image']; // default keep old image
    if (!empty($_FILES['image']['name'])) {
        $new_image = $_FILES['image']['name'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = 'uploaded_thrift_img/'.$new_image;
        if (move_uploaded_file($image_tmp_name, $image_folder)) {
            $image = $new_image;
            // Optionally, delete old image file here if you want
        } else {
            $message[] = 'Failed to upload new image!';
        }
    }

    if (empty($message)) {
        $update_query = "UPDATE thrift_products SET
            title = '$title',
            author = '$author',
            genre = '$genre',
            price = '$price',
            `condition`= '$condition',
            location = '$location',
            image = '$image'
            WHERE id = $item_id AND user_id = $user_id";

        if (mysqli_query($conn, $update_query)) {
            $message[] = 'Thrift item updated successfully!';
            // Refresh the product info to show updated data
            $query = mysqli_query($conn, "SELECT * FROM thrift_products WHERE id = $item_id AND user_id = $user_id");
            $product = mysqli_fetch_assoc($query);
        } else {
            $message[] = 'Failed to update thrift item: ' . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Thrift Item</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'header.php'; ?>

<?php

if (!isset($message) || !is_array($message)) {
    $message = [];
}
if (!empty($message)) {
    var_dump($message);

    foreach ($message as $msg) {
        echo '<div class="message"><span>' . htmlspecialchars($msg) . '</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
    }
}
?>

<div class="form-container">
    <form action="" method="post" enctype="multipart/form-data">
        <h3>Edit Thrift Book</h3>
        <input type="text" name="title" placeholder="Book Title" required class="box" value="<?php echo htmlspecialchars($product['title']); ?>">
        <input type="text" name="author" placeholder="Author" required class="box" value="<?php echo htmlspecialchars($product['author']); ?>">
        <input type="text" name="genre" placeholder="Genre" required class="box" value="<?php echo htmlspecialchars($product['genre']); ?>">
        <input type="number" name="price" placeholder="Price" min="1" step="0.01" required class="box" value="<?php echo htmlspecialchars($product['price']); ?>">
        <input type="text" name="condition" placeholder="Condition (Good, Used, etc.)" required class="box" value="<?php echo htmlspecialchars($product['condition']); ?>">
        <input type="text" name="location" placeholder="Your Location" required class="box" value="<?php echo htmlspecialchars($product['location']); ?>">
        <p>Current Image:</p>
        <img src="uploaded_thrift_img/<?php echo htmlspecialchars($product['image']); ?>" alt="Current Image" style="max-width: 200px;">
        <input type="file" name="image" accept="image/*" class="box">
        <input type="submit" name="update" value="Update Thrift Item" class="btn">
    </form>
</div>

<?php include 'footer.php'; ?>

</body>
</html>
