<?php
include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
   exit;
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   mysqli_query($conn, "DELETE FROM `users` WHERE id = '$delete_id'") or die('query failed');
   header('location:admin_users.php');
   exit;
}

// Get user statistics
$total_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM `users`")->fetch_assoc()['total'];
$admin_users = mysqli_query($conn, "SELECT COUNT(*) as admins FROM `users` WHERE user_type = 'admin'")->fetch_assoc()['admins'];
$regular_users = mysqli_query($conn, "SELECT COUNT(*) as regular FROM `users` WHERE user_type = 'user'")->fetch_assoc()['regular'];

// Fetch users
$select_users = mysqli_query($conn, "SELECT * FROM `users` ORDER BY id DESC") or die('query failed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>User Management - Admin Panel</title>

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">

   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/admin_header.css">
   <link rel="stylesheet" href="css/admin_users.css">

</head>
<body>
   
<?php include 'admin_header.php'; ?>

<div class="main-content">
   <section class="users-section">
      <header class="section-header">
         <h1 class="title">User Management</h1>
         <div class="user-stats">
            <div class="stat-item">
               <i class="fas fa-users"></i>
               <div>
                  <span class="stat-number"><?php echo $total_users; ?></span>
                  <span class="stat-label">Total Users</span>
               </div>
            </div>
            <div class="stat-item">
               <i class="fas fa-user-shield"></i>
               <div>
                  <span class="stat-number"><?php echo $admin_users; ?></span>
                  <span class="stat-label">Admins</span>
               </div>
            </div>
            <div class="stat-item">
               <i class="fas fa-user"></i>
               <div>
                  <span class="stat-number"><?php echo $regular_users; ?></span>
                  <span class="stat-label">Regular Users</span>
               </div>
            </div>
         </div>
      </header>

      <!-- Users Table -->
      <div class="users-container">
         <div class="table-header">
            <h3>All Users (<?php echo $total_users; ?>)</h3>
            <div class="table-actions">
               <div class="search-box">
                  <input type="text" id="user-search" placeholder="Search users...">
                  <i class="fas fa-search"></i>
               </div>
               <div class="filter-options">
                  <select id="type-filter">
                     <option value="">All Types</option>
                     <option value="admin">Admin</option>
                     <option value="user">User</option>
                  </select>
               </div>
            </div>
         </div>

         <div class="table-responsive">
            <table class="users-table">
               <thead>
                  <tr>
                     <th>User ID</th>
                     <th>User Info</th>
                     <th>Type</th>
                     <th>Status</th>
                     <th>Actions</th>
                  </tr>
               </thead>
               <tbody>
                  <?php if(mysqli_num_rows($select_users) > 0): ?>
                     <?php while($fetch_users = mysqli_fetch_assoc($select_users)): 
                        $user_type_class = $fetch_users['user_type'] == 'admin' ? 'user-type-admin' : 'user-type-user';
                        $is_current_user = ($fetch_users['id'] == $admin_id);
                     ?>
                     <tr class="user-row" data-type="<?php echo $fetch_users['user_type']; ?>">
                        <td class="user-id">#<?php echo $fetch_users['id']; ?></td>
                        <td class="user-info">
                           <div class="user-avatar">
                              <i class="fas fa-user-circle"></i>
                           </div>
                           <div class="user-details">
                              <div class="user-name"><?php echo htmlspecialchars($fetch_users['name']); ?></div>
                              <div class="user-email"><?php echo htmlspecialchars($fetch_users['email']); ?></div>
                           </div>
                        </td>
                        <td class="user-type">
                           <span class="type-badge <?php echo $user_type_class; ?>">
                              <i class="fas <?php echo $fetch_users['user_type'] == 'admin' ? 'fa-user-shield' : 'fa-user'; ?>"></i>
                              <?php echo ucfirst($fetch_users['user_type']); ?>
                           </span>
                        </td>
                        <td class="user-status">
                           <span class="status-badge <?php echo $is_current_user ? 'status-active' : 'status-inactive'; ?>">
                              <?php echo $is_current_user ? 'Current Session' : 'Inactive'; ?>
                           </span>
                        </td>
                        <td class="user-actions">
                           <div class="action-buttons">
                              <?php if(!$is_current_user): ?>
                                 <a href="admin_users.php?delete=<?php echo $fetch_users['id']; ?>" 
                                    class="action-btn delete-btn" 
                                    title="Delete User"
                                    data-user-id="<?php echo $fetch_users['id']; ?>"
                                    data-user-name="<?php echo htmlspecialchars($fetch_users['name']); ?>">
                                    <i class="fas fa-trash"></i>
                                 </a>
                              <?php else: ?>
                                 <span class="action-btn current-user-btn" title="Current User">
                                    <i class="fas fa-user-check"></i>
                                 </span>
                              <?php endif; ?>
                           </div>
                        </td>
                     </tr>
                     <?php endwhile; ?>
                  <?php else: ?>
                     <tr>
                        <td colspan="5" class="empty-users">
                           <div class="empty-state">
                              <i class="fas fa-users"></i>
                              <h4>No Users Found</h4>
                              <p>There are no users in the system yet.</p>
                           </div>
                        </td>
                     </tr>
                  <?php endif; ?>
               </tbody>
            </table>
         </div>
      </div>
   </section>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-overlay">
   <div class="modal-content delete-modal">
      <div class="modal-header">
         <h3><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
         <button class="close-btn" id="close-delete-modal">
            <i class="fas fa-times"></i>
         </button>
      </div>
      <div class="modal-body">
         <div class="delete-icon">
            <i class="fas fa-trash"></i>
         </div>
         <h4>Delete User?</h4>
         <p>Are you sure you want to delete user <strong id="delete-user-name"></strong>?</p>
         <div class="delete-details">
            <div class="detail-item">
               <span class="label">User ID:</span>
               <span class="value" id="delete-user-id"></span>
            </div>
            <div class="detail-item">
               <span class="label">Email:</span>
               <span class="value" id="delete-user-email"></span>
            </div>
         </div>
         <p class="warning-text">This action cannot be undone and will permanently remove the user account.</p>
      </div>
      <div class="modal-actions">
         <a href="#" id="confirm-delete" class="btn btn-danger">
            <i class="fas fa-trash"></i> Yes, Delete User
         </a>
         <button type="button" id="cancel-delete" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
         </button>
      </div>
   </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const deleteModal = document.getElementById('delete-modal');
    const userSearch = document.getElementById('user-search');
    const typeFilter = document.getElementById('type-filter');
    const userRows = document.querySelectorAll('.user-row');

    // Search functionality
    if(userSearch) {
        userSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            userRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Filter functionality
    if(typeFilter) {
        typeFilter.addEventListener('change', function() {
            const filterValue = this.value;
            userRows.forEach(row => {
                if(!filterValue || row.getAttribute('data-type') === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Delete user functionality
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            const userEmail = this.closest('tr').querySelector('.user-email').textContent;
            
            document.getElementById('delete-user-name').textContent = userName;
            document.getElementById('delete-user-id').textContent = '#' + userId;
            document.getElementById('delete-user-email').textContent = userEmail;
            document.getElementById('confirm-delete').href = this.href;
            
            deleteModal.classList.add('active');
        });
    });

    // Modal close functionality
    function setupModal(modal, closeBtn, cancelBtn) {
        closeBtn.addEventListener('click', () => modal.classList.remove('active'));
        cancelBtn.addEventListener('click', () => modal.classList.remove('active'));
        modal.addEventListener('click', (e) => {
            if(e.target === modal) modal.classList.remove('active');
        });
    }

    // Setup delete modal
    setupModal(
        deleteModal,
        document.getElementById('close-delete-modal'),
        document.getElementById('cancel-delete')
    );

    // ESC key to close modals
    document.addEventListener('keydown', (e) => {
        if(e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });

    // Auto-hide messages after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.admin-message').forEach(msg => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 300);
        });
    }, 5000);
});
</script>

</body>
</html>