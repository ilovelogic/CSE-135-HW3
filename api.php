<?php
header("Content-Type: application/json");

# for testing, long-term goal is using a database
$mockStaticData = [["id" => 1, "userAgent" => "Mozilla/5.0", "language" => "en-US", "cookieEnabled" => true],
  ["id" => 2, "userAgent" => "Chrome/90", "language" => "fr-FR", "cookieEnabled" => false]];

$request = $_SERVER['REQUEST_URI']; // ex. /api.php/static/123
$method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE

if ($request[0] === "/") {
    $request = substr($request,1); // remove leading /
}

$pathArr = explode('/', $request); // breaks up into ["api.php", "static", "123"]

$cleanPathArr = array_filter($pathArr); // removes empty entries (results of a leading or trailing /)

$resource = $cleanPathArr[1] ?? null; // "static"
$id = $cleanPathArr[2] ?? null; // "123"

if ($resource === "static") {
    switch ($method) {
        case 'GET':
            if ($id) {
                # checks if any of the data entries have an id matching the requested id
                # uses loose comparison since id from url is a string and id in mock data is an int
                $entry = array_filter($mockStaticData, fn($dataEntry) => $dataEntry["id"] == $id);
            }
            else { # no id => return all static data
                echo json_encode($mockStaticData); 
            }
            break;

        case 'POST':
            // reads in json payload to associative array
            $payload = json_decode(file_get_contents('php://input'),true); 
            echo json_encode(["message" => "POST recieved successfully", "data" => $input]);
            break;

        case 'PUT':
            if ($id) {
                $payload = json_decode(file_get_contents('php://input'), true);
                // insert update logic for working with the data base HERE
                echo json_encode(["message" => "PUT received for ID $id", "data" => $input]);
            } 
            else {
                http_response_code(400); // 400 means bad request
                echo json_encode(["error" => "ID required for PUT"]);
            }
            break;

        case 'DELETE':
            if ($id) {
                // insert delete logic for interacting with the data base HERE
                echo json_encode(["message" => "DELETE received for ID $id"]);
            } 
            else {
                http_response_code(400); // 400 means bad request
                echo json_encode(["error" => "ID required for DELETE"]);
            }
            break;

        default:
            http_response_code(405); // 405 means method not allowed
            echo json_encode(["error" => "Method $method not supported"]);
    }
}
else {
  http_response_code(404); // 404 means not found (applied since we only support "static")
  echo json_encode(["error" => "Requested resource: $resource. Resource not found"]);
}
?>