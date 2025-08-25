<?php
header("Cache-Control: no-cache");
header("Content-Type: text/html");

echo "<!doctype html>";
echo "<head><title>POST Request Echo</title></head>";

echo "<body>";
echo "<h1 align=center>POST Request Echo</h1>";
echo "<hr/>";

echo "<p><strong>Raw Message Body:<strong></p>" . file_get_contents('php://input');

echo "<p><strong>Parsed Message Body:<strong></p>";
echo "<ul>";
foreach ($_POST as $field => $value) {
    echo "<li><strong>" . htmlspecialchars($field) . "</strong>: " . htmlspecialchars($value) . "</li>";
}
echo "</ul>";

echo "</body>";
echo "</html>";
?>