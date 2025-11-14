<?php
include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
};

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   mysqli_query($conn, "DELETE FROM `message` WHERE id = '$delete_id'") or die('query failed');
   header('location:admin_contacts.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Messages - Admin Panel</title>

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">

   <!-- Custom Admin CSS -->
   <link rel="stylesheet" href="css/admin_contact.css">

</head>
<body>
   
<?php include 'admin_header.php'; ?>

<section class="messages-section">
   <div class="container">
      <div class="section-header">
         <h1 class="section-title">Customer Messages</h1>
         <p class="section-subtitle">Manage all customer inquiries and feedback</p>
      </div>

      <div class="messages-grid">
      <?php
         $select_message = mysqli_query($conn, "SELECT * FROM `message` ORDER BY id DESC") or die('query failed');
         if(mysqli_num_rows($select_message) > 0){
            while($fetch_message = mysqli_fetch_assoc($select_message)){
      ?>
         <div class="message-card">
            <div class="message-header">
               <div class="user-info">
                  <h3 class="user-name"><?php echo $fetch_message['name']; ?></h3>
                  <p class="user-email"><?php echo $fetch_message['email']; ?></p>
               </div>
               <div class="message-meta">
                  <span class="user-id">User ID: <?php echo $fetch_message['user_id']; ?></span>
                  <span class="message-date"><?php echo date('M j, Y', strtotime($fetch_message['created_at'] ?? 'now')); ?></span>
               </div>
            </div>
            
            <div class="message-content">
               <div class="contact-details">
                  <div class="detail-item">
                     <i class="fas fa-phone"></i>
                     <span><?php echo $fetch_message['number']; ?></span>
                  </div>
                  <div class="detail-item">
                     <i class="fas fa-envelope"></i>
                     <span><?php echo $fetch_message['email']; ?></span>
                  </div>
               </div>
               
               <div class="message-text">
                  <p><?php echo $fetch_message['message']; ?></p>
               </div>
            </div>
            
            <div class="message-actions">
               <a href="admin_contacts.php?delete=<?php echo $fetch_message['id']; ?>" 
                  onclick="return confirm('Are you sure you want to delete this message?');" 
                  class="btn btn-danger">
                  <i class="fas fa-trash"></i>
                  Delete Message
               </a>
            </div>
         </div>
      <?php
            };
         }else{
            echo '
            <div class="empty-state">
               <div class="empty-icon">
                  <i class="fas fa-comments"></i>
               </div>
               <h3>No Messages Yet</h3>
               <p>You haven\'t received any customer messages yet.</p>
            </div>
            ';
         }
      ?>
      </div>
   </div>
</section>

<!-- Custom Admin JS -->
<script src="js/admin_script.js"></script>

</body>
</html>