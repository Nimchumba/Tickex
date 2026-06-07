<?php
include "config.php";

if($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_token'])) {
    header('Location: login.php');
    exit();
}

$id_token = $_POST['id_token'];
$google_client_id = GOOGLE_CLIENT_ID;

$verify_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
$response = @file_get_contents($verify_url);
if($response === false) {
    $error = 'Unable to verify Google sign-in. Please try again.';
} else {
    $data = json_decode($response, true);

    if(empty($data['aud']) || $data['aud'] !== $google_client_id) {
        $error = 'Google sign-in client mismatch.';
    } elseif(empty($data['email_verified']) || $data['email_verified'] !== 'true') {
        $error = 'Google account email not verified.';
    } else {
        $email = mysqli_real_escape_string($conn, $data['email']);
        $full_name = mysqli_real_escape_string($conn, $data['name'] ?? preg_replace('/@.*$/', '', $email));

        $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if(mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
        } else {
            $insert = mysqli_query($conn, "INSERT INTO users (full_name, email, password) VALUES ('$full_name', '$email', '')");
            if(!$insert) {
                $error = 'Failed to create account: ' . mysqli_error($conn);
            } else {
                $user_id = mysqli_insert_id($conn);
                $user = [
                    'id' => $user_id,
                    'full_name' => $full_name,
                    'email' => $email,
                ];
            }
        }

        if(empty($error)) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header('Location: dashboard.php');
            exit();
        }
    }
}

if(!empty($error)) {
    $_SESSION['oauth_error'] = $error;
    header('Location: login.php');
    exit();
}
