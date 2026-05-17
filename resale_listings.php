<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get all available resale tickets
$resales = mysqli_query($conn, "
    SELECT r.*, u.full_name as seller_name, e.title, e.event_date, e.event_time, e.location, e.image 
    FROM ticket_resales r
    JOIN users u ON r.seller_user_id = u.id
    JOIN tickets t ON r.original_ticket_id = t.id
    JOIN events e ON t.event_id = e.id
    WHERE r.status = 'available'
    ORDER BY r.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Discounted Tickets - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .resale-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .resale-header h1 {
            font-size: 48px;
            color: #facc15;
            margin-bottom: 10px;
        }
        .resale-header p {
            color: #ccc;
        }
        .resale-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .resale-card {
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s;
            border-left: 4px solid #facc15;
        }
        .resale-card:hover {
            transform: translateY(-5px);
        }
        .resale-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }
        .resale-info {
            padding: 20px;
        }
        .resale-info h3 {
            font-size: 20px;
            margin-bottom: 8px;
            color: white;
        }
        .resale-info p {
            color: #ccc;
            font-size: 13px;
            margin: 5px 0;
        }
        .price-section {
            margin: 15px 0;
            padding: 10px;
            background: #0f172a;
            border-radius: 8px;
        }
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 14px;
        }
        .resale-price {
            font-size: 24px;
            color: #facc15;
            font-weight: bold;
        }
        .price-per-ticket {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .discount-badge {
            background: #00aa00;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            margin-left: 10px;
        }
        .quantity-badge {
            background: #facc15;
            color: black;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            margin-right: 10px;
        }
        .seller-info {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
        .buy-btn {
            display: block;
            background: #facc15;
            color: black;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 15px;
            transition: 0.2s;
        }
        .buy-btn:hover {
            background: #eab308;
            transform: scale(1.02);
        }
        .no-resales {
            text-align: center;
            padding: 60px;
            background: #1e293b;
            border-radius: 15px;
        }
        .no-resales h2 {
            color: #facc15;
            margin-bottom: 20px;
        }
        .no-resales p {
            color: #999;
            margin-bottom: 30px;
        }
        @media (max-width: 768px) {
            .resale-grid {
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
            <li onclick="location.href='wishlist.php'">💙 Wishlist</li>
            <li onclick="location.href='calendar.php'">📅 Calendar</li>
            <li class="active">🔥 Resale</li>
        </ul>
        
        <div style="margin-top: 50px;">
            <a href="logout.php" class="btn" style="width: 100%; text-align: center;">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="resale-header">
            <h1>🔥 Discounted Resale Tickets</h1>
            <p>Get tickets for less! All tickets are 20% off original price</p>
        </div>
        
        <?php if(mysqli_num_rows($resales) > 0): ?>
            <div class="resale-grid">
                <?php while($resale = mysqli_fetch_assoc($resales)): 
                    $original_total = $resale['original_price'];
                    $resale_total = $resale['resale_price'];
                    $quantity = $resale['quantity'] ?? 1;
                    $original_per_ticket = $original_total / $quantity;
                    $resale_per_ticket = $resale_total / $quantity;
                    $savings_per_ticket = $original_per_ticket - $resale_per_ticket;
                    $percent = round(($savings_per_ticket / $original_per_ticket) * 100);
                ?>
                <div class="resale-card">
                    <img src="assets/images/<?php echo $resale['image'] ? $resale['image'] : 'placeholder.jpg'; ?>" 
                         class="resale-image"
                         onerror="this.src='assets/images/placeholder.jpg'">
                    
                    <div class="resale-info">
                        <h3><?php echo $resale['title']; ?></h3>
                        <p>📅 <?php echo date('d M Y', strtotime($resale['event_date'])); ?> at <?php echo date('h:i A', strtotime($resale['event_time'])); ?></p>
                        <p>📍 <?php echo $resale['location']; ?></p>
                        
                        <div class="price-section">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                                <span class="quantity-badge">🎫 <?php echo $quantity; ?> ticket(s) available</span>
                                <span class="discount-badge">Save <?php echo $percent; ?>%</span>
                            </div>
                            <div style="margin-top: 10px;">
                                <span class="original-price">Original: KES <?php echo number_format($original_per_ticket); ?> each</span><br>
                                <span class="resale-price">Resale: KES <?php echo number_format($resale_per_ticket); ?> each</span>
                                <div class="price-per-ticket">
                                    Total: KES <?php echo number_format($resale_total); ?> for <?php echo $quantity; ?> ticket(s)
                                </div>
                            </div>
                        </div>
                        
                        <div class="seller-info">
                            👤 Listed by: <?php echo $resale['seller_name']; ?>
                        </div>
                        
                        <a href="buy_resale.php?id=<?php echo $resale['id']; ?>" class="buy-btn">
                            🎟️ Buy Now - Save <?php echo $percent; ?>% (KES <?php echo number_format($resale_per_ticket); ?>/ticket)
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-resales">
                <h2>🔥 No Resale Tickets Available</h2>
                <p>Check back later for discounted tickets from other users</p>
                <a href="dashboard.php" class="btn">Browse Events</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>