<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$buyer_id = $_SESSION['user_id'];
$resale_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = "";
$error = "";

// Get resale ticket details
$resale = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT r.*, u.full_name as seller_name, e.title, e.event_date, e.event_time, e.location, e.image, t.ticket_type_name, t.quantity as original_ticket_quantity
    FROM ticket_resales r
    JOIN users u ON r.seller_user_id = u.id
    JOIN tickets t ON r.original_ticket_id = t.id
    JOIN events e ON t.event_id = e.id
    WHERE r.id = $resale_id AND r.status = 'available'
"));

if(!$resale) {
    header("Location: resale_listings.php");
    exit();
}

// Check if buyer is trying to buy their own ticket
if($resale['seller_user_id'] == $buyer_id) {
    $error = "You cannot buy your own ticket!";
}

// Calculate per-ticket prices
$resale_quantity = $resale['quantity'] ?? 1;
$original_total = $resale['original_price'];
$resale_total = $resale['resale_price'];
$original_per_ticket = $original_total / $resale_quantity;
$resale_per_ticket = $resale_total / $resale_quantity;

// Handle purchase
if(isset($_POST['confirm_purchase'])) {
    $buy_quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : $resale_quantity;
    
    if($buy_quantity > $resale_quantity) {
        $error = "Only {$resale_quantity} ticket(s) available";
    } elseif($buy_quantity <= 0) {
        $error = "Please select a valid quantity";
    } elseif($error) {
        // Don't proceed
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            $total_price = $resale_per_ticket * $buy_quantity;
            
            // Update resale (reduce quantity or mark as sold)
            if($buy_quantity == $resale_quantity) {
                // All tickets sold
                mysqli_query($conn, "UPDATE ticket_resales SET status = 'sold', buyer_user_id = $buyer_id, sold_at = NOW() WHERE id = $resale_id");
            } else {
                // Partial sale - reduce quantity and update prices
                $new_quantity = $resale_quantity - $buy_quantity;
                $new_original_total = $original_per_ticket * $new_quantity;
                $new_resale_total = $resale_per_ticket * $new_quantity;
                
                mysqli_query($conn, "UPDATE ticket_resales SET 
                    quantity = $new_quantity, 
                    remaining_quantity = $new_quantity,
                    original_price = $new_original_total,
                    resale_price = $new_resale_total
                    WHERE id = $resale_id");
                
                // Also create a record for the sold portion (optional - for tracking)
                mysqli_query($conn, "
                    INSERT INTO ticket_resales_sold (resale_id, buyer_user_id, quantity, amount, sold_at)
                    VALUES ($resale_id, $buyer_id, $buy_quantity, $total_price, NOW())
                ");
            }
            
            // Get the original ticket
            $original_ticket = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tickets WHERE id = " . $resale['original_ticket_id']));
            
            // Create new ticket for buyer
            $new_ticket_code = strtoupper(uniqid("TKT") . rand(1000, 9999));
            $insert = mysqli_query($conn, "
                INSERT INTO tickets (user_id, event_id, ticket_type_id, ticket_type_name, quantity, total_price, payment_method, ticket_code, status, phone)
                VALUES (
                    $buyer_id, 
                    {$original_ticket['event_id']}, 
                    {$original_ticket['ticket_type_id']}, 
                    '{$original_ticket['ticket_type_name']}', 
                    $buy_quantity, 
                    $total_price, 
                    'Resale Purchase', 
                    '$new_ticket_code', 
                    'Confirmed',
                    '{$_SESSION['user_phone']}'
                )
            ");
            
            if($insert) {
                $new_ticket_id = mysqli_insert_id($conn);
                mysqli_commit($conn);
                
                $savings = ($original_per_ticket - $resale_per_ticket) * $buy_quantity;
                $message = "✅ {$buy_quantity} ticket(s) purchased successfully! You saved KES " . number_format($savings);
                $success_ticket_id = $new_ticket_id;
            } else {
                throw new Exception("Failed to create ticket");
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Purchase failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Buy Resale Ticket - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .purchase-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            margin: 50px auto;
        }
        .ticket-info {
            background: #0f172a;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .price-breakdown {
            background: #0f172a;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .original-price {
            text-decoration: line-through;
            color: #999;
        }
        .resale-price {
            font-size: 32px;
            color: #facc15;
            font-weight: bold;
        }
        .savings {
            color: #00aa00;
            font-weight: bold;
        }
        .quantity-selector {
            margin: 20px 0;
            padding: 15px;
            background: #0f172a;
            border-radius: 8px;
            text-align: center;
        }
        .quantity-selector select {
            padding: 10px 15px;
            border-radius: 5px;
            background: #1e293b;
            color: white;
            border: 1px solid #facc15;
            font-size: 16px;
            margin-left: 10px;
        }
        .message {
            background: #00aa00;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error {
            background: #ff0000;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .btn-confirm {
            background: #facc15;
            color: black;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 18px;
            width: 100%;
            cursor: pointer;
        }
        .btn-confirm:hover {
            background: #eab308;
        }
        .total-price {
            font-size: 20px;
            color: #facc15;
            font-weight: bold;
            margin-top: 10px;
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
        </ul>
    </div>

    <div class="main">
        <div class="purchase-container">
            <h2 style="color: #facc15; text-align: center;">🎟️ Purchase Resale Ticket</h2>
            
            <?php if($message): ?>
                <div class="message">
                    <?php echo $message; ?>
                    <br><br>
                    <a href="my_tickets.php" class="btn">View My Tickets</a>
                    <a href="resale_listings.php" class="btn btn-outline">Browse More Deals</a>
                </div>
            <?php else: ?>
            
            <?php if($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="ticket-info">
                <h3><?php echo $resale['title']; ?></h3>
                <p>📅 <?php echo date('d M Y', strtotime($resale['event_date'])); ?> at <?php echo date('h:i A', strtotime($resale['event_time'])); ?></p>
                <p>📍 <?php echo $resale['location']; ?></p>
                <p>🎫 Ticket Type: <?php echo $resale['ticket_type_name']; ?></p>
                <p>👤 Seller: <?php echo $resale['seller_name']; ?></p>
                <p>📦 Available: <?php echo $resale_quantity; ?> ticket(s)</p>
            </div>
            
            <div class="price-breakdown">
                <div style="text-align: center;">
                    <span class="original-price">Original: KES <?php echo number_format($original_per_ticket); ?> each</span><br>
                    <span class="resale-price">Resale: KES <?php echo number_format($resale_per_ticket); ?> each</span>
                    <div class="savings">✨ Save KES <?php echo number_format($original_per_ticket - $resale_per_ticket); ?> per ticket (<?php echo round(($original_per_ticket - $resale_per_ticket) / $original_per_ticket * 100); ?>% off)</div>
                </div>
            </div>
            
            <form method="POST">
                <div class="quantity-selector">
                    <label>🎫 How many tickets do you want to buy?</label>
                    <select name="quantity" id="quantity" onchange="updateTotal()">
                        <?php for($i = 1; $i <= $resale_quantity; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> ticket(s) - KES <?php echo number_format($resale_per_ticket * $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div style="text-align: center; margin: 15px 0;">
                    <span class="total-price" id="totalDisplay">Total: KES <?php echo number_format($resale_per_ticket); ?></span>
                </div>
                
                <button type="submit" name="confirm_purchase" class="btn-confirm">
                    ✅ Confirm Purchase
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 15px;">
                <a href="resale_listings.php" style="color: #facc15;">← Back to Resale Listings</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateTotal() {
    let qty = document.getElementById('quantity').value;
    let pricePerTicket = <?php echo $resale_per_ticket; ?>;
    let total = qty * pricePerTicket;
    document.getElementById('totalDisplay').innerHTML = 'Total: KES ' + total.toLocaleString();
}
</script>

</body>
</html>