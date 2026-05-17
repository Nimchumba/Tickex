<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's tickets
$tickets = mysqli_query($conn, "
    SELECT t.*, e.title, e.event_date, e.event_time, e.location, e.image 
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE t.user_id = $user_id
    ORDER BY t.purchase_date DESC
");

// Get stats
$stats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_bookings,
        SUM(quantity) as total_tickets,
        SUM(total_price) as total_spent
    FROM tickets 
    WHERE user_id = $user_id
");
$user_stats = mysqli_fetch_assoc($stats);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Tickets - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .tickets-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        .tickets-header h1 {
            font-size: 48px;
            color: #facc15;
            margin-bottom: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #1e293b;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 {
            color: #facc15;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: white;
        }
        .success-msg {
            background: #00aa00;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 18px;
        }
        .ticket-card {
            background: #1e293b;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 20px;
            display: flex;
            border-left: 5px solid;
            transition: transform 0.2s;
        }
        .ticket-card:hover {
            transform: scale(1.01);
        }
        .ticket-card.regular {
            border-left-color: #ffffff;
        }
        .ticket-card.vip {
            border-left-color: #facc15;
        }
        .ticket-card.vvip {
            border-left-color: #ff0000;
        }
        .ticket-image {
            width: 200px;
            height: 150px;
            object-fit: cover;
        }
        .ticket-info {
            padding: 20px;
            flex: 1;
        }
        .ticket-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .ticket-type-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .ticket-type-badge.regular {
            background: white;
            color: black;
        }
        .ticket-type-badge.vip {
            background: #facc15;
            color: black;
        }
        .ticket-type-badge.vvip {
            background: #ff0000;
            color: white;
        }
        .ticket-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 15px 0;
        }
        .ticket-details p {
            color: #ccc;
            margin: 0;
        }
        .ticket-details strong {
            color: white;
            display: block;
            margin-bottom: 5px;
        }
        .ticket-code {
            background: #0f172a;
            padding: 12px 20px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 16px;
            color: #facc15;
            letter-spacing: 1px;
            display: inline-block;
            border: 1px dashed #facc15;
            cursor: pointer;
            transition: 0.2s;
        }
        .ticket-code:hover {
            background: #facc15;
            color: black;
        }
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-left: 15px;
        }
        .status-badge.confirmed {
            background: #00aa00;
            color: white;
        }
        .no-tickets {
            text-align: center;
            padding: 60px;
            background: #1e293b;
            border-radius: 15px;
        }
        .no-tickets h2 {
            color: #facc15;
            margin-bottom: 20px;
        }
        .no-tickets p {
            color: #999;
            margin-bottom: 30px;
        }
        .purchase-date {
            margin-top: 15px;
            font-size: 12px;
            color: #666;
        }
        
        /* PRO TIP STYLES */
        .pro-tip-section {
            background: linear-gradient(135deg, #2d3748, #1a202c);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #facc15;
            position: relative;
        }
        .pro-tip-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .pro-tip-title span {
            background: #facc15;
            color: black;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .pro-tip-title h3 {
            color: #facc15;
            font-size: 18px;
        }
        .pro-tip-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .pro-tip-card {
            background: #1e293b;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #facc15;
        }
        .pro-tip-card h4 {
            color: #facc15;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .pro-tip-card p {
            color: #ccc;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .pro-tip-card .tip-example {
            background: #0f172a;
            padding: 8px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            color: #facc15;
        }
        .copy-feedback {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #00aa00;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: none;
            z-index: 1000;
        }
        .qr-preview {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            z-index: 1000;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .qr-preview img {
            max-width: 300px;
        }
        .qr-preview.show {
            display: block;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 999;
        }
        .overlay.show {
            display: block;
        }
        
        /* Resell Button Styles */
        .resell-btn {
            background: #dc2626;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
        }
        .resell-btn:hover {
            background: #b91c1c;
            transform: scale(1.02);
        }
        
        /* Quantity Selector Styles */
        .sell-qty-select {
            padding: 8px;
            border-radius: 5px;
            background: #0f172a;
            color: white;
            border: 1px solid #facc15;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .pro-tip-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Copy feedback notification -->
<div id="copyFeedback" class="copy-feedback">✅ Ticket code copied to clipboard!</div>

<!-- QR Preview Modal -->
<div id="qrModal" class="qr-preview">
    <img id="qrImage" src="" alt="QR Code">
    <button onclick="closeQR()" class="btn" style="margin-top: 10px; width: 100%;">Close</button>
</div>
<div id="overlay" class="overlay" onclick="closeQR()"></div>

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
            <li class="active">🎟️ My Tickets</li>
            <li onclick="location.href='wishlist.php'">💙 Wishlist</li>
            <li onclick="location.href='calendar.php'">📅 Calendar</li>
            <li onclick="location.href='resale_listings.php'">🔥 Resale</li>
        </ul>
        
        <div style="margin-top: 50px;">
            <a href="logout.php" class="btn" style="width: 100%; text-align: center;">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="tickets-header">
            <h1>My Tickets</h1>
            <p>Your purchased tickets and booking history</p>
        </div>
        
        <!-- PRO TIP SECTION - Only show if user has tickets -->
        <?php if($user_stats['total_bookings'] > 0): ?>
        <div class="pro-tip-section">
            <div class="pro-tip-title">
                <span>🔥 PRO TIP</span>
                <h3>Make the most of your tickets</h3>
            </div>
            
            <div class="pro-tip-grid">
                <div class="pro-tip-card">
                    <h4>📱 QR Code Tips</h4>
                    <p>• Save QR code to your phone for quick access</p>
                    <p>• Take screenshot as backup</p>
                    <p>• No internet? QR code works offline!</p>
                    <div class="tip-example">💡 Pro tip: Add to Google Wallet/Apple Wallet</div>
                </div>
                
                <div class="pro-tip-card">
                    <h4>📥 PDF Ticket Tips</h4>
                    <p>• Download and save before event day</p>
                    <p>• Print if you prefer physical copy</p>
                    <p>• Share with friends if group tickets</p>
                    <div class="tip-example">💡 Pro tip: Email PDF to yourself as backup</div>
                </div>
                
                <div class="pro-tip-card">
                    <h4>🎟️ Ticket Code Tips</h4>
                    <p>• Click code to copy instantly</p>
                    <p>• Use at self-service kiosks</p>
                    <p>• Quote for customer support</p>
                    <div class="tip-example">💡 Pro tip: Write code on your phone notes</div>
                </div>
            </div>
            
            <div style="margin-top: 15px; text-align: center; color: #facc15; font-size: 14px;">
                ⚡ Quick tip: Click any ticket code to copy! Try it below 👇
            </div>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['success'])): ?>
            <div class="success-msg">
                ✅ <?php echo isset($_GET['count']) ? $_GET['count'] : 'Ticket'; ?> purchased successfully!
                <br>
                <small style="color: #fff;">Check your tickets below</small>
            </div>
        <?php endif; ?>
        
        <!-- Stats Section -->
        <?php if($user_stats['total_bookings'] > 0): ?>
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <div class="stat-number"><?php echo $user_stats['total_bookings']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Tickets</h3>
                <div class="stat-number"><?php echo $user_stats['total_tickets']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Spent</h3>
                <div class="stat-number">KES <?php echo number_format($user_stats['total_spent']); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if(mysqli_num_rows($tickets) > 0): ?>
            <?php while($ticket = mysqli_fetch_assoc($tickets)): 
                $type_class = strtolower($ticket['ticket_type_name']);
                $price_per_ticket = $ticket['total_price'] / $ticket['quantity'];
            ?>
                <div class="ticket-card <?php echo $type_class; ?>">
                    <img src="assets/images/<?php echo $ticket['image'] ? $ticket['image'] : 'placeholder.jpg'; ?>" 
                         class="ticket-image"
                         onerror="this.src='assets/images/placeholder.jpg'">
                    
                    <div class="ticket-info">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="color: <?php 
                                echo $ticket['ticket_type_name'] == 'VIP' ? '#facc15' : 
                                    ($ticket['ticket_type_name'] == 'VVIP' ? '#ff0000' : 'white'); 
                            ?>;"><?php echo $ticket['title']; ?></h3>
                            <span class="ticket-type-badge <?php echo $type_class; ?>">
                                🎫 <?php echo $ticket['ticket_type_name']; ?>
                            </span>
                        </div>
                        
                        <div class="ticket-details">
                            <div>
                                <strong>📅 Date</strong>
                                <?php echo date('d M Y', strtotime($ticket['event_date'])); ?>
                            </div>
                            <div>
                                <strong>⏰ Time</strong>
                                <?php echo date('h:i A', strtotime($ticket['event_time'])); ?>
                            </div>
                            <div>
                                <strong>📍 Location</strong>
                                <?php echo $ticket['location']; ?>
                            </div>
                            <div>
                                <strong>🎫 Quantity</strong>
                                <?php echo $ticket['quantity']; ?> ticket(s)
                            </div>
                            <div>
                                <strong>💰 Unit Price</strong>
                                KES <?php echo number_format($price_per_ticket); ?>
                            </div>
                            <div>
                                <strong>💳 Total Paid</strong>
                                KES <?php echo number_format($ticket['total_price']); ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <span class="ticket-code" onclick="copyTicketCode('<?php echo $ticket['ticket_code']; ?>')" title="Click to copy">
                                🎟️ <?php echo $ticket['ticket_code']; ?>
                            </span>
                            <span class="status-badge confirmed">✓ <?php echo $ticket['status']; ?></span>
                            <span style="color: #999; font-size: 14px;">
                                💳 <?php echo $ticket['payment_method']; ?>
                            </span>
                        </div>

                        <!-- Download & Resell buttons with Quantity Selector -->
                        <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                            <a href="qr_ticket.php?id=<?php echo $ticket['id']; ?>" 
                               class="btn" 
                               style="padding: 8px 15px; font-size: 14px;">
                                📱 View QR Code
                            </a>
                            <a href="generate_pdf.php?id=<?php echo $ticket['id']; ?>" 
                               class="btn btn-outline" 
                               style="padding: 8px 15px; font-size: 14px;">
                                📥 Download PDF Ticket
                            </a>
                            <button class="btn btn-outline" 
                                    style="padding: 8px 15px; font-size: 14px;"
                                    onclick="shareTicket('<?php echo $ticket['ticket_code']; ?>', '<?php echo $ticket['title']; ?>')">
                                📤 Share
                            </button>
                            
                            <!-- Quantity Selector for Resell -->
                            <select id="sell_qty_<?php echo $ticket['id']; ?>" class="sell-qty-select">
                                <?php for($i = 1; $i <= $ticket['quantity']; $i++): ?>
                                    <option value="<?php echo $i; ?>">Sell <?php echo $i; ?> of <?php echo $ticket['quantity']; ?></option>
                                <?php endfor; ?>
                            </select>
                            
                            <button class="resell-btn" 
                                    onclick="resellTicket(<?php echo $ticket['id']; ?>, <?php echo $ticket['total_price']; ?>, <?php echo $ticket['quantity']; ?>)">
                                🔄 Resell Selected
                            </button>
                        </div>             
                        
                        <div class="purchase-date">
                            Purchased: <?php echo date('d M Y h:i A', strtotime($ticket['purchase_date'])); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-tickets">
                <h2>🎫 No Tickets Yet</h2>
                <p>You haven't purchased any tickets. Browse events and get your tickets now!</p>
                <a href="dashboard.php" class="btn" style="padding: 15px 40px;">Browse Events</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Function to copy ticket code to clipboard
function copyTicketCode(code) {
    navigator.clipboard.writeText(code).then(function() {
        let feedback = document.getElementById('copyFeedback');
        feedback.style.display = 'block';
        setTimeout(function() {
            feedback.style.display = 'none';
        }, 2000);
    }, function(err) {
        alert('Failed to copy code. Please copy manually: ' + code);
    });
}

// Function to show QR preview
function showQRPreview(event, ticketId) {
    event.preventDefault();
    let qrModal = document.getElementById('qrModal');
    let overlay = document.getElementById('overlay');
    let qrImage = document.getElementById('qrImage');
    
    qrImage.src = 'generate_qr.php?id=' + ticketId + '&t=' + new Date().getTime();
    qrModal.classList.add('show');
    overlay.classList.add('show');
}

// Function to close QR preview
function closeQR() {
    document.getElementById('qrModal').classList.remove('show');
    document.getElementById('overlay').classList.remove('show');
}

// Function to share ticket
function shareTicket(code, eventName) {
    if (navigator.share) {
        navigator.share({
            title: 'My Ticket - ' + eventName,
            text: 'I just got my ticket for ' + eventName + '! Code: ' + code,
            url: window.location.href,
        }).catch(console.error);
    } else {
        alert('Share this ticket code: ' + code);
    }
}

// Updated Function to resell ticket with quantity selection
function resellTicket(ticketId, totalPrice, totalQuantity) {
    let qtySelect = document.getElementById('sell_qty_' + ticketId);
    let quantity = parseInt(qtySelect.value);
    let pricePerTicket = totalPrice / totalQuantity;
    let resalePricePerTicket = Math.round(pricePerTicket * 0.8);
    let totalResalePrice = resalePricePerTicket * quantity;
    
    if(confirm(`Sell ${quantity} ticket(s) for KES ${totalResalePrice.toLocaleString()}? (20% less per ticket - Original: KES ${Math.round(pricePerTicket).toLocaleString()} each)`)) {
        window.location.href = `resell_ticket.php?id=${ticketId}&qty=${quantity}&price=${totalResalePrice}`;
    }
}

// Add keyboard support for closing modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQR();
    }
});

// Pro tip: Double click ticket to copy
document.querySelectorAll('.ticket-code').forEach(function(element) {
    element.addEventListener('dblclick', function() {
        let code = this.innerText.replace('🎟️', '').trim();
        copyTicketCode(code);
    });
});
</script>

</body>
</html>