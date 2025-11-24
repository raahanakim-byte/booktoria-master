<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
   exit;
}

// Enhanced security - prevent deletion of current admin
if(isset($_GET['delete'])){
   $delete_id = mysqli_real_escape_string($conn, $_GET['delete']);
   
   // Prevent self-deletion
   if($delete_id == $admin_id) {
      $_SESSION['error_message'] = "You cannot delete your own account!";
      header('location:admin_users.php');
      exit;
   }
   
   // Check if user exists before deletion
   $check_user = mysqli_query($conn, "SELECT * FROM `users` WHERE id = '$delete_id'");
   if(mysqli_num_rows($check_user) > 0) {
      mysqli_query($conn, "DELETE FROM `users` WHERE id = '$delete_id'") or die('query failed');
      $_SESSION['success_message'] = "User deleted successfully!";
   } else {
      $_SESSION['error_message'] = "User not found!";
   }
   
   header('location:admin_users.php');
   exit;
}

// Add user functionality
if(isset($_POST['add_user'])){
   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $password = mysqli_real_escape_string($conn, md5($_POST['password']));
   $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
   
   // Validate inputs
   if(empty($name) || empty($email) || empty($_POST['password'])) {
      $_SESSION['error_message'] = "All fields are required!";
   } else {
      // Check if email already exists
      $check_email = mysqli_query($conn, "SELECT email FROM `users` WHERE email = '$email'");
      if(mysqli_num_rows($check_email) > 0) {
         $_SESSION['error_message'] = "Email already exists!";
      } else {
         // Insert new user
         mysqli_query($conn, "INSERT INTO `users`(name, email, password, user_type) VALUES('$name', '$email', '$password', '$user_type')") or die('query failed');
         $_SESSION['success_message'] = "User added successfully!";
         header('location:admin_users.php');
         exit;
      }
   }
}

// Edit user functionality
if(isset($_POST['edit_user'])){
   $edit_id = mysqli_real_escape_string($conn, $_POST['edit_id']);
   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
   
   // Check if password is being updated
   $password_update = "";
   if(!empty($_POST['password'])) {
      $password = mysqli_real_escape_string($conn, md5($_POST['password']));
      $password_update = ", password = '$password'";
   }
   
   // Check if email is being changed to one that already exists (excluding current user)
   $check_email = mysqli_query($conn, "SELECT id FROM `users` WHERE email = '$email' AND id != '$edit_id'");
   if(mysqli_num_rows($check_email) > 0) {
      $_SESSION['error_message'] = "Email already exists!";
   } else {
      mysqli_query($conn, "UPDATE `users` SET name = '$name', email = '$email', user_type = '$user_type' $password_update WHERE id = '$edit_id'") or die('query failed');
      $_SESSION['success_message'] = "User updated successfully!";
      header('location:admin_users.php');
      exit;
   }
}

// Get user statistics
$total_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM `users`")->fetch_assoc()['total'];
$admin_users = mysqli_query($conn, "SELECT COUNT(*) as admins FROM `users` WHERE user_type = 'admin'")->fetch_assoc()['admins'];
$regular_users = mysqli_query($conn, "SELECT COUNT(*) as regular FROM `users` WHERE user_type = 'user'")->fetch_assoc()['regular'];

// Fetch users with pagination
$limit = 10; // Users per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Calculate total pages
$total_pages = ceil($total_users / $limit);

$select_users = mysqli_query($conn, "SELECT * FROM `users` ORDER BY id DESC LIMIT $limit OFFSET $offset") or die('query failed');

// Get user for editing if requested
$edit_user = null;
if(isset($_GET['edit'])) {
   $edit_id = mysqli_real_escape_string($conn, $_GET['edit']);
   $edit_user_result = mysqli_query($conn, "SELECT * FROM `users` WHERE id = '$edit_id'");
   if(mysqli_num_rows($edit_user_result) > 0) {
      $edit_user = mysqli_fetch_assoc($edit_user_result);
   }
}
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
            <div class="stat-item">
               <i class="fas fa-user-clock"></i>
            
            </div>
         </div>
      </header>

      <!-- Flash Messages -->
      <?php if(isset($_SESSION['success_message'])): ?>
         <div class="flash-message success">
            <i class="fas fa-check-circle"></i>
            <span><?php echo $_SESSION['success_message']; ?></span>
            <button class="close-flash"><i class="fas fa-times"></i></button>
         </div>
         <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>
      
      <?php if(isset($_SESSION['error_message'])): ?>
         <div class="flash-message error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $_SESSION['error_message']; ?></span>
            <button class="close-flash"><i class="fas fa-times"></i></button>
         </div>
         <?php unset($_SESSION['error_message']); ?>
      <?php endif; ?>

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
               <button id="add-user-btn" class="btn btn-primary">
                  <i class="fas fa-plus"></i> Add User
               </button>
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
                     <th>Registration Date</th>
                     <th>Actions</th>
                  </tr>
               </thead>
               <tbody>
                  <?php if(mysqli_num_rows($select_users) > 0): ?>
                     <?php while($fetch_users = mysqli_fetch_assoc($select_users)): 
                        $user_type_class = $fetch_users['user_type'] == 'admin' ? 'user-type-admin' : 'user-type-user';
                        $is_current_user = ($fetch_users['id'] == $admin_id);
                        $registration_date = date('M j, Y', strtotime($fetch_users['created_at'] ?? 'now'));
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
                        <td class="registration-date">
                           <?php echo $registration_date; ?>
                        </td>
                        <td class="user-actions">
                           <div class="action-buttons">
                              <a href="admin_users.php?edit=<?php echo $fetch_users['id']; ?>" 
                                 class="action-btn edit-btn" 
                                 title="Edit User">
                                 <i class="fas fa-edit"></i>
                              </a>
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
                        <td colspan="6" class="empty-users">
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

         <!-- Pagination -->
         <?php if($total_pages > 1): ?>
         <div class="pagination">
            <?php if($page > 1): ?>
               <a href="admin_users.php?page=<?php echo $page - 1; ?>" class="page-link prev">
                  <i class="fas fa-chevron-left"></i> Previous
               </a>
            <?php endif; ?>
            
            <div class="page-numbers">
               <?php 
               $start_page = max(1, $page - 2);
               $end_page = min($total_pages, $page + 2);
               
               for($i = $start_page; $i <= $end_page; $i++): 
                  $active_class = $i == $page ? 'active' : '';
               ?>
                  <a href="admin_users.php?page=<?php echo $i; ?>" class="page-link <?php echo $active_class; ?>">
                     <?php echo $i; ?>
                  </a>
               <?php endfor; ?>
            </div>
            
            <?php if($page < $total_pages): ?>
               <a href="admin_users.php?page=<?php echo $page + 1; ?>" class="page-link next">
                  Next <i class="fas fa-chevron-right"></i>
               </a>
            <?php endif; ?>
         </div>
         <?php endif; ?>
      </div>
   </section>
</div>

<!-- Add/Edit User Modal -->
<div id="user-modal" class="modal-overlay">
   <div class="modal-content user-modal">
      <div class="modal-header">
         <h3><i class="fas <?php echo $edit_user ? 'fa-edit' : 'fa-user-plus'; ?>"></i> 
            <?php echo $edit_user ? 'Edit User' : 'Add New User'; ?>
         </h3>
         <button class="close-btn" id="close-user-modal">
            <i class="fas fa-times"></i>
         </button>
      </div>
      <form method="POST" id="user-form">
         <div class="modal-body">
            <?php if($edit_user): ?>
               <input type="hidden" name="edit_id" value="<?php echo $edit_user['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
               <label for="name">Full Name</label>
               <input type="text" id="name" name="name" 
                      value="<?php echo $edit_user ? htmlspecialchars($edit_user['name']) : ''; ?>" 
                      required>
            </div>
            
            <div class="form-group">
               <label for="email">Email Address</label>
               <input type="email" id="email" name="email" 
                      value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" 
                      required>
            </div>
            
            <div class="form-group">
               <label for="password">Password <?php echo $edit_user ? '(Leave blank to keep current)' : ''; ?></label>
               <input type="password" id="password" name="password" <?php echo $edit_user ? '' : 'required'; ?>>
               <div class="password-toggle">
                  <i class="fas fa-eye" id="toggle-password"></i>
               </div>
            </div>
            
            <div class="form-group">
               <label for="user_type">User Type</label>
               <select id="user_type" name="user_type" required>
                  <option value="user" <?php echo ($edit_user && $edit_user['user_type'] == 'user') ? 'selected' : ''; ?>>User</option>
                  <option value="admin" <?php echo ($edit_user && $edit_user['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
               </select>
            </div>
         </div>
         <div class="modal-actions">
            <button type="submit" name="<?php echo $edit_user ? 'edit_user' : 'add_user'; ?>" class="btn btn-primary">
               <i class="fas <?php echo $edit_user ? 'fa-save' : 'fa-plus'; ?>"></i> 
               <?php echo $edit_user ? 'Update User' : 'Add User'; ?>
            </button>
            <button type="button" id="cancel-user" class="btn btn-secondary">
               <i class="fas fa-times"></i> Cancel
            </button>
         </div>
      </form>
   </div>
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
    const userModal = document.getElementById('user-modal');
    const userSearch = document.getElementById('user-search');
    const typeFilter = document.getElementById('type-filter');
    const userRows = document.querySelectorAll('.user-row');
    const addUserBtn = document.getElementById('add-user-btn');
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    const closeFlashBtns = document.querySelectorAll('.close-flash');

    // Show user modal if editing or adding
    <?php if(isset($_GET['edit']) || isset($_GET['add'])): ?>
        userModal.classList.add('active');
    <?php endif; ?>

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

    // Add user button
    if(addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            window.location.href = 'admin_users.php?add=true';
        });
    }

    // Password toggle
    if(togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Close flash messages
    closeFlashBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.flash-message').remove();
        });
    });

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
        if(closeBtn) closeBtn.addEventListener('click', () => modal.classList.remove('active'));
        if(cancelBtn) cancelBtn.addEventListener('click', () => modal.classList.remove('active'));
        modal.addEventListener('click', (e) => {
            if(e.target === modal) modal.classList.remove('active');
        });
    }

    // Setup modals
    setupModal(
        deleteModal,
        document.getElementById('close-delete-modal'),
        document.getElementById('cancel-delete')
    );
    
    setupModal(
        userModal,
        document.getElementById('close-user-modal'),
        document.getElementById('cancel-user')
    );

    // ESC key to close modals
    document.addEventListener('keydown', (e) => {
        if(e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });

    // Auto-hide flash messages after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.flash-message').forEach(msg => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 300);
        });
    }, 5000);
});
</script>

</body>
</html>