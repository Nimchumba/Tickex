<?php
include "config.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user's tickets (we'll create purchases table later)
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Tickets - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <div class="sidebar">
        <h2 class="logo">Tickex</h2>
        <p style="color: #facc15;">Welcome, <?php echo $_SESSION['user_name']; ?></p>
        <ul>
            <li onclick="location.href='dashboard.php'">Home</li>
            <li class="active">My Tickets</li>
        </ul>
        <div style="margin-top: 50px;">
            <a href="logout.php" class="btn" style="width: 100%; text-align: center;">Logout</a>
        </div>
    </div>

    <div class="main">
        <h1>My Tickets</h1>
        
        <div style="background: #1e293b; padding: 50px; border-radius: 15px; text-align: center;">
            <p style="color: #999;">You haven't purchased any tickets yet.</p>
            <p style="color: #999;">Your purchased tickets will appear here.</p>
            <a href="dashboard.php" class="btn" style="margin-top: 20px;">Browse Events</a>
        </div>
    </div>
</div>

</body>
</html>