<?php
if(isset($message)){
    foreach($message as $msg){
        echo '
        <div class="admin-message">
            <span>'.$msg.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
        </div>
        ';
    }
}
?>

<aside class="admin-sidebar">
    <div class="sidebar-header">
        <a href="admin_page.php" class="logo">
            <i class="fas fa-cogs"></i>
            <span>AdminPanel</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <a href="admin_page.php" class="nav-item <?= $current_page == 'admin_page.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>
        <a href="admin_products.php" class="nav-item <?= $current_page == 'admin_products.php' ? 'active' : '' ?>">
            <i class="fas fa-box"></i><span>Products</span>
        </a>
        <a href="admin_orders.php" class="nav-item <?= $current_page == 'admin_orders.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i><span>Orders</span>
        </a>
        <a href="admin_users.php" class="nav-item <?= $current_page == 'admin_users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i><span>Users</span>
        </a>
        <a href="admin_contacts.php" class="nav-item <?= $current_page == 'admin_contacts.php' ? 'active' : '' ?>">
            <i class="fas fa-envelope"></i><span>Messages</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="account-info">
            <p><i class="fas fa-user"></i> <strong><?= $_SESSION['admin_name']; ?></strong></p>
            <p><i class="fas fa-envelope"></i> <?= $_SESSION['admin_email']; ?></p>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>

<!-- Mobile Toggle -->
<div id="menu-toggle" class="mobile-menu-toggle">
    <i class="fas fa-bars" id="bars"></i>
</div>
<div class="sidebar-overlay"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggle = document.getElementById('menu-toggle');
    const overlay = document.querySelector('.sidebar-overlay');
    
    // Sidebar toggle functionality
    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            toggle.classList.toggle('active');
        });
        
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            toggle.classList.remove('active');
        });
    }
    
    // Enhanced message handling with auto-dismiss
    const messages = document.querySelectorAll('.admin-message');
    messages.forEach(message => {
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (message.parentNode) {
                message.classList.add('hiding');
                setTimeout(() => {
                    if (message.parentNode) {
                        message.remove();
                    }
                }, 300);
            }
        }, 5000);
        
        // Improved close button functionality
        const closeBtn = message.querySelector('.fa-times');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                this.parentElement.classList.add('hiding');
                setTimeout(() => {
                    if (this.parentElement.parentNode) {
                        this.parentElement.remove();
                    }
                }, 300);
            });
        }
    });
    
    // Close sidebar when clicking on a nav item on mobile
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                toggle.classList.remove('active');
            }
        });
    });
    
    // Close sidebar when pressing Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            toggle.classList.remove('active');
        }
    });
});
</script>
