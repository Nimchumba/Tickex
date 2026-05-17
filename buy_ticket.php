<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['buy_ticket'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = (int)$_POST['event_id'];
    $quantity = (int)$_POST['quantity'];
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    
    // Get event details
    $result = mysqli_query($conn, "SELECT * FROM events WHERE id = $event_id");
    $event = mysqli_fetch_assoc($result);
    
    if(!$event) {
        header("Location: dashboard.php");
        exit();
    }
    
    // Calculate total
    $total_price = $event['price'] * $quantity;
    
    // Generate unique ticket code
    $ticket_code = strtoupper(uniqid("TKT") . rand(100, 999));
    $transaction_code = strtoupper(uniqid("TXN") . rand(100, 999));
    
    // Insert into tickets table
    $insert = mysqli_query($conn, "
        INSERT INTO tickets (user_id, event_id, quantity, total_price, payment_method, transaction_code, ticket_code) 
        VALUES ('$user_id', '$event_id', '$quantity', '$total_price', '$payment_method', '$transaction_code', '$ticket_code')
    ");
    
    if($insert) {
        // Redirect to my tickets with success message
        header("Location: my_tickets.php?success=1&code=" . $ticket_code);
        exit();
    } else {
        // Error
        header("Location: event.php?id=$event_id&error=1");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>