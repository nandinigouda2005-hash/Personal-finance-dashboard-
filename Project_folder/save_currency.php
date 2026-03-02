<?php
session_start();
include 'db_connect.php'; // your DB connection

$user_id = $_SESSION['user_id'];
$currency = $_POST['currency'];

$sql = "UPDATE users SET currency = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $currency, $user_id);
$stmt->execute();

echo "success";
?>