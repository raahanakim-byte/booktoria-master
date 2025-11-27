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

/* ======================================================
   STOCK VALIDATION FUNCTION
====================================================== */
function checkStockBeforeAdd($conn, $product_name, $quantity) {
    $product_name = mysqli_real_escape_string($conn, $product_name);
    $stock_check = mysqli_query($conn, "SELECT stock FROM products WHERE name = '$product_name'");
    
    if(mysqli_num_rows($stock_check) > 0) {
        $product = mysqli_fetch_assoc($stock_check);
        $current_stock = $product['stock'];
        
        if($current_stock <= 0) {
            return 'Product is out of stock!';
        }
        
        if($quantity > $current_stock) {
            return "Only $current_stock items available in stock!";
        }
        
        return true;
    }
    
    return 'Product not found!';
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $product_quantity = (int)$_POST['product_quantity'];

    // ✅ CHECK IF USER IS LOGGED IN - REDIRECT TO LOGIN IF NOT
    if (!$user_id) {
        $_SESSION['redirect_after_login'] = "view_book.php?id=" . $book_id; // Remember where they came from
        header('location: login.php');
        exit;
    }

    // FIRST: CHECK STOCK AVAILABILITY
    $stock_check = checkStockBeforeAdd($conn, $book['name'], $product_quantity);
    
    if($stock_check !== true) {
        $message[] = $stock_check;
    } else {
        // Logged-in user: DB cart
        $check = mysqli_query($conn, "SELECT * FROM cart WHERE name = '{$book['name']}' AND user_id = '$user_id'");
        if (mysqli_num_rows($check) > 0) {
            // ✅ CHECK IF THIS IS SECOND ATTEMPT
            if(isset($_SESSION['duplicate_attempt'][$book['name']])) {
                // Second attempt - REDIRECT TO CART
                $_SESSION['info_message'] = 'This book is already in your cart!';
                unset($_SESSION['duplicate_attempt'][$book['name']]); // Clear the flag
                header('location: cart.php');
                exit;
            } else {
                // First attempt - SHOW MESSAGE
                $_SESSION['duplicate_attempt'][$book['name']] = true; // Set flag
                $message[] = 'This book is already in your cart! Click "Add to Cart" again to view your cart.';
            }
        } else {
            mysqli_query($conn, "INSERT INTO cart(user_id, name, price, quantity, image) VALUES('$user_id','{$book['name']}','{$book['price']}','$product_quantity','{$book['image']}')");
            $message[] = 'Product added to cart!';
            
            // Clear any duplicate flags for this book
            if(isset($_SESSION['duplicate_attempt'][$book['name']])) {
                unset($_SESSION['duplicate_attempt'][$book['name']]);
            }
        }
    }
}

// Recommended books (for logged-in users)
$recommended_books = [];
if ($user_id) {
    $recommended_books = getRecommendedBooks($conn, $user_id);
}

// Get current stock for display
$current_stock = $book['stock'];
$is_out_of_stock = $current_stock <= 0;
$is_low_stock = $current_stock > 0 && $current_stock <= 5;
$stock_class = $is_out_of_stock ? 'out-of-stock' : ($is_low_stock ? 'low-stock' : 'in-stock');
$stock_message = $is_out_of_stock ? 'Out of Stock' : ($is_low_stock ? "Only $current_stock left!" : "$current_stock in stock");
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
<style>
    .stock-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 10px 0;
        font-size: 16px;
        font-weight: 500;
    }
    
    .stock-info.out-of-stock {
        color: #dc3545;
    }
    
    .stock-info.low-stock {
        color: #ffc107;
    }
    
    .stock-info.in-stock {
        color: #28a745;
    }
    
    .btn-primary:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .stock-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
        margin-left: 10px;
    }
    
    .badge-out-of-stock {
        background-color: #dc3545;
        color: white;
    }
    
    .badge-low-stock {
        background-color: #ffc107;
        color: black;
    }
    
    .quantity-input:disabled {
        background-color: #e9ecef;
        cursor: not-allowed;
    }
    
    .view-book {
        padding: 2rem 0;
    }
    
    .book-card {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 3rem;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 2rem;
        margin-bottom: 3rem;
    }
    
    .book-image img {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .book-details h1 {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        color: #2c3e50;
    }
    
    .book-details .author {
        font-size: 1.4rem;
        color: #7f8c8d;
        margin-bottom: 1rem;
        font-style: italic;
    }
    
    .book-details .genre,
    .book-details .location,
    .book-details .price {
        font-size: 1.1rem;
        margin-bottom: 0.8rem;
        color: #555;
    }
    
    .add-to-cart-form {
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 2px solid #ecf0f1;
    }
    
    .add-to-cart-form label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .quantity-input {
        width: 80px;
        padding: 8px 12px;
        border: 2px solid #bdc3c7;
        border-radius: 6px;
        font-size: 16px;
        margin-right: 1rem;
    }
    
    .recommended .product-card {
        margin-bottom: 1rem;
    }
</style>
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
                    
                    <div class="stock-info <?= $stock_class ?>">
                        <i class="fas fa-cubes"></i>
                        <span><?= $stock_message ?></span>
                        <?php if($is_out_of_stock): ?>
                            <span class="stock-badge badge-out-of-stock">Out of Stock</span>
                        <?php elseif($is_low_stock): ?>
                            <span class="stock-badge badge-low-stock">Low Stock</span>
                        <?php endif; ?>
                    </div>

                    <form method="post" class="add-to-cart-form">
                        <label for="product_quantity">Quantity:</label>
                        <input type="number" 
                               min="1" 
                               max="<?= $current_stock ?>" 
                               name="product_quantity" 
                               value="1" 
                               class="quantity-input"
                               <?= $is_out_of_stock ? 'disabled' : '' ?>>
                        <button type="submit" 
                                name="add_to_cart" 
                                class="btn btn-primary"
                                <?= $is_out_of_stock ? 'disabled' : '' ?>>
                            <i class="fas fa-cart-plus"></i> 
                            <?= $is_out_of_stock ? 'Out of Stock' : 'Add to Cart' ?>
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
                    <?php
                    // Get stock info for recommended books
                    $rbook_stock_query = mysqli_query($conn, "SELECT stock FROM products WHERE name = '{$rbook['name']}'");
                    $rbook_stock_data = mysqli_fetch_assoc($rbook_stock_query);
                    $rbook_current_stock = $rbook_stock_data['stock'] ?? 0;
                    $rbook_is_out_of_stock = $rbook_current_stock <= 0;
                    ?>
                    <form action="" method="post" class="product-card">
                        <div class="card-image">
                            <img src="uploaded_img/<?= $rbook['image'] ?>" alt="<?= $rbook['name'] ?>">
                            <?php if($rbook_is_out_of_stock): ?>
                                <div class="stock-badge badge-out-of-stock">Out of Stock</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3><?= $rbook['name'] ?></h3>
                            <p class="author">by <?= $rbook['author'] ?></p>
                            <p class="genre"><?= $rbook['genre'] ?></p>
                            <div class="stock-info <?= $rbook_is_out_of_stock ? 'out-of-stock' : 'in-stock' ?>">
                                <i class="fas fa-cubes"></i>
                                <span><?= $rbook_is_out_of_stock ? 'Out of Stock' : $rbook_current_stock . ' in stock' ?></span>
                            </div>
                            <div class="product-actions">
                                <input type="hidden" name="product_quantity" value="1">
                                <input type="hidden" name="product_name" value="<?= $rbook['name'] ?>">
                                <input type="hidden" name="product_price" value="<?= $rbook['price'] ?>">
                                <input type="hidden" name="product_image" value="<?= $rbook['image'] ?>">
                                <button type="submit" 
                                        name="add_to_cart" 
                                        class="btn-cart"
                                        <?= $rbook_is_out_of_stock ? 'disabled' : '' ?>>
                                    <?= $rbook_is_out_of_stock ? 'Out of Stock' : 'Add to Cart' ?>
                                </button>
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

<script>
    // Message auto-remove
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 300);
        }, 5000);
    });

    // Quantity input validation
    const quantityInput = document.querySelector('.quantity-input');
    const addToCartBtn = document.querySelector('.btn-primary');
    
    if(quantityInput && addToCartBtn) {
        quantityInput.addEventListener('change', function() {
            const maxStock = parseInt(this.getAttribute('max'));
            const currentValue = parseInt(this.value);
            
            if(currentValue > maxStock) {
                this.value = maxStock;
                alert(`Only ${maxStock} items available in stock!`);
            }
            
            if(currentValue < 1) {
                this.value = 1;
            }
        });
    }
</script>
</body>
</html>