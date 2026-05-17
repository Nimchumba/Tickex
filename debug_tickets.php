<?php
include "config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo "Not logged in<br>";
    echo "<a href='login.php'>Login first</a>";
    exit();
}

$user_id = $_SESSION['user_id'];
echo "<h1>Debug: Your Tickets</h1>";
echo "User ID: " . $user_id . "<br><br>";

// Show all tickets for this user
$result = mysqli_query($conn, "
    SELECT t.*, e.title 
    FROM tickets t
    JOIN events e ON t.event_id = e.id
    WHERE t.user_id = $user_id
");

echo "<h2>Tickets in database:</h2>";
if(mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Ticket ID</th><th>Ticket Code</th><th>Event</th><th>Type</th><th>Action</th></tr>";
    
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['ticket_code'] . "</td>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . $row['ticket_type_name'] . "</td>";
        echo "<td><a href='generate_qr.php?id=" . $row['id'] . "' target='_blank'>Test QR</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No tickets found for this user!</p>";
}

// Also show all tickets in system (for debugging)
echo "<h2>All tickets in system (any user):</h2>";
$all = mysqli_query($conn, "SELECT t.*, u.full_name FROM tickets t JOIN users u ON t.user_id = u.id");
if(mysqli_num_rows($all) > 0) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Ticket ID</th><th>User</th><th>Ticket Code</th><th>Event ID</th></tr>";
    
    while($row = mysqli_fetch_assoc($all)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['ticket_code'] . "</td>";
        echo "<td>" . $row['event_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No tickets in system at all!</p>";
}
?>