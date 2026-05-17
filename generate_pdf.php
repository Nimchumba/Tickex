<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get ticket details
$result = mysqli_query($conn, "
    SELECT t.*, e.title, e.event_date, e.event_time, e.location, e.image 
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE t.id = $ticket_id AND t.user_id = " . $_SESSION['user_id']
);

if(mysqli_num_rows($result) == 0) {
    die("Ticket not found");
}

$ticket = mysqli_fetch_assoc($result);

// Create QR code URL
$qr_data = "Ticket: " . $ticket['ticket_code'];
$qr_url = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode($qr_data) . "&choe=UTF-8";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ticket - <?php echo $ticket['title']; ?></title>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .ticket {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border-left: 5px solid <?php 
                echo $ticket['ticket_type_name'] == 'VIP' ? '#facc15' : 
                    ($ticket['ticket_type_name'] == 'VVIP' ? '#ff0000' : '#333'); 
            ?>;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #facc15;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #facc15;
            margin: 0;
            font-size: 32px;
        }
        .header h2 {
            color: <?php 
                echo $ticket['ticket_type_name'] == 'VIP' ? '#facc15' : 
                    ($ticket['ticket_type_name'] == 'VVIP' ? '#ff0000' : '#333'); 
            ?>;
            margin: 5px 0 0;
        }
        .event-title {
            text-align: center;
            font-size: 22px;
            margin: 20px 0;
            color: #333;
        }
        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .detail-item {
            text-align: center;
        }
        .detail-item strong {
            display: block;
            color: #facc15;
            margin-bottom: 5px;
        }
        .qr-section {
            text-align: center;
            margin: 20px 0;
        }
        .qr-section img {
            background: white;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .ticket-code {
            text-align: center;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 20px;
            letter-spacing: 2px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        .no-print button {
            background: #facc15;
            color: black;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin: 5px;
        }
        .no-print button:hover {
            background: #eab308;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>TICKEX</h1>
            <h2><?php echo $ticket['ticket_type_name']; ?> TICKET</h2>
        </div>
        
        <div class="event-title">
            <?php echo $ticket['title']; ?>
        </div>
        
        <div class="details">
            <div class="detail-item">
                <strong>📅 Date</strong>
                <?php echo date('d M Y', strtotime($ticket['event_date'])); ?>
            </div>
            <div class="detail-item">
                <strong>⏰ Time</strong>
                <?php echo date('h:i A', strtotime($ticket['event_time'])); ?>
            </div>
            <div class="detail-item">
                <strong>📍 Location</strong>
                <?php echo $ticket['location']; ?>
            </div>
            <div class="detail-item">
                <strong>🎫 Quantity</strong>
                <?php echo $ticket['quantity']; ?> ticket(s)
            </div>
            <div class="detail-item">
                <strong>💰 Amount</strong>
                KES <?php echo number_format($ticket['total_price']); ?>
            </div>
            <div class="detail-item">
                <strong>💳 Payment</strong>
                <?php echo $ticket['payment_method']; ?>
            </div>
        </div>
        
        <div class="qr-section">
            <img src="<?php echo $qr_url; ?>" alt="QR Code">
        </div>
        
        <div class="ticket-code">
            🎟️ <?php echo $ticket['ticket_code']; ?>
        </div>
        
        <div class="footer">
            <p>Ticket Holder: <?php echo $_SESSION['user_name']; ?></p>
            <p>Purchased: <?php echo date('d M Y h:i A', strtotime($ticket['purchase_date'])); ?></p>
            <p>Present this ticket at the entrance. Valid for one-time entry.</p>
        </div>
    </div>
    
    <div class="no-print">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
        <button onclick="window.location.href='my_tickets.php'">← Back to Tickets</button>
    </div>
    
    <script>
        // Auto print dialog (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>