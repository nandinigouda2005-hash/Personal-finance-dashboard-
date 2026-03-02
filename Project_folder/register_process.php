<?php
include("config.php");

$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO users (first_name, last_name, email, password)
        VALUES ('$first_name', '$last_name', '$email', '$password')";

if (mysqli_query($conn, $sql)) {
    echo "Registration Successful!";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>