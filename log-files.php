<?php
/*
  Parses Apache2 log format:
  %v:%p\t%h\t%u\t%t\t"%r"\t%>s\t%O\t"%{Referer}i"\t"%{User-Agent}i"\t%D\t%f\t%X\t"%{HTTP_COOKIE}e"
  and inserts data into MySQL web_analytics database.
 */

// Connection details for mySQL database
$servername = "localhost";
$username = "root";
$password = "jTsB472@^";
$dbname = "web_analytics";

$logFile = '/var/log/apache2/access.log'; // Log file path

// MySQL connection using mysqli
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_errno) {
    die("Failed to connect to MySQL: " . $conn->connect_error);
}

// Prepares insert statement, to prevent SQL injection from custom setting ot User-Agent and so on
$stmt = $conn->prepare("
    INSERT INTO apacheLogs (
        vhost, port, clientIP, authUser, datetimeReqReceived,
        requestLine, httpStatus, bytesSent, referer, userAgent, 
        timeToServeMS, filename, connStatus, cookie 
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Open log file for reading
$handle = fopen($logFile, "r");
if (!$handle) {
    die("Cannot open log file: $logFile");
}

while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    // Splits line by tab character
    $parts = explode("\t", $line);
    if (count($parts) !== 13) {
        echo "Unexpected number of parts of log file provided, given delimiter /t, when parsing $line.";
        continue; // Skips to processing next line without entering this line into the DB
    }

    // Map parts to variables
    $vhostPort     = $parts[0]; // Format: vhost:port
    $clientIP      = $parts[1];
    $authUser      = $parts[2];
    $timeStr       = $parts[3]; // Time with brackets e.g. [01/Sep/2025:12:00:00 +0000]
    $requestLine   = trim($parts[4], '"'); // Removes enclosing quotes
    $httpStatus    = intval($parts[5]);
    $bytesSent     = intval($parts[6]);
    $referer       = trim($parts[7], '"');
    $userAgent     = trim($parts[8], '"');
    $timeToServeMS = intval($parts[9]);
    $filename      = $parts[10];
    $connStatus    = $parts[11];
    $cookie        = trim($parts[12], '"');

    // Separates vhost and port
    $colonPos = strrpos($vhostPort, ':');
    if ($colonPos !== false) {
        $vhost = substr($vhostPort, 0, $colonPos);
        $port = substr($vhostPort, $colonPos + 1);
    } else {
        $vhost = $vhostPort;
        $port = null;
    }

    // Converts Apache log time format "[01/Sep/2025:12:00:00 +0000]" to MySQL DATETIME "YYYY-MM-DD HH:MM:SS"
    $timeStr = trim($timeStr, '[]');
    $datetimeReqReceived = DateTime::createFromFormat('d/M/Y:H:i:s O', $timeStr);
    if (!$datetimeReqReceived) {
        // Invalid date format, skips this line and moves on to the next
        echo "Invalid date format encountered when parsing $line. Expected format is setup like [01/Sep/2025:12:00:00 +0000].";
        continue;
    }
    $datetimeReqReceivedSql = $datetimeReqReceived->format('Y-m-d H:i:s');

    // Binds parameters and executes insert
    $stmt->bind_param(
        'sisssiisssisss',
        $vhost,
        $port,
        $clientIP,
        $authUser,
        $datetimeReqReceivedSql,
        $requestLine,
        $httpStatus,
        $bytesSent,
        $referer,
        $userAgent,
        $timeToServeMS,
        $filename,
        $connStatus,
        $cookie
    );
    $stmt->execute();
}

fclose($handle);
$stmt->close();
$conn->close();

echo "Log parsing and insertion into DB complete.\n";
?>
