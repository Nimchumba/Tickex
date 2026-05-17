<?php
include "config.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's wishlist items
$wishlist = mysqli_query($conn, "
    SELECT w.*, e.title, e.event_date, e.event_time, e.location, e.price, e.image, e.id as event_id
    FROM wishlist w
    JOIN events e ON w.event_id = e.id
    WHERE w.user_id = $user_id
    ORDER BY w.added_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Wishlist - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .wishlist-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .wishlist-header h1 {
            font-size: 48px;
            color: #facc15;
            margin-bottom: 10px;
        }
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .wishlist-card {
            background: #1e293b;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            position: relative;
        }
        .wishlist-card:hover {
            transform: scale(1.05);
        }
        .wishlist-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .wishlist-info {
            padding: 15px;
        }
        .wishlist-info h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        .wishlist-info p {
            font-size: 13px;
            color: #ccc;
            margin: 5px 0;
        }
        .price {
            color: #facc15;
            font-weight: bold;
            font-size: 18px;
            margin: 10px 0;
        }
        .remove-wishlist {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .remove-wishlist:hover {
            background: #dc2626;
        }
        .btn-book {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background: #facc15;
            color: black;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            width: 100%;
            text-align: center;
        }
        .empty-wishlist {
            text-align: center;
            padding: 60px;
            background: #1e293b;
            border-radius: 15px;
        }
        .empty-wishlist h2 {
            color: #facc15;
            margin-bottom: 20px;
        }
        .empty-wishlist p {
            color: #999;
            margin-bottom: 30px;
        }
        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
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
            <li class="active">💙 Wishlist</li>
            <li onclick="location.href='calendar.php'">📅 Calendar</li>
            <li onclick="location.href='resale_listings.php'">🔥 Resale</li>
        </ul>
        
        <div style="margin-top: 50px;">
            <a href="logout.php" class="btn" style="width: 100%; text-align: center;">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="wishlist-header">
            <h1>💙 My Wishlist</h1>
            <p>Your saved favorite events</p>
        </div>
        
        <?php if(mysqli_num_rows($wishlist) > 0): ?>
            <div class="wishlist-grid">
                <?php while($item = mysqli_fetch_assoc($wishlist)): ?>
                <div class="wishlist-card" id="wishlist-item-<?php echo $item['id']; ?>">
                    <button class="remove-wishlist" onclick="removeFromWishlist(<?php echo $item['event_id']; ?>, <?php echo $item['id']; ?>)">✖</button>
                    <img src="assets/images/<?php echo $item['image'] ? $item['image'] : 'placeholder.jpg'; ?>" 
                         class="wishlist-img"
                         onerror="this.src='assets/images/placeholder.jpg'">
                    <div class="wishlist-info">
                        <h3><?php echo $item['title']; ?></h3>
                        <p>📅 <?php echo date('d M Y', strtotime($item['event_date'])); ?></p>
                        <p>⏰ <?php echo date('h:i A', strtotime($item['event_time'])); ?></p>
                        <p>📍 <?php echo $item['location']; ?></p>
                        <div class="price">KES <?php echo number_format($item['price']); ?></div>
                        <a href="event.php?id=<?php echo $item['event_id']; ?>" class="btn-book">🎟️ Book Now</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <h2>💙 Your wishlist is empty</h2>
                <p>Save your favorite events by clicking the 💙 button on any event page</p>
                <a href="dashboard.php" class="btn">Browse Events</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function removeFromWishlist(eventId, wishlistId) {
    if(confirm('Remove this event from your wishlist?')) {
        fetch('wishlist_toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'event_id=' + eventId
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'removed') {
                document.getElementById('wishlist-item-' + wishlistId).remove();
                // Reload page if no items left
                if(document.querySelectorAll('.wishlist-card').length === 0) {
                    location.reload();
                }
            }
        });
    }
}
</script>

</body>
</html>