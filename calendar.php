<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current month and year
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get first day of month
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$start_day = date('w', $first_day); // 0 = Sunday, 6 = Saturday

// Adjust to make Monday first day of week (1 = Monday, 7 = Sunday)
$start_day = $start_day == 0 ? 6 : $start_day - 1;

// Previous and next month links
$prev_month = $month - 1;
$prev_year = $year;
if($prev_month < 1) {
    $prev_month = 12;
    $prev_year = $year - 1;
}

$next_month = $month + 1;
$next_year = $year;
if($next_month > 12) {
    $next_month = 1;
    $next_year = $year + 1;
}

// Get events for this month
$events_query = mysqli_query($conn, "
    SELECT e.*, c.name as category_name 
    FROM events e 
    JOIN categories c ON e.category_id = c.id 
    WHERE MONTH(e.event_date) = $month AND YEAR(e.event_date) = $year
    ORDER BY e.event_date ASC
");

$events_by_date = [];
while($event = mysqli_fetch_assoc($events_query)) {
    $day = date('j', strtotime($event['event_date']));
    if(!isset($events_by_date[$day])) {
        $events_by_date[$day] = [];
    }
    $events_by_date[$day][] = $event;
}

// Month names
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Calendar - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .calendar-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .calendar-title {
            text-align: center;
        }
        
        .calendar-title h2 {
            color: #facc15;
            font-size: 28px;
            margin: 0;
        }
        
        .calendar-title p {
            color: #ccc;
            margin-top: 5px;
        }
        
        .nav-btn {
            background: #0f172a;
            border: 1px solid #facc15;
            color: #facc15;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: 0.2s;
        }
        
        .nav-btn:hover {
            background: #facc15;
            color: black;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .weekday {
            text-align: center;
            padding: 15px;
            font-weight: bold;
            color: #facc15;
            background: #0f172a;
            border-radius: 8px;
        }
        
        .calendar-day {
            background: #0f172a;
            border-radius: 8px;
            min-height: 100px;
            padding: 10px;
            position: relative;
            transition: 0.2s;
            cursor: pointer;
        }
        
        .calendar-day:hover {
            background: #334155;
            transform: scale(1.02);
        }
        
        .calendar-day.today {
            border: 2px solid #facc15;
        }
        
        .day-number {
            font-size: 18px;
            font-weight: bold;
            color: white;
            margin-bottom: 8px;
        }
        
        .day-events {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .event-badge {
            background: #facc15;
            color: black;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .event-badge.more {
            background: #334155;
            color: #ccc;
            text-align: center;
        }
        
        .event-badge:hover {
            background: #eab308;
        }
        
        .empty-day {
            background: #0f172a;
            border-radius: 8px;
            min-height: 100px;
            opacity: 0.5;
        }
        
        .events-list {
            margin-top: 30px;
            background: #0f172a;
            border-radius: 10px;
            padding: 20px;
        }
        
        .events-list h3 {
            color: #facc15;
            margin-bottom: 15px;
        }
        
        .event-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            background: #1e293b;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: 0.2s;
        }
        
        .event-item:hover {
            transform: translateX(5px);
        }
        
        .event-date {
            min-width: 80px;
            color: #facc15;
            font-weight: bold;
        }
        
        .event-title {
            flex: 1;
            color: white;
        }
        
        .event-category {
            background: #facc15;
            color: black;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .event-price {
            color: #facc15;
            font-weight: bold;
        }
        
        .book-link {
            background: #facc15;
            color: black;
            padding: 5px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
        }
        
        .book-link:hover {
            background: #eab308;
        }
        
        .no-events {
            text-align: center;
            padding: 30px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .calendar-grid {
                gap: 5px;
            }
            
            .calendar-day {
                min-height: 70px;
                padding: 5px;
            }
            
            .day-number {
                font-size: 14px;
            }
            
            .event-badge {
                font-size: 8px;
                padding: 2px 4px;
            }
            
            .weekday {
                padding: 10px;
                font-size: 12px;
            }
            
            .event-item {
                flex-direction: column;
                text-align: center;
            }
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
            <li onclick="location.href='category.php?id=1'">🎤 Concerts</li>
            <li onclick="location.href='category.php?id=2'">🎬 Movies</li>
            <li onclick="location.href='category.php?id=3'">⚽ Sports</li>
            <li onclick="location.href='category.php?id=4'">🎪 Festivals</li>
            <li onclick="location.href='category.php?id=5'">💻 Tech Events</li>
            <li onclick="location.href='my_tickets.php'">🎟️ My Tickets</li>
            <li onclick="location.href='wishlist.php'">💙 Wishlist</li>
            <li class="active">📅 Calendar</li>
            <li onclick="location.href='resale_listings.php'">🔥 Resale</li>
        </ul>
        
        <div style="margin-top: 50px;">
            <a href="logout.php" class="btn" style="width: 100%; text-align: center;">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="calendar-container">
            <div class="calendar-header">
                <a href="calendar.php?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="nav-btn">← Previous</a>
                
                <div class="calendar-title">
                    <h2><?php echo $month_names[$month] . ' ' . $year; ?></h2>
                    <p>Click on any day to see events</p>
                </div>
                
                <a href="calendar.php?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="nav-btn">Next →</a>
            </div>
            
            <div class="calendar-grid">
                <div class="weekday">Mon</div>
                <div class="weekday">Tue</div>
                <div class="weekday">Wed</div>
                <div class="weekday">Thu</div>
                <div class="weekday">Fri</div>
                <div class="weekday">Sat</div>
                <div class="weekday">Sun</div>
                
                <?php
                $current_day = 1;
                $today = date('j');
                $current_month = date('m');
                $current_year = date('Y');
                
                // Empty cells before first day
                for($i = 0; $i < $start_day; $i++) {
                    echo '<div class="empty-day"></div>';
                }
                
                // Fill in days
                for($day = 1; $day <= $days_in_month; $day++) {
                    $is_today = ($day == $today && $month == $current_month && $year == $current_year);
                    $has_events = isset($events_by_date[$day]) && count($events_by_date[$day]) > 0;
                    ?>
                    <div class="calendar-day <?php echo $is_today ? 'today' : ''; ?>" onclick="showDayEvents(<?php echo $day; ?>)">
                        <div class="day-number"><?php echo $day; ?></div>
                        <?php if($has_events): ?>
                            <div class="day-events">
                                <?php 
                                $event_count = count($events_by_date[$day]);
                                $display_count = min(3, $event_count);
                                for($i = 0; $i < $display_count; $i++):
                                    $e = $events_by_date[$day][$i];
                                ?>
                                    <div class="event-badge" onclick="event.stopPropagation(); location.href='event.php?id=<?php echo $e['id']; ?>'">
                                        <?php echo $e['title']; ?>
                                    </div>
                                <?php endfor; ?>
                                <?php if($event_count > 3): ?>
                                    <div class="event-badge more" onclick="event.stopPropagation(); showDayEvents(<?php echo $day; ?>)">
                                        +<?php echo $event_count - 3; ?> more
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
                
                // Fill remaining cells
                $remaining = (7 - (($start_day + $days_in_month) % 7)) % 7;
                for($i = 0; $i < $remaining; $i++) {
                    echo '<div class="empty-day"></div>';
                }
                ?>
            </div>
            
            <!-- Events List for Selected Day -->
            <div class="events-list" id="events-list">
                <h3>📅 Events for <span id="selected-date"><?php echo $month_names[$month] . ' ' . date('j') . ', ' . $year; ?></span></h3>
                <div id="day-events-container">
                    <?php
                    $today_events = isset($events_by_date[date('j')]) ? $events_by_date[date('j')] : [];
                    if(count($today_events) > 0):
                        foreach($today_events as $event):
                    ?>
                        <div class="event-item">
                            <div class="event-date"><?php echo date('d M', strtotime($event['event_date'])); ?></div>
                            <div class="event-title"><?php echo $event['title']; ?></div>
                            <div class="event-category"><?php echo $event['category_name']; ?></div>
                            <div class="event-price">KES <?php echo number_format($event['price']); ?></div>
                            <a href="event.php?id=<?php echo $event['id']; ?>" class="book-link">View →</a>
                        </div>
                    <?php 
                        endforeach;
                    else:
                    ?>
                        <div class="no-events">No events on this day</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDayEvents(day) {
    // Get month and year from URL or current
    const urlParams = new URLSearchParams(window.location.search);
    let month = urlParams.get('month') || <?php echo $month; ?>;
    let year = urlParams.get('year') || <?php echo $year; ?>;
    
    // Update selected date display
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('selected-date').innerHTML = monthNames[month-1] + ' ' + day + ', ' + year;
    
    // Fetch events for this day via AJAX
    fetch(`get_day_events.php?day=${day}&month=${month}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('day-events-container');
            if(data.length > 0) {
                container.innerHTML = data.map(event => `
                    <div class="event-item">
                        <div class="event-date">${event.date}</div>
                        <div class="event-title">${event.title}</div>
                        <div class="event-category">${event.category}</div>
                        <div class="event-price">KES ${event.price.toLocaleString()}</div>
                        <a href="event.php?id=${event.id}" class="book-link">View →</a>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="no-events">No events on this day</div>';
            }
        });
}
</script>

</body>
</html>