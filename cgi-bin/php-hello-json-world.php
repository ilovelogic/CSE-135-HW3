<?php
header("Cache-Control: no-cache");
header("Content-type: application/json");

$date = date("Y-m-d");
$address = $_SERVER['REMOTE_ADDR'];

$mssg = ["message" => "Hello World from PHP!", "date" => "Today's date is $date", "ipaddress" => $address];
echo json_encode($mssg);
?>