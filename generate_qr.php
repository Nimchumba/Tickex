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
    SELECT t.*, e.title, e.event_date, e.event_time, e.location 
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE t.id = $ticket_id AND t.user_id = " . $_SESSION['user_id']
);

if(mysqli_num_rows($result) == 0) {
    die("Ticket not found");
}

$ticket = mysqli_fetch_assoc($result);

// Create QR code data
$qr_data = "Ticket Code: " . $ticket['ticket_code'] . "\n";
$qr_data .= "Event: " . $ticket['title'] . "\n";
$qr_data .= "Date: " . date('d M Y', strtotime($ticket['event_date'])) . "\n";
$qr_data .= "Time: " . date('h:i A', strtotime($ticket['event_time'])) . "\n";
$qr_data .= "Location: " . $ticket['location'] . "\n";
$qr_data .= "Type: " . $ticket['ticket_type_name'] . "\n";
$qr_data .= "Name: " . $_SESSION['user_name'];

// URL encode the data
$encoded_data = urlencode($qr_data);

// Use Google Charts API (free, no library needed)
$qr_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $encoded_data . "&choe=UTF-8";
?>

<!DOCTYPE html>
<html>
<head>
    <title>QR Code - Tickex</title>
    <style>
        body {
            background: #0f172a;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .qr-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        h2 {
            color: #facc15;
            margin-bottom: 20px;
        }
        .qr-image {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            display: inline-block;
        }
        .qr-image img {
            width: 250px;
            height: 250px;
        }
        .ticket-info {
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        .ticket-info p {
            margin: 8px 0;
            color: #ccc;
            font-size: 14px;
        }
        .ticket-info strong {
            color: #facc15;
        }
        .ticket-code {
            background: #0f172a;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 16px;
            color: #facc15;
            margin: 15px 0;
            border: 1px dashed #facc15;
        }
        .btn {
            display: inline-block;
            background: #facc15;
            color: black;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin: 5px;
        }
        .btn:hover {
            background: #eab308;
        }
        .btn-print {
            background: #334155;
            color: white;
        }
        .btn-print:hover {
            background: #475569;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .qr-container {
                background: white;
                box-shadow: none;
            }
            h2 { color: black; }
            .btn, .btn-print { display: none; }
            .ticket-info { background: #f0f0f0; }
            .ticket-info strong { color: black; }
            .ticket-info p { color: #333; }
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <h2>🎟️ Your Ticket QR Code</h2>
        
        <div class="qr-image">
            <img src="<?php echo $qr_url; ?>" alt="QR Code">
        </div>
        
        <div class="ticket-code">
            <?php echo $ticket['ticket_code']; ?>
        </div>
        
        <div class="ticket-info">
            <p><strong>Event:</strong> <?php echo $ticket['title']; ?></p>
            <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($ticket['event_date'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($ticket['event_time'])); ?></p>
            <p><strong>Location:</strong> <?php echo $ticket['location']; ?></p>
            <p><strong>Ticket Type:</strong> <?php echo $ticket['ticket_type_name']; ?></p>
            <p><strong>Holder:</strong> <?php echo $_SESSION['user_name']; ?></p>
        </div>
        
        <div>
            <button onclick="window.print()" class="btn btn-print">🖨️ Print</button>
            <a href="my_tickets.php" class="btn">← Back to Tickets</a>
        </div>
    </div>
</body>
</html>