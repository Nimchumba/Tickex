<?php
include "../config.php";

// Destroy admin session
unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
session_destroy();

header("Location: login.php");
exit();
?>
