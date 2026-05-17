<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get event ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch event details
$result = mysqli_query($conn, 
    "SELECT e.*, c.name as category_name, c.id as category_id
     FROM events e 
     JOIN categories c ON e.category_id = c.id 
     WHERE e.id = $id");
     
if(mysqli_num_rows($result) == 0) {
    header("Location: dashboard.php");
    exit();
}

$event = mysqli_fetch_assoc($result);

// Get ticket types
$ticket_types = mysqli_query($conn, "SELECT * FROM ticket_types WHERE event_id = $id ORDER BY price_multiplier");
$ticket_types_data = [];
while($t = mysqli_fetch_assoc($ticket_types)) {
    $ticket_types_data[] = $t;
}

// Check if event is in user's wishlist
$wishlist_check = mysqli_query($conn, "SELECT * FROM wishlist WHERE user_id = " . $_SESSION['user_id'] . " AND event_id = $id");
$in_wishlist = mysqli_num_rows($wishlist_check) > 0;

// Get seat selection status
$allow_seat_selection = $event['allow_seat_selection'] ?? 1;
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $event['title']; ?> - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .ticket-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        .ticket-card {
            background: #1e293b;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            border-left: 4px solid;
            transition: 0.3s;
        }
        .ticket-card.regular { border-left-color: white; }
        .ticket-card.vip { border-left-color: #facc15; }
        .ticket-card.vvip { border-left-color: #ff0000; }
        .ticket-card.selected { border: 2px solid #facc15; transform: scale(1.02); }
        .ticket-name { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .ticket-name.regular { color: white; }
        .ticket-name.vip { color: #facc15; }
        .ticket-name.vvip { color: #ff0000; }
        .ticket-price { font-size: 28px; color: #facc15; font-weight: bold; margin: 10px 0; }
        .quantity-selector { display: flex; align-items: center; gap: 20px; margin: 20px 0; padding: 15px; background: #1e293b; border-radius: 10px; }
        .total-display { background: #1e293b; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; border: 1px solid #facc15; }
        .total-display .amount { font-size: 42px; color: #facc15; font-weight: bold; }
        .buy-btn { background: #facc15; color: black; padding: 15px; border: none; border-radius: 10px; font-weight: bold; font-size: 20px; width: 100%; cursor: pointer; text-decoration: none; display: block; text-align: center; }
        .wishlist-btn { background: transparent; border: 2px solid #facc15; color: #facc15; padding: 8px 15px; border-radius: 8px; cursor: pointer; }
        .wishlist-btn.active { background: #facc15; color: black; }
        .event-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .seat-info { background: #0f172a; padding: 8px 15px; border-radius: 20px; font-size: 12px; display: inline-block; margin-top: 10px; }
        @media (max-width: 768px) { .ticket-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <h2 class="logo">Tickex</h2>
        <p style="color: #facc15;">Welcome, <?php echo $_SESSION['user_name']; ?></p>
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
        <div style="margin-top: 50px;"><a href="logout.php" class="btn" style="width: 100%; text-align: center;">Logout</a></div>
    </div>

    <div class="main">
        <a href="category.php?id=<?php echo $event['category_id']; ?>" class="btn btn-outline" style="margin-bottom: 20px;">← Back</a>
        
        <div style="display: flex; gap: 40px; background: #1e293b; padding: 30px; border-radius: 15px;">
            <div style="flex: 1;">
                <img src="assets/images/<?php echo $event['image'] ?: 'placeholder.jpg'; ?>" style="width: 100%; border-radius: 10px;">
            </div>
            <div style="flex: 2;">
                <div class="event-header">
                    <h1 style="font-size: 36px;"><?php echo $event['title']; ?></h1>
                    <button class="wishlist-btn <?php echo $in_wishlist ? 'active' : ''; ?>" onclick="toggleWishlist(<?php echo $id; ?>, this)">
                        💙 <?php echo $in_wishlist ? 'Saved' : 'Save'; ?>
                    </button>
                </div>
                <p style="color: #facc15;">Category: <?php echo $event['category_name']; ?></p>
                <p>📅 <?php echo date('l, d F Y', strtotime($event['event_date'])); ?></p>
                <p>⏰ <?php echo date('h:i A', strtotime($event['event_time'])); ?></p>
                <p>📍 <?php echo $event['location']; ?></p>
                
                <div class="ticket-grid">
                    <?php foreach($ticket_types_data as $index => $ticket): 
                        $price = $event['price'] * $ticket['price_multiplier'];
                        $class = strtolower($ticket['type_name']);
                    ?>
                    <div class="ticket-card <?php echo $class; ?> <?php echo $index == 0 ? 'selected' : ''; ?>" 
                         onclick="selectTicket('<?php echo $ticket['type_name']; ?>', <?php echo $price; ?>, <?php echo $ticket['id']; ?>)"
                         id="card-<?php echo $ticket['type_name']; ?>">
                        <div class="ticket-name <?php echo $class; ?>"><?php echo $ticket['type_name']; ?></div>
                        <div class="ticket-price">KES <?php echo number_format($price); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="quantity-selector">
                    <label>Quantity:</label>
                    <select id="quantity">
                        <?php for($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> ticket(s)</option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <input type="hidden" id="selected-type" value="Regular">
                <input type="hidden" id="selected-price" value="<?php echo $event['price']; ?>">
                <input type="hidden" id="selected-type-id" value="<?php echo $ticket_types_data[0]['id']; ?>">
                
                <div class="total-display">
                    <div class="amount" id="total-amount">KES <?php echo number_format($event['price']); ?></div>
                </div>
                
                <?php if($allow_seat_selection == 1): ?>
                    <a href="#" id="pay-link" class="buy-btn" onclick="goToSeatSelection(event)">🪑 Select Seats & Continue</a>
                    <div class="seat-info">💡 This event has assigned seating. Please select your seats.</div>
                <?php else: ?>
                    <a href="#" id="pay-link" class="buy-btn">📱 Verify with OTP</a>
                    <div class="seat-info">🎟️ General admission - No seat selection required.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
let selectedType = 'Regular';
let selectedPrice = <?php echo $event['price']; ?>;
let selectedTypeId = <?php echo $ticket_types_data[0]['id']; ?>;
let quantity = 1;
let allowSeatSelection = <?php echo $allow_seat_selection; ?>;

function selectTicket(type, price, id) {
    document.querySelectorAll('.ticket-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('card-' + type).classList.add('selected');
    selectedType = type;
    selectedPrice = price;
    selectedTypeId = id;
    document.getElementById('selected-type').value = type;
    document.getElementById('selected-type-id').value = id;
    updateTotal();
}

function updateTotal() {
    quantity = document.getElementById('quantity').value;
    let total = selectedPrice * quantity;
    document.getElementById('total-amount').innerHTML = 'KES ' + total.toLocaleString();
    updatePayLink();
}

function updatePayLink() {
    let eventId = <?php echo $id; ?>;
    let qty = quantity;
    let link = document.getElementById('pay-link');
    if(allowSeatSelection == 1) {
        link.href = `seat_selection.php?event_id=${eventId}&type=${selectedType}&qty=${qty}`;
    } else {
        link.href = `otp_payment.php?event_id=${eventId}&type=${selectedType}&qty=${qty}`;
    }
}

function goToSeatSelection(event) {
    event.preventDefault();
    window.location.href = `seat_selection.php?event_id=<?php echo $id; ?>&type=${selectedType}&qty=${quantity}`;
}

function toggleWishlist(eventId, btn) {
    fetch('wishlist_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'event_id=' + eventId
    })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'added') {
            btn.innerHTML = '💙 Saved';
            btn.classList.add('active');
        } else {
            btn.innerHTML = '💙 Save';
            btn.classList.remove('active');
        }
    });
}

document.getElementById('quantity').addEventListener('change', updateTotal);
updateTotal();
</script>

</body>
</html>