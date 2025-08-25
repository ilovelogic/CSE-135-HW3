<?php
header("Cache-Control: no-cache");
header("Content-Type: text/html");

echo "<!doctype html>";
echo "<head><title>General Request Echo</title></head>";

echo "<body>";
echo "<h1 align=center>General Request Echo</h1>";
echo "<hr/>";

echo "<p><strong>Request Method: </strong>" . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p><strong>Protocol: </strong>" . $_SERVER['SERVER_PROTOCOL'] . "</p>";
echo "<p><strong>Query: </strong>" . $_SERVER['QUERY_STRING'] . "</p>";
echo "<p><strong>Message Body: </strong>";
echo "<ul>";
foreach ($_REQUEST as $field => $value) {
    echo "<li>" . htmlspecialchars($field) . ": " . htmlspecialchars($value) . "</li>";
}
echo "</ul>";
echo "</p>";

echo "</body>";
echo "</html>";
?>