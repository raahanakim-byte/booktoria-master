<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'config.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['add_thrift'])) {

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $condition = mysqli_real_escape_string($conn, $_POST['condition']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    // ------- Handle Image Upload -------
    $image_name = $_FILES['image']['name'];
    $tmp_name = $_FILES['image']['tmp_name'];

    $folder = 'uploaded_thrift_img/' . $image_name;

  if (!empty($image_name)) {
    if (!move_uploaded_file($tmp_name, $folder)) {
        die("Upload failed! Check folder permissions.");
    }
}

    // ------- Insert into Database -------
    $insert = mysqli_query($conn, "
        INSERT INTO thrift_products (user_id, title, author, genre, price, `condition`, location, image, posted_on)
        VALUES('$user_id', '$title', '$author', '$genre', '$price', '$condition', '$location', '$image_name', NOW())
    ") or die('query failed: ' . mysqli_error($conn));

    if ($insert) {
        $message = "Thrift book added successfully!";
    } else {
        $message = "Failed to add thrift book!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Thrift Book</title>
    <link rel="stylesheet" href="css/sidebar.css">
   <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/thrift.css">

</head>
<body>
<?php include 'header.php'?>

<div class="main-content">

    <?php if(isset($message)): ?>
    <div class="message">
        <span><?= $message ?></span>
    </div>
    <?php endif; ?>

    <div class="add-thrift-container">
        <h2>Sell a Thrift Book</h2>

        <form action="" method="post" enctype="multipart/form-data" class="add-thrift-form">

            <label>Book Title</label>
            <input type="text" name="title" required>

            <label>Author</label>
            <input type="text" name="author" required>

            <label>Genre</label>
            <input type="text" name="genre" required>

            <label>Price (Rs.)</label>
            <input type="number" name="price" step="0.01" required>

            <label>Condition</label>
            <select name="condition" required>
                <option value="Like New">Like New</option>
                <option value="Very Good">Very Good</option>
                <option value="Good">Good</option>
                <option value="Fair">Fair</option>
            </select>

            <label>Location</label>
            <input type="text" name="location" required>

            <img class="image-preview" id="preview">

            <label>Upload Image</label>
            <input type="file" name="image" required>

            <button type="submit" name="add_thrift" class="btn">Add Thrift Book</button>

        </form>
    </div>

</div> <!-- END MAIN CONTENT -->

<script>
document.querySelector("input[name='image']").addEventListener("change", function(e) {
    const preview = document.querySelector(".image-preview");
    preview.style.display = "block";
    preview.src = URL.createObjectURL(e.target.files[0]);
});
</script>

</body>

</html>
