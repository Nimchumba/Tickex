<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$ticket_type = isset($_GET['type']) ? $_GET['type'] : 'Regular';
$quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

$event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM events WHERE id = $event_id"));
if(!$event) {
    header("Location: dashboard.php");
    exit();
}

// Get venue layout
$venue_layout = $event['venue_layout'] ?? 'theater';

// Get available seats for this ticket type
$seats = mysqli_query($conn, "
    SELECT * FROM seats 
    WHERE event_id = $event_id 
    AND status = 'available' 
    AND seat_type = '$ticket_type'
    ORDER BY seat_row, seat_number
");

$has_seats = mysqli_num_rows($seats) > 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Seats - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .seat-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 30px;
            margin-top: 20px;
        }
        .screen {
            background: #0f172a;
            text-align: center;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            color: #facc15;
            font-weight: bold;
        }
        .seat-grid {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 20px 0;
            overflow-x: auto;
        }
        .seat-row {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .seat {
            width: 50px;
            height: 50px;
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            font-weight: bold;
        }
        .seat.available:hover {
            background: #facc15;
            color: black;
            transform: scale(1.1);
        }
        .seat.selected {
            background: #facc15;
            color: black;
            border-color: #facc15;
        }
        .seat.booked {
            background: #dc2626;
            border-color: #dc2626;
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        /* Stadium Layout Styles */
        .stadium-layout {
            background: #0f172a;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stadium-side {
            background: #1e293b;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .stadium-side h4 {
            color: #facc15;
            text-align: center;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .pitch {
            background: #15803d;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 10px;
            color: white;
            font-weight: bold;
        }
        .seat-row-stadium {
            display: flex;
            gap: 5px;
            margin: 5px 0;
            flex-wrap: wrap;
            justify-content: center;
        }
        .seat-stadium {
            width: 40px;
            height: 40px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            cursor: pointer;
            transition: 0.2s;
        }
        .seat-stadium.available:hover {
            background: #facc15;
            color: black;
            transform: scale(1.05);
        }
        .seat-stadium.selected {
            background: #facc15;
            color: black;
            border-color: #facc15;
        }
        .seat-stadium.booked {
            background: #dc2626;
            border-color: #dc2626;
            cursor: not-allowed;
            opacity: 0.5;
        }
        .seat-stadium.vvip {
            border-top: 3px solid #ff0000;
        }
        .seat-stadium.vip {
            border-top: 3px solid #facc15;
        }
        .seat-stadium.regular {
            border-top: 3px solid #ffffff;
        }
        
        .legend {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .legend-box {
            width: 30px;
            height: 30px;
            border-radius: 5px;
        }
        .selected-seats {
            background: #0f172a;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .confirm-btn {
            background: #facc15;
            color: black;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            width: 100%;
            cursor: pointer;
        }
        .confirm-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .section-divider {
            border-top: 2px solid #facc15;
            margin: 20px 0;
            text-align: center;
            position: relative;
        }
        .section-divider span {
            background: #0f172a;
            padding: 0 10px;
            position: relative;
            top: -12px;
            color: #facc15;
        }
        @media (max-width: 768px) {
            .seat { width: 40px; height: 40px; font-size: 12px; }
            .seat-stadium { width: 28px; height: 28px; font-size: 8px; }
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
        <div class="seat-container">
            <h2 style="color: #facc15;">🪑 Select Your <?php echo $ticket_type; ?> Seats</h2>
            <h3><?php echo $event['title']; ?></h3>
            <p>📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?> | <?php echo $event['location']; ?></p>
            
            <?php if(!$has_seats): ?>
                <div style="background: #facc15; color: black; padding: 20px; border-radius: 8px; text-align: center;">
                    <p>⚠️ No seats available for this ticket type.</p>
                    <a href="event.php?id=<?php echo $event_id; ?>" class="btn" style="margin-top: 10px;">← Back to Event</a>
                </div>
            <?php else: ?>
            
            <?php if($venue_layout == 'stadium'): ?>
                <!-- STADIUM LAYOUT -->
                <div class="stadium-layout">
                    <div class="pitch">
                        ⚽ PITCH / FIELD ⚽
                    </div>
                    
                    <?php
                    // Separate seats by side
                    $home_seats = [];
                    $away_seats = [];
                    
                    mysqli_data_seek($seats, 0);
                    while($seat = mysqli_fetch_assoc($seats)) {
                        if(strpos($seat['seat_row'], 'HOME') !== false) {
                            $home_seats[] = $seat;
                        } else {
                            $away_seats[] = $seat;
                        }
                    }
                    
                    // Group seats by row
                    $home_by_row = [];
                    foreach($home_seats as $seat) {
                        $row_name = $seat['seat_row'];
                        if(!isset($home_by_row[$row_name])) {
                            $home_by_row[$row_name] = [];
                        }
                        $home_by_row[$row_name][] = $seat;
                    }
                    
                    $away_by_row = [];
                    foreach($away_seats as $seat) {
                        $row_name = $seat['seat_row'];
                        if(!isset($away_by_row[$row_name])) {
                            $away_by_row[$row_name] = [];
                        }
                        $away_by_row[$row_name][] = $seat;
                    }
                    
                    // Sort rows by row number (ascending - bottom to top)
                    ksort($home_by_row);
                    ksort($away_by_row);
                    ?>
                    
                    <!-- HOME SIDE -->
                    <div class="stadium-side">
                        <h4>🏠 HOME SECTION</h4>
                        <?php foreach($home_by_row as $row_name => $row_seats):
                            $row_num = explode('-', $row_name)[1];
                            $seat_type_class = '';
                            if($row_num <= 8) {
                                $seat_type_class = 'regular';
                            } elseif($row_num <= 12) {
                                $seat_type_class = 'vip';
                            } else {
                                $seat_type_class = 'vvip';
                            }
                        ?>
                        <div class="seat-row-stadium">
                            <div style="width: 40px; font-size: 12px; color: #facc15;">Row <?php echo $row_num; ?></div>
                            <?php foreach($row_seats as $seat): 
                                $status_class = $seat['status'] == 'available' ? 'available' : 'booked';
                            ?>
                                <div class="seat-stadium <?php echo $status_class; ?> <?php echo $seat_type_class; ?>" 
                                     data-id="<?php echo $seat['id']; ?>"
                                     data-row="<?php echo $seat['seat_row']; ?>"
                                     data-number="<?php echo $seat['seat_number']; ?>"
                                     data-price="<?php echo $seat['price']; ?>"
                                     onclick="selectSeat(this)">
                                    <?php echo $seat['seat_number']; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="section-divider">
                        <span>⚡ CENTER LINE ⚡</span>
                    </div>
                    
                    <!-- AWAY SIDE -->
                    <div class="stadium-side">
                        <h4>✈️ AWAY SECTION</h4>
                        <?php foreach($away_by_row as $row_name => $row_seats):
                            $row_num = explode('-', $row_name)[1];
                            $seat_type_class = '';
                            if($row_num <= 8) {
                                $seat_type_class = 'regular';
                            } elseif($row_num <= 12) {
                                $seat_type_class = 'vip';
                            } else {
                                $seat_type_class = 'vvip';
                            }
                        ?>
                        <div class="seat-row-stadium">
                            <div style="width: 40px; font-size: 12px; color: #facc15;">Row <?php echo $row_num; ?></div>
                            <?php foreach($row_seats as $seat): 
                                $status_class = $seat['status'] == 'available' ? 'available' : 'booked';
                            ?>
                                <div class="seat-stadium <?php echo $status_class; ?> <?php echo $seat_type_class; ?>" 
                                     data-id="<?php echo $seat['id']; ?>"
                                     data-row="<?php echo $seat['seat_row']; ?>"
                                     data-number="<?php echo $seat['seat_number']; ?>"
                                     data-price="<?php echo $seat['price']; ?>"
                                     onclick="selectSeat(this)">
                                    <?php echo $seat['seat_number']; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="legend">
                    <div class="legend-item"><div class="legend-box" style="background: #0f172a; border: 2px solid #334155;"></div><span>Available</span></div>
                    <div class="legend-item"><div class="legend-box" style="background: #facc15;"></div><span>Selected</span></div>
                    <div class="legend-item"><div class="legend-box" style="background: #dc2626;"></div><span>Booked</span></div>
                </div>
                
            <?php else: ?>
                <!-- THEATER / CINEMA LAYOUT -->
                <div class="screen">🎬 SCREEN</div>
                
                <div class="legend">
                    <div class="legend-item"><div class="legend-box" style="background: #0f172a; border: 2px solid #334155;"></div><span>Available</span></div>
                    <div class="legend-item"><div class="legend-box" style="background: #facc15;"></div><span>Selected</span></div>
                    <div class="legend-item"><div class="legend-box" style="background: #dc2626;"></div><span>Booked</span></div>
                </div>
                
                <div class="seat-grid">
                    <?php
                    $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
                    $seats_by_row = [];
                    mysqli_data_seek($seats, 0);
                    while($seat = mysqli_fetch_assoc($seats)) {
                        $seats_by_row[$seat['seat_row']][] = $seat;
                    }
                    
                    foreach($rows as $row):
                        if(!isset($seats_by_row[$row])) continue;
                    ?>
                    <div class="seat-row">
                        <div style="width: 40px; font-weight: bold; color: #facc15;"><?php echo $row; ?></div>
                        <?php for($num = 1; $num <= 15; $num++):
                            $seat = null;
                            foreach($seats_by_row[$row] as $s) {
                                if($s['seat_number'] == $num) {
                                    $seat = $s;
                                    break;
                                }
                            }
                            if($seat):
                                $status_class = $seat['status'] == 'available' ? 'available' : 'booked';
                                $type_class = strtolower($seat['seat_type']);
                        ?>
                            <div class="seat <?php echo $status_class; ?> <?php echo $type_class; ?>" 
                                 data-id="<?php echo $seat['id']; ?>"
                                 data-row="<?php echo $row; ?>"
                                 data-number="<?php echo $num; ?>"
                                 data-price="<?php echo $seat['price']; ?>"
                                 onclick="selectSeat(this)">
                                <?php echo $num; ?>
                            </div>
                        <?php else: ?>
                            <div class="seat" style="opacity: 0; visibility: hidden;"></div>
                        <?php endif; endfor; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="selected-seats">
                <h4>Selected Seats (<span id="selectedCount">0</span>/<?php echo $quantity; ?>)</h4>
                <div id="selectedSeatsList"></div>
            </div>
            
            <form method="POST" action="process_seats.php">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                <input type="hidden" name="ticket_type" value="<?php echo $ticket_type; ?>">
                <input type="hidden" name="quantity" value="<?php echo $quantity; ?>">
                <input type="hidden" name="selected_seats" id="selectedSeats" value="">
                <button type="submit" name="confirm_seats" class="confirm-btn" id="confirmBtn" disabled>Confirm Seats →</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let selectedSeats = [];
let maxSeats = <?php echo $quantity; ?>;

function selectSeat(element) {
    if(element.classList.contains('booked')) return;
    
    let seatId = element.dataset.id;
    let seatRow = element.dataset.row;
    let seatNumber = element.dataset.number;
    let seatPrice = parseFloat(element.dataset.price);
    
    let index = selectedSeats.findIndex(s => s.id == seatId);
    
    if(index === -1) {
        if(selectedSeats.length >= maxSeats) {
            alert(`You can only select ${maxSeats} seat(s)`);
            return;
        }
        selectedSeats.push({
            id: seatId,
            row: seatRow,
            number: seatNumber,
            price: seatPrice
        });
        element.classList.add('selected');
    } else {
        selectedSeats.splice(index, 1);
        element.classList.remove('selected');
    }
    
    updateSelectedDisplay();
}

function updateSelectedDisplay() {
    document.getElementById('selectedCount').innerHTML = selectedSeats.length;
    
    let listHtml = '';
    selectedSeats.forEach(seat => {
        listHtml += `<span style="display: inline-block; background: #facc15; color: black; padding: 5px 10px; border-radius: 5px; margin: 5px;">
                        ${seat.row}${seat.number}
                     </span>`;
    });
    
    document.getElementById('selectedSeatsList').innerHTML = listHtml;
    document.getElementById('selectedSeats').value = JSON.stringify(selectedSeats);
    document.getElementById('confirmBtn').disabled = selectedSeats.length !== maxSeats;
}
</script>

</body>
</html>