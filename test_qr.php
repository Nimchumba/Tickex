<?php
// Simple test to check if QR code library is working
echo "<h1>QR Code Library Test</h1>";

// Check if the library file exists
$library_path = 'lib/phpqrcode/qrlib.php';
if (file_exists($library_path)) {
    echo "✅ QR code library found at: " . $library_path . "<br>";
    
    // Try to include it
    include $library_path;
    echo "✅ Library included successfully!<br>";
    
    // Try to generate a simple QR code
    $tempDir = "tickets/";
    
    // Check if tickets folder exists
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
        echo "✅ Created tickets folder<br>";
    }
    
    // Check if tickets folder is writable
    if (is_writable($tempDir)) {
        echo "✅ Tickets folder is writable<br>";
    } else {
        echo "❌ Tickets folder is NOT writable. Fix permissions.<br>";
    }
    
    echo "<br>✅ QR code library is ready to use!";
    
} else {
    echo "❌ QR code library NOT found at: " . $library_path . "<br>";
    echo "<br>Please install the library first.";
}
?>