<?php
session_start();
include("config.php");

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    if(password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];   // make sure your users table has id column
$_SESSION['user'] = $row['first_name'];
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Invalid password";
    }
} else {
    echo "User not found";
}
?>