<?php

// connection info for mySQL database
$servername = "localhost";
$username = "root";
$password = "jTsB472@^";
$dbname = "web_analytics";

header("Content-Type: application/json");

// connects to mySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// checks connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// prepares statement
$stmt = $conn->prepare("INSERT INTO jsOff (request_time, ip_address) VALUES (NOW(), ?)");

$ip = $_SERVER['REMOTE_ADDR'];
$stmt->bind_param("s", $ip);

$stmt->execute();
$stmt->close();
$conn->close();

// sends a simple 1x1 transparent GIF to satisfy image request
header('Content-Type: image/gif');
echo base64_decode('R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
?>