<?php
include 'db_connect.php';
session_start();

$user_id = $_SESSION['user_id'];

$monthly_income = $_POST['monthly_income'];
$income_source = $_POST['income_source'];
$income_date = $_POST['income_date'];

$sql = "INSERT INTO income_details 
(user_id, monthly_income, income_source, income_date)
VALUES (?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("idss", $user_id, $monthly_income, $income_source, $income_date);
$stmt->execute();

header("Location: dashboard.php");
exit();
?>