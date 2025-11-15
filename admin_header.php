<?php
if(isset($message)){
    foreach($message as $msg){
        $type = 'info';
        if(strpos(strtolower($msg), 'error') !== false) $type = 'error';
        if(strpos(strtolower($msg), 'success') !== false) $type = 'success';
        if(strpos(strtolower($msg), 'warning') !== false) $type = 'warning';
        
        echo '
        <div class="admin-message '.$type.'">
            <div class="message-content">
                <i class="fas '.($type == 'success' ? 'fa-check-circle' : ($type == 'error' ? 'fa-exclamation-circle' : ($type == 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'))).'"></i>
                <span>'.$msg.'</span>
            </div>
            <button class="message-close" onclick="this.parentElement.remove();">
                <i class="fas fa-times"></i>
            </button>
        </div>
        ';
    }
}
?>
<div class="admin-header-wrapper">
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <a href="admin_page.php" class="logo">
            <div class="logo-icon">
                <i class="fas fa-cogs"></i>
            </div>
            <span class="logo-text">ADMIN PANEL</span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php 
        $current_page = basename($_SERVER['PHP_SELF']); 
        $nav_items = [
            'admin_page.php' => ['icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
            'admin_products.php' => ['icon' => 'fas fa-box', 'text' => 'Products'],
            'admin_orders.php' => ['icon' => 'fas fa-shopping-cart', 'text' => 'Orders'],
            'admin_users.php' => ['icon' => 'fas fa-users', 'text' => 'Users'],
            'admin_contacts.php' => ['icon' => 'fas fa-envelope', 'text' => 'Queries']
        ];
        ?>
        
        <?php foreach($nav_items as $page => $item): ?>
            <a href="<?= $page ?>" class="nav-item <?= $current_page == $page ? 'active' : '' ?>" title="<?= $item['text'] ?>">
                <i class="<?= $item['icon'] ?>"></i>
                <span class="nav-text"><?= $item['text'] ?></span>
                <?php if($current_page == $page): ?>
                    <div class="active-indicator"></div>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="user-details">
                <span class="user-name">Admin</span>
                <span class="user-role">Administrator</span>
            </div>
        </div>
        <a href="logout.php" class="logout-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
            <span class="logout-text">Logout</span>
        </a>
    </div>
</aside>
</div>

<!-- Mobile Toggle -->
<div id="menu-toggle" class="mobile-menu-toggle">
    <i class="fas fa-bars"></i>
</div>
<div class="sidebar-overlay"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggle = document.getElementById('menu-toggle');
    const overlay = document.querySelector('.sidebar-overlay');
    const body = document.body;

    // Sidebar toggle functionality
    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        toggle.classList.toggle('active');
        body.classList.toggle('sidebar-open');
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        toggle.classList.remove('active');
        body.classList.remove('sidebar-open');
    }

    toggle?.addEventListener('click', toggleSidebar);
    overlay?.addEventListener('click', closeSidebar);

    // Auto-dismiss messages with improved animation
    document.querySelectorAll('.admin-message').forEach(msg => {
        setTimeout(() => {
            if (msg.parentNode) {
                msg.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => msg.remove(), 300);
            }
        }, 5000);

        const closeBtn = msg.querySelector('.message-close');
        closeBtn?.addEventListener('click', function(){
            this.closest('.admin-message').style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => this.closest('.admin-message').remove(), 300);
        });
    });

    // Close sidebar on mobile nav click
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
            if(window.innerWidth <= 768){
                closeSidebar();
            }
        });
    });

    // Close sidebar with Escape key
    document.addEventListener('keydown', e => {
        if(e.key === 'Escape'){
            closeSidebar();
        }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });

    // Add hover effects for desktop
    if (window.innerWidth > 768) {
        sidebar.classList.add('desktop-hover');
    }
});
</script>