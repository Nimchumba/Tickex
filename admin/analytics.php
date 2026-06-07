<?php
include "../config.php";

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get total revenue
$revenue_result = mysqli_query($conn, "SELECT SUM(total_price) as total FROM tickets");
$revenue = mysqli_fetch_assoc($revenue_result);
$total_revenue = $revenue['total'] ? $revenue['total'] : 0;

// Get total tickets sold
$tickets_result = mysqli_query($conn, "SELECT SUM(quantity) as total FROM tickets");
$tickets_sold = mysqli_fetch_assoc($tickets_result);
$total_tickets = $tickets_sold['total'] ? $tickets_sold['total'] : 0;

// Get total events
$events_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM events");
$total_events = mysqli_fetch_assoc($events_result)['total'];

// Get total users
$users_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users = mysqli_fetch_assoc($users_result)['total'];

// Get sales by category
$category_sales = mysqli_query($conn, "
    SELECT c.name, SUM(t.total_price) as revenue, COUNT(t.id) as bookings, SUM(t.quantity) as tickets
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    JOIN categories c ON e.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY revenue DESC
");

// Get sales by ticket type (NEW)
$ticket_type_sales = mysqli_query($conn, "
    SELECT 
        ticket_type_name, 
        SUM(quantity) as tickets_sold, 
        COUNT(*) as bookings,
        SUM(total_price) as revenue,
        AVG(total_price / quantity) as avg_price
    FROM tickets
    GROUP BY ticket_type_name
    ORDER BY 
        CASE ticket_type_name
            WHEN 'Regular' THEN 1
            WHEN 'VIP' THEN 2
            WHEN 'VVIP' THEN 3
            ELSE 4
        END
");

// Get top selling events
$top_events = mysqli_query($conn, "
    SELECT e.title, c.name as category, SUM(t.quantity) as tickets_sold, SUM(t.total_price) as revenue
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    JOIN categories c ON e.category_id = c.id
    GROUP BY e.id, e.title, c.name
    ORDER BY revenue DESC
    LIMIT 10
");

// Get recent transactions
$recent_tickets = mysqli_query($conn, "
    SELECT t.*, u.full_name, e.title, e.event_date
    FROM tickets t
    JOIN users u ON t.user_id = u.id
    JOIN events e ON t.event_id = e.id
    ORDER BY t.purchase_date DESC
    LIMIT 20
");

// Get monthly revenue (last 6 months)
$monthly_revenue = mysqli_query($conn, "
    SELECT 
        DATE_FORMAT(purchase_date, '%Y-%m') as month,
        SUM(total_price) as revenue,
        SUM(quantity) as tickets
    FROM tickets
    WHERE purchase_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(purchase_date, '%Y-%m')
    ORDER BY month DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Analytics - Tickex Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-header {
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border-left: 5px solid #facc15;
        }
        .stat-card h3 {
            color: #facc15;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: white;
        }
        .stat-label {
            color: #999;
            font-size: 14px;
            margin-top: 5px;
        }
        .revenue-number {
            color: #00aa00;
            font-size: 36px;
            font-weight: bold;
        }
        .analytics-section {
            background: #1e293b;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .section-title {
            color: #facc15;
            font-size: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #334155;
            padding-bottom: 10px;
        }
        .analytics-table {
            width: 100%;
            border-collapse: collapse;
        }
        .analytics-table th {
            text-align: left;
            padding: 12px;
            background: #0f172a;
            color: #facc15;
        }
        .analytics-table td {
            padding: 12px;
            border-bottom: 1px solid #334155;
            color: white;
        }
        .analytics-table tr:hover {
            background: #334155;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .badge-regular {
            background: white;
            color: black;
        }
        .badge-vip {
            background: #facc15;
            color: black;
        }
        .badge-vvip {
            background: #ff0000;
            color: white;
        }
        .amount {
            color: #00aa00;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .mini-chart {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .month-bar {
            flex: 1;
            text-align: center;
        }
        .bar {
            height: 100px;
            background: #facc15;
            width: 100%;
            border-radius: 5px 5px 0 0;
            margin-bottom: 5px;
            position: relative;
        }
        .bar-value {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="logo">Tickex Admin</h2>
        <p style="color: #facc15; margin-bottom: 20px;">Welcome, <?php echo $_SESSION['user_name']; ?></p>
        
        <ul>
            <li onclick="location.href='add_event.php'">➕ Add Event</li>
            <li onclick="location.href='events.php'">📋 Manage Events</li>
            <li class="active">📊 Analytics</li>
            <li onclick="location.href='manage_reviews.php'">⭐ Manage Reviews</li>
            <li onclick="location.href='wishlist.php'">💙 Wishlist</li>
            <li onclick="location.href='../dashboard.php'">🏠 View Site</li>
            <li onclick="location.href='../logout.php'">🚪 Logout</li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="dashboard-header">
            <h1>📊 Sales Analytics</h1>
            <p>Track your ticket sales and revenue with ticket type breakdown</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="revenue-number">KES <?php echo number_format($total_revenue); ?></div>
                <div class="stat-label">From all ticket sales</div>
            </div>
            
            <div class="stat-card">
                <h3>Tickets Sold</h3>
                <div class="stat-number"><?php echo number_format($total_tickets); ?></div>
                <div class="stat-label">Total tickets purchased</div>
            </div>
            
            <div class="stat-card">
                <h3>Active Events</h3>
                <div class="stat-number"><?php echo $total_events; ?></div>
                <div class="stat-label">Events in system</div>
            </div>
            
            <div class="stat-card">
                <h3>Registered Users</h3>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">Active accounts</div>
            </div>
        </div>
        
        <!-- Ticket Type Sales (NEW) -->
        <div class="analytics-section">
            <h2 class="section-title">🎟️ Ticket Type Performance</h2>
            
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Ticket Type</th>
                        <th>Bookings</th>
                        <th>Tickets Sold</th>
                        <th>Average Price</th>
                        <th>Revenue</th>
                        <th>% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($ticket_type_sales) > 0): ?>
                        <?php 
                        $grand_total = 0;
                        mysqli_data_seek($ticket_type_sales, 0);
                        while($type = mysqli_fetch_assoc($ticket_type_sales)) {
                            $grand_total += $type['revenue'];
                        }
                        mysqli_data_seek($ticket_type_sales, 0);
                        
                        while($type = mysqli_fetch_assoc($ticket_type_sales)): 
                            $percentage = $grand_total > 0 ? round(($type['revenue'] / $grand_total) * 100, 1) : 0;
                            $badge_class = 'badge-' . strtolower($type['ticket_type_name']);
                        ?>
                        <tr>
                            <td>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo $type['ticket_type_name']; ?>
                                </span>
                            </td>
                            <td><?php echo $type['bookings']; ?></td>
                            <td><?php echo $type['tickets_sold']; ?></td>
                            <td>KES <?php echo number_format($type['avg_price']); ?></td>
                            <td class="amount">KES <?php echo number_format($type['revenue']); ?></td>
                            <td><?php echo $percentage; ?>%</td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">No ticket sales data yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Sales by Category -->
        <div class="analytics-section">
            <h2 class="section-title">📈 Sales by Category</h2>
            
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Tickets Sold</th>
                        <th>Bookings</th>
                        <th>Revenue</th>
                        <th>% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($category_sales) > 0): ?>
                        <?php while($cat = mysqli_fetch_assoc($category_sales)): 
                            $percentage = $total_revenue > 0 ? round(($cat['revenue'] / $total_revenue) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo $cat['name']; ?></strong></td>
                            <td><?php echo $cat['tickets'] ? $cat['tickets'] : 0; ?></td>
                            <td><?php echo $cat['bookings'] ? $cat['bookings'] : 0; ?></td>
                            <td class="amount">KES <?php echo number_format($cat['revenue'] ? $cat['revenue'] : 0); ?></td>
                            <td><?php echo $percentage; ?>%</td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-data">No sales data yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Monthly Revenue Trend -->
        <?php if(mysqli_num_rows($monthly_revenue) > 0): ?>
        <div class="analytics-section">
            <h2 class="section-title">📅 Monthly Revenue (Last 6 Months)</h2>
            
            <?php
            $max_revenue = 0;
            $months = [];
            mysqli_data_seek($monthly_revenue, 0);
            while($month = mysqli_fetch_assoc($monthly_revenue)) {
                $max_revenue = max($max_revenue, $month['revenue']);
                $months[] = $month;
            }
            ?>
            
            <div class="mini-chart">
                <?php foreach(array_reverse($months) as $month): 
                    $height = $max_revenue > 0 ? ($month['revenue'] / $max_revenue) * 100 : 0;
                    $month_name = date('M Y', strtotime($month['month'] . '-01'));
                ?>
                <div class="month-bar">
                    <div class="bar" style="height: <?php echo $height; ?>px; background: <?php echo $height > 50 ? '#facc15' : '#eab308'; ?>;">
                        <span class="bar-value">KES <?php echo number_format($month['revenue'] / 1000); ?>k</span>
                    </div>
                    <div style="color: white; font-size: 12px;"><?php echo $month_name; ?></div>
                    <div style="color: #999; font-size: 10px;"><?php echo $month['tickets']; ?> tickets</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Top Selling Events -->
        <div class="analytics-section">
            <h2 class="section-title">🏆 Top Selling Events</h2>
            
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Category</th>
                        <th>Tickets Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($top_events) > 0): ?>
                        <?php while($event = mysqli_fetch_assoc($top_events)): ?>
                        <tr>
                            <td><strong><?php echo $event['title']; ?></strong></td>
                            <td><?php echo $event['category']; ?></td>
                            <td><?php echo $event['tickets_sold']; ?></td>
                            <td class="amount">KES <?php echo number_format($event['revenue']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data">No sales data yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Recent Transactions -->
        <div class="analytics-section">
            <h2 class="section-title">🕒 Recent Transactions</h2>
            
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Ticket Code</th>
                        <th>Customer</th>
                        <th>Event</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($recent_tickets) > 0): ?>
                        <?php while($ticket = mysqli_fetch_assoc($recent_tickets)): 
                            $badge_class = 'badge-' . strtolower($ticket['ticket_type_name']);
                        ?>
                        <tr>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $ticket['ticket_code']; ?></span></td>
                            <td><?php echo $ticket['full_name']; ?></td>
                            <td><?php echo substr($ticket['title'], 0, 20) . '...'; ?></td>
                            <td><?php echo $ticket['ticket_type_name']; ?></td>
                            <td><?php echo $ticket['quantity']; ?></td>
                            <td class="amount">KES <?php echo number_format($ticket['total_price']); ?></td>
                            <td><?php echo date('d M H:i', strtotime($ticket['purchase_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">No transactions yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Quick Stats -->
        <div class="analytics-section">
            <h2 class="section-title">📊 Quick Stats</h2>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div style="background: #0f172a; padding: 20px; border-radius: 10px;">
                    <h3 style="color: #facc15; margin-bottom: 10px;">Average Ticket Price</h3>
                    <p style="font-size: 28px; font-weight: bold; color: white;">
                        KES <?php echo $total_tickets > 0 ? number_format($total_revenue / $total_tickets) : 0; ?>
                    </p>
                </div>
                
                <div style="background: #0f172a; padding: 20px; border-radius: 10px;">
                    <h3 style="color: #facc15; margin-bottom: 10px;">Average per Booking</h3>
                    <?php 
                    $total_bookings = mysqli_num_rows($recent_tickets) > 0 ? mysqli_num_rows($recent_tickets) : 1;
                    $avg_booking = $total_revenue > 0 ? number_format($total_revenue / $total_bookings) : 0;
                    ?>
                    <p style="font-size: 28px; font-weight: bold; color: white;">KES <?php echo $avg_booking; ?></p>
                </div>
                
                <div style="background: #0f172a; padding: 20px; border-radius: 10px;">
                    <h3 style="color: #facc15; margin-bottom: 10px;">VIP/VVIP Share</h3>
                    <?php
                    $vip_revenue = 0;
                    $vvip_revenue = 0;
                    mysqli_data_seek($ticket_type_sales, 0);
                    while($type = mysqli_fetch_assoc($ticket_type_sales)) {
                        if($type['ticket_type_name'] == 'VIP') $vip_revenue = $type['revenue'];
                        if($type['ticket_type_name'] == 'VVIP') $vvip_revenue = $type['revenue'];
                    }
                    $premium_share = $total_revenue > 0 ? (($vip_revenue + $vvip_revenue) / $total_revenue) * 100 : 0;
                    ?>
                    <p style="font-size: 28px; font-weight: bold; color: #facc15;"><?php echo round($premium_share, 1); ?>%</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>