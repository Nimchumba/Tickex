<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";
$step = 1; // Step 1: Phone, Step 2: OTP

// Get event and ticket details from URL
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$ticket_type = isset($_GET['type']) ? $_GET['type'] : 'Regular';
$quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

// Get event details
$event_result = mysqli_query($conn, "SELECT * FROM events WHERE id = $event_id");
$event = mysqli_fetch_assoc($event_result);

if(!$event) {
    header("Location: dashboard.php");
    exit();
}

// Calculate price
$base_price = $event['price'];
if($ticket_type == 'VIP') {
    $final_price = $base_price * 1.3;
} elseif($ticket_type == 'VVIP') {
    $final_price = $base_price * 1.6;
} else {
    $final_price = $base_price;
}
$total = $final_price * $quantity;

$selected_seats = $_SESSION['selected_seats'] ?? [];
$selected_seat_count = count($selected_seats);
if(empty($selected_seats) || $event_id == 0) {
    header("Location: seat_selection.php?event_id=$event_id&type=$ticket_type&qty=$quantity");
    exit();
}

$seat_total = 0;
$seat_labels = [];
foreach($selected_seats as $seat) {
    $seat_total += floatval($seat['price']);
    $seat_labels[] = $seat['row'] . $seat['number'];
}

if($selected_seat_count !== $quantity) {
    $quantity = $selected_seat_count;
}

$total = $seat_total;

// Handle Send OTP
if(isset($_POST['send_otp'])) {
    $phone = $_POST['phone'];
    
    if(empty($phone)) {
        $error = "Please enter your phone number";
        $step = 1;
    } else {
        $generated_otp = rand(111111, 999999);
        
        $_SESSION['otp'] = $generated_otp;
        $_SESSION['otp_time'] = time();
        $_SESSION['otp_phone'] = $phone;
        
        $message = "✅ OTP sent! Your code is: <strong>$generated_otp</strong>";
        $step = 2;
    }
}

// Handle Verify OTP
if(isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp_code'];
    $stored_otp = $_SESSION['otp'] ?? '';
    $otp_time = $_SESSION['otp_time'] ?? 0;
    $phone = $_SESSION['otp_phone'] ?? '';
    
    if(time() - $otp_time > 300) {
        $error = "OTP has expired. Please request a new one.";
        $step = 1;
    } elseif($entered_otp == $stored_otp) {
        // Get ticket_type_id
        $type_result = mysqli_query($conn, "SELECT id FROM ticket_types WHERE event_id = $event_id AND type_name = '$ticket_type'");
        if(mysqli_num_rows($type_result) == 0) {
            $multiplier = $ticket_type == 'VIP' ? 1.3 : ($ticket_type == 'VVIP' ? 1.6 : 1.0);
            mysqli_query($conn, "INSERT INTO ticket_types (event_id, type_name, price_multiplier, description) 
                                 VALUES ($event_id, '$ticket_type', $multiplier, 'Auto-created')");
            $type_result = mysqli_query($conn, "SELECT id FROM ticket_types WHERE event_id = $event_id AND type_name = '$ticket_type'");
        }
        
        $ticket_type_data = mysqli_fetch_assoc($type_result);
        $ticket_type_id = $ticket_type_data['id'];
        
        $ticket_code = strtoupper(uniqid("TKT") . rand(1000, 9999));
        $transaction_code = "OTP" . time() . rand(100, 999);
        
        $insert = mysqli_query($conn, "
            INSERT INTO tickets (user_id, event_id, ticket_type_id, ticket_type_name, 
            quantity, total_price, payment_method, transaction_code, ticket_code, status, phone)
            VALUES ($user_id, $event_id, $ticket_type_id, '$ticket_type',
            $quantity, $total, 'OTP', '$transaction_code', '$ticket_code', 'Confirmed', '$phone')
        ");
        
        if($insert) {
            $ticket_id = mysqli_insert_id($conn);
            foreach($selected_seats as $seat) {
                $seat_id = (int)$seat['id'];
                mysqli_query($conn, "UPDATE seats SET status = 'booked', booked_at = NOW() WHERE id = $seat_id");
            }
            unset($_SESSION['otp']);
            unset($_SESSION['otp_time']);
            unset($_SESSION['otp_phone']);
            unset($_SESSION['selected_seats']);
            unset($_SESSION['selected_seats_quantity']);
            unset($_SESSION['selected_seats_event_id']);
            unset($_SESSION['selected_seats_ticket_type']);
            
            header("Location: payment_success.php?ticket_id=$ticket_id");
            exit();
        } else {
            $error = "Database error: " . mysqli_error($conn);
            $step = 1;
        }
    } else {
        $error = "Invalid OTP. Please try again.";
        $step = 2;
    }
}

// Handle Resend OTP
if(isset($_POST['resend_otp'])) {
    $phone = $_SESSION['otp_phone'] ?? '';
    if(!empty($phone)) {
        $generated_otp = rand(111111, 999999);
        $_SESSION['otp'] = $generated_otp;
        $_SESSION['otp_time'] = time();
        $message = "✅ New OTP sent: <strong>$generated_otp</strong>";
        $step = 2;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>OTP Payment - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #0f172a; }
        .payment-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 30px;
            max-width: 450px;
            margin: 50px auto;
        }
        .event-summary {
            background: #0f172a;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .amount {
            font-size: 48px;
            color: #facc15;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: white;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            background: #0f172a;
            border: 1px solid #334155;
            color: white;
        }
        .btn-primary {
            background: #facc15;
            color: black;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            width: 100%;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-secondary {
            background: #334155;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            margin-top: 10px;
        }
        .message {
            background: #00aa00;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error {
            background: #ff0000;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .otp-display {
            background: #0f172a;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 2px solid #facc15;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #facc15;
            letter-spacing: 5px;
        }
        h2 { color: #facc15; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #facc15; }
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
        <div class="payment-container">
            <h2>📱 OTP Verification</h2>
            
            <div class="event-summary">
                <h3><?php echo $event['title']; ?></h3>
                <p>📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
                <p>📍 <?php echo $event['location']; ?></p>
                <p>🎫 <?php echo $ticket_type; ?> × <?php echo $quantity; ?></p>
                <p>💺 Seats: <?php echo implode(', ', $seat_labels); ?></p>
            </div>
            
            <div class="amount">KES <?php echo number_format($total); ?></div>
            
            <?php if($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($step == 1): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>📞 Phone Number:</label>
                        <input type="tel" name="phone" placeholder="0712345678" required>
                    </div>
                    <button type="submit" name="send_otp" class="btn-primary">📲 Send OTP Code</button>
                </form>
            <?php else: ?>
                <div class="otp-display">
                    <p>OTP sent to: <strong><?php echo $_SESSION['otp_phone']; ?></strong></p>
                    <div class="otp-code"><?php echo $_SESSION['otp']; ?></div>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>🔐 Enter OTP Code:</label>
                        <input type="text" name="otp_code" placeholder="Enter 6-digit code" maxlength="6" required>
                    </div>
                    <button type="submit" name="verify_otp" class="btn-primary">✅ Verify & Get Ticket</button>
                    <button type="submit" name="resend_otp" class="btn-secondary">📲 Resend OTP</button>
                </form>
            <?php endif; ?>
            
            <a href="seat_selection.php?event_id=<?php echo $event_id; ?>&type=<?php echo urlencode($ticket_type); ?>&qty=<?php echo $quantity; ?>" class="back-link">← Change selected seats</a>
        </div>
    </div>
</div>

</body>
</html>