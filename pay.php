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

// Get event and ticket info from URL
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$ticket_type = isset($_GET['type']) ? $_GET['type'] : 'Regular';
$quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

if($event_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Get event details
$event = mysqli_query($conn, "SELECT * FROM events WHERE id = $event_id");
$event_data = mysqli_fetch_assoc($event);

// Calculate price based on ticket type
$base_price = $event_data['price'];
if($ticket_type == 'VIP') {
    $final_price = $base_price * 1.3;
} elseif($ticket_type == 'VVIP') {
    $final_price = $base_price * 1.6;
} else {
    $final_price = $base_price;
}
$total = $final_price * $quantity;

// Handle payment submission
if(isset($_POST['pay_now'])) {
    $mpesa_code = mysqli_real_escape_string($conn, $_POST['mpesa_code']);
    
    // Generate ticket code
    $ticket_code = strtoupper(uniqid("TKT") . rand(1000, 9999));
    $transaction_code = strtoupper(uniqid("MPESA") . rand(100, 999));
    
    // Save to database
    $insert = mysqli_query($conn, "
        INSERT INTO tickets 
        (user_id, event_id, ticket_type_name, quantity, total_price, payment_method, mpesa_code, transaction_code, ticket_code, status) 
        VALUES 
        ($user_id, $event_id, '$ticket_type', $quantity, $total, 'M-Pesa', '$mpesa_code', '$transaction_code', '$ticket_code', 'Confirmed')
    ");
    
    if($insert) {
        $ticket_id = mysqli_insert_id($conn);
        $message = "✅ Payment successful!";
    } else {
        $error = "Payment failed: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>M-Pesa Payment - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .payment-box {
            background: #1e293b;
            padding: 30px;
            border-radius: 15px;
            max-width: 450px;
            margin: 40px auto;
        }
        .event-summary {
            background: #0f172a;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #facc15;
        }
        .mpesa-instructions {
            background: #0f172a;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .instruction-step {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
            color: #ccc;
        }
        .step-number {
            background: #facc15;
            color: black;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .highlight {
            background: #334155;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 18px;
            color: #facc15;
            text-align: center;
            margin: 10px 0;
        }
        .form-group {
            margin: 20px 0;
        }
        .form-group label {
            display: block;
            color: white;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            font-size: 16px;
        }
        .pay-btn {
            background: #facc15;
            color: black;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 18px;
            width: 100%;
            cursor: pointer;
            margin-top: 20px;
        }
        .pay-btn:hover {
            background: #eab308;
        }
        .message {
            background: #00aa00;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .error {
            background: #ff0000;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .total {
            font-size: 28px;
            color: #facc15;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
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
            <li onclick="location.href='my_tickets.php'">🎟️ My Tickets</li>
            <li onclick="location.href='logout.php'">🚪 Logout</li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="payment-box">
            <h2 style="color: #facc15; text-align: center;">📱 M-Pesa Payment</h2>
            
            <?php if($message): ?>
                <div class="message">
                    ✅ <?php echo $message; ?>
                    <br>
                    <small>Your ticket has been confirmed</small>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="my_tickets.php" class="btn">View My Tickets</a>
                    <a href="dashboard.php" class="btn btn-outline" style="margin-left: 10px;">Browse More</a>
                </div>
            <?php else: ?>
            
            <!-- Event Summary -->
            <div class="event-summary">
                <h3 style="color: white; margin-bottom: 10px;"><?php echo $event_data['title']; ?></h3>
                <p style="color: #ccc;">📅 <?php echo date('d M Y', strtotime($event_data['event_date'])); ?></p>
                <p style="color: #ccc;">📍 <?php echo $event_data['location']; ?></p>
                <p style="color: #ccc;">🎫 <?php echo $ticket_type; ?> × <?php echo $quantity; ?></p>
            </div>
            
            <!-- Total Amount -->
            <div class="total">
                KES <?php echo number_format($total); ?>
            </div>
            
            <?php if($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- M-Pesa Instructions -->
            <div class="mpesa-instructions">
                <h3 style="color: #facc15; margin-bottom: 15px;">📱 How to pay via M-Pesa:</h3>
                
                <div class="instruction-step">
                    <span class="step-number">1</span>
                    <span>Go to <strong>M-Pesa</strong> on your phone</span>
                </div>
                
                <div class="instruction-step">
                    <span class="step-number">2</span>
                    <span>Select <strong>Lipa Na M-Pesa</strong></span>
                </div>
                
                <div class="instruction-step">
                    <span class="step-number">3</span>
                    <span>Select <strong>Pay Bill</strong></span>
                </div>
                
                <div class="instruction-step">
                    <span class="step-number">4</span>
                    <span>Enter Business No:</span>
                </div>
                <div class="highlight">174379</div>
                
                <div class="instruction-step">
                    <span class="step-number">5</span>
                    <span>Enter Account No:</span>
                </div>
                <div class="highlight"><?php echo $event_id . $ticket_type; ?></div>
                
                <div class="instruction-step">
                    <span class="step-number">6</span>
                    <span>Enter Amount:</span>
                </div>
                <div class="highlight">KES <?php echo number_format($total); ?></div>
                
                <div class="instruction-step">
                    <span class="step-number">7</span>
                    <span>Enter your PIN and <strong>OK</strong></span>
                </div>
            </div>
            
            <!-- Payment Form -->
            <form method="POST">
                <div class="form-group">
                    <label>📲 Enter M-Pesa Confirmation Code:</label>
                    <input type="text" name="mpesa_code" placeholder="e.g. QWE2R3T4Y5" required pattern="[A-Z0-9]{10,}" title="Enter the code from M-Pesa message">
                    <small style="color: #999;">Enter the code from the M-Pesa confirmation SMS</small>
                </div>
                
                <button type="submit" name="pay_now" class="pay-btn">
                    ✅ Confirm Payment
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: #999;">
                <small>Ticket will be issued immediately after confirmation</small>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>