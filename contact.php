<?php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
   header('location: login.php');
   exit;
}

// detect page for active nav
$current_page = basename($_SERVER['PHP_SELF']);

$message = [];

// Prefill fields if stored (optional)
$prefill_name  = $_SESSION['user_name'] ?? "";
$prefill_email = $_SESSION['user_email'] ?? "";

if (isset($_POST['send'])) {

   // Clean inputs
   $name   = trim($_POST['name']);
   $email  = trim($_POST['email']);
   $number = trim($_POST['number']);
   $msg    = trim($_POST['message']);

   // Prevent empty submit (just in case)
   if ($name === "" || $email === "" || $number === "" || $msg === "") {
      $message[] = "All fields are required.";
   } else {

      // 🔐 Prepared Statement — prevent SQL injection
      $check = $conn->prepare("
         SELECT id FROM message
         WHERE user_id = ? AND name = ? AND email = ? AND number = ? AND message = ?
      ");
      $check->bind_param("issss", $user_id, $name, $email, $number, $msg);
      $check->execute();
      $check->store_result();

      if ($check->num_rows > 0) {
         $message[] = "You have already sent this message!";
      } else {
         $insert = $conn->prepare("
            INSERT INTO message (user_id, name, email, number, message)
            VALUES (?, ?, ?, ?, ?)
         ");
         $insert->bind_param("issss", $user_id, $name, $email, $number, $msg);

         if ($insert->execute()) {
            $message[] = "Message sent successfully!";
         } else {
            $message[] = "Something went wrong. Try again!";
         }

         $insert->close();
      }

      $check->close();
   }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <title>Contact Us — Booktoria</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   <link rel="stylesheet" href="css/home.css">
   <link rel="stylesheet" href="css/contact.css">
   <link rel="stylesheet" href="css/sidebar.css">
</head>

<body>

<?php include 'header.php'; ?>

<div class="main-content">

   <!-- Feedback Messages -->
   <?php if (!empty($message)): ?>
      <?php foreach ($message as $msg): ?>
      <div class="message">
         <span><?= htmlspecialchars($msg) ?></span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      <?php endforeach; ?>
   <?php endif; ?>

   <!-- Page Banner -->
   <section class="breadcrumb">
      <div class="container">
         <div class="breadcrumb-content">
            <h1>Contact Us</h1>
         </div>
      </div>
   </section>

   <!-- Contact Section -->
   <section class="contact-section">
      <div class="container">

         <div class="contact-grid">

            <!-- Contact Form -->
            <div class="contact-form-container">

               <div class="form-header">
                  <h2 class="section-title">Send Us a Message</h2>
                  <p class="form-subtitle">
                     We'd love to hear from you. Send us a message and we'll respond soon.
                  </p>
               </div>

               <form method="POST" class="contact-form">

                  <!-- Name -->
                  <div class="form-group">
                     <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" class="form-input"
                               placeholder="Your Full Name"
                               value="<?= htmlspecialchars($prefill_name) ?>"
                               required>
                     </div>
                  </div>

                  <!-- Email -->
                  <div class="form-group">
                     <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="form-input"
                               placeholder="Your Email Address"
                               value="<?= htmlspecialchars($prefill_email) ?>"
                               required>
                     </div>
                  </div>

                  <!-- Phone -->
                  <div class="form-group">
                     <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="text" maxlength="12" minlength="7"
                               name="number" class="form-input"
                               placeholder="Your Phone Number" required>
                     </div>
                  </div>

                  <!-- Message -->
                  <div class="form-group">
                     <div class="input-group textarea-group">
                        <i class="fas fa-comment"></i>
                        <textarea name="message" class="form-textarea"
                                  placeholder="Your Message..." rows="6"
                                  required></textarea>
                     </div>
                  </div>

                  <button type="submit" name="send" class="btn-contact">
                     <i class="fas fa-paper-plane"></i> Send Message
                  </button>

               </form>
            </div>

         </div>

         <!-- FAQ Section -->
         <div class="faq-section">
            <h2 class="section-title">Frequently Asked Questions</h2>

            <div class="faq-grid">

               <div class="faq-item">
                  <h4>How long does shipping take?</h4>
                  <p>3–5 business days. Express shipping available.</p>
               </div>

               <div class="faq-item">
                  <h4>Can I return a book?</h4>
                  <p>Returns accepted within 30 days.</p>
               </div>

               <div class="faq-item">
                  <h4>Do you ship internationally?</h4>
                  <p>Currently domestic only — international coming soon!</p>
               </div>

               <div class="faq-item">
                  <h4>How can I track my order?</h4>
                  <p>You will receive a tracking link via email.</p>
               </div>

            </div>
         </div>

      </div>
   </section>

</div>

<script>
// Proper input-group focus effects
document.querySelectorAll('.form-input, .form-textarea').forEach(input => {
   input.addEventListener('focus', () => {
      input.closest('.input-group').classList.add('focused');
   });

   input.addEventListener('blur', () => {
      if (!input.value.trim())
         input.closest('.input-group').classList.remove('focused');
   });
});
</script>

</body>
</html>
