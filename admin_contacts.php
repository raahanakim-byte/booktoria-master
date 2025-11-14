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

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   mysqli_query($conn, "DELETE FROM `message` WHERE id = '$delete_id'") or die('query failed');
   header('location:admin_contacts.php');
   exit;
}

// Get message statistics
$total_messages = mysqli_query($conn, "SELECT COUNT(*) as total FROM `message`")->fetch_assoc()['total'];
// $today_messages = mysqli_query($conn, "SELECT COUNT(*) as today FROM `message` WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['today'] ?? 0;

// Fetch messages
$select_message = mysqli_query($conn, "SELECT * FROM `message` ORDER BY id DESC") or die('query failed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Contact Messages - Admin Panel</title>

   <!-- Font Awesome -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">

   <!-- Custom CSS -->
   <link rel="stylesheet" href="css/admin_header.css">
   <link rel="stylesheet" href="css/admin_contacts.css">

</head>
<body>
   
<?php include 'admin_header.php'; ?>

<div class="main-content">
   <section class="messages-section">
      <header class="section-header">
         <h1 class="title">Contact Messages</h1>
         <div class="message-stats">
            <div class="stat-item">
               <i class="fas fa-envelope"></i>
               <div>
                  <span class="stat-number"><?php echo $total_messages; ?></span>
                  <span class="stat-label">Total Messages</span>
               </div>
            </div>
            <!-- <div class="stat-item">
               <i class="fas fa-calendar-day"></i>
               <div>
                  <span class="stat-number"><?php echo $today_messages; ?></span>
                  <span class="stat-label">Today</span>
               </div>
            </div> -->
            <div class="stat-item">
               <i class="fas fa-clock"></i>
               <div>
                  <span class="stat-number"><?php echo $total_messages; ?></span>
                  <span class="stat-label">All Messages</span>
               </div>
            </div>
         </div>
      </header>

      <!-- Messages Container -->
      <div class="messages-container">
         <div class="messages-header">
            <h3>All Messages (<?php echo $total_messages; ?>)</h3>
            <div class="messages-actions">
               <div class="search-box">
                  <input type="text" id="message-search" placeholder="Search messages...">
                  <i class="fas fa-search"></i>
               </div>
               <?php if($total_messages > 0): ?>
               <button class="clear-all-btn" id="clear-all-btn">
                  <i class="fas fa-trash-alt"></i> Clear All
               </button>
               <?php endif; ?>
            </div>
         </div>

         <div class="messages-grid">
            <?php if(mysqli_num_rows($select_message) > 0): ?>
               <?php while($fetch_message = mysqli_fetch_assoc($select_message)): ?>
               <div class="message-card" data-message="<?php echo strtolower(htmlspecialchars($fetch_message['message'])); ?>">
                  <div class="message-header">
                     <div class="sender-info">
                        <div class="sender-avatar">
                           <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="sender-details">
                           <div class="sender-name"><?php echo htmlspecialchars($fetch_message['name']); ?></div>
                           <div class="sender-contact">
                              <span class="contact-email"><?php echo htmlspecialchars($fetch_message['email']); ?></span>
                              <span class="contact-separator">•</span>
                              <span class="contact-phone"><?php echo htmlspecialchars($fetch_message['number']); ?></span>
                           </div>
                        </div>
                     </div>
                     <div class="message-meta">
                        <div class="message-id">Message ID: #<?php echo $fetch_message['id']; ?></div>
                        <div class="user-id">User ID: #<?php echo $fetch_message['user_id']; ?></div>
                     </div>
                  </div>

                  <div class="message-content">
                     <p><?php echo htmlspecialchars($fetch_message['message']); ?></p>
                  </div>

                  <div class="message-actions">
                     <button class="action-btn reply-btn" title="Reply to Message" onclick="replyToMessage('<?php echo htmlspecialchars($fetch_message['email']); ?>')">
                        <i class="fas fa-reply"></i>
                     </button>
                     <button class="action-btn view-btn" title="View Full Message">
                        <i class="fas fa-expand"></i>
                     </button>
                     <a href="admin_contacts.php?delete=<?php echo $fetch_message['id']; ?>" 
                        class="action-btn delete-btn" 
                        title="Delete Message"
                        data-message-id="<?php echo $fetch_message['id']; ?>"
                        data-sender-name="<?php echo htmlspecialchars($fetch_message['name']); ?>">
                        <i class="fas fa-trash"></i>
                     </a>
                  </div>
               </div>
               <?php endwhile; ?>
            <?php else: ?>
               <div class="empty-messages">
                  <div class="empty-state">
                     <i class="fas fa-envelope-open"></i>
                     <h4>No Messages Yet</h4>
                     <p>You haven't received any contact messages yet.</p>
                  </div>
               </div>
            <?php endif; ?>
         </div>
      </div>
   </section>
</div>

<!-- Message Detail Modal -->
<div id="message-detail-modal" class="modal-overlay">
   <div class="modal-content">
      <div class="modal-header">
         <h3><i class="fas fa-envelope"></i> Message Details</h3>
         <button class="close-btn" id="close-detail-modal">
            <i class="fas fa-times"></i>
         </button>
      </div>
      <div class="modal-body" id="message-detail-content">
         <!-- Message details will be loaded here -->
      </div>
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
         <h4>Delete Message?</h4>
         <p>Are you sure you want to delete the message from <strong id="delete-sender-name"></strong>?</p>
         <div class="delete-details">
            <div class="detail-item">
               <span class="label">Message ID:</span>
               <span class="value" id="delete-message-id"></span>
            </div>
         </div>
         <p class="warning-text">This action cannot be undone.</p>
      </div>
      <div class="modal-actions">
         <a href="#" id="confirm-delete" class="btn btn-danger">
            <i class="fas fa-trash"></i> Yes, Delete Message
         </a>
         <button type="button" id="cancel-delete" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
         </button>
      </div>
   </div>
</div>

<!-- Clear All Confirmation Modal -->
<div id="clear-all-modal" class="modal-overlay">
   <div class="modal-content delete-modal">
      <div class="modal-header">
         <h3><i class="fas fa-exclamation-triangle"></i> Clear All Messages</h3>
         <button class="close-btn" id="close-clear-modal">
            <i class="fas fa-times"></i>
         </button>
      </div>
      <div class="modal-body">
         <div class="delete-icon">
            <i class="fas fa-trash-alt"></i>
         </div>
         <h4>Clear All Messages?</h4>
         <p>This will permanently delete all contact messages. This action cannot be undone.</p>
         <div class="delete-details">
            <div class="detail-item">
               <span class="label">Total Messages:</span>
               <span class="value"><?php echo $total_messages; ?></span>
            </div>
         </div>
         <p class="warning-text">All message data will be permanently lost.</p>
      </div>
      <div class="modal-actions">
         <button type="button" id="confirm-clear" class="btn btn-danger">
            <i class="fas fa-trash-alt"></i> Yes, Clear All
         </button>
         <button type="button" id="cancel-clear" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
         </button>
      </div>
   </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const messageDetailModal = document.getElementById('message-detail-modal');
    const deleteModal = document.getElementById('delete-modal');
    const clearAllModal = document.getElementById('clear-all-modal');
    const messageSearch = document.getElementById('message-search');
    const messageCards = document.querySelectorAll('.message-card');
    const clearAllBtn = document.getElementById('clear-all-btn');

    // Search functionality
    if(messageSearch) {
        messageSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            messageCards.forEach(card => {
                const messageText = card.getAttribute('data-message');
                if(messageText.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // View message details
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const messageCard = this.closest('.message-card');
            const senderName = messageCard.querySelector('.sender-name').textContent;
            const senderEmail = messageCard.querySelector('.contact-email').textContent;
            const senderPhone = messageCard.querySelector('.contact-phone').textContent;
            const messageId = messageCard.querySelector('.message-id').textContent;
            const userId = messageCard.querySelector('.user-id').textContent;
            const messageContent = messageCard.querySelector('.message-content p').textContent;

            document.getElementById('message-detail-content').innerHTML = `
                <div class="message-detail">
                    <div class="detail-header">
                        <div class="sender-avatar large">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="sender-info">
                            <h4>${senderName}</h4>
                            <div class="contact-info">
                                <span><i class="fas fa-envelope"></i> ${senderEmail}</span>
                                <span><i class="fas fa-phone"></i> ${senderPhone}</span>
                            </div>
                        </div>
                    </div>
                    <div class="detail-meta">
                        <div class="meta-item">
                            <i class="fas fa-hashtag"></i>
                            <span>${messageId}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <span>${userId}</span>
                        </div>
                    </div>
                    <div class="detail-content">
                        <h5>Message:</h5>
                        <div class="message-text">
                            <p>${messageContent}</p>
                        </div>
                    </div>
                    <div class="detail-actions">
                        <button class="btn btn-primary" onclick="replyToMessage('${senderEmail}')">
                            <i class="fas fa-reply"></i> Reply via Email
                        </button>
                        <button class="btn btn-secondary" id="close-detail">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                </div>
            `;

            // Add event listener to close button inside modal
            document.getElementById('close-detail').addEventListener('click', () => {
                messageDetailModal.classList.remove('active');
            });

            messageDetailModal.classList.add('active');
        });
    });

    // Delete message functionality
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const messageId = this.getAttribute('data-message-id');
            const senderName = this.getAttribute('data-sender-name');
            
            document.getElementById('delete-sender-name').textContent = senderName;
            document.getElementById('delete-message-id').textContent = '#' + messageId;
            document.getElementById('confirm-delete').href = this.href;
            
            deleteModal.classList.add('active');
        });
    });

    // Clear all messages
    if(clearAllBtn) {
        clearAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearAllModal.classList.add('active');
        });
    }

    // Handle clear all confirmation
    document.getElementById('confirm-clear').addEventListener('click', function() {
        // Delete all messages one by one
        const deleteButtons = document.querySelectorAll('.delete-btn');
        let deletedCount = 0;
        
        deleteButtons.forEach(btn => {
            const messageId = btn.getAttribute('data-message-id');
            fetch(`admin_contacts.php?delete=${messageId}`)
                .then(() => {
                    deletedCount++;
                    if(deletedCount === deleteButtons.length) {
                        location.reload();
                    }
                });
        });
        
        clearAllModal.classList.remove('active');
    });

    // Modal close functionality
    function setupModal(modal, closeBtn, cancelBtn = null) {
        closeBtn.addEventListener('click', () => modal.classList.remove('active'));
        if(cancelBtn) {
            cancelBtn.addEventListener('click', () => modal.classList.remove('active'));
        }
        modal.addEventListener('click', (e) => {
            if(e.target === modal) modal.classList.remove('active');
        });
    }

    // Setup modals
    setupModal(
        messageDetailModal,
        document.getElementById('close-detail-modal')
    );
    
    setupModal(
        deleteModal,
        document.getElementById('close-delete-modal'),
        document.getElementById('cancel-delete')
    );
    
    setupModal(
        clearAllModal,
        document.getElementById('close-clear-modal'),
        document.getElementById('cancel-clear')
    );

    // ESC key to close modals
    document.addEventListener('keydown', (e) => {
        if(e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
});

// Reply to message function
function replyToMessage(email) {
    window.location.href = `mailto:${email}?subject=Re: Your Message&body=Dear customer,%0D%0A%0D%0AThank you for contacting us. We have received your message and will get back to you shortly.%0D%0A%0D%0ABest regards,%0D%0AAdmin Team`;
}
</script>

</body>
</html>