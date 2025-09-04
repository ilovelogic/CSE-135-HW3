<?php
// =============================
// Secure Proxy for API Requests
// =============================

// Get params safely
$resource = $_GET['resource'] ?? '';
$cols     = $_GET['cols'] ?? '';

// Build the API URL
$apiUrl = "https://annekelley.site/api.php";
if ($resource !== '') {
    $apiUrl .= "/" . urlencode($resource);
}
if ($cols !== '') {
    $apiUrl .= "/" . urlencode($cols);
}

// Initialize cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Add Basic Auth credentials (safe here, hidden from browser)
curl_setopt($ch, CURLOPT_USERPWD, "yourUser:yourPass");

// Forward headers (optional)
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json"
]);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    http_response_code(500);
    echo json_encode(["error" => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Forward response to browser
http_response_code($httpCode);
header("Content-Type: application/json");
echo $response;
