<?php
include "../config.php";

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// For event creation, admin ID is the creator
$user_id = 1; // Use admin ID (1) for all admin-created events

$success = "";
$error = "";

// Get all categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

// Handle form submission
if(isset($_POST['add_event'])) {
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
    $image = "";
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/";
        
        // Create images folder if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Generate unique filename
        $image = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            // Allow certain file formats
            if($imageFileType == "jpg" || $imageFileType == "jpeg" || $imageFileType == "png" || $imageFileType == "gif") {
                // Upload file
                if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Image uploaded successfully
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
    
    // Only insert if no error
    if(empty($error)) {
        $insert = mysqli_query($conn, 
            "INSERT INTO events (user_id, category_id, title, description, event_date, event_time, location, price, image, allow_seat_selection, venue_layout) 
             VALUES ('$user_id', '$category_id', '$title', '$description', '$event_date', '$event_time', '$location', '$price', '$image', '$allow_seat_selection', '$venue_layout')");
        
        if($insert) {
            $event_id = mysqli_insert_id($conn);
            $seat_message = "";
            
            // Insert Regular ticket (always)
            mysqli_query($conn, "INSERT INTO ticket_types (event_id, type_name, price_multiplier, description, max_quantity) 
                                 VALUES ($event_id, 'Regular', 1.00, 'Standard admission', 500)");
            
            // Insert VIP if enabled
            if(isset($_POST['vip_enabled'])) {
                $vip_desc = mysqli_real_escape_string($conn, $_POST['vip_description']);
                mysqli_query($conn, "INSERT INTO ticket_types (event_id, type_name, price_multiplier, description, max_quantity) 
                                     VALUES ($event_id, 'VIP', 1.30, '$vip_desc', 100)");
            }
            
            // Insert VVIP if enabled
            if(isset($_POST['vvip_enabled'])) {
                $vvip_desc = mysqli_real_escape_string($conn, $_POST['vvip_description']);
                mysqli_query($conn, "INSERT INTO ticket_types (event_id, type_name, price_multiplier, description, max_quantity) 
                                     VALUES ($event_id, 'VVIP', 1.60, '$vvip_desc', 50)");
            }
            
            // Generate seats if seat selection is enabled
            if($allow_seat_selection == 1) {
                if($venue_layout == 'stadium') {
                    // STADIUM LAYOUT - Home/Away sections, rows 1-15
                    $sections = ['HOME', 'AWAY'];
                    $rows_per_section = 15;
                    $seat_count = 0;
                    
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
                                $seat_count++;
                            }
                        }
                    }
                    $seat_message = " Stadium seats generated: " . $seat_count . " seats";
                    
                } elseif($venue_layout == 'cinema') {
                    // CINEMA LAYOUT - Rows A-J, 15 seats per row
                    $rows = ['A','B','C','D','E','F','G','H','I','J'];
                    $seats_per_row = 15;
                    $seat_count = 0;
                    
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
                            $seat_count++;
                        }
                    }
                    $seat_message = " Cinema seats generated: " . $seat_count . " seats";
                    
                } else {
                    // THEATER LAYOUT - Rows A-H, 10 seats per row
                    $rows = ['A','B','C','D','E','F','G','H'];
                    $seats_per_row = 10;
                    $seat_count = 0;
                    
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
                            $seat_count++;
                        }
                    }
                    $seat_message = " Theater seats generated: " . $seat_count . " seats";
                }
            }
            
            $success = "Event added successfully! With ticket types." . $seat_message;
        } else {
            $error = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Event - Tickex Admin</title>
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
            padding: 15px;
            background: #1e293b;
            border-radius: 8px;
        }
        .price-preview {
            color: #facc15;
            font-size: 18px;
            font-weight: bold;
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
            <li class="active">➕ Add Event</li>
            <li onclick="location.href='events.php'">📋 Manage Events</li>
            <li onclick="location.href='analytics.php'">📊 Analytics</li>
            <li onclick="location.href='manage_reviews.php'">⭐ Manage Reviews</li>
            <li onclick="location.href='../dashboard.php'">🏠 View Site</li>
            <li onclick="location.href='../logout.php'">🚪 Logout</li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <h1>Add New Event</h1>
        
        <?php if($success): ?>
            <div class="success-msg">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error-msg">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data" class="admin-form">
            
            <div class="form-group">
                <label>🎤 Event Title:</label>
                <input type="text" name="title" required placeholder="e.g. AfterDark Concert">
            </div>
            
            <div class="form-group">
                <label>📁 Category:</label>
                <select name="category_id" required>
                    <option value="">-- Select Category --</option>
                    <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $cat['id']; ?>">
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>📅 Event Date:</label>
                <input type="date" name="event_date" required>
            </div>
            
            <div class="form-group">
                <label>⏰ Event Time:</label>
                <input type="time" name="event_time" required>
            </div>
            
            <div class="form-group">
                <label>📍 Location:</label>
                <input type="text" name="location" required placeholder="e.g. KICC Nairobi">
            </div>
            
            <div class="form-group">
                <label>💰 Base Price (KES) - Regular Ticket:</label>
                <input type="number" name="price" id="base_price" required placeholder="e.g. 1500" oninput="updateTicketPrices()">
            </div>
            
            <!-- ===== SEAT SELECTION SECTION ===== -->
            <div class="form-group">
                <label>🎭 Seat Selection:</label>
                <div class="seat-selection-box">
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="allow_seat_selection" value="1" checked onchange="toggleVenueLayout(true)">
                            ✅ Yes - Allow seat selection (seated event)
                        </label>
                        <label>
                            <input type="radio" name="allow_seat_selection" value="0" onchange="toggleVenueLayout(false)">
                            ❌ No - General admission (standing/floor)
                        </label>
                    </div>
                    
                    <div class="venue-layout" id="venue_layout_div">
                        <label>🏟️ Venue Layout:</label>
                        <select name="venue_layout" id="venue_layout" style="width: 100%; padding: 12px; margin-top: 10px; border-radius: 8px; background: #0f172a; border: 1px solid #334155; color: white;">
                            <option value="theater">🎭 Theater (Rows A-H, 10 seats per row)</option>
                            <option value="cinema">🎬 Cinema (Rows A-J, 15 seats per row)</option>
                            <option value="stadium">🏟️ Stadium (Home/Away Sections, 20 seats per row)</option>
                        </select>
                        
                        <div class="layout-note" id="layout_note">
                            📌 Theater Layout: Rows A-H, 10 seats per row
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>🎟️ Ticket Types:</label>
                <div class="ticket-type-box">
                    
                    <!-- Regular Ticket (always included) -->
                    <div class="ticket-type-item">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="font-weight: bold; color: #facc15;">✅ Regular Ticket</span>
                            <span style="color: #999;">(Always included)</span>
                        </div>
                        <div style="color: white;">
                            Price: <span class="price-preview" id="regular_price">0</span>
                        </div>
                    </div>
                    
                    <!-- VIP Ticket -->
                    <div class="ticket-type-item">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <input type="checkbox" name="vip_enabled" id="vip_enabled" value="1" style="width: 20px; height: 20px;" onchange="updateTicketPrices()">
                            <span style="font-weight: bold; color: #facc15;">VIP Ticket</span>
                            <span style="color: #999;">(30% more than regular)</span>
                        </div>
                        <div style="margin-bottom: 10px; color: white;">
                            Price: <span class="price-preview" id="vip_price">0</span>
                        </div>
                        <div>
                            <input type="text" name="vip_description" placeholder="VIP Benefits (e.g., Access to VIP lounge, Free drinks)" 
                                   style="width: 100%; padding: 10px; border-radius: 5px; background: #0f172a; border: 1px solid #334155; color: white;">
                        </div>
                    </div>
                    
                    <!-- VVIP Ticket -->
                    <div class="ticket-type-item">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <input type="checkbox" name="vvip_enabled" id="vvip_enabled" value="1" style="width: 20px; height: 20px;" onchange="updateTicketPrices()">
                            <span style="font-weight: bold; color: #facc15;">VVIP Ticket</span>
                            <span style="color: #999;">(60% more than regular)</span>
                        </div>
                        <div style="margin-bottom: 10px; color: white;">
                            Price: <span class="price-preview" id="vvip_price">0</span>
                        </div>
                        <div>
                            <input type="text" name="vvip_description" placeholder="VVIP Benefits (e.g., Meet & Greet, Backstage access)" 
                                   style="width: 100%; padding: 10px; border-radius: 5px; background: #0f172a; border: 1px solid #334155; color: white;">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>📝 Description:</label>
                <textarea name="description" placeholder="Tell people about this event..."></textarea>
            </div>
            
            <div class="form-group">
                <label>🖼️ Event Image:</label>
                <input type="file" name="image" accept="image/*" id="imageInput">
                <p style="color: #999; font-size: 12px; margin-top: 5px;">
                    Allowed: JPG, JPEG, PNG, GIF (Max 2MB)
                </p>
                <div class="image-preview" id="imagePreview">
                    <img src="" alt="Image Preview">
                </div>
            </div>
            
            <button type="submit" name="add_event" class="btn-submit">
                ➕ Add Event with Ticket Types
            </button>
        </form>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
        </div>
    </div>
</div>

<script>
// Update ticket prices based on base price
function updateTicketPrices() {
    let basePrice = parseFloat(document.getElementById('base_price').value) || 0;
    
    // Regular price
    document.getElementById('regular_price').textContent = 'KES ' + basePrice.toLocaleString();
    
    // VIP price (30% more)
    let vipCheckbox = document.getElementById('vip_enabled');
    let vipPriceSpan = document.getElementById('vip_price');
    if(vipCheckbox.checked) {
        let vipPrice = basePrice * 1.3;
        vipPriceSpan.textContent = 'KES ' + Math.round(vipPrice).toLocaleString();
    } else {
        vipPriceSpan.textContent = 'KES 0';
    }
    
    // VVIP price (60% more)
    let vvipCheckbox = document.getElementById('vvip_enabled');
    let vvipPriceSpan = document.getElementById('vvip_price');
    if(vvipCheckbox.checked) {
        let vvipPrice = basePrice * 1.6;
        vvipPriceSpan.textContent = 'KES ' + Math.round(vvipPrice).toLocaleString();
    } else {
        vvipPriceSpan.textContent = 'KES 0';
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