<?php
include "db_connect.php";
session_start();

// make sure the user is logged in and we have an ID stored in the session
if (!isset($_SESSION['user_id'])) {
    // no user id available, can't save targets
    echo json_encode(["success" => false, "error" => "not_authenticated"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $_SESSION['user_id'];
$type = $data['type'];
$value = $data['value'];

// Check if record exists
$check = $conn->prepare("SELECT id FROM tracker_targets WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {

    // UPDATE
    if ($type == "income") {
        $sql = "UPDATE tracker_targets SET income_target = ?, updated_at = NOW() WHERE user_id = ?";
    } elseif ($type == "expense") {
        $sql = "UPDATE tracker_targets SET expense_target = ?, updated_at = NOW() WHERE user_id = ?";
    } else {
        $sql = "UPDATE tracker_targets SET savings_target = ?, updated_at = NOW() WHERE user_id = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $value, $user_id);

} else {

    // INSERT FIRST TIME
    $income = 0;
    $expense = 0;
    $savings = 0;

    if ($type == "income") $income = $value;
    if ($type == "expense") $expense = $value;
    if ($type == "savings") $savings = $value;

    $stmt = $conn->prepare("
        INSERT INTO tracker_targets 
        (user_id, income_target, expense_target, savings_target) 
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param("iddd", $user_id, $income, $expense, $savings);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    // return error information for debugging
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>