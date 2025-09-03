<?php
namespace Model;

use mysqli;
use mysqli_sql_exception;

class AnalyticsModel {
    private $conn;

    private $apacheLogColMap = [
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

    public function __construct() {
        // Define your DB credentials here or load from config
        $host = "localhost";
        $user = "your_db_user";
        $password = "your_db_password";
        $dbname = "your_db_name";

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->conn = new mysqli($host, $user, $password, $dbname);
            $this->conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            // Handle connection error
            die("Database connection failed: " . $e->getMessage());
        }
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

    public function insert($table, $data) {
        // Prepares a comma seperated list of the cols to be submitted for the new table entry
        $cols = implode(", ", array_keys($data)); 
        // array_keys returns an array of the input's keys
        // implode makes a str of the entries of the array, seperated by the given delimiter (", ")

        // Builds a string of "?, ?, ...", with one "?" for each value, to use in the param binding
        $place = implode(", ", array_fill(0, count($data), "?")); 
        // array_fill builds a new array, where the entries are all "?" and the size is count($data)

        $stmt = $this->conn->prepare("INSERT INTO `$table` ($cols) VALUES ($place)");

        // str_repeat returns a string comprised of count($data) number of "s"s
        $stmt->bind_param(str_repeat("s", count($data)), ...array_values($data));
        // str_repeat returns a string comprised of count($data) number of "s"s
        return $stmt->execute();
    }
    public function update($table, $id, $data) {
        $assign = implode(", ", array_map(fn($k) => "$k = ?", array_keys($data)));
        $sql = "UPDATE `$table` SET $assign WHERE id = ?";
        $stmt = $this->conn->prepare($sql);

        // Types: one 's' per data item (string), plus one for $id
        $types = str_repeat("s", count($data)) . "s";

        $params = array_merge(array_values($data), [$id]); // Combines arrays $data and [$id]
        // Used instead of array_push because array_push returns the number of elements in new array
        // whereas array_merge returns the new array, which is what we want $params to be here

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
