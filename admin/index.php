<?php
include "../config.php";

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Tickex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #1e293b;
            border-radius: 15px;
        }
        .admin-header h1 {
            color: #facc15;
            margin: 0;
        }
        .admin-header .logout-btn {
            background: #dc2626;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }
        .admin-header .logout-btn:hover {
            background: #b91c1c;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .dashboard-card {
            background: #1e293b;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid #facc15;
        }
        .dashboard-card h3 {
            color: #facc15;
            margin: 0 0 10px 0;
        }
        .dashboard-card p {
            color: #ccc;
            margin: 0;
        }
        .dashboard-card a {
            display: inline-block;
            margin-top: 15px;
            background: #facc15;
            color: black;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
        .dashboard-card a:hover {
            background: #eab308;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h2 class="logo">Tickex</h2>
        <p style="color: #facc15; margin-bottom: 20px;">👤 Admin Panel</p>
        <ul>
            <li onclick="location.href='events.php'">📋 Manage Events</li>
            <li onclick="location.href='add_event.php'">➕ Add Event</li>
            <li onclick="location.href='analytics.php'">📊 Analytics</li>
            <li onclick="location.href='manage_reviews.php'">⭐ Reviews</li>
            <li onclick="location.href='../index.php'">🏠 Back to Home</li>
            <li onclick="location.href='logout.php'">🚪 Logout</li>
        </ul>
    </div>
    
    <div class="main">
        <div class="admin-header">
            <h1>🔐 Admin Dashboard</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        
        <div class="dashboard-cards">
            <div class="dashboard-card">
                <h3>📋 Manage Events</h3>
                <p>Create, edit, and delete events</p>
                <a href="events.php">Go to Events</a>
            </div>
            
            <div class="dashboard-card">
                <h3>➕ Add New Event</h3>
                <p>Create a new event</p>
                <a href="add_event.php">Add Event</a>
            </div>
            
            <div class="dashboard-card">
                <h3>📊 Analytics</h3>
                <p>View event statistics</p>
                <a href="analytics.php">View Stats</a>
            </div>
            
            <div class="dashboard-card">
                <h3>⭐ Reviews</h3>
                <p>Manage user reviews</p>
                <a href="manage_reviews.php">Manage Reviews</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
