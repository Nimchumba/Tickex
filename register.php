<?php
include "config.php";

$error = "";
$success = "";

if(isset($_POST['register'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if($password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already exists
        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if(mysqli_num_rows($check) > 0) {
            $error = "Email already registered!";
        } else {
            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $insert = mysqli_query($conn, "INSERT INTO users (full_name, email, password) 
                                           VALUES ('$full_name', '$email', '$hashed')");
            
            if($insert) {
                $success = "Registration successful! <a href='login.php'>Login here</a>";
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-body">
    <div class="overlay"></div>
    
    <div class="landing-content">
        <h1 class="brand">Tickex</h1>
        <h2>Create Account</h2>
        
        <?php if($error): ?>
            <div style="color: red; margin-bottom: 10px;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div style="color: green; margin-bottom: 10px;"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="text" name="full_name" placeholder="Full Name" required 
                   style="padding: 10px; margin: 5px; width: 250px; border-radius: 5px;" /><br>
            
            <input type="email" name="email" placeholder="Email Address" required 
                   style="padding: 10px; margin: 5px; width: 250px; border-radius: 5px;" /><br>
            
            <input type="password" name="password" placeholder="Password" required 
                   style="padding: 10px; margin: 5px; width: 250px; border-radius: 5px;" /><br>
            
            <input type="password" name="confirm_password" placeholder="Confirm Password" required 
                   style="padding: 10px; margin: 5px; width: 250px; border-radius: 5px;" /><br>
            
            <button type="submit" name="register" class="btn">Register</button>
        </form>
        
        <p style="margin-top: 20px;">Already have an account? <a href="login.php" style="color: #facc15;">Login</a></p>
        <a href="index.php" class="btn btn-outline" style="margin-top: 10px;">Back to Home</a>
    </div>
</body>
</html>