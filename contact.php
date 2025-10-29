<?php
include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

if(isset($_POST['send'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $number = $_POST['number'];
   $msg = mysqli_real_escape_string($conn, $_POST['message']);

   $select_message = mysqli_query($conn, "SELECT * FROM `message` WHERE name = '$name' AND email = '$email' AND number = '$number' AND message = '$msg'") or die('query failed');

   if(mysqli_num_rows($select_message) > 0){
      $message[] = 'message sent already!';
   }else{
      mysqli_query($conn, "INSERT INTO `message`(user_id, name, email, number, message) VALUES('$user_id', '$name', '$email', '$number', '$msg')") or die('query failed');
      $message[] = 'message sent successfully!';
   }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>BookNook - Contact Us</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="css/home.css">
   <link rel="stylesheet" href="css/contact.css">
</head>
<body>
   <!-- Display Messages -->
   <?php
   if(isset($message)){
      foreach($message as $message){
         echo '
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
   ?>

   <!-- Header -->
   <header>
      <div class="container header-content">
         <a href="home.php" class="logo">
            <i class="fas fa-book-open"></i>
            <span>BookNook</span>
         </a>
         
         <nav id="main-nav">
            <ul>
               <li><a href="home.php">Home</a></li>
               <li><a href="shop.php">Shop</a></li>
               <li><a href="orders.php">Orders</a></li>
               <li><a href="thrift_list.php">Thrift Books</a></li>
               <li><a href="contact.php" class="active">Contact</a></li>
            </ul>
         </nav>
         
         <div class="header-actions">
            <i class="fas fa-search" id="search-btn"></i>
            <a href="cart.php" class="cart-icon">
               <i class="fas fa-shopping-cart" id="cart-btn"></i>
            </a>
            <i class="fas fa-user" id="user-btn"></i>
            <div class="mobile-menu-btn" id="menu-btn">
               <i class="fas fa-bars"></i>
            </div>
         </div>
      </div>
   </header>

   <!-- Breadcrumb Section -->
   <section class="breadcrumb">
      <div class="container">
         <div class="breadcrumb-content">
            <h1>Get In Touch</h1>
            <div class="breadcrumb-nav">
               <a href="home.php">
                  <i class="fas fa-home"></i> Home
               </a>
               <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
               <span class="breadcrumb-current">Contact</span>
            </div>
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
                  <p class="form-subtitle">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
               </div>
               
               <form action="" method="post" class="contact-form">
                  <div class="form-group">
                     <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" required placeholder="Your Full Name" class="form-input">
                     </div>
                  </div>
                  
                  <div class="form-group">
                     <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" required placeholder="Your Email Address" class="form-input">
                     </div>
                  </div>
                  
                  <div class="form-group">
                     <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="number" name="number" required placeholder="Your Phone Number" class="form-input">
                     </div>
                  </div>
                  
                  <div class="form-group">
                     <div class="input-group textarea-group">
                        <i class="fas fa-comment"></i>
                        <textarea name="message" required placeholder="Your Message..." class="form-textarea" rows="6"></textarea>
                     </div>
                  </div>
                  
                  <button type="submit" name="send" class="btn btn-contact">
                     <i class="fas fa-paper-plane"></i>
                     Send Message
                  </button>
               </form>
            </div>

            <!-- Contact Info -->
            <div class="contact-info">
               <div class="info-card">
                  <div class="info-icon">
                     <i class="fas fa-map-marker-alt"></i>
                  </div>
                  <div class="info-content">
                     <h3>Visit Our Store</h3>
                     <p>123 Book Street<br>Readville, RK 12345</p>
                  </div>
               </div>
               
               <div class="info-card">
                  <div class="info-icon">
                     <i class="fas fa-phone"></i>
                  </div>
                  <div class="info-content">
                     <h3>Call Us</h3>
                     <p>+1 (555) 123-4567<br>Mon-Fri: 9AM-6PM</p>
                  </div>
               </div>
               
               <div class="info-card">
                  <div class="info-icon">
                     <i class="fas fa-envelope"></i>
                  </div>
                  <div class="info-content">
                     <h3>Email Us</h3>
                     <p>hello@booknook.com<br>support@booknook.com</p>
                  </div>
               </div>
               
               <div class="info-card">
                  <div class="info-icon">
                     <i class="fas fa-clock"></i>
                  </div>
                  <div class="info-content">
                     <h3>Opening Hours</h3>
                     <p>Monday - Friday: 9AM - 8PM<br>Saturday - Sunday: 10AM - 6PM</p>
                  </div>
               </div>
            </div>
         </div>

         <!-- FAQ Section -->
         <div class="faq-section">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-grid">
               <div class="faq-item">
                  <h4>How long does shipping take?</h4>
                  <p>Standard shipping takes 3-5 business days. Express shipping is available for 1-2 business days.</p>
               </div>
               
               <div class="faq-item">
                  <h4>Can I return a book?</h4>
                  <p>Yes, we accept returns within 30 days of purchase. Books must be in original condition.</p>
               </div>
               
               <div class="faq-item">
                  <h4>Do you ship internationally?</h4>
                  <p>Currently, we only ship within the country. International shipping coming soon!</p>
               </div>
               
               <div class="faq-item">
                  <h4>How can I track my order?</h4>
                  <p>You'll receive a tracking number via email once your order ships. You can also check in your account.</p>
               </div>
            </div>
         </div>
      </div>
   </section>

   <!-- Newsletter -->
   <section class="newsletter">
      <div class="container">
         <h2>Stay Connected</h2>
         <p>Subscribe to our newsletter for updates, new arrivals, and exclusive offers.</p>
         <form class="newsletter-form">
            <input type="email" placeholder="Your email address" class="newsletter-input" required>
            <button type="submit" class="btn btn-secondary">Subscribe</button>
         </form>
      </div>
   </section>

   <!-- Footer -->
   <footer>
      <div class="container">
         <div class="footer-content">
            <div class="footer-column">
               <h3>BookNook</h3>
               <p>Your favorite online bookstore with carefully curated selections for every type of reader.</p>
              
            </div>
            
            <div class="footer-column">
               <h3>Shop</h3>
               <ul class="footer-links">
                  <li><a href="shop.php">All Books</a></li>
                  <li><a href="shop.php?genre=Fiction">Fiction</a></li>
                  <li><a href="shop.php?genre=Non-Fiction">Non-Fiction</a></li>
                  <li><a href="thrift_list.php">Thrift Books</a></li>
                  <li><a href="shop.php">New Arrivals</a></li>
               </ul>
            </div>
            
            <div class="footer-column">
               <h3>Help</h3>
               <ul class="footer-links">
                  <li><a href="#">Shipping Info</a></li>
                  <li><a href="#">Returns</a></li>
                  <li><a href="#">FAQ</a></li>
                  <li><a href="contact.php">Contact Us</a></li>
                  <li><a href="#">Privacy Policy</a></li>
               </ul>
            </div>
            
            <div class="footer-column">
               <h3>Contact</h3>
               <ul class="footer-links">
                  <li><i class="fas fa-map-marker-alt"></i> 123 Book Street, Readville</li>
                  <li><i class="fas fa-phone"></i> (555) 123-4567</li>
                  <li><i class="fas fa-envelope"></i> hello@booknook.com</li>
               </ul>
            </div>
         </div>
         
         <div class="footer-bottom">
            <p>&copy; 2023 BookNook. All rights reserved.</p>
         </div>
      </div>
   </footer>

   <script>
      // Mobile menu toggle
      document.getElementById('menu-btn').addEventListener('click', function() {
         document.getElementById('main-nav').classList.toggle('active');
      });

      // Close mobile menu when clicking outside
      document.addEventListener('click', function(event) {
         const nav = document.getElementById('main-nav');
         const menuBtn = document.getElementById('menu-btn');
         
         if (!nav.contains(event.target) && !menuBtn.contains(event.target)) {
            nav.classList.remove('active');
         }
      });

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

      // Form input animations
      document.querySelectorAll('.form-input, .form-textarea').forEach(input => {
         input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
         });
         
         input.addEventListener('blur', function() {
            if (!this.value) {
               this.parentElement.classList.remove('focused');
            }
         });
      });
   </script>
</body>
</html>