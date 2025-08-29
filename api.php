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

// connection info for mySQL database
$servername = "localhost";
$username = "root";
$password = "jTsB472@^";
$dbname = "web_analytics";

header("Content-Type: application/json");

// connects to mySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// checks connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$request = $_SERVER['REQUEST_URI']; // ex. /api.php/static/123
$method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE

// removes leading /
if ($request[0] === "/") {
    $request = substr($request,1);
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


$id = $pathArr[2] ?? null; // "12gd3dfh"

if ($resource) {
    switch ($method) {
        case 'GET':
            get($conn, $resource, $id);
            break;

        case 'POST':
            setEntry($conn, $resource, $method, 0);
            break;

        case 'PUT':
            if ($id) {   // id is required, since we must know what resource we are updating      
                setEntry($conn, $resource, $method, $id);
                echo json_encode(["message" => "PUT completed for ID $id"]);
            } 
            else {
                http_response_code(400); // 400 means bad request
                echo json_encode(["error" => "ID required for PUT"]);
            }
            break;

        case 'DELETE':
            if ($id) {
                deleteEntry($conn, $resource, $id);
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

function get($conn, $resource, $id) {
    if ($id) {
        // checks if any of the data entries have an id matching the requested id
        // uses loose comparison since id from url is a string and id in mock data is an int
        
        // ? means that anything coming later should be treated as a literal
        $sqlStmt = $conn->prepare("SELECT * FROM $resource WHERE id = ?"); // to prevent SQL injection
        $sqlStmt->bind_param("s", $id); // "i" means treat as int

        $sqlStmt->execute();
        $dbEntry = $sqlStmt->get_result(); // returns a mysqli_result object corresponding to id
        $dbEntryArr = $dbEntry->fetch_assoc(); // returns single row as associative array

        if ($dbEntryArr) {
            echo json_encode($dbEntryArr);
        }
        else { // id did not match any found in the db
            http_response_code(400); // 400 means bad request
            echo json_encode(["error" => "ID $id not found in entries"]);
        }
    }
    else { // no id provided => return all static data
        $dbEntries = $conn->query("SELECT * FROM $resource"); // returns as mysqli_result object
        $dbEntriesArr = []; // to create associative array
        while ($row = $dbEntries->fetch_assoc()) {
            $dbEntriesArr[] = $row;
        }
        echo json_encode($dbEntriesArr);
    }
}

// handles POST and PUT requests
function setEntry($conn, $resource, $method, $id) {
    $inputArr = inputToArr();

    if ($resource === "static") {
        sendStaticStmt($conn, $method, $inputArr, $id);
    }

    else if ($resource === "performance") {
        sendPerfStmt($conn, $method, $inputArr, $id);
    }

    else if ($resource === "activity") {
        // checks if activityLog exists and is an array
        if (isset($inputArr['activityLog']) && is_array($inputArr['activityLog'])) {
            foreach ($inputArr['activityLog'] as $event) {
                // inserts each event separately, passing current single event array
                sendActivityStmt($conn, $method, $event, $id);
            }
        } else {
            // case where no events are sent or structure is unexpected
            http_response_code(400);
            echo json_encode(["error" => "No activity log found or invalid structure"]);
        }
    }
    echo json_encode(["data entered" => $inputArr]);
}


function inputToArr() {
    if (empty($_POST)) {
        // reads raw input from the request body
        $rawInput = file_get_contents('php://input');
        
        // $_SERVER['CONTENT_TYPE'] is haystack, 'application/json' is needle
        if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') === 0) {
            $inputArr = json_decode($rawInput, true); // parse JSON body
        }
        else { // assumes x-www-form-urlencoded
            parse_str($rawInput, $inputArr); // parses it into an associative array
        }      
    } 
    else {
        // use $_POST for x-www-form-urlencoded data submitted via POST
        $inputArr = $_POST;
    }
    error_log(print_r($inputArr, true)); // for debugging purposes
    return $inputArr;
}

function sendStaticStmt($conn, $method, $inputArr, $id) {
    // cleans input, assigning nonexistent values to null
    // I switched to using vals rather than an array for debugging purposes
    
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
    if ($method === 'POST') {
        $entryId = $inputArr['id'] ?? null; // must generate id if not sent
        if(is_null($entryId)) {
            http_response_code(400);
            echo json_encode(["error" => "ID must be sent in the message body for POST"]);
            exit();
        }
        $sql = "INSERT INTO static (
            userAgent, userLang, acceptsCookies, allowsJavaScript, allowsImages,
            allowsCSS, userScreenWidth, userScreenHeight, userWindowWidth, userWindowHeight,
            userNetConnType, id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }
    else if ($method === 'PUT') {
        $entryId = $id;
        $sql = "UPDATE static SET 
            userAgent = ?, 
            userLang = ?, 
            acceptsCookies = ?, 
            allowsJavaScript = ?, 
            allowsImages = ?, 
            allowsCSS = ?, 
            userScreenWidth = ?, 
            userScreenHeight = ?, 
            userWindowWidth = ?, 
            userWindowHeight = ?, 
            userNetConnType = ?
            WHERE id = ?";
    }
    else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid method: " . $method]);
        exit();
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit();
    }

    // Bind parameters with explicit types (s = string, i = integer)
    $stmt->bind_param(
        "ssiiiiiiiiss",
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
        $userNetConnType,
        $entryId
    );

    execStmt($stmt);
    $stmt->close();
}

function sendPerfStmt($conn, $method, $inputArr, $id) {

    // cleans input associative array
    $input = [
        'pageLoadTimingObject' => $inputArr['pageLoadTimingObject'] ?? null,  // could be complex object, store as JSON
        'pageLoadTimeTotal' => isset($inputArr['pageLoadTimeTotal']) ? (float)$inputArr['pageLoadTimeTotal'] : null,
        'pageLoadStart' => isset($inputArr['pageLoadStart']) ? (float)$inputArr['pageLoadStart'] : null,
        'pageLoadEnd' => isset($inputArr['pageLoadEnd']) ? (float)$inputArr['pageLoadEnd'] : null,
    ];

    // JSON-encode complex objects for storage (nullable)
    $pageLoadTimingObjectJson = $input['pageLoadTimingObject'] ? json_encode($input['pageLoadTimingObject']) : null;

    // prepares statement with placeholders
    if ($method === 'POST') {
        $entryId = $inputArr['id'] ?? null; // must generate id if not sent
        if(is_null($entryId)) {
            http_response_code(400);
            echo json_encode(["error" => "ID must be sent in the message body for POST"]);
            exit();
        }
        $sql = "INSERT INTO performance (
            pageLoadTimingObject,
            pageLoadTimeTotal,
            pageLoadStart,
            pageLoadEnd,
            id
        ) VALUES (?, ?, ?, ?, ?)";
    }
    else if ($method === 'PUT') {
        $entryId = $id;
        $sql = "UPDATE static SET 
            pageLoadTimingObject = ?,
            pageLoadTimeTotal = ?,
            pageLoadStart = ?,
            pageLoadEnd = ?
            WHERE id = ?";
    }
    else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid method: " . $method]);
        exit();
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit();
    }

    // binds parameters (s = string, d = double/float), nullable fields use null
    $stmt->bind_param(
        "sddds",
        $pageLoadTimingObjectJson,
        $input['pageLoadTimeTotal'],
        $input['pageLoadStart'],
        $input['pageLoadEnd'],
        $entryId
    );

    execStmt($stmt);
    $stmt->close();
}

function sendActivityStmt($conn, $method, $inputArr, $id) {
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
        'key_val' => $inputArr['key'] ?? null,
        'key_code' => $inputArr['code'] ?? null,
        'event_timestamp' => isset($inputArr['event_timestamp']) ? (int)$inputArr['event_timestamp'] : null,
        'event_time_ms' => isset($inputArr['event_time_ms']) ? (int)$inputArr['event_time_ms'] : null,
        'userState' => $inputArr['userState'] ?? null,
        'screenState' => $inputArr['screenState'] ?? null,
        'idleDuration' => isset($inputArr['idleDuration']) ? (int)$inputArr['idleDuration'] : null,
        'url' => $inputArr['url'] ?? null,
        'title' => $inputArr['title'] ?? null
    ];

    if ($method === 'POST') {
        $entryId = $inputArr['sessionID'] ?? null;
        if(is_null($entryId)) {
            http_response_code(400);
            echo json_encode(["error" => "ID must be sent in the message body for POST"]);
            exit();
        }
        $sql = "INSERT INTO activity (
            event_type, message, filename, lineno, colno, error,
            clientX, clientY, button, scrollX, scrollY,
            key_val, key_code, event_timestamp, event_time_ms,
            userState, screenState, idleDuration, url, title, id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }
    else if ($method === 'PUT') {
        $entryId = $id;
        $sql = "UPDATE static SET 
            event_type = ?, 
            message = ?, 
            filename = ?, 
            lineno = ?, 
            colno = ?, 
            error = ?,
            clientX = ?, 
            clientY = ?, 
            button = ?, 
            scrollX = ?, 
            scrollY = ?,
            key_val = ?, 
            key_code = ?, 
            event_timestamp = ?,
            event_time_ms = ?,
            userState = ?, 
            screenState = ?, 
            idleDuration = ?, 
            url = ?, 
            title = ?
            WHERE id = ?";
    }
    else {
        http_response_code(400);
        echo json_encode(["error" => "Invalid method: " . $method]);
        exit();
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit();
    }

    $stmt->bind_param(
        "sssiisiiiiissisississ",
        $input['event_type'],
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
        $input['event_timestamp'],
        $input['event_time_ms'],
        $input['userState'],
        $input['screenState'],
        $input['idleDuration'],
        $input['url'],
        $input['title'],
        $entryId
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

function deleteEntry($conn, $resource, $id) {
    
    $stmt = $conn->prepare("DELETE FROM $resource WHERE id = ?");

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit();
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        http_response_code(204); // No Content response code
        echo json_encode(["success" => true, "message" => "Delete completed for ID $id"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Execute failed: " . $stmt->error]);
    }
    $stmt->close();
}
?>