<?php

/*
Implements the following routing:

HTTP Method | Example                   | Route Description
GET         | /api.php/static           | Retrieve every entry logged in the static table
GET         | /api.php/static/{id}      | Retrieve a specific entry logged in the static table matching the id
GET         | /api.php/performance      | Retrieve every entry logged in the performance table
GET         | /api.php/performance/{id} | Retrieve a specific entry logged in the performance table matching the id
GET         | /api.php/activity         | Retrieve every entry logged in the activity table
GET         | /api.php/activity/{id}    | Retrieve a specific entry logged in the activity table matching the id
POST        | /api.php/static           | Add a new entry to the static table
POST        | /api.php/performance      | Add a new entry to the performance table
POST        | /api.php/activity         | Add a new entry to the activity table
PUT         | /api.php/static/{id}      | Update a specific entry from the static table matching the id
PUT         | /api.php/performance/{id} | Update a specific entry from the performance table matching the id
PUT         | /api.php/activity/{id}    | Update a specific entry from the activity table matching the id
DELETE      | /api.php/static/{id}      | Delete a specific entry from the static table matching the id
DELETE      | /api.php/performance/{id} | Delete a specific entry from the performance table matching the id
DELETE      | /api.php/activity/{id}    | Delete a specific entry from the activity table matching the id
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


$request = $_SERVER['REQUEST_URI']; // ex. /api.php/static/123
$method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE


if ($request[0] === "/") {
    $request = substr($request,1); // remove leading /
}

$pathArr = explode('/', $request); // breaks up into ["api.php", "static", "123"]

$tmpResource = $pathArr[1] ?? null; // "static"
if ($tmpResource === "static") {
    $resource = "static";
}
else if ($tmpResource === "activity") {
    $resource = "activity";
}
else if ($tmpResource === "performance") {
    $resource = "performance";
}


$id = $pathArr[2] ?? null; // "123"

if ($resource) {
    switch ($method) {
        case 'GET':
            if ($id) {
                # checks if any of the data entries have an id matching the requested id
                # uses loose comparison since id from url is a string and id in mock data is an int
                
                # ? means that anything coming later should be treated as a literal
                $sqlStmt = $conn->prepare("SELECT * FROM $resource WHERE id = ?"); # to present SQL injection
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
                $dbEntries = $conn->query("SELECT * FROM $resource"); # returns as mysqli_result object
                $dbEntriesArr = []; # to create associative array
                while ($row = $dbEntries->fetch_assoc()) {
                    $dbEntriesArr[] = $row;
                }
                echo json_encode($dbEntriesArr);
            }
            break;

        case 'POST':
            // reads in payload to associative array
            $inputArr = inputToArr();

            if ($resource === "static") {
                sendStaticStmt($conn, $inputArr);
            }

            else if ($resource === "performance") {
                sendPerfStmt($conn, $inputArr);
            }

            else if ($resource === "activity") {
                // checks if activityLog exists and is an array
                if (isset($inputArr['activityLog']) && is_array($inputArr['activityLog'])) {
                    foreach ($inputArr['activityLog'] as $event) {
                        // inserts each event separately, passing current single event array
                        sendActivityStmt($conn, $event);
                    }
                } else {
                    // case where no events are sent or structure is unexpected
                    http_response_code(400);
                    echo json_encode(["error" => "No activity log found or invalid structure"]);
                }
            }
            echo json_encode(["message" => "POST recieved successfully", "data" => $inputArr]);
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
  echo json_encode(["error" => "Resource $tmpResource not found"]);
}



function inputToArr() {
    if (empty($_POST)) {
        // parse JSON body
        $inputArr = json_decode(file_get_contents('php://input'), true);
    } else {
        // use $_POST for x-www-form-urlencoded data
        $inputArr = $_POST;
    }
    return $inputArr;
}

function sendStaticStmt($conn, $inputArr) {

    // cleans input, assigning nonexistent values to null
    $id = $inputArr['id'] ?? time();
    $userAgent = $inputArr['userAgent'] ?? null;
    $userLang = $inputArr['userLang'] ?? null;
    $acceptsCookies = isset($inputArr['acceptsCookies']) ? ($inputArr['acceptsCookies'] ? 1 : 0) : null;
    $allowsJavaScript = isset($inputArr['allowsJavaScript']) ? ($inputArr['allowsJavaScript'] ? 1 : 0) : null;
    $allowsImages = isset($inputArr['allowsImages']) ? ($inputArr['allowsImages'] ? 1 : 0) : null;
    $allowsCSS = isset($inputArr['allowsCSS']) ? ($inputArr['allowsCSS'] ? 1 : 0) : null;
    $userScreenWidth = isset($inputArr['userScreenWidth']) ? (int)$inputArr['userScreenWidth'] : null;
    $userScreenHeight = isset($inputArr['userScreenHeight']) ? (int)$inputArr['userScreenHeight'] : null;
    $userWindowWidth = isset($inputArr['userWindowWidth']) ? (int)$inputArr['userWindowWidth'] : null;
    $userWindowHeight = isset($inputArr['userWindowHeight']) ? (int)$inputArr['userWindowHeight'] : null;
    $userNetConnType = $inputArr['userNetConnType'] ?? null;

    // prepares insert statement with placeholders (nullable fields allowed in DB schema)
    $sql = "INSERT INTO static (
        id, userAgent, userLang, acceptsCookies, allowsJavaScript, allowsImages,
        allowsCSS, userScreenWidth, userScreenHeight, userWindowWidth, userWindowHeight,
        userNetConnType
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit();
    }

    // Bind parameters with explicit types (s = string, i = integer)
    $stmt->bind_param(
        "issiiiiiiiis",
        $id,
        $userAgent,
        $userLang,
        $acceptsCookies,
        $allowsJavaScript,
        $allowsImages,
        $allowsCSS,
        $userScreenWidth,
        $userScreenHeight,
        $userWindowWidth,
        $userWindowHeight,
        $userNetConnType
    );

    execStmt($stmt);
    $stmt->close();
}

function sendPerfStmt($conn, $inputArr) {

    // cleans input associative array
    $input = [
        'pageLoadTimingObject' => $inputArr['pageLoadTimingObject'] ?? null,  // could be complex object, store as JSON
        'pageLoadTimeTotal' => isset($inputArr['pageLoadTimeTotal']) ? (float)$inputArr['pageLoadTimeTotal'] : null,
        'pageLoadStart' => isset($inputArr['pageLoadStart']) ? (float)$inputArr['pageLoadStart'] : null,
        'pageLoadEnd' => isset($inputArr['pageLoadEnd']) ? (float)$inputArr['pageLoadEnd'] : null,
    ];

    // JSON-encode complex objects for storage (nullable)
    $pageLoadTimingObjectJson = $input['pageLoadTimingObject'] ? json_encode($input['pageLoadTimingObject']) : null;

    // prepares insert statement with placeholders
    $sql = "INSERT INTO performance (
        pageLoadTimingObject,
        pageLoadTimeTotal,
        pageLoadStart,
        pageLoadEnd
    ) VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit();
    }

    // binds parameters (s = string, d = double/float), nullable fields use null
    $stmt->bind_param(
        "sddd",
        $pageLoadTimingObjectJson,
        $input['pageLoadTimeTotal'],
        $input['pageLoadStart'],
        $input['pageLoadEnd']
    );

    execStmt($stmt);
    $stmt->close();
}

function sendActivityStmt($conn, $inputArr) {
    $input = [
        'type' => $inputArr['type'] ?? null,
        'message' => $inputArr['message'] ?? null,
        'filename' => $inputArr['filename'] ?? null,
        'lineno' => isset($inputArr['lineno']) ? (int)$inputArr['lineno'] : null,
        'colno' => isset($inputArr['colno']) ? (int)$inputArr['colno'] : null,
        'error' => isset($inputArr['error']) ? json_encode($inputArr['error']) : null,
        'clientX' => isset($inputArr['clientX']) ? (int)$inputArr['clientX'] : null,
        'clientY' => isset($inputArr['clientY']) ? (int)$inputArr['clientY'] : null,
        'button' => isset($inputArr['button']) ? (int)$inputArr['button'] : null,
        'scrollX' => isset($inputArr['scrollX']) ? (int)$inputArr['scrollX'] : null,
        'scrollY' => isset($inputArr['scrollY']) ? (int)$inputArr['scrollY'] : null,
        'key' => $inputArr['key'] ?? null,
        'code' => $inputArr['code'] ?? null,
        'timestamp' => isset($inputArr['timestamp']) ? (int)$inputArr['timestamp'] : null,
        'sessionId' => $inputArr['sessionId'] ?? null // for tying to specific user session if available
    ];

    $sql = "INSERT INTO activity (
        type, message, filename, lineno, colno, error,
        clientX, clientY, button, scrollX, scrollY,
        `key`, `code`, timestamp, sessionId
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit();
    }

    $stmt->bind_param(
        "sssiisiiiissiis",
        $input['type'],
        $input['message'],
        $input['filename'],
        $input['lineno'],
        $input['colno'],
        $input['error'],
        $input['clientX'],
        $input['clientY'],
        $input['button'],
        $input['scrollX'],
        $input['scrollY'],
        $input['key'],
        $input['code'],
        $input['timestamp'],
        $input['sessionId']
    );

    execStmt($stmt);
    $stmt->close();
}

function execStmt($stmt) {
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["success" => true, "insertId" => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Execute failed: " . $stmt->error]);
    }
}
?>