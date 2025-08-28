<?php

/*
Implements the following routing:

HTTP Method | Example              | Route Description
GET         | /api.php/static      | Retrieve every entry logged in the static table
GET         | /api.php/static/{id} | Retrieve a specific entry logged in the static table (that matches the given id)
POST        | /api.php/static      | Add a new entry to the static table
PUT         | /api.php/static/{id} | Update a specific entry from the static table (that matches the given id)
DELETE      | /api.php/static/{id} | Delete a specific entry from the static table (that matches the given id)
*/

# connection info for mySQL database
$servername = "localhost";
$username = "root";
$password = "jTsB472@^";
$dbname = "web_analytics";

// connect to mySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

$resource = $pathArr[1] ?? null; // "static"
$id = $pathArr[2] ?? null; // "123"

if ($resource === "static") {
    switch ($method) {
        case 'GET':
            if ($id) {
                # checks if any of the data entries have an id matching the requested id
                # uses loose comparison since id from url is a string and id in mock data is an int
                
                # ? means that anything coming later should be treated as a literal
                $sqlStmt = $conn->prepare("SELECT * FROM static WHERE id = ?"); # to present SQL injection
                $sqlStmt->bind_param("i", $id); # "i" means treat as int

                $sqlStmt->execute();
                $dbEntry = $sqlStmt->get_result(); # returns a mysqli_result object corresponding to id
                $dbEntryArr = $dbEntry->fetch_assoc(); # returns single row as associative array

                if ($dbEntryArr) {
                    echo json_encode($dbEntryArr);
                }
                else { # id did not match any found in the db
                    http_response_code(400); // 400 means bad request
                    echo json_encode(["error" => "ID $id not found in entries"]);
                }
            }
            else { # no id provided => return all static data
                $dbEntries = $conn->query("SELECT * FROM static"); # returns as mysqli_result object
                $dbEntriesArr = []; # to create associative array
                while ($row = $dbEntries->fetch_assoc()) {
                    $dbEntriesArr[] = $row;
                }
                echo json_encode($dbEntriesArr);
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
  echo json_encode(["error" => "Resource $resource not found"]);
}
?>