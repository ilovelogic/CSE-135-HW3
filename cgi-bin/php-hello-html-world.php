<?php
header("Cache-Control: no-cache");
header("Content-type: text/html");

echo "<!doctype html>";
echo "<head><title>Hello PHP World</title></head>";

echo "<h1 align=center>Hello PHP World</h1>";
echo "<hr/>";
echo "<p>Hello world</p>";
echo "<p>This page was generated with the PHP programming language</p>";
echo "<p>This page was run at: " . date("Y-m-d") . "</p>Your current IP address is " . $_SERVER['REMOTE_ADDR'];
echo "</body>";
echo "</html>";
?>