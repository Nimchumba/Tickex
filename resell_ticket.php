<?php
include "config.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$resale_price = isset($_GET['price']) ? (float)$_GET['price'] : 0;
$quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
$user_id = $_SESSION['user_id'];

// Get ticket details
$ticket = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT t.*, e.title, e.event_date 
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE t.id = $ticket_id AND t.user_id = $user_id
"));

if(!$ticket) {
    header("Location: my_tickets.php");
    exit();
}

// Validate quantity
if($quantity > $ticket['quantity']) {
    $error = "You cannot sell more tickets than you have!";
} else {
    // Check if already listed
    $existing = mysqli_query($conn, "SELECT * FROM ticket_resales WHERE original_ticket_id = $ticket_id AND status IN ('available', 'pending')");
    
    if(mysqli_num_rows($existing) > 0) {
        $error = "This ticket is already listed for resale";
    } else {
        $price_per_ticket = $ticket['total_price'] / $ticket['quantity'];
        $resale_price_per_ticket = $price_per_ticket * 0.8;
        
        $insert = mysqli_query($conn, "
            INSERT INTO ticket_resales (original_ticket_id, seller_user_id, original_price, resale_price, quantity, remaining_quantity, status)
            VALUES ($ticket_id, $user_id, {$ticket['total_price']}, $resale_price, $quantity, $quantity, 'available')
        ");
        
        if($insert) {
            $success = "{$quantity} ticket(s) listed for resale at KES " . number_format($resale_price);
        } else {
            $error = "Failed to list ticket";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Resell Ticket - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .resale-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            margin: 50px auto;
        }
        .success { color: #00aa00; font-size: 48px; }
        .error { color: #ff0000; font-size: 48px; }
        .price { font-size: 36px; color: #facc15; margin: 20px 0; }
        .details { color: #ccc; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h2 class="logo">Tickex</h2>
        <ul>
            <li onclick="location.href='dashboard.php'">🏠 Home</li>
            <li onclick="location.href='my_tickets.php'">🎟️ My Tickets</li>
        </ul>
    </div>
    <div class="main">
        <div class="resale-container">
            <?php if(isset($success)): ?>
                <div class="success">✅</div>
                <h2 style="color: #facc15;">Ticket(s) Listed for Resale!</h2>
                <div class="price">KES <?php echo number_format($resale_price); ?></div>
                <div class="details">Quantity: <?php echo $quantity; ?> ticket(s)</div>
                <div class="details">Original price: KES <?php echo number_format($ticket['total_price']); ?></div>
                <div class="details">You saved the buyer: KES <?php echo number_format(($ticket['total_price'] / $ticket['quantity'] * $quantity) - $resale_price); ?></div>
                <a href="my_tickets.php" class="btn" style="margin-top: 20px;">← Back to My Tickets</a>
            <?php else: ?>
                <div class="error">❌</div>
                <h2 style="color: #ff0000;">Failed to List Ticket</h2>
                <p><?php echo $error; ?></p>
                <a href="my_tickets.php" class="btn" style="margin-top: 20px;">← Back</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>