<?php
include "config.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

$result = mysqli_query($conn, "
    SELECT t.*, e.title, e.event_date, e.event_time, e.location 
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE t.id = $ticket_id AND t.user_id = " . $_SESSION['user_id']
);

if(mysqli_num_rows($result) == 0) {
    header("Location: dashboard.php");
    exit();
}

$ticket = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Success - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .success-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            margin: 50px auto;
        }
        .checkmark { font-size: 80px; color: #00aa00; margin-bottom: 20px; }
        .ticket-info {
            background: #0f172a;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            text-align: left;
        }
        .ticket-code {
            background: #facc15;
            color: black;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 20px;
            font-weight: bold;
        }
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h2 class="logo">Tickex</h2>
        <ul>
            <li onclick="location.href='dashboard.php'">🏠 Home</li>
            <li onclick="location.href='my_tickets.php'">🎟️ My Tickets</li>
             <li onclick="location.href='wishlist.php'">💙 Wishlist</li>
            <li onclick="location.href='calendar.php'">📅 Calendar</li>
            <li onclick="location.href='resale_listings.php'">🔥 Resale</li> 
        </ul>
    </div>
    <div class="main">
        <div class="success-container">
            <div class="checkmark">✅</div>
            <h1 style="color: #facc15;">Payment Successful!</h1>
            <p>Your ticket has been confirmed</p>
            <div class="ticket-info">
                <p><strong>Event:</strong> <?php echo $ticket['title']; ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($ticket['event_date'])); ?></p>
                <p><strong>Location:</strong> <?php echo $ticket['location']; ?></p>
                <p><strong>Quantity:</strong> <?php echo $ticket['quantity']; ?></p>
            </div>
            <div class="ticket-code">🎟️ <?php echo $ticket['ticket_code']; ?></div>
            
            <div class="btn-group">
                <a href="my_tickets.php" class="btn">🎟️ View My Tickets</a>
                <a href="qr_ticket.php?id=<?php echo $ticket_id; ?>" class="btn">📱 View QR Code</a>
                <a href="dashboard.php" class="btn btn-outline">🏠 Back to Home</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>