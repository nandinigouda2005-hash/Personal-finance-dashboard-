<?php
$conn = new mysqli("localhost", "root", "", "index");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>