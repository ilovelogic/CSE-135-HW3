<?php
header("Cache-Control: no-cache");
header("Content-Type: text/html");

session_destroy();
$_SESSION = [];

echo "<!doctype html>";
echo "<head>";
echo "<title>Session Destroyed</title>";
echo "</head>";

echo "<body>";
echo "<h1>PHP Sessions Page 1</h1>";
echo "<a href=\"/php-cgiform.html\">Back to the CGI Form</a><br/>";
echo "<a href=\"/cgi-bin/php-URL-sessions-1.php\">Back to Page 1</a><br/>";
echo "<a href=\"/cgi-bin/php-URL-sessions-2.php\">Back to Page 2</a><br/>";

echo "</body>";
echo "</html>";
?>