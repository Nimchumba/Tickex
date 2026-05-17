<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="logo">Tickex</h2>
        <p style="color: #facc15; margin-bottom: 20px;">Welcome, <?php echo $_SESSION['user_name']; ?></p>
        
        <ul>
    <li onclick="location.href='dashboard.php'">🏠 Home</li>
    <li onclick="location.href='category.php?id=1'">🎤 Concerts</li>
    <li onclick="location.href='category.php?id=2'">🎬 Movies</li>
    <li onclick="location.href='category.php?id=3'">⚽ Sports</li>
    <li onclick="location.href='category.php?id=4'">🎪 Festivals</li>
    <li onclick="location.href='category.php?id=5'">💻 Tech Events</li>
    <li onclick="location.href='my_tickets.php'">🎟️ My Tickets</li>
    <li onclick="location.href='wishlist.php'">💙 Wishlist</li>
    <li onclick="location.href='calendar.php'">📅 Calendar</li>
    <li onclick="location.href='resale_listings.php'">🔥 Resale</li>
</ul>
      
        <div style="margin-top: 50px;">
            <a href="logout.php" class="btn" style="width: 100%; text-align: center;">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="hero">
            <h1>Welcome back, <?php echo $_SESSION['user_name']; ?>!</h1>
            <p>Book tickets for the biggest events in the country.</p>
            <a href="#events" class="btn">Browse Events</a>
        </div>

        <h2 class="section-title" id="events">All Events</h2>
        
        <?php
        // Get all categories
        $categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
        
        while($cat = mysqli_fetch_assoc($categories)) {
            echo "<h2 class='category-title' id='".strtolower($cat['name'])."'>".$cat['name']."</h2>";
            
            // Get 20 events per category
            $events = mysqli_query($conn, 
                "SELECT * FROM events 
                 WHERE category_id=".$cat['id']." 
                 ORDER BY event_date DESC 
                 LIMIT 20");
            
            if(mysqli_num_rows($events) > 0) {
                echo "<div class='event-row'>";
                
                while($event = mysqli_fetch_assoc($events)) {
        ?>
                    <div class='event-card'>
                        <img src="assets/images/<?php echo $event['image'] ? $event['image'] : 'placeholder.jpg'; ?>" 
                             class='event-img' 
                             onerror="this.src='assets/images/placeholder.jpg'">
                        
                        <div class='event-info'>
                            <h3><?php echo $event['title']; ?></h3>
                            <p>📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
                            <p>⏰ <?php echo date('h:i A', strtotime($event['event_time'])); ?></p>
                            <p>📍 <?php echo $event['location']; ?></p>
                            <p class='price'>KES <?php echo number_format($event['price']); ?></p>
                            
                            <a href='event.php?id=<?php echo $event['id']; ?>' class='btn' style='padding: 5px 10px; font-size: 14px;'>
                                View Details
                            </a>
                        </div>
                    </div>
        <?php
                }
                echo "</div>";
            } else {
                echo "<p style='color: #999; margin: 20px;'>No events in this category yet.</p>";
            }
        }
        ?>
    </div>
</div>

</body>
</html>