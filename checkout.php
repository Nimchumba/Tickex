<?php
include "config.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart'])) {
    $cart = json_decode($_POST['cart'], true);
    $total = 0;
    
    // Calculate total
    foreach($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Tickex</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container">
    <div class="sidebar">
        <!-- Your sidebar code -->
    </div>
    
    <div class="main">
        <h1>Checkout</h1>
        
        <div style="background: #1e293b; padding: 30px; border-radius: 15px;">
            <h3 style="color: #facc15; margin-bottom: 20px;">Order Summary</h3>
            
            <?php foreach($cart as $item): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 10px; background: #0f172a; border-radius: 5px;">
                    <span><?php echo $item['type']; ?> x<?php echo $item['quantity']; ?></span>
                    <span>KES <?php echo number_format($item['price'] * $item['quantity']); ?></span>
                </div>
            <?php endforeach; ?>
            
            <div style="margin: 20px 0; font-size: 24px; font-weight: bold;">
                Total: KES <?php echo number_format($total); ?>
            </div>
            
            <form method="POST" action="process_payment.php">
                <input type="hidden" name="cart" value='<?php echo htmlspecialchars(json_encode($cart)); ?>'>
                
                <div style="margin-bottom: 20px;">
                    <label>Payment Method:</label>
                    <select name="payment_method" style="width: 100%; padding: 12px; margin-top: 5px;">
                        <option value="M-Pesa">M-Pesa</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Cash on Delivery">Cash on Delivery</option>
                    </select>
                </div>
                
                <button type="submit" name="pay" class="btn" style="width: 100%; padding: 15px;">Confirm Payment</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php
} else {
    header("Location: dashboard.php");
    exit();
}
?>