<?php
include "../config.php";

// Check if user is logged in (any logged in user can manage reviews for simplicity)
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$message = "";
$error = "";

// Handle delete review (admin)
if(isset($_GET['delete'])) {
    $review_id = (int)$_GET['delete'];
    $event_id = (int)$_GET['event_id'];
    
    $delete = mysqli_query($conn, "DELETE FROM reviews WHERE id = $review_id");
    if($delete) {
        // Update event average rating
        $avg_result = mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE event_id = $event_id");
        $avg_data = mysqli_fetch_assoc($avg_result);
        mysqli_query($conn, "UPDATE events SET avg_rating = " . ($avg_data['avg_rating'] ?? 0) . ", total_reviews = " . ($avg_data['total'] ?? 0) . " WHERE id = $event_id");
        
        $message = "✅ Review deleted successfully!";
    } else {
        $error = "Failed to delete review";
    }
}

// Get all reviews with event and user info
$reviews = mysqli_query($conn, "
    SELECT r.*, u.full_name as user_name, e.title as event_title 
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN events e ON r.event_id = e.id
    ORDER BY r.review_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Reviews - Tickex Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .reviews-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 30px;
        }
        .reviews-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .reviews-table th {
            text-align: left;
            padding: 15px;
            background: #0f172a;
            color: #facc15;
        }
        .reviews-table td {
            padding: 15px;
            border-bottom: 1px solid #334155;
            color: white;
        }
        .reviews-table tr:hover {
            background: #334155;
        }
        .stars {
            color: #facc15;
        }
        .delete-btn {
            background: #dc2626;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
        }
        .delete-btn:hover {
            background: #b91c1c;
        }
        .message {
            background: #00aa00;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error {
            background: #ff0000;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #facc15;
        }
        .review-text {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .badge {
            background: #facc15;
            color: black;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
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
            <li onclick="location.href='analytics.php'">📊 Analytics</li>
            <li class="active">⭐ Manage Reviews</li>
            <li onclick="location.href='wishlist.php'">💙 Wishlist</li>
            <li onclick="location.href='../dashboard.php'">🏠 View Site</li>
            <li onclick="location.href='../logout.php'">🚪 Logout</li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="reviews-container">
            <h1 style="color: #facc15;">⭐ Manage Reviews</h1>
            <p>View and delete user reviews</p>
            
            <?php if($message): ?>
                <div class="message">✅ <?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <table class="reviews-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($reviews) > 0): ?>
                        <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                        <tr>
                            <td><?php echo $review['id']; ?></td>
                            <td><?php echo $review['user_name']; ?></td>
                            <td><?php echo $review['event_title']; ?></td>
                            <td class="stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                <?php endfor; ?>
                                (<?php echo $review['rating']; ?>)
                            </td>
                            <td class="review-text" title="<?php echo htmlspecialchars($review['review']); ?>">
                                <?php echo $review['review'] ? substr($review['review'], 0, 50) . '...' : '-'; ?>
                            </td>
                            <td><?php echo date('d M Y', strtotime($review['review_date'])); ?></td>
                            <td>
                                <a href="?delete=<?php echo $review['id']; ?>&event_id=<?php echo $review['event_id']; ?>" 
                                   class="delete-btn" 
                                   onclick="return confirm('Delete this review?')">🗑️ Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #999;">No reviews yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>