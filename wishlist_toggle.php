<?php
include "config.php";

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

if($event_id == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid event']);
    exit();
}

// Check if already in wishlist
$check = mysqli_query($conn, "SELECT * FROM wishlist WHERE user_id = $user_id AND event_id = $event_id");

if(mysqli_num_rows($check) > 0) {
    // Remove from wishlist
    mysqli_query($conn, "DELETE FROM wishlist WHERE user_id = $user_id AND event_id = $event_id");
    echo json_encode(['status' => 'removed']);
} else {
    // Add to wishlist
    mysqli_query($conn, "INSERT INTO wishlist (user_id, event_id) VALUES ($user_id, $event_id)");
    echo json_encode(['status' => 'added']);
}
?>