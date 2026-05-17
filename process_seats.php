<?php
include "config.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['confirm_seats'])) {
    $event_id = (int)$_POST['event_id'];
    $ticket_type = $_POST['ticket_type'];
    $quantity = (int)$_POST['quantity'];
    $selected_seats = json_decode($_POST['selected_seats'], true);
    
    if(empty($selected_seats)) {
        header("Location: seat_selection.php?event_id=$event_id&type=$ticket_type&qty=$quantity&error=1");
        exit();
    }
    
    // Reserve selected seats
    foreach($selected_seats as $seat) {
        mysqli_query($conn, "UPDATE seats SET status = 'reserved', booked_at = NOW() WHERE id = " . $seat['id']);
    }
    
    // Store seat info in session
    $_SESSION['selected_seats'] = $selected_seats;
    
    // Redirect to payment
    header("Location: otp_payment.php?event_id=$event_id&type=$ticket_type&qty=$quantity");
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>