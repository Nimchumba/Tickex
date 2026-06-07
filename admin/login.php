<?php
include "../config.php";

$error = "";

// Hard-coded admin credentials (for student/trial use)
// In production, use a database admin_users table with hashed passwords
$admin_username = "admin";
$admin_password = "704665395Nim"; // CHANGE THIS IN PRODUCTION

if(isset($_POST['admin_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'Administrator';
        header("Location: events.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Tickex</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: url('https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1600') center/cover no-repeat fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: Arial, sans-serif;
            position: relative;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }
        .admin-login-container {
            background: #1e293b;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            width: 100%;
            max-width: 400px;
        }
        .admin-login-container h1 {
            color: #facc15;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .admin-title {
            text-align: center;
            color: #ccc;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: white;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #facc15;
            box-shadow: 0 0 5px rgba(250, 204, 21, 0.3);
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #facc15;
            color: black;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .login-btn:hover {
            background: #eab308;
        }
        .error-msg {
            background: #dc2626;
            color: white;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #facc15;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .credentials-info {
            background: #0f172a;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 12px;
            color: #ccc;
            border-left: 3px solid #facc15;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <h1>🔐 Tickex Admin</h1>
        <p class="admin-title">Administrator Dashboard</p>
        
        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" placeholder="Enter admin username" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" placeholder="Enter admin password" required>
            </div>
            
            <button type="submit" name="admin_login" class="login-btn">Login to Admin Panel</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>
