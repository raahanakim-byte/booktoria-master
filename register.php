<?php

include 'config.php';

if(isset($_POST['submit'])){

   if(isset($_POST['submit'])){

   $name = trim($_POST['name']);
   $email = trim($_POST['email']);
   $password = $_POST['password'];
   $cpassword = $_POST['cpassword'];
   $user_type = $_POST['user_type'];

   // Basic format validations
   if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
      $message[] = 'Name must only contain letters and spaces.';
   } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $message[] = 'Invalid email format.';
   } elseif (preg_match("/^\d+$/", $email)) {
      $message[] = 'Email cannot be purely numbers.';
   } elseif (strlen($password) < 8 || !preg_match("/[\d\W]/", $password)) {
      $message[] = 'Password must be at least 8 characters and include a number or symbol.';
   } elseif ($password !== $cpassword) {
      $message[] = 'Confirm password does not match.';
   } else {
      // Sanitize inputs for DB
      $name = mysqli_real_escape_string($conn, $name);
      $email = mysqli_real_escape_string($conn, $email);
      $pass = mysqli_real_escape_string($conn, md5($password));

      // Check if user exists
      $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email'") or die('query failed');

      if(mysqli_num_rows($select_users) > 0){
         $message[] = 'User already exists!';
      } else {
         mysqli_query($conn, "INSERT INTO `users`(name, email, password, user_type) VALUES('$name', '$email', '$pass', '$user_type')") or die('query failed');
         $message[] = 'Registered successfully!';
         header('location:login.php');
         exit();
      }
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
   <title>register</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.csss">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>



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
   
<div class="form-container">

   <form action="" method="post">
      <h3>register now</h3>
      <input type="text" name="name" placeholder="enter your name" required class="box">
      <input type="email" name="email" placeholder="enter your email" required class="box">
      <input type="password" name="password" placeholder="enter your password" required class="box">
      <input type="password" name="cpassword" placeholder="confirm your password" required class="box">
      <select name="user_type" class="box">
         <option value="user">user</option>
         <option value="admin">admin</option>
      </select>
      <input type="submit" name="submit" value="register now" class="btn">
      <p>already have an account? <a href="login.php">login now</a></p>
   </form>

</div>
<script>
function validateForm() {
   const name = document.querySelector('[name="name"]').value.trim();
   const email = document.querySelector('[name="email"]').value.trim();
   const password = document.querySelector('[name="password"]').value;
   const cpassword = document.querySelector('[name="cpassword"]').value;

   const nameRegex = /^[a-zA-Z\s]+$/;
   const emailRegex = /^[^\d]+[^\s@]+@[^\s@]+\.[^\s@]+$/;
   const passRegex = /[\d\W]/;

   if (!nameRegex.test(name)) {
      alert("Name can only contain letters and spaces.");
      return false;
   }

   if (!emailRegex.test(email)) {
      alert("Enter a valid email (not purely numbers).");
      return false;
   }

   if (password.length < 8 || !passRegex.test(password)) {
      alert("Password must be at least 8 characters and contain a number or symbol.");
      return false;
   }

   if (password !== cpassword) {
      alert("Passwords do not match.");
      return false;
   }

   return true;
}
</script>

</body>
</html>
