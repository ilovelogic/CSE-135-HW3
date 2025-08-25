<?php
header("Cache-Control: no-cache");
header("Content-Type: text/html");

session_destroy();
$_SESSION = [];

$params = session_get_cookie_params();
setcookie("PHPSESSID", '', time()-1, $params['path'], $params['domain'], $params['secure'], $params['httponly']);

echo "<!doctype html>";
echo "<head>";
echo "<title>Session Destroyed</title>";
echo "</head>";

echo "<body>";
echo "<h1>Session Destroyed</h1>";
echo "<a href=\"/php-cgiform.html\">Back to the CGI Form</a><br/>";
echo "<a href=\"/cgi-bin/php-cookie-sessions-1.php\">Back to Page 1</a><br/>";
echo "<a href=\"/cgi-bin/php-cookie-sessions-2.php\">Back to Page 2</a><br/>";

echo "</body>";
echo "</html>";
?>