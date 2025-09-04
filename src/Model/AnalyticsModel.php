<?php
namespace Model;

use mysqli;
use mysqli_sql_exception;

class AnalyticsModel {
    private $conn;

    private $logsColMap = [
        "entryNum"            => "i", // INT NOT NULL AUTO_INCREMENT
        "vhost"               => "s", // VARCHAR(255) NULL
        "port"                => "i", // SMALLINT UNSIGNED NULL
        "clientIP"            => "s", // VARCHAR(45) NOT NULL
        "authUser"            => "s", // VARCHAR(255) NULL
        "datetimeReqReceived" => "s", // DATETIME
        "requestLine"         => "s", // VARCHAR(2048)
        "httpStatus"          => "i", // SMALLINT UNSIGNED
        "bytesSent"           => "i", // INT UNSIGNED
        "referer"             => "s", // VARCHAR(2083)
        "userAgent"           => "s", // VARCHAR(512)
        "timeToServeMS"       => "i", // INT UNSIGNED
        "filename"            => "s", // VARCHAR(1024)
        "connStatus"          => "s", // CHAR(1)
        "cookie"              => "s", // VARCHAR(4096)
    ];

    private $staticColMap = [
        "id"                => "s",  // VARCHAR(255)
        "userAgent"         => "s",  // VARCHAR(255)
        "userLang"          => "s",  // VARCHAR(10)
        "acceptsCookies"    => "i",  // TINYINT(1)
        "allowsJavaScript"  => "i",  // TINYINT(1)
        "allowsImages"      => "i",  // TINYINT(1)
        "allowsCSS"         => "i",  // TINYINT(1)
        "userScreenWidth"   => "i",  // INT UNSIGNED
        "userScreenHeight"  => "i",  // INT UNSIGNED
        "userWindowWidth"   => "i",  // INT UNSIGNED
        "userWindowHeight"  => "i",  // INT UNSIGNED
        "userNetConnType"   => "s",  // VARCHAR(20)
    ];

    private $performanceColMap = [
        "pageLoadTimingObject" => "s",  // JSON stored as string in PHP
        "pageLoadStart"        => "d",  // DOUBLE
        "pageLoadEnd"          => "d",  // DOUBLE
        "pageLoadTimeTotal"    => "d",  // DOUBLE
        "id"                   => "s",  // VARCHAR(255)
    ];

    private $activityColMap = [
        "id"            => "s", // VARCHAR(255)
        "eventType"     => "s", // VARCHAR(20)
        "eventTimestamp"=> "s", // TIMESTAMP
        "message"       => "s", // TEXT
        "filename"      => "s", // VARCHAR(255)
        "lineno"        => "i", // INT
        "colno"         => "i", // INT
        "error"         => "s", // TEXT
        "clientX"       => "i", // INT
        "clientY"       => "i", // INT
        "button"        => "i", // TINYINT
        "scrollX"       => "i", // INT
        "scrollY"       => "i", // INT
        "keyVal"        => "s", // VARCHAR(50)
        "keyCode"       => "s", // VARCHAR(50)
        "eventTimeMs"   => "s", // BIGINT (use 's' or 'd')
        "userState"     => "s", // VARCHAR(10)
        "screenState"   => "s", // VARCHAR(10)
        "idleDuration"  => "s", // BIGINT
        "url"           => "s", // TEXT
        "title"         => "s", // TEXT
        "eventCount"    => "i", // INT UNSIGNED PRIMARY KEY
    ];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Generic fetch helpers
    public function fetchAll($table) {
        $result = $this->conn->query("SELECT * FROM `$table`");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function fetchById($table, $id) {
        $stmt = $this->conn->prepare("SELECT * FROM `$table` WHERE id = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getTypes($table, $data) {
        $colNames = array_keys($data); // array_keys returns an array of the input's keys

        // Determines which column to type array to use based on the requested table
        switch ($table) {
            case "static":
                $colMap = $this->staticColMap; // shallow copy
                break;
            case "performance":
                $colMap = $this->performanceColMap;
                break;
            case "activity":
                $colMap =  $this->activityColMap;
                break;
            case "apacheLogs":
                $colMap = $this->logsColMap;
                break;
        }

        // Uses the column to type map to create the appropriate string of types (e.g. "ssiiss")
        $types = "";
        foreach($colNames as $col) {
            $type = $colMap[$col] ?? null;
            if (is_null($type)) {
                http_response_code(400);
                echo json_encode(["error" => "$col is not a column of the table $table"]);
                die();
            }
            $types .= $type;
        }
        return $types;
    }

    public function insert($table, $data) {

        // For the activity data submission, $data's entries are arrays with the cols and values
        if ($table === "activity") { 
            foreach($data as $activity) {
                $this->insert("activity", $activity);
            }
            exit(); // process complete
        }

        // Prepares a comma seperated list of the cols to be submitted for the new table entry
        $cols = implode(", ", array_keys($data)); 
        // implode makes a str of the entries of the array, seperated by the given delimiter (", ")

        // Builds a string of "?, ?, ..." to use in the param binding
        $place = implode(", ", array_fill(0, count($data), "?")); 
        // array_fill builds a new array, where the entries are all "?" and the size is count($data)

        $types = $this->getTypes($table, $data); // types string for param binding (e.g. "ssidsss")

        $stmt = $this->conn->prepare("INSERT INTO `$table` ($cols) VALUES ($place)");
        $stmt->bind_param($types, ...array_values($data));
        return $stmt->execute();
    }

    public function update($table, $id, $data) {
        $assign = implode(", ", array_map(fn($k) => "$k = ?", array_keys($data)));
        $sql = "UPDATE `$table` SET $assign WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        $types = $this->getTypes($table, $data);

        $params = array_merge(array_values($data), [$id]); // Combines arrays $data and [$id]
        // Used instead of array_push because array_push returns the number of elements in new array
        // whereas array_merge returns the new array, which is what we want $params to be here

        // Since we need them to be by reference and not value
        $bindNames = [];
        $bindNames[] = &$types;
        foreach ($params as $k => $v) {
            $bindNames[] = &$params[$k];
        }
        
        // Allows for a dynamic number of elements, whereas bind_param works only for a fixed amount
        call_user_func_array([$stmt, "bind_param"], $bindNames); // [$stmt, "bind_param"] is a callable array,
        // so we call bind_param on $stmt, with argument $bindNames

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function delete($table, $id) {
        $stmt = $this->conn->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->bind_param("s", $id);
        return $stmt->execute();
    }

    // Analytics/reporting queries for ZingChart:
    public function avgTimeToServeByFile() {
        $q = "SELECT filename, AVG(timeToServeMS) AS avgTime FROM logs GROUP BY filename";
        $result = $this->conn->query($q);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function sessionCountByWidth() {
        $q = "SELECT userScreenWidth AS width, COUNT(DISTINCT id) AS sessions FROM static GROUP BY width";
        $result = $this->conn->query($q);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function deviceMemoryDistribution() {
        $q = "SELECT deviceMemory, COUNT(*) AS count FROM static GROUP BY deviceMemory";
        $result = $this->conn->query($q);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
