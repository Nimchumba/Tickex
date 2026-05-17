<?php
include "config.php";

$error = "";

if(isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Check user
    $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    
    if(mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if(password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "Email not found!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="landing-body">
    <div class="overlay"></div>
    
    <div class="landing-content">
        <h1 class="brand">Tickex</h1>
        <h2>Login to Your Account</h2>
        
        <?php if($error): ?>
            <div style="color: red; margin-bottom: 10px;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email Address" required 
                   style="padding: 10px; margin: 5px; width: 250px; border-radius: 5px;" /><br>
            
            <input type="password" name="password" placeholder="Password" required 
                   style="padding: 10px; margin: 5px; width: 250px; border-radius: 5px;" /><br>
            
            <button type="submit" name="login" class="btn">Login</button>
        </form>
        
        <p style="margin-top: 20px;">No account? <a href="register.php" style="color: #facc15;">Register here</a></p>
        <a href="index.php" class="btn btn-outline" style="margin-top: 10px;">Back to Home</a>
    </div>
</body>
</html>