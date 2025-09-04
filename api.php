<?php
/*
Implements the following routing where:
 - id is the unique string identifying a session
 - resource is the table name, one of static, performance, and activity


HTTP Method | Example                               | Route Description
GET         | /api.php/{resource}                   | Retrieve every entry logged in the specified resource table
GET         | /api.php/{resource}}/{id}             | Retrieve a specific entry logged in the specified resource table matching the id
GET         | /api.php/{resource}/{col_1&...&col_n} | Retrieve the given columns of all entries of the specified resource table
POST        | /api.php/{resource}                   | Add a new entry to the specified resource table
PUT         | /api.php/{resource}}/{id}             | Update a specific entry from the specified resource table matching the id
DELETE      | /api.php/{resource}/{id}              | Delete a specific entry from the specified resource table matching the id
*/

// Ensures we can use this API in reporting.annekelley.site
header("Access-Control-Allow-Origin: https://reporting.annekelley.site");

// Loading Composer autoloader and using Dotenv\Dotenv in order to get login info from .env file
require __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;


// Creates a Dotenv instance, pointing to project root directory
$dotenv = Dotenv::createImmutable(__DIR__);


// Loads the variables from the .env file into environment
$dotenv->load();


// Environment variables are accessible with getenv() or $_ENV
$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];
$port = 25060;
$cert = "ca-certificate.crt";



// Responds with json encoding of requested data
header("Content-Type: application/json");

// Connects to mySQL database
$conn = new mysqli($servername, $username, $password, $dbname, $port, $cert);
// Note that ca-certificate.crt is not on the repo and is kept only on the server itself


// Checks connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$request = $_SERVER['REQUEST_URI']; // ex. /api.php/static/123
$method = $_SERVER['REQUEST_METHOD']; // GET, POST, PUT, DELETE


// Removes leading /
if ($request[0] === "/") {
    $request = substr($request,1);
}


$pathArr = explode('/', $request); // Breaks up into ["api.php", "resource", "cols/id"]


$resource = $pathArr[1] ?? null; // e.g. "static"


// Checks if given resource is one of the defined options
if ($resource !== "static" && $resource !== "activity" && $resource !== "performance") {
    http_response_code(400); // Bad Request
    echo json_encode(["error" => "Resource must be given as static, activity, or performance"]);
    die();
}


$idOrCols = $pathArr[2] ?? null; // ex. "A12GD3dF343" or "id&keyCode&screenState"


if (!is_null($idOrCols)) { // Possible that columns were passed to be requested
    // Calls colReq, which checks if request is for columns, and if so, handles it and returns a 1
    // Else, returns a 0
    if(colReq($conn, $resource,$idOrCols)) {
        die(); // Everything we needed to do, in this case, was handled by colReq()
    }
}


// Not cols => must be id or nothing
$id = $idOrCols;


switch ($method) {
    case 'GET':
        get($conn, $resource, $id); // $id would be empty or null if requesting all entries
        // which is handled in get()
        break;


    case 'POST':
        $inputArr = inputToArr();
        $id = $inputArr['id'] ?? null; // ID required in payload for POST


        if(is_null($id) && $resource !== "activity") { // For activity, the id is stored differently
            // in the payload than it is in static and performance
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "ID must be sent in the message body for POST"]);
            exit();
        }
        setEntry($conn, $resource, $method, $inputArr, $id);
        break;


    case 'PUT':
        if ($id) {   // ID is required in query str for PUT
            setEntry($conn, $resource, $method, $inputArr, $id);
            echo json_encode(["message" => "PUT completed for ID $id"]);
        }
        else {
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "ID required for PUT"]);
        }
        break;


    case 'DELETE':
        if ($id) { // ID required in path for DELETE
            deleteEntry($conn, $resource, $id);
        }
        else {
            http_response_code(400); // Bad Request
            echo json_encode(["error" => "ID required for DELETE"]);
        }
        break;


    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(["error" => "Method $method not supported"]);
}


// Checks if the request includes column names in the last part of the path
// If so, fetches and sends the requested columns
// Returns a 1 if columns were requested; else, returns a 0
function colReq($conn, $resource, $idOrCols) {
    // Possible vals that might be passed in query string as column names
    $staticArr = ["id", "userAgent", "userLang", "acceptsCookies", "allowsJavaScript", "allowsImages",
        "allowsCSS", "userScreenWidth", "userScreenHeight", "userWindowWidth", "userWindowHeight",
        "userNetConnType"];


    $perfArr = ["id", "pageLoadTimingObject", "pageLoadTimeTotal", "pageLoadStart",
        "pageLoadEnd"];


    $activityArr = ["id", "eventType", "message", "filename", "lineno", "colno", "error",
        "clientX", "clientY", "button", "scrollX", "scrollY",
        "keyVal", "keyCode", "eventTimestamp", "eventTimeMs",
        "userState", "screenState", "idleDuration", "url", "title", "eventCount"];
   
    $reqCols = []; // to store all request columns of the table
    switch ($resource) {
        case 'static':
            foreach($staticArr as $colName) {
                // Calling strpos works since no column name is a substring of another column name
                // Using this approach is forgiving, as any delimiter or even more delimiter
                // between the column names would work, so long as the column names are present
                if (strpos($idOrCols,$colName) !== false) {
                    array_push($reqCols, $colName); // appends $colName to $reqCols
                }
            }
            break;
        case 'performance':
            foreach($perfArr as $colName) {
                if (strpos($idOrCols,$colName) !== false) {
                    array_push($reqCols, $colName); // appends $colName to $reqCols
                }
            }
            break;
        case 'activity':
            foreach($activityArr as $colName) {
                if (strpos($idOrCols, $colName) !== false) {
                    array_push($reqCols, $colName); // appends $colName to $reqCols
                }
            }
            break;
    }
    if (count($reqCols) > 0) { // If at least one column name identified
        getCols($conn, $resource, $reqCols); // Handles entire process, retrieves and returns cols
        return 1; // Was a column request
    }
    else {
        return 0; // Not column request
    }
}


// Given column names in $reqCols, prepares and executes a SQL query for the columns from $resource
function getCols($conn, $resource, $reqCols) {
    $sqlStmt = "SELECT ";


    $lastIndex = count($reqCols) - 1;
    for($i = 0; $i < $lastIndex; $i++) {
        $sqlStmt .= "$reqCols[$i], ";
    }
    $sqlStmt .= "$reqCols[$lastIndex] FROM $resource";


    $dbEntries = $conn->query($sqlStmt); // Returns a mysqli_result object with all rows of $resource
    // and cols specified by $reqCols


    returnDbEntries($dbEntries, null);
}


// Handles GET request for a specific entry of table $resource (if $id is provided)
// or for all entries (when $id is not sent)
function get($conn, $resource, $id) {
    if ($id) {
        // ? => Place for something that will be treated as a literal when binded to it
        $sqlStmt = $conn->prepare("SELECT * FROM $resource WHERE id = ?"); // To prevent SQL injection
        $sqlStmt->bind_param("s", $id); // "s" means treat as string


        $sqlStmt->execute();
        $dbEntries = $sqlStmt->get_result(); // Returns a mysqli_result object corresponding to id
        returnDbEntries($dbEntries, $id);
    }
    else { // No id provided => Return all static data
        $dbEntries = $conn->query("SELECT * FROM $resource"); // returns as mysqli_result object
        returnDbEntries($dbEntries, null);
    }
}


// Takes mysqli_result object and returns the corresponding associative array
// $id should only be sent if request was a GET for a specific id,
// and is only used in this function to send a more complete error message
function returnDbEntries($dbEntries, $id) {
    $dbEntriesArr = []; // To create associative array


    while ($row = $dbEntries->fetch_assoc()) {
        $dbEntriesArr[] = $row; // Could have many rows even for a specific ID
        // since one ID can have many events stored in activity table
    }


    if ($dbEntriesArr) {
        echo json_encode($dbEntriesArr);
    }
    else if ($id) { // ID was given but did not match any found in the DB
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "ID $id not found in entries"]);
    }
    else {
        http_response_code(500);
        echo json_encode(["error" => "Serverside error when trying to get all entries"]);
    }
}


// Handles all POST and PUT requests
function setEntry($conn, $resource, $method, $inputArr, $id) {
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
                $id = $event['id'] ?? null;
                if(is_null($id)) {
                    http_response_code(400); // Bad Request
                    echo json_stringify($event);
                    echo json_encode(["error" => "ID must be sent with the event $event in payload"]);
                    exit();
                }
                sendActivityStmt($conn, $method, $event, $id);
            }
        } else {
            // Case where no events are sent or structure is unexpected
            http_response_code(400);
            echo json_encode(["error" => "No activity log found or invalid structure"]);
        }
    }


    echo json_encode(["data entered" => $inputArr]);
    $conn->close();
}


// Parses raw input into an associative array
function inputToArr() {
    if (empty($_POST)) {
        // Reads raw input from the request body
        $rawInput = file_get_contents('php://input');
       
        // $_SERVER['CONTENT_TYPE'] is haystack, 'application/json' is needle
        if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') === 0) {
            $inputArr = json_decode($rawInput, true); // parse JSON body
        }
        else { // Assumes x-www-form-urlencoded
            parse_str($rawInput, $inputArr); // Parses it into an associative array
        }      
    }
    else {
        // Use $_POST for x-www-form-urlencoded data submitted via POST
        $inputArr = $_POST;
    }
    return $inputArr;
}


// Prepares and executes SQL statements related to making a $method (POST/PUT) request
// that submits data $inputArr to be identified by $id in the static table
function sendStaticStmt($conn, $method, $inputArr, $id) {


    // Cleans input, assigning nonexistent values to null
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


    // Prepares insert statement with placeholders (nullable fields allowed in DB schema)
    if ($method === 'POST') {
        $sql = "INSERT INTO static (
            userAgent, userLang, acceptsCookies, allowsJavaScript, allowsImages,
            allowsCSS, userScreenWidth, userScreenHeight, userWindowWidth, userWindowHeight,
            userNetConnType, id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    }
    else if ($method === 'PUT') {
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


    // Binds parameters with explicit types (s = string, i = integer)
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
        $id
    );


    execStmt($stmt);
    $stmt->close();
}


// Prepares and executes SQL statements related to making a $method (POST/PUT) request
// that submits data $inputArr to be identified by $id in the performance table
function sendPerfStmt($conn, $method, $inputArr, $id) {


    // Cleans input associative array
    $input = [
        'pageLoadTimingObject' => $inputArr['pageLoadTimingObject'] ?? null,  // could be complex object, store as JSON
        'pageLoadTimeTotal' => isset($inputArr['pageLoadTimeTotal']) ? (float)$inputArr['pageLoadTimeTotal'] : null,
        'pageLoadStart' => isset($inputArr['pageLoadStart']) ? (float)$inputArr['pageLoadStart'] : null,
        'pageLoadEnd' => isset($inputArr['pageLoadEnd']) ? (float)$inputArr['pageLoadEnd'] : null,
    ];


    // JSON-encode complex objects for storage (nullable)
    $pageLoadTimingObjectJson = $input['pageLoadTimingObject'] ? json_encode($input['pageLoadTimingObject']) : null;


    // Prepares statement with placeholders
    if ($method === 'POST') {
        $sql = "INSERT INTO performance (
            pageLoadTimingObject,
            pageLoadTimeTotal,
            pageLoadStart,
            pageLoadEnd,
            id
        ) VALUES (?, ?, ?, ?, ?)";
    }
    else if ($method === 'PUT') {
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


    // Binds parameters (s = string, d = double), nullable fields use null
    $stmt->bind_param(
        "sddds",
        $pageLoadTimingObjectJson,
        $input['pageLoadTimeTotal'],
        $input['pageLoadStart'],
        $input['pageLoadEnd'],
        $id
    );


    execStmt($stmt);
    $stmt->close();
}


// Prepares and executes SQL statements related to making a $method (POST/PUT) request
// that submits data $inputArr to be identified by $id in the activity table
function sendActivityStmt($conn, $method, $inputArr, $id) {
    $input = [
        'eventType' => $inputArr['eventType'] ?? null,
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
        'keyVal' => $inputArr['key'] ?? null,
        'keyCode' => $inputArr['code'] ?? null,
        'eventTimestamp' => isset($inputArr['eventTimestamp']) ? (int)$inputArr['eventTimestamp'] : null,
        'eventTimeMs' => isset($inputArr['eventTimeMs']) ? (int)$inputArr['eventTimeMs'] : null,
        'userState' => $inputArr['userState'] ?? null,
        'screenState' => $inputArr['screenState'] ?? null,
        'idleDuration' => isset($inputArr['idleDuration']) ? (int)$inputArr['idleDuration'] : null,
        'url' => $inputArr['url'] ?? null,
        'title' => $inputArr['title'] ?? null,
        'eventCount' => isset($inputArr['eventCount']) ? (int)$inputArr['eventCount'] : null
    ];


    if ($method === 'POST') {
        $sql = "INSERT INTO activity (
            eventType, message, filename, lineno, colno, error,
            clientX, clientY, button, scrollX, scrollY,
            keyVal, keyCode, eventTimestamp, eventTimeMs,
            userState, screenState, idleDuration, url, title, eventCount, id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, (FROM_UNIXTIME(? / 1000)), ?, ?, ?, ?, ?, ?, ?, ?)";
    } // (FROM_UNIXTIME(? / 1000)) converts from ms to timestamp formatting of 'YYYY-MM-DD HH:MM:SS'
    else if ($method === 'PUT') {
        $sql = "UPDATE static SET
            eventType = ?,
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
            keyVal = ?,
            keyCode = ?,
            eventTimestamp = (FROM_UNIXTIME(? / 1000)),
            eventTimeMs = ?,
            userState = ?,
            screenState = ?,
            idleDuration = ?,
            url = ?,
            title = ?,
            eventCount = ?
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
        "sssiisiiiiissisissisis",
        $input['eventType'],
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
        $input['eventTimestamp'],
        $input['eventTimeMs'],
        $input['userState'],
        $input['screenState'],
        $input['idleDuration'],
        $input['url'],
        $input['title'],
        $input['eventCount'],
        $id
    );


    execStmt($stmt);
    $stmt->close();
}


// Executes POST and PUT related SQL queries
function execStmt($stmt) {
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(["success" => true, "Insert ID" => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Execute failed: " . $stmt->error]);
    }
}


// Deletes entry identified by $id in table given by $resource
function deleteEntry($conn, $resource, $id) {
   
    $stmt = $conn->prepare("DELETE FROM $resource WHERE id = ?");


    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Prepare failed: " . $conn->error]);
        exit();
    }


    $stmt->bind_param("s", $id);


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
