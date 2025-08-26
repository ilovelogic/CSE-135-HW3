<?php
header("Content-Type: application/json");

$mockStaticData = [["id" => 1, "userAgent" => "Mozilla/5.0", "language" => "en-US", "cookieEnabled" => true],
  ["id" => 2, "userAgent" => "Chrome/90", "language" => "fr-FR", "cookieEnabled" => false]];

$request = trim($_SERVER['REQUEST_URI']); // ex. /api/static/123, trim removes leading, trailing /
$method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE

$pathArr = explode('/', $request);
$cleanPathArr = array_filter($pathArr); // removes empty elements
$resource = $cleanPathArr[1] ?? null;
$id = $cleanPathArr[2] ?? null;

if ($resource === "static") {
    switch ($method) {
        case 'GET':
            break;
        case 'POST':
            break;
        case 'PUT':
            break;
        case 'DELETE':
            break;
    }
}





?>