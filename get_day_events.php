<?php
include "config.php";

header('Content-Type: application/json');

$day = isset($_GET['day']) ? (int)$_GET['day'] : 0;
$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;

if($day == 0 || $month == 0 || $year == 0) {
    echo json_encode([]);
    exit();
}

// Get events for specific day
$query = "SELECT e.*, c.name as category_name 
          FROM events e 
          JOIN categories c ON e.category_id = c.id 
          WHERE DAY(e.event_date) = $day 
          AND MONTH(e.event_date) = $month 
          AND YEAR(e.event_date) = $year
          ORDER BY e.event_date ASC";

$result = mysqli_query($conn, $query);
$events = [];

while($row = mysqli_fetch_assoc($result)) {
    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'date' => date('d M', strtotime($row['event_date'])),
        'category' => $row['category_name'],
        'price' => (float)$row['price']
    ];
}

echo json_encode($events);
?>