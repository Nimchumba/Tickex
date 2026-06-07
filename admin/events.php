<?php
include "../config.php";

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle delete request
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // First get the image name to delete the file
    $result = mysqli_query($conn, "SELECT image FROM events WHERE id = $id");
    $event = mysqli_fetch_assoc($result);
    
    // Delete image file if exists
    if($event['image'] && file_exists("../assets/images/".$event['image'])) {
        unlink("../assets/images/".$event['image']);
    }
    
    // Delete from database
    mysqli_query($conn, "DELETE FROM events WHERE id = $id");
    
    header("Location: events.php?msg=deleted");
    exit();
}

// Get all events with category names
$events = mysqli_query($conn, "
    SELECT e.*, c.name as category_name 
    FROM events e 
    JOIN categories c ON e.category_id = c.id 
    ORDER BY e.event_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Events - Tickex Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .events-table {
            width: 100%;
            background: #1e293b;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 20px;
        }
        .events-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .events-table th {
            background: #0f172a;
            color: #facc15;
            padding: 15px;
            text-align: left;
        }
        .events-table td {
            padding: 15px;
            border-bottom: 1px solid #334155;
            color: white;
        }
        .events-table tr:hover {
            background: #334155;
        }
        .event-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .action-btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin: 0 5px;
            display: inline-block;
        }
        .edit-btn {
            background: #facc15;
            color: black;
        }
        .delete-btn {
            background: #dc2626;
            color: white;
        }
        .view-btn {
            background: #3b82f6;
            color: white;
        }
        .success-msg {
            background: #00aa00;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
            <li class="active">📋 Manage Events</li>
             <li onclick="location.href='analytics.php'">📊 Analytics</li>
            <li onclick="location.href='manage_reviews.php'">⭐ Manage Reviews</li>
            <li onclick="location.href='wishlist.php'">💙 Wishlist</li>
            <li onclick="location.href='../dashboard.php'">🏠 View Site</li>
            <li onclick="location.href='../logout.php'">🚪 Logout</li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="header-actions">
            <h1>Manage Events</h1>
            <a href="add_event.php" class="btn">+ Add New Event</a>
        </div>
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="success-msg">✅ Event deleted successfully!</div>
        <?php endif; ?>
        
        <div class="events-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($event = mysqli_fetch_assoc($events)): ?>
                    <tr>
                        <td><?php echo $event['id']; ?></td>
                        <td>
                            <?php if($event['image']): ?>
                                <img src="../assets/images/<?php echo $event['image']; ?>" 
                                     class="event-image" 
                                     onerror="this.src='../assets/images/placeholder.jpg'">
                            <?php else: ?>
                                <img src="../assets/images/placeholder.jpg" class="event-image">
                            <?php endif; ?>
                        </td>
                        <td><?php echo $event['title']; ?></td>
                        <td><?php echo $event['category_name']; ?></td>
                        <td><?php echo date('d M Y', strtotime($event['event_date'])); ?></td>
                        <td><?php echo $event['location']; ?></td>
                        <td>KES <?php echo number_format($event['price']); ?></td>
                        <td>
                            <a href="../event.php?id=<?php echo $event['id']; ?>" 
                               class="action-btn view-btn" 
                               target="_blank">👁️ View</a>
                            
                            <a href="edit_event.php?id=<?php echo $event['id']; ?>" 
                               class="action-btn edit-btn">✏️ Edit</a>
                            
                            <a href="?delete=<?php echo $event['id']; ?>" 
                               class="action-btn delete-btn"
                               onclick="return confirm('⚠️ Are you sure you want to delete this event?');">🗑️ Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>