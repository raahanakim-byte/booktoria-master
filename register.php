<?php
include 'config.php';
session_start();

if(isset($_POST['submit'])){
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $user_type = "user";

    // Validation
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
        $name = mysqli_real_escape_string($conn, $name);
        $email = mysqli_real_escape_string($conn, $email);
        $pass = mysqli_real_escape_string($conn, md5($password));

        $select_users = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email'") or die('query failed');

        if(mysqli_num_rows($select_users) > 0){
            $message[] = 'User already exists!';
        } else {
            mysqli_query($conn, "INSERT INTO `users`(name,email,password,user_type) VALUES('$name','$email','$pass','$user_type')") or die('query failed');
            $message[] = 'Registered successfully!';
            header('location:login.php');
            exit();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Booktoria</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
<link rel="stylesheet" href="css/register.css">
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
            <h1>Create Account</h1>
            <p>Join Booktoria and start discovering amazing books</p>
        </div>

        <form action="" method="post" class="auth-form" onsubmit="return validateForm()">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="name" placeholder="Enter your full name" required class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="Enter your email" required class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password" placeholder="Enter your password" required class="form-input">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="cpassword" placeholder="Confirm your password" required class="form-input">
                </div>
            </div>

            

            <button type="submit" name="submit" class="auth-btn primary">
                <span class="btn-text">Register Now</span>
                <i class="fas fa-arrow-right btn-icon"></i>
            </button>

            <p class="auth-footer">Already have an account? <a href="login.php" class="auth-link">Sign in</a></p>
        </form>
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
function validateForm() {
    const name = document.querySelector('[name="name"]').value.trim();
    const email = document.querySelector('[name="email"]').value.trim();
    const password = document.querySelector('[name="password"]').value;
    const cpassword = document.querySelector('[name="cpassword"]').value;

    const nameRegex = /^[a-zA-Z\s]+$/;
    const emailRegex = /^[^\d]+[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const passRegex = /[\d\W]/;

    if (!nameRegex.test(name)) { alert("Name can only contain letters and spaces."); return false; }
    if (!emailRegex.test(email)) { alert("Enter a valid email (not purely numbers)."); return false; }
    if (password.length < 8 || !passRegex.test(password)) { alert("Password must be at least 8 characters and contain a number or symbol."); return false; }
    if (password !== cpassword) { alert("Passwords do not match."); return false; }
    return true;
}
</script>

</body>
</html>
