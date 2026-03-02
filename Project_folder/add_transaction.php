<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];

$name = $_POST['transaction_name'];
$type = $_POST['type'];
$amount = $_POST['amount'];
$date = $_POST['date'];

$sql = "INSERT INTO transactions 
(user_id, transaction_name, type, amount, transaction_date)
VALUES ('$user_id', '$name', '$type', '$amount', '$date')";

if(mysqli_query($conn, $sql)){
    header("Location: dashboard.php");
    exit();
} else {
    echo "Error: " . mysqli_error($conn);
}
?>