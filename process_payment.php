<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pay']) && isset($_POST['cart'])) {
    $cart = json_decode($_POST['cart'], true);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    if(empty($cart)) {
        header("Location: dashboard.php");
        exit();
    }
    
    $purchase_success = true;
    $purchase_count = 0;
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        foreach($cart as $item) {
            $ticket_type_id = $item['ticket_id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $type_name = $item['type'];
            $total = $price * $quantity;
            
            // Get event_id from ticket_type
            $result = mysqli_query($conn, "SELECT event_id FROM ticket_types WHERE id = $ticket_type_id");
            if(mysqli_num_rows($result) == 0) {
                throw new Exception("Ticket type not found");
            }
            $ticket_type = mysqli_fetch_assoc($result);
            $event_id = $ticket_type['event_id'];
            
            // Generate unique codes
            $ticket_code = strtoupper(uniqid("TKT") . rand(1000, 9999));
            $transaction_code = strtoupper(uniqid("TXN") . rand(1000, 9999));
            
            // Insert into tickets table
            $insert = mysqli_query($conn, "
                INSERT INTO tickets (
                    user_id, 
                    event_id, 
                    ticket_type_id, 
                    ticket_type_name, 
                    quantity, 
                    total_price, 
                    payment_method, 
                    transaction_code, 
                    ticket_code,
                    phone,
                    status
                ) VALUES (
                    $user_id, 
                    $event_id, 
                    $ticket_type_id, 
                    '$type_name', 
                    $quantity, 
                    $total, 
                    '$payment_method', 
                    '$transaction_code', 
                    '$ticket_code',
                    '$phone',
                    'Confirmed'
                )
            ");
            
            if(!$insert) {
                throw new Exception("Failed to insert ticket: " . mysqli_error($conn));
            }
            
            $purchase_count++;
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Clear cart from session if you stored it
        $_SESSION['cart'] = [];
        
        // Redirect to success page
        header("Location: my_tickets.php?success=1&count=$purchase_count");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        header("Location: checkout.php?error=1&message=" . urlencode($e->getMessage()));
        exit();
    }
    
} else {
    header("Location: dashboard.php");
    exit();
}
?>