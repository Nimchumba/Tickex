<?php
include "config.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$message = "";
$error = "";

// Check if user has attended this event (has a ticket)
$ticket_check = mysqli_query($conn, "SELECT * FROM tickets WHERE user_id = $user_id AND event_id = $event_id LIMIT 1");
$has_ticket = mysqli_num_rows($ticket_check) > 0;

// Get event details
$event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM events WHERE id = $event_id"));

if(!$event) {
    header("Location: dashboard.php");
    exit();
}

// Check if already reviewed
$review_check = mysqli_query($conn, "SELECT * FROM reviews WHERE user_id = $user_id AND event_id = $event_id");
$already_reviewed = mysqli_num_rows($review_check) > 0;
$existing_review = $already_reviewed ? mysqli_fetch_assoc($review_check) : null;

// Handle review submission
if(isset($_POST['submit_review'])) {
    $rating = (int)$_POST['rating'];
    $review_text = mysqli_real_escape_string($conn, $_POST['review']);
    
    if($rating < 1 || $rating > 5) {
        $error = "Please select a valid rating";
    } else {
        if($already_reviewed) {
            // Update existing review
            $update = mysqli_query($conn, "UPDATE reviews SET rating = $rating, review = '$review_text', review_date = NOW() 
                                           WHERE user_id = $user_id AND event_id = $event_id");
            if($update) {
                $message = "✅ Your review has been updated!";
                $existing_review = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reviews WHERE user_id = $user_id AND event_id = $event_id"));
            } else {
                $error = "Failed to update review";
            }
        } else {
            // Insert new review
            $insert = mysqli_query($conn, "INSERT INTO reviews (user_id, event_id, rating, review) 
                                           VALUES ($user_id, $event_id, $rating, '$review_text')");
            if($insert) {
                $message = "✅ Thank you for your review!";
                $already_reviewed = true;
                $existing_review = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reviews WHERE user_id = $user_id AND event_id = $event_id"));
            } else {
                $error = "Failed to submit review";
            }
        }
        
        // Update event average rating
        $avg_result = mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE event_id = $event_id");
        $avg_data = mysqli_fetch_assoc($avg_result);
        mysqli_query($conn, "UPDATE events SET avg_rating = " . ($avg_data['avg_rating'] ?? 0) . ", total_reviews = " . ($avg_data['total'] ?? 0) . " WHERE id = $event_id");
    }
}

// Handle Delete Review (for users)
if(isset($_POST['delete_review'])) {
    if($already_reviewed) {
        $delete = mysqli_query($conn, "DELETE FROM reviews WHERE user_id = $user_id AND event_id = $event_id");
        if($delete) {
            // Update event average rating
            $avg_result = mysqli_query($conn, "SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE event_id = $event_id");
            $avg_data = mysqli_fetch_assoc($avg_result);
            mysqli_query($conn, "UPDATE events SET avg_rating = " . ($avg_data['avg_rating'] ?? 0) . ", total_reviews = " . ($avg_data['total'] ?? 0) . " WHERE id = $event_id");
            
            $message = "✅ Your review has been deleted!";
            $already_reviewed = false;
            $existing_review = null;
        } else {
            $error = "Failed to delete review";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Event - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .review-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            margin: 40px auto;
        }
        .event-info {
            background: #0f172a;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }
        .stars {
            font-size: 45px;
            color: #334155;
            cursor: pointer;
            display: flex;
            justify-content: center;
            gap: 5px;
            margin: 20px 0;
        }
        .stars span {
            transition: color 0.2s;
            cursor: pointer;
        }
        .stars span.active, .stars span:hover {
            color: #facc15;
        }
        .review-text {
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            background: #0f172a;
            border: 1px solid #334155;
            color: white;
            min-height: 120px;
            margin: 15px 0;
        }
        .submit-btn {
            background: #facc15;
            color: black;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            width: 100%;
            cursor: pointer;
        }
        .message {
            background: #00aa00;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error {
            background: #ff0000;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .warning {
            background: #facc15;
            color: black;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .current-rating {
            background: #0f172a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .current-stars {
            color: #facc15;
            font-size: 24px;
        }
        .delete-btn {
            background: #dc2626;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .delete-btn:hover {
            background: #b91c1c;
        }
        .flex-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-secondary {
            background: #334155;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .btn-secondary:hover {
            background: #475569;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <h2 class="logo">Tickex</h2>
        <p style="color: #facc15;">Welcome, <?php echo $_SESSION['user_name']; ?></p>
        <ul>
            <li onclick="location.href='dashboard.php'">🏠 Home</li>
            <li onclick="location.href='my_tickets.php'">🎟️ My Tickets</li>
            <li onclick="location.href='wishlist.php'">💙 Wishlist</li>
            <li onclick="location.href='calendar.php'">📅 Calendar</li>
            <li onclick="location.href='resale_listings.php'">🔥 Resale</li>
        </ul>
    </div>

    <div class="main">
        <div class="review-container">
            <h2 style="color: #facc15; text-align: center;">⭐ Rate & Review</h2>
            
            <div class="event-info">
                <h3><?php echo $event['title']; ?></h3>
                <p>📅 <?php echo date('d M Y', strtotime($event['event_date'])); ?></p>
            </div>
            
            <?php if($message): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="error">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!$has_ticket): ?>
                <div class="warning">
                    ⚠️ You need to purchase a ticket for this event before you can review it.
                    <br><br>
                    <a href="event.php?id=<?php echo $event_id; ?>" class="btn">Buy Ticket</a>
                </div>
            <?php else: ?>
                
                <?php if($already_reviewed): ?>
                    <div class="current-rating">
                        <div class="flex-between">
                            <p>Your current rating:</p>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete your review? This cannot be undone.')">
                                <button type="submit" name="delete_review" class="delete-btn">🗑️ Delete Review</button>
                            </form>
                        </div>
                        <div class="current-stars">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <?php echo $i <= $existing_review['rating'] ? '★' : '☆'; ?>
                            <?php endfor; ?>
                        </div>
                        <p style="color: #ccc; margin-top: 10px;"><?php echo nl2br(htmlspecialchars($existing_review['review'])); ?></p>
                    </div>
                    
                    <form method="POST">
                        <div class="stars" id="stars">
                            <span data-value="1">☆</span>
                            <span data-value="2">☆</span>
                            <span data-value="3">☆</span>
                            <span data-value="4">☆</span>
                            <span data-value="5">☆</span>
                        </div>
                        <input type="hidden" name="rating" id="rating" value="<?php echo $existing_review['rating']; ?>">
                        
                        <textarea name="review" class="review-text" placeholder="Update your review..."><?php echo htmlspecialchars($existing_review['review']); ?></textarea>
                        
                        <button type="submit" name="submit_review" class="submit-btn">
                            ✏️ Update Review
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST">
                        <div class="stars" id="stars">
                            <span data-value="1">☆</span>
                            <span data-value="2">☆</span>
                            <span data-value="3">☆</span>
                            <span data-value="4">☆</span>
                            <span data-value="5">☆</span>
                        </div>
                        <input type="hidden" name="rating" id="rating" value="5">
                        
                        <textarea name="review" class="review-text" placeholder="Share your experience... (optional)"></textarea>
                        
                        <button type="submit" name="submit_review" class="submit-btn">
                            ⭐ Submit Review
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="event.php?id=<?php echo $event_id; ?>" style="color: #facc15;">← Back to Event</a>
            </div>
        </div>
    </div>
</div>

<script>
// Star rating system
const stars = document.querySelectorAll('.stars span');
const ratingInput = document.getElementById('rating');

if(stars.length > 0) {
    stars.forEach((star, index) => {
        star.addEventListener('mouseover', function() {
            stars.forEach(s => s.classList.remove('active'));
            for(let i = 0; i <= index; i++) {
                stars[i].classList.add('active');
                stars[i].textContent = '★';
            }
        });
        
        star.addEventListener('mouseout', function() {
            stars.forEach(s => s.classList.remove('active'));
            let selected = parseInt(ratingInput.value);
            for(let i = 0; i < stars.length; i++) {
                if(i < selected) {
                    stars[i].classList.add('active');
                    stars[i].textContent = '★';
                } else {
                    stars[i].textContent = '☆';
                }
            }
        });
        
        star.addEventListener('click', function() {
            let value = parseInt(this.dataset.value);
            ratingInput.value = value;
            for(let i = 0; i < stars.length; i++) {
                if(i < value) {
                    stars[i].classList.add('active');
                    stars[i].textContent = '★';
                } else {
                    stars[i].classList.remove('active');
                    stars[i].textContent = '☆';
                }
            }
        });
    });

    // Set initial stars
    let defaultRating = <?php echo $existing_review['rating'] ?? 5; ?>;
    ratingInput.value = defaultRating;
    for(let i = 0; i < stars.length; i++) {
        if(i < defaultRating) {
            stars[i].classList.add('active');
            stars[i].textContent = '★';
        }
    }
}
</script>

</body>
</html>