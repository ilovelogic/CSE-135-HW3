<?php
header("Cache-Control: no-cache");
header("Content-Type: text/html");

session_start();



echo "<!doctype html>";
echo "<head>";
echo "<title>PHP Sessions</title>";
echo "</head>";

echo "<body>";
echo "<h1 align=center>PHP Sessions Page 1</h1>";
echo "<hr/>";

$_SESSION['username'] = $_SESSION['username'] ?? $_POST['username'] ?? "You do not have a name set";
echo "<p><strong>Name: </strong>" . $_SESSION['username'] . "</p>";

echo "<a href=\"/php-cgiform.html?PHPSESSID=" . session_id() . "\">CGI Form</a><br/>";
echo "<a href=\"/cgi-bin/php-URL-sessions-2.php?PHPSESSID=" . session_id() . "\">Session Page 2</a>";

echo "<form style=\"margin-top:30px\" action = \"php-destroy-URL-session.php\" method = \"get\">";
echo "<input type=\"hidden\" name=\"PHPSESSID\" value=\"" . session_id() . "\"/>";
echo "<button type = \"submit\">Destroy Session</button>";
echo "</form>";
echo "</body>";
echo "</html>";
?>