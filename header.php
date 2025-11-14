<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
include 'config.php';

$user_id = $_SESSION['user_id'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="home.php" class="logo">
            <i class="fas fa-book-open"></i>
            <span>Booktoria</span>
        </a>

        <div class="sidebar-cart">
            <a href="cart.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" id="sidebar-cart-count">
                    <?php 
                        $cart_count = 0;
                        if ($user_id) {
                            $res = mysqli_query($conn, "SELECT * FROM cart WHERE user_id='$user_id'");
                            $cart_count = mysqli_num_rows($res);
                        }
                        echo $cart_count;
                    ?>
                </span>
            </a>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="home.php" class="nav-item <?= $current_page == 'home.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i><span>Home</span>
        </a>
        <a href="shop.php" class="nav-item <?= $current_page == 'shop.php' ? 'active' : '' ?>">
            <i class="fas fa-store"></i><span>Store</span>
        </a>
        <a href="thrift_list.php" class="nav-item <?= $current_page == 'thrift_list.php' ? 'active' : '' ?>">
            <i class="fas fa-recycle"></i><span>Thrift Books</span>
        </a>
        <a href="orders.php" class="nav-item <?= $current_page == 'orders.php' ? 'active' : '' ?>">
            <i class="fas fa-box"></i><span>Orders</span>
        </a>
        <a href="contact.php" class="nav-item <?= $current_page == 'contact.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i><span>Contact</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <?php if($user_id): ?>
                <div class="user-welcome">
                    <i class="fas fa-user-circle"></i>
                    <div class="user-details">
                        <span class="welcome-text">Welcome back!</span>
                        <strong class="username"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></strong>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="auth-btn login-btn">
                        <i class="fas fa-sign-in-alt"></i><span>Login</span>
                    </a>
                    <a href="register.php" class="auth-btn register-btn">
                        <i class="fas fa-user-plus"></i><span>Register</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</aside>

<!-- Mobile Toggle & Overlay -->
<div id="menu-toggle" class="mobile-menu-toggle">
    <i class="fas fa-bars"></i>
</div>
<div class="sidebar-overlay"></div>

<!-- JS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const menuToggle = document.getElementById('menu-toggle');
    const overlay = document.querySelector('.sidebar-overlay');

    // Mobile sidebar toggle
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-open');
        overlay.classList.toggle('active');
    });

    // Close sidebar when overlay clicked
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('sidebar-open');
        overlay.classList.remove('active');
    });

    // Fetch and update cart count
    async function updateCartCount() {
        try {
            const response = await fetch('cart_count.php');
            const data = await response.json();
            document.getElementById('sidebar-cart-count').textContent = data.count;
        } catch(err) {
            console.error('Error fetching cart count:', err);
        }
    }

    // Initial fetch + periodic updates
    updateCartCount();
    setInterval(updateCartCount, 5000);
});
</script>
