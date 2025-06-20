<?php
$host = "localhost";
$user = "root";
$password = "123456";
$dbname = "guidancehub";

$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
