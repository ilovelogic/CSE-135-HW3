<?php
header("Cache-Control: no-cache");
header("Content-Type: text/html");

echo "<!doctype html>";
echo "<head><title>GET Request Echo</title></head>";
echo "<h1 align=center>GET Request Echo</h1>";
echo "<hr/>";
echo "<p><strong>Query String: </strong></p>" . $_SERVER['QUERY_STRING'];

?>