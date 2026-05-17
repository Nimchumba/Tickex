<?php
include "../config.php";

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error = "";

// Get event ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch event details
$result = mysqli_query($conn, "SELECT * FROM events WHERE id = $id");
$event = mysqli_fetch_assoc($result);

if(!$event) {
    header("Location: events.php");
    exit();
}

// Fetch ticket types for this event
$ticket_types_result = mysqli_query($conn, "SELECT * FROM ticket_types WHERE event_id = $id");
$ticket_types = [];
$vip_data = null;
$vvip_data = null;
$regular_data = null;

while($ticket = mysqli_fetch_assoc($ticket_types_result)) {
    $ticket_types[] = $ticket;
    if($ticket['type_name'] == 'VIP') {
        $vip_data = $ticket;
    }
    if($ticket['type_name'] == 'VVIP') {
        $vvip_data = $ticket;
    }
    if($ticket['type_name'] == 'Regular') {
        $regular_data = $ticket;
    }
}

// Get all categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

// Helper function to generate seats
function generateSeats($conn, $event_id, $venue_layout, $price) {
    // First, delete existing seats for this event
    mysqli_query($conn, "DELETE FROM seats WHERE event_id = $event_id");
    
    if($venue_layout == 'stadium') {
        // STADIUM LAYOUT - Home/Away sections, rows 1-15
        $sections = ['HOME', 'AWAY'];
        $rows_per_section = 15;
        
        foreach($sections as $section) {
            for($row = 1; $row <= $rows_per_section; $row++) {
                $row_letter = $section . '-' . $row;
                
                // Determine seat type based on row position (bottom to top)
                if($row <= 8) {
                    $seat_type = 'Regular';
                    $seat_price = $price;
                } elseif($row <= 12) {
                    $seat_type = 'VIP';
                    $seat_price = $price * 1.3;
                } else {
                    $seat_type = 'VVIP';
                    $seat_price = $price * 1.6;
                }
                
                // 20 seats per row
                for($num = 1; $num <= 20; $num++) {
                    mysqli_query($conn, "INSERT INTO seats (event_id, seat_row, seat_number, seat_type, price, status) 
                                         VALUES ($event_id, '$row_letter', $num, '$seat_type', $seat_price, 'available')");
                }
            }
        }
        return "Stadium seats generated: " . (2 * 15 * 20) . " seats";
        
    } elseif($venue_layout == 'cinema') {
        // CINEMA LAYOUT - Rows A-J, 15 seats per row
        $rows = ['A','B','C','D','E','F','G','H','I','J'];
        $seats_per_row = 15;
        
        foreach($rows as $index => $row) {
            for($num = 1; $num <= $seats_per_row; $num++) {
                if($index < 2) {
                    $seat_type = 'VVIP';
                    $seat_price = $price * 1.6;
                } elseif($index < 5) {
                    $seat_type = 'VIP';
                    $seat_price = $price * 1.3;
                } else {
                    $seat_type = 'Regular';
                    $seat_price = $price;
                }
                
                mysqli_query($conn, "INSERT INTO seats (event_id, seat_row, seat_number, seat_type, price, status) 
                                     VALUES ($event_id, $row, $num, '$seat_type', $seat_price, 'available')");
            }
        }
        return "Cinema seats generated: " . (10 * 15) . " seats";
        
    } else {
        // THEATER LAYOUT - Rows A-H, 10 seats per row
        $rows = ['A','B','C','D','E','F','G','H'];
        $seats_per_row = 10;
        
        foreach($rows as $index => $row) {
            for($num = 1; $num <= $seats_per_row; $num++) {
                if($index < 2) {
                    $seat_type = 'VVIP';
                    $seat_price = $price * 1.6;
                } elseif($index < 5) {
                    $seat_type = 'VIP';
                    $seat_price = $price * 1.3;
                } else {
                    $seat_type = 'Regular';
                    $seat_price = $price;
                }
                
                mysqli_query($conn, "INSERT INTO seats (event_id, seat_row, seat_number, seat_type, price, status) 
                                     VALUES ($event_id, $row, $num, '$seat_type', $seat_price, 'available')");
            }
        }
        return "Theater seats generated: " . (8 * 10) . " seats";
    }
}

// Handle form submission
if(isset($_POST['update_event'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $category_id = (int)$_POST['category_id'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $price = (float)$_POST['price'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $allow_seat_selection = isset($_POST['allow_seat_selection']) ? (int)$_POST['allow_seat_selection'] : 1;
    $venue_layout = mysqli_real_escape_string($conn, $_POST['venue_layout'] ?? 'theater');
    
    // Handle image upload
    $image = $event['image'];
    
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/";
        
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            if($imageFileType == "jpg" || $imageFileType == "jpeg" || $imageFileType == "png" || $imageFileType == "gif") {
                if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    if($event['image'] && file_exists($target_dir . $event['image'])) {
                        unlink($target_dir . $event['image']);
                    }
                } else {
                    $error = "Sorry, there was an error uploading your file.";
                }
            } else {
                $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        } else {
            $error = "File is not an image.";
        }
    }
    
    if(empty($error)) {
        mysqli_begin_transaction($conn);
        
        try {
            // Update event
            $update = mysqli_query($conn, 
                "UPDATE events SET 
                    title = '$title',
                    category_id = '$category_id',
                    description = '$description',
                    event_date = '$event_date',
                    event_time = '$event_time',
                    location = '$location',
                    price = '$price',
                    image = '$image',
                    allow_seat_selection = '$allow_seat_selection',
                    venue_layout = '$venue_layout'
                 WHERE id = $id");
            
            if(!$update) {
                throw new Exception("Failed to update event: " . mysqli_error($conn));
            }
            
            // Handle seat generation if seat selection is enabled
            if($allow_seat_selection == 1) {
                $seat_message = generateSeats($conn, $id, $venue_layout, $price);
            } else {
                // If seat selection is disabled, delete all seats
                mysqli_query($conn, "DELETE FROM seats WHERE event_id = $id");
                $seat_message = "Seats deleted (event is now general admission)";
            }
            
            // Handle Regular ticket
            $regular_desc = mysqli_real_escape_string($conn, $_POST['regular_description']);
            $regular_max = (int)$_POST['regular_max_quantity'];
            
            if($regular_data) {
                mysqli_query($conn, "UPDATE ticket_types SET 
                    description = '$regular_desc',
                    max_quantity = $regular_max
                    WHERE event_id = $id AND type_name = 'Regular'");
            } else {
                mysqli_query($conn, "INSERT INTO ticket_types (event_id, type_name, price_multiplier, description, max_quantity) 
                    VALUES ($id, 'Regular', 1.00, '$regular_desc', $regular_max)");
            }
            
            // Handle VIP ticket
            if(isset($_POST['vip_enabled'])) {
                $vip_desc = mysqli_real_escape_string($conn, $_POST['vip_description']);
                $vip_max = (int)$_POST['vip_max_quantity'];
                
                $check_vip = mysqli_query($conn, "SELECT id FROM ticket_types WHERE event_id = $id AND type_name = 'VIP'");
                
                if(mysqli_num_rows($check_vip) > 0) {
                    mysqli_query($conn, "UPDATE ticket_types SET 
                        description = '$vip_desc',
                        price_multiplier = 1.30,
                        max_quantity = $vip_max
                        WHERE event_id = $id AND type_name = 'VIP'");
                } else {
                    mysqli_query($conn, "INSERT INTO ticket_types (event_id, type_name, price_multiplier, description, max_quantity) 
                        VALUES ($id, 'VIP', 1.30, '$vip_desc', $vip_max)");
                }
            } else {
                mysqli_query($conn, "DELETE FROM ticket_types WHERE event_id = $id AND type_name = 'VIP'");
            }
            
            // Handle VVIP ticket
            if(isset($_POST['vvip_enabled'])) {
                $vvip_desc = mysqli_real_escape_string($conn, $_POST['vvip_description']);
                $vvip_max = (int)$_POST['vvip_max_quantity'];
                
                $check_vvip = mysqli_query($conn, "SELECT id FROM ticket_types WHERE event_id = $id AND type_name = 'VVIP'");
                
                if(mysqli_num_rows($check_vvip) > 0) {
                    mysqli_query($conn, "UPDATE ticket_types SET 
                        description = '$vvip_desc',
                        price_multiplier = 1.60,
                        max_quantity = $vvip_max
                        WHERE event_id = $id AND type_name = 'VVIP'");
                } else {
                    mysqli_query($conn, "INSERT INTO ticket_types (event_id, type_name, price_multiplier, description, max_quantity) 
                        VALUES ($id, 'VVIP', 1.60, '$vvip_desc', $vvip_max)");
                }
            } else {
                mysqli_query($conn, "DELETE FROM ticket_types WHERE event_id = $id AND type_name = 'VVIP'");
            }
            
            mysqli_commit($conn);
            
            $success = "Event updated successfully! " . $seat_message;
            
            // Refresh event data
            $result = mysqli_query($conn, "SELECT * FROM events WHERE id = $id");
            $event = mysqli_fetch_assoc($result);
            
            // Refresh ticket types
            $ticket_types_result = mysqli_query($conn, "SELECT * FROM ticket_types WHERE event_id = $id");
            $ticket_types = [];
            $vip_data = null;
            $vvip_data = null;
            $regular_data = null;
            
            while($ticket = mysqli_fetch_assoc($ticket_types_result)) {
                $ticket_types[] = $ticket;
                if($ticket['type_name'] == 'VIP') {
                    $vip_data = $ticket;
                }
                if($ticket['type_name'] == 'VVIP') {
                    $vvip_data = $ticket;
                }
                if($ticket['type_name'] == 'Regular') {
                    $regular_data = $ticket;
                }
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Event - Tickex Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-form {
            background: #1e293b;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: white;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, 
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            font-size: 16px;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .form-group input[type="file"] {
            padding: 10px;
            background: #0f172a;
            border: 1px dashed #facc15;
        }
        .ticket-type-box {
            background: #0f172a;
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .ticket-type-item {
            margin-bottom: 15px;
            padding: 20px;
            background: #1e293b;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .ticket-type-item.regular {
            border-left-color: #ffffff;
        }
        .ticket-type-item.vip {
            border-left-color: #facc15;
        }
        .ticket-type-item.vvip {
            border-left-color: #ff0000;
        }
        .ticket-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .ticket-header input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        .ticket-header label {
            font-weight: bold;
            font-size: 18px;
        }
        .ticket-header label.regular {
            color: white;
        }
        .ticket-header label.vip {
            color: #facc15;
        }
        .ticket-header label.vvip {
            color: #ff0000;
        }
        .price-preview {
            color: #facc15;
            font-size: 18px;
            font-weight: bold;
            margin-left: auto;
        }
        .success-msg {
            background: #00aa00;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error-msg {
            background: #ff0000;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .btn-submit {
            background: #facc15;
            color: black;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover {
            background: #eab308;
        }
        .current-image {
            margin: 10px 0;
            padding: 10px;
            background: #0f172a;
            border-radius: 8px;
        }
        .current-image img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #facc15;
        }
        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            display: none;
        }
        .image-preview img {
            width: 100%;
            border-radius: 8px;
            border: 2px solid #facc15;
        }
        .inline-input {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .inline-input input[type="number"] {
            width: 100px;
        }
        .regular-note {
            color: #999;
            font-size: 12px;
            margin-left: 10px;
        }
        
        /* Seat Selection Styles */
        .seat-selection-box {
            background: #0f172a;
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .radio-group {
            display: flex;
            gap: 30px;
            margin-top: 10px;
            flex-wrap: wrap;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            cursor: pointer;
        }
        .venue-layout {
            margin-top: 15px;
            padding: 15px;
            background: #0f172a;
            border-radius: 8px;
        }
        .layout-note {
            color: #999;
            font-size: 12px;
            margin-top: 10px;
            padding: 8px;
            background: #0f172a;
            border-radius: 5px;
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
            <li class="active">✏️ Edit Event</li>
            <li onclick="location.href='../dashboard.php'">🏠 View Site</li>
            <li onclick="location.href='../logout.php'">🚪 Logout</li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <h1>Edit Event</h1>
        
        <?php if($success): ?>
            <div class="success-msg">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error-msg">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
            
            <div class="form-group">
                <label>🎤 Event Title:</label>
                <input type="text" name="title" required value="<?php echo htmlspecialchars($event['title']); ?>">
            </div>
            
            <div class="form-group">
                <label>📁 Category:</label>
                <select name="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                            <?php echo ($cat['id'] == $event['category_id']) ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>📅 Event Date:</label>
                <input type="date" name="event_date" required value="<?php echo $event['event_date']; ?>">
            </div>
            
            <div class="form-group">
                <label>⏰ Event Time:</label>
                <input type="time" name="event_time" required value="<?php echo $event['event_time']; ?>">
            </div>
            
            <div class="form-group">
                <label>📍 Location:</label>
                <input type="text" name="location" required value="<?php echo htmlspecialchars($event['location']); ?>">
            </div>
            
            <div class="form-group">
                <label>💰 Base Price (KES) - Regular Ticket:</label>
                <input type="number" name="price" id="base_price" required value="<?php echo $event['price']; ?>" oninput="updateTicketPrices()">
            </div>
            
            <!-- ===== SEAT SELECTION SECTION ===== -->
            <div class="form-group">
                <label>🎭 Seat Selection:</label>
                <div class="seat-selection-box">
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="allow_seat_selection" value="1" <?php echo ($event['allow_seat_selection'] ?? 1) == 1 ? 'checked' : ''; ?> onchange="toggleVenueLayout(true)">
                            ✅ Yes - Allow seat selection (seated event)
                        </label>
                        <label>
                            <input type="radio" name="allow_seat_selection" value="0" <?php echo ($event['allow_seat_selection'] ?? 1) == 0 ? 'checked' : ''; ?> onchange="toggleVenueLayout(false)">
                            ❌ No - General admission (standing/floor)
                        </label>
                    </div>
                    
                    <div class="venue-layout" id="venue_layout_div" style="<?php echo ($event['allow_seat_selection'] ?? 1) == 1 ? 'display: block;' : 'display: none;'; ?>">
                        <label>🏟️ Venue Layout:</label>
                        <select name="venue_layout" id="venue_layout" style="width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; background: #0f172a; border: 1px solid #334155; color: white;">
                            <option value="theater" <?php echo ($event['venue_layout'] ?? 'theater') == 'theater' ? 'selected' : ''; ?>>🎭 Theater (Rows A-H, 10 seats per row)</option>
                            <option value="cinema" <?php echo ($event['venue_layout'] ?? 'theater') == 'cinema' ? 'selected' : ''; ?>>🎬 Cinema (Rows A-J, 15 seats per row)</option>
                            <option value="stadium" <?php echo ($event['venue_layout'] ?? 'theater') == 'stadium' ? 'selected' : ''; ?>>🏟️ Stadium (Home/Away Sections, 20 seats per row)</option>
                        </select>
                        
                        <div class="layout-note" id="layout_note">
                            <?php
                            $current_layout = $event['venue_layout'] ?? 'theater';
                            if($current_layout == 'stadium') {
                                echo "📌 Stadium Layout: HOME & AWAY sections, rows 1-15 (bottom to top), 20 seats per row";
                            } elseif($current_layout == 'cinema') {
                                echo "📌 Cinema Layout: Rows A-J, 15 seats per row";
                            } else {
                                echo "📌 Theater Layout: Rows A-H, 10 seats per row";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>🎟️ Ticket Types:</label>
                <div class="ticket-type-box">
                    
                    <!-- Regular Ticket (Always included) -->
                    <div class="ticket-type-item regular">
                        <div class="ticket-header">
                            <span style="font-weight: bold; color: white;">✅ Regular Ticket</span>
                            <span class="regular-note">(Always included - 0% markup)</span>
                            <span class="price-preview" id="regular_price">KES <?php echo number_format($event['price']); ?></span>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <input type="text" name="regular_description" placeholder="Regular ticket benefits" 
                                   value="<?php echo $regular_data ? htmlspecialchars($regular_data['description']) : 'Standard admission'; ?>"
                                   style="width: 100%; padding: 10px; border-radius: 5px; background: #0f172a; border: 1px solid #334155; color: white;">
                        </div>
                        
                        <div class="inline-input">
                            <span style="color: white;">Max quantity:</span>
                            <input type="number" name="regular_max_quantity" value="<?php echo $regular_data ? $regular_data['max_quantity'] : '500'; ?>" min="1" max="1000">
                        </div>
                    </div>
                    
                    <!-- VIP Ticket -->
                    <div class="ticket-type-item vip">
                        <div class="ticket-header">
                            <input type="checkbox" name="vip_enabled" id="vip_enabled" value="1" 
                                <?php echo $vip_data ? 'checked' : ''; ?> onchange="updateTicketPrices()">
                            <label for="vip_enabled" style="color: #facc15;">VIP Ticket</label>
                            <span style="color: #999;">(30% more than regular)</span>
                            <span class="price-preview" id="vip_price">KES <?php echo $vip_data ? number_format($event['price'] * 1.3) : '0'; ?></span>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <input type="text" name="vip_description" placeholder="VIP Benefits (e.g., Access to VIP lounge, Free drinks)" 
                                   value="<?php echo $vip_data ? htmlspecialchars($vip_data['description']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border-radius: 5px; background: #0f172a; border: 1px solid #334155; color: white;">
                        </div>
                        
                        <div class="inline-input">
                            <span style="color: white;">Max quantity:</span>
                            <input type="number" name="vip_max_quantity" value="<?php echo $vip_data ? $vip_data['max_quantity'] : '100'; ?>" min="1" max="500">
                        </div>
                    </div>
                    
                    <!-- VVIP Ticket -->
                    <div class="ticket-type-item vvip">
                        <div class="ticket-header">
                            <input type="checkbox" name="vvip_enabled" id="vvip_enabled" value="1" 
                                <?php echo $vvip_data ? 'checked' : ''; ?> onchange="updateTicketPrices()">
                            <label for="vvip_enabled" style="color: #ff0000;">VVIP Ticket</label>
                            <span style="color: #999;">(60% more than regular)</span>
                            <span class="price-preview" id="vvip_price">KES <?php echo $vvip_data ? number_format($event['price'] * 1.6) : '0'; ?></span>
                        </div>
                        
                        <div style="margin-bottom: 10px;">
                            <input type="text" name="vvip_description" placeholder="VVIP Benefits (e.g., Meet & Greet, Backstage access)" 
                                   value="<?php echo $vvip_data ? htmlspecialchars($vvip_data['description']) : ''; ?>"
                                   style="width: 100%; padding: 10px; border-radius: 5px; background: #0f172a; border: 1px solid #334155; color: white;">
                        </div>
                        
                        <div class="inline-input">
                            <span style="color: white;">Max quantity:</span>
                            <input type="number" name="vvip_max_quantity" value="<?php echo $vvip_data ? $vvip_data['max_quantity'] : '50'; ?>" min="1" max="500">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>📝 Description:</label>
                <textarea name="description"><?php echo htmlspecialchars($event['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>🖼️ Current Image:</label>
                <div class="current-image">
                    <?php if($event['image']): ?>
                        <img src="../assets/images/<?php echo $event['image']; ?>" 
                             onerror="this.src='../assets/images/placeholder.jpg'">
                        <p style="color: #999; margin-top: 5px;"><?php echo $event['image']; ?></p>
                    <?php else: ?>
                        <p style="color: #999;">No image uploaded</p>
                    <?php endif; ?>
                </div>
                
                <label style="margin-top: 10px;">🖼️ Upload New Image (optional):</label>
                <input type="file" name="image" accept="image/*" id="imageInput">
                <p style="color: #999; font-size: 12px; margin-top: 5px;">
                    Leave empty to keep current image. Allowed: JPG, JPEG, PNG, GIF
                </p>
                <div class="image-preview" id="imagePreview">
                    <img src="" alt="Image Preview">
                </div>
            </div>
            
            <button type="submit" name="update_event" class="btn-submit">
                ✏️ Update Event
            </button>
        </form>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="events.php" class="btn btn-outline">← Back to Events</a>
        </div>
    </div>
</div>

<script>
// Update ticket prices based on base price
function updateTicketPrices() {
    let basePrice = parseFloat(document.getElementById('base_price').value) || 0;
    
    // Regular price
    document.getElementById('regular_price').innerHTML = 'KES ' + basePrice.toLocaleString();
    
    // VIP price (30% more)
    let vipCheckbox = document.getElementById('vip_enabled');
    let vipPriceSpan = document.getElementById('vip_price');
    if(vipCheckbox.checked) {
        let vipPrice = basePrice * 1.3;
        vipPriceSpan.innerHTML = 'KES ' + Math.round(vipPrice).toLocaleString();
    } else {
        vipPriceSpan.innerHTML = 'KES 0';
    }
    
    // VVIP price (60% more)
    let vvipCheckbox = document.getElementById('vvip_enabled');
    let vvipPriceSpan = document.getElementById('vvip_price');
    if(vvipCheckbox.checked) {
        let vvipPrice = basePrice * 1.6;
        vvipPriceSpan.innerHTML = 'KES ' + Math.round(vvipPrice).toLocaleString();
    } else {
        vvipPriceSpan.innerHTML = 'KES 0';
    }
}

// Toggle venue layout visibility
function toggleVenueLayout(show) {
    const venueDiv = document.getElementById('venue_layout_div');
    if(venueDiv) {
        venueDiv.style.display = show ? 'block' : 'none';
    }
}

// Update layout note when venue layout changes
document.getElementById('venue_layout')?.addEventListener('change', function() {
    const layoutNote = document.getElementById('layout_note');
    if(this.value === 'stadium') {
        layoutNote.innerHTML = '📌 Stadium Layout: HOME & AWAY sections, rows 1-15 (bottom to top), 20 seats per row';
    } else if(this.value === 'cinema') {
        layoutNote.innerHTML = '📌 Cinema Layout: Rows A-J, 15 seats per row';
    } else {
        layoutNote.innerHTML = '📌 Theater Layout: Rows A-H, 10 seats per row';
    }
});

// Image preview before upload
document.getElementById('imageInput').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    const file = e.target.files[0];
    
    if(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.style.display = 'block';
            preview.querySelector('img').src = e.target.result;
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

// Initial price update
updateTicketPrices();
</script>

</body>
</html>