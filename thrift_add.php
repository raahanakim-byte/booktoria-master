<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('location:my_thrift.php');
    exit;
}

// Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM thrift_products WHERE id = $delete_id AND user_id = $user_id");
    header('location:my_thrift.php');
    exit;
}

// Handle add thrift item form submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $genre = $_POST['genre'] ?? '';
    $price = $_POST['price'] ?? '';
    $condition = $_POST['condition'] ?? '';
    $location = $_POST['location'] ?? '';

    // Image upload
    $image = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    $upload_dir = 'uploaded_thrift_img/';
    $target_path = $upload_dir . basename($image);

    if (move_uploaded_file($image_tmp, $target_path)) {
        $stmt = $conn->prepare("INSERT INTO thrift_products (user_id, title, author, genre, price, `condition`, location, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdsss", $user_id, $title, $author, $genre, $price, $condition, $location, $image);

        if ($stmt->execute()) {
            $success = "Thrift item added successfully!";
        } else {
            $error = "Error: " . $stmt->error;
        }
    } else {
        $error = "Failed to upload image.";
    }
}

// Fetch thrift products for this user
$select_products = mysqli_query($conn, "SELECT * FROM thrift_products WHERE user_id = $user_id ORDER BY posted_on DESC") or die('Query failed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Thrift Items</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">

    <style>
        /* Layout: form and list side by side */
        .container {
            display: flex;
            gap: 40px;
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .form-container {
            flex: 1;
            min-width: 350px;
            background: #f8f8f8;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
            height: fit-content;
        }
        .form-container h2 {
            text-align: center;
        }
        form input, form select, form textarea {
            display: block;
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        form input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            padding: 10px 0;
            transition: background-color 0.3s ease;
        }
        form input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .message {
            text-align: center;
            color: green;
            margin-bottom: 15px;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .list-container {
            flex: 2;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .product-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            background: #fff;
            box-shadow: 0 2px 5px rgb(0 0 0 / 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .product-card img.thrift-image {
            max-width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .product-card h3 {
            margin: 10px 0 5px;
        }
        .product-card p {
            margin: 3px 0;
            font-size: 0.9rem;
        }
        .product-card small {
            color: #666;
            margin-top: 5px;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 6px 14px;
            margin: 5px 3px 0 0;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }

        .empty {
            grid-column: 1 / -1;
            text-align: center;
            font-size: 1.1rem;
            color: #555;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <div class="form-container">
        <h2>Add Thrift Book</h2>

        <?php if ($success) echo "<p class='message'>$success</p>"; ?>
        <?php if ($error) echo "<p class='error'>$error</p>"; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Book Title" required>
            <input type="text" name="author" placeholder="Author" required>
            <input type="text" name="genre" placeholder="Genre" required>
            <input type="number" name="price" placeholder="Price" required>
            <select name="condition" required>
                <option value="">Select Condition</option>
                <option value="new">New</option>
                <option value="like new">Like New</option>
                <option value="good">Good</option>
                <option value="acceptable">Acceptable</option>
            </select>
            <input type="text" name="location" placeholder="Your Location" required>
            <input type="file" name="image" accept="image/*" required>
            <input type="submit" name="submit" value="Add Thrift Item">
        </form>
    </div>

    <div class="list-container">
        <h1>My Thrift Items</h1>
        <?php if (mysqli_num_rows($select_products) > 0): ?>
            <?php while ($product = mysqli_fetch_assoc($select_products)): ?>
                <div class="product-card">
                    <img src="uploaded_thrift_img/<?php echo htmlspecialchars($product['image']); ?>" alt="Book Image" class="thrift-image">
                    <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                    <p><strong>Author:</strong> <?php echo htmlspecialchars($product['author']); ?></p>
                    <p><strong>Genre:</strong> <?php echo htmlspecialchars($product['genre']); ?></p>
                    <p><strong>Condition:</strong> <?php echo htmlspecialchars($product['condition']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($product['location']); ?></p>
                    <p><strong>Price:</strong> Rs.<?php echo htmlspecialchars($product['price']); ?></p>
                    <small><em>Posted on: <?php echo date("F j, Y, g:i a", strtotime($product['posted_on'])); ?></em></small>
                    <br>
                    <a href="edit_thrift.php?id=<?php echo $product['id']; ?>" class="btn">Edit</a>
                    <a href="my_thrift.php?delete=<?php echo $product['id']; ?>" class="btn" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty">You have not added any thrift items yet!</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
