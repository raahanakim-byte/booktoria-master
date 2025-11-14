<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'config.php';
include 'recommendations.php';

$user_id = $_SESSION['user_id'] ?? null;
$message = [];

// Validate and get book ID
$book_id = $_GET['id'] ?? null;
if (!$book_id || !is_numeric($book_id)) {
    header("Location: shop.php");
    exit;
}

// Fetch book info
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: shop.php");
    exit;
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_quantity = (int)$_POST['product_quantity'];

    if ($user_id) {
        // Logged-in user: DB cart
        $check = mysqli_query($conn, "SELECT * FROM cart WHERE name = '{$book['name']}' AND user_id = '$user_id'");
        if (mysqli_num_rows($check) > 0) {
            $message[] = 'Already added to cart!';
        } else {
            mysqli_query($conn, "INSERT INTO cart(user_id, name, price, quantity, image) VALUES('$user_id','{$book['name']}','{$book['price']}','$product_quantity','{$book['image']}')");
            $message[] = 'Product added to cart!';
        }
    } else {
        // Guest cart: session
        if (!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];
        if (isset($_SESSION['guest_cart'][$book_id])) {
            $message[] = 'Already in cart!';
        } else {
            $_SESSION['guest_cart'][$book_id] = [
                'id' => $book_id,
                'name' => $book['name'],
                'price' => $book['price'],
                'image' => $book['image'],
                'quantity' => $product_quantity,
                'type' => 'normal'
            ];
            $message[] = 'Added to cart (guest mode)!';
        }
    }
}

// Recommended books (for logged-in users)
$recommended_books = [];
if ($user_id) {
    $recommended_books = getRecommendedBooks($conn, $user_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($book['name']) ?> | Booktoria</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/home.css">
<link rel="stylesheet" href="css/sidebar.css">
<link rel="stylesheet" href="css/cart.css">
</head>
<body>
<?php include 'header.php'; ?>

<div class="main-content">
    <!-- Messages -->
    <?php foreach($message as $msg): ?>
        <div class="message">
            <span><?= htmlspecialchars($msg) ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
        </div>
    <?php endforeach; ?>

    <section class="view-book">
        <div class="container">
            <div class="book-card">
                <div class="book-image">
                    <img src="uploaded_img/<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['name']) ?>">
                </div>
                <div class="book-details">
                    <h1><?= htmlspecialchars($book['name']) ?></h1>
                    <p class="author">by <?= htmlspecialchars($book['author']) ?></p>
                    <p class="genre">Genre: <?= htmlspecialchars($book['genre']) ?></p>
                    <?php if(!empty($book['location'])): ?>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($book['location']) ?></p>
                    <?php endif; ?>
                    <p class="price">Price: Rs. <?= number_format($book['price']) ?>/-</p>
                    <p class="stock">Available: <?= $book['stock'] ?> pcs</p>

                    <form method="post" class="add-to-cart-form">
                        <label for="product_quantity">Quantity:</label>
                        <input type="number" min="1" max="<?= $book['stock'] ?>" name="product_quantity" value="1" class="quantity-input">
                        <button type="submit" name="add_to_cart" class="btn btn-primary">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php if($user_id && !empty($recommended_books)): ?>
    <section class="recommended">
        <div class="container">
            <h2 class="section-title">Recommended For You</h2>
            <div class="products-grid">
                <?php foreach ($recommended_books as $rbook): ?>
                    <form action="" method="post" class="product-card">
                        <div class="card-image">
                            <img src="uploaded_img/<?= $rbook['image'] ?>" alt="<?= $rbook['name'] ?>">
                        </div>
                        <div class="card-body">
                            <h3><?= $rbook['name'] ?></h3>
                            <p class="author">by <?= $rbook['author'] ?></p>
                            <p class="genre"><?= $rbook['genre'] ?></p>
                            <div class="product-actions">
                                <input type="hidden" name="product_quantity" value="1">
                                <input type="hidden" name="product_name" value="<?= $rbook['name'] ?>">
                                <input type="hidden" name="product_price" value="<?= $rbook['price'] ?>">
                                <input type="hidden" name="product_image" value="<?= $rbook['image'] ?>">
                                <button type="submit" name="add_to_cart" class="btn-cart">Add to Cart</button>
                                <a href="view_book.php?id=<?= $rbook['id'] ?>" class="btn btn-outline">View</a>
                            </div>
                        </div>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</div>
</body>
</html>
