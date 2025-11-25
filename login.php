<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'config.php';

if(isset($_POST['submit'])){
   $email = trim($_POST['email']);
   $password = $_POST['password'];

   if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $message[] = 'Invalid email format.';
   } elseif (strlen($password) < 8) {
      $message[] = 'Password must be at least 8 characters.';
   } else {
      $email = mysqli_real_escape_string($conn, $email);
      $pass = mysqli_real_escape_string($conn, md5($password));

      $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email' AND password = '$pass'") or die('query failed');

      if(mysqli_num_rows($select_users) > 0){
         $row = mysqli_fetch_assoc($select_users);

         if($row['user_type'] == 'admin'){
            $_SESSION['admin_name'] = $row['name'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['admin_id'] = $row['id'];
            header('location:admin_page.php');
            exit();
         } elseif($row['user_type'] == 'user'){
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_email'] = $row['email'];
            $_SESSION['user_id'] = $row['id'];
            header('location:home.php');
            exit();
         }
      } else {
         $message[] = 'Incorrect email or password!';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login - Booktoria</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/auth.css">

</head>
<body class="auth-page">

<?php
if(isset($message)){
   foreach($message as $msg){
      echo '
      <div class="message">
         <span>'.$msg.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<div class="auth-container">
   <div class="auth-card">
      <div class="auth-header">
         <h1>Welcome Back</h1>
         <p>Sign in to your Booktoria account</p>
      </div>

      <form action="" method="post" class="auth-form">
         <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
               <i class="fas fa-envelope input-icon"></i>
               <input type="email" name="email" id="email" placeholder="Enter your email" required class="form-input">
            </div>
         </div>

         <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
               <i class="fas fa-lock input-icon"></i>
               <input type="password" name="password" id="password" placeholder="Enter your password" required class="form-input">
               <button type="button" class="password-toggle" id="passwordToggle">
                  <i class="fas fa-eye"></i>
               </button>
            </div>
            <div class="password-requirements">
               Password must be at least 8 characters
            </div>
         </div>

         

         <button type="submit" name="submit" class="auth-btn primary">
            <span class="btn-text">Sign In</span>
            <i class="fas fa-arrow-right btn-icon"></i>
         </button>
      </form>

      <div class="auth-divider">
         <span>Or continue with</span>
      </div>

    

      <div class="auth-footer">
         <p>Don't have an account? <a href="register.php" class="auth-link">Create account</a></p>
      </div>
   </div>

   <div class="auth-decoration">
      <div class="decoration-content">
         <i class="fas fa-book-open decoration-icon"></i>
         <h2>Discover Your Next Favorite Read</h2>
         <p>Join thousands of readers exploring amazing books</p>
      </div>
   </div>
</div>

<script>
   // Password toggle functionality
   const passwordToggle = document.getElementById('passwordToggle');
   const passwordInput = document.getElementById('password');

   passwordToggle.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
   });

   // Form validation
   const form = document.querySelector('.auth-form');
   form.addEventListener('submit', function(e) {
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;

      if (!email || !password) {
         e.preventDefault();
         showMessage('Please fill in all fields', 'error');
      }
   });

   function showMessage(text, type) {
      // Remove existing messages
      const existingMessages = document.querySelectorAll('.message');
      existingMessages.forEach(msg => msg.remove());

      // Create new message
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${type}`;
      messageDiv.innerHTML = `
         <span>${text}</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      `;
      
      document.body.insertBefore(messageDiv, document.querySelector('.auth-container'));
   }

   // Auto-remove messages after 5 seconds
   setInterval(() => {
      const messages = document.querySelectorAll('.message');
      messages.forEach(message => {
         setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => { message.remove(); }, 300);
         }, 5000);
      });
   }, 1000);
</script>

</body>
</html>