<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get category name
$cat_result = mysqli_query($conn, "SELECT * FROM categories WHERE id = $category_id");
$category = mysqli_fetch_assoc($cat_result);

if(!$category) {
    header("Location: dashboard.php");
    exit();
}

// Get events for this category
$events = mysqli_query($conn, "
    SELECT * FROM events 
    WHERE category_id = $category_id 
    ORDER BY event_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $category['name']; ?> - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .category-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .category-header h1 {
            font-size: 48px;
            color: #facc15;
            margin-bottom: 10px;
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .event-card {
            background: #1e293b;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .event-card:hover {
            transform: scale(1.05);
        }
        .event-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .event-info {
            padding: 15px;
        }
        .event-info h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .event-info p {
            font-size: 14px;
            color: #ccc;
            margin: 5px 0;
        }
        .price {
            color: #facc15 !important;
            font-weight: bold;
            font-size: 16px !important;
        }
        .btn-view {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background: #facc15;
            color: black;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .no-events {
            text-align: center;
            padding: 50px;
            background: #1e293b;
            border-radius: 15px;
            color: #999;
        }
    </style>
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
        <div class="category-header">
            <h1><?php echo $category['name']; ?></h1>
            <p>Browse all <?php echo $category['name']; ?> events</p>
        </div>
        
        <?php if(mysqli_num_rows($events) > 0): ?>
            <div class="events-grid">
                <?php while($event = mysqli_fetch_assoc($events)): ?>
                <div class="event-card">
                    <img src="assets/images/<?php echo $event['image'] ? $event['image'] : 'placeholder.jpg'; ?>" 
                         class="event-img"
                         onerror="this.src='assets/images/placeholder.jpg'">
                    
                    <div class="event-info">
                        <h3><?php echo $event['title']; ?></h3>
                        <p>📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
                        <p>⏰ <?php echo date('h:i A', strtotime($event['event_time'])); ?></p>
                        <p>📍 <?php echo $event['location']; ?></p>
                        <p class="price">KES <?php echo number_format($event['price']); ?></p>
                        
                        <a href="event.php?id=<?php echo $event['id']; ?>" class="btn-view">View Details</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-events">
                <h2>No events found in this category</h2>
                <p>Check back later for upcoming events!</p>
                <a href="dashboard.php" class="btn" style="margin-top: 20px;">Browse All Events</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>