<?php
$conn = mysqli_connect("localhost", "root", "", "index");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>