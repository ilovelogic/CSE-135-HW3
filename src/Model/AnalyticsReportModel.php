<?php
namespace Model;
use mysqli;

class AnalyticsModel {
    private $conn;
    
    private $apacheLogColTypeMap = [
        "entryNum"            => "i", // INT NOT NULL AUTO_INCREMENT
        "vhost"               => "s", // VARCHAR(255) NULL
        "port"                => "i", // SMALLINT UNSIGNED NULL
        "clientIP"            => "s", // VARCHAR(45) NOT NULL
        "authUser"            => "s", // VARCHAR(255) NULL
        "datetimeReqReceived" => "s", // DATETIME NOT NULL
        "requestLine"         => "s", // VARCHAR(2048) NULL
        "httpStatus"          => "i", // SMALLINT UNSIGNED NULL
        "bytesSent"           => "i", // INT UNSIGNED NULL
        "referer"             => "s", // VARCHAR(2083) NULL
        "userAgent"           => "s", // VARCHAR(512) NULL
        "timeToServeMS"       => "i", // INT UNSIGNED NULL
        "filename"            => "s", // VARCHAR(1024) NULL
        "connStatus"          => "s", // CHAR(1) NULL
        "cookie"              => "s", // VARCHAR(4096) NULL
    ];

    private $activityColTypeMap = [
        "id"            => "s", // VARCHAR(255) NOT NULL
        "eventType"     => "s", // VARCHAR(20) NULL
        "eventTimestamp"=> "s", // TIMESTAMP NULL (treated as string)
        "message"       => "s", // TEXT NULL
        "filename"      => "s", // VARCHAR(255) NULL
        "lineno"        => "i", // INT NULL
        "colno"         => "i", // INT NULL
        "error"         => "s", // TEXT NULL
        "clientX"       => "i", // INT NULL
        "clientY"       => "i", // INT NULL
        "button"        => "i", // TINYINT NULL
        "scrollX"       => "i", // INT NULL
        "scrollY"       => "i", // INT NULL
        "keyVal"        => "s", // VARCHAR(50) NULL
        "keyCode"       => "s", // VARCHAR(50) NULL
        "eventTimeMs"   => "s", // BIGINT NULL (could be "s" or "d", look into this more)
        "userState"     => "s", // VARCHAR(10) NULL
        "screenState"   => "s", // VARCHAR(10) NULL
        "idleDuration"  => "s", // BIGINT NULL (same as eventTimeMs)
        "url"           => "s", // TEXT NULL
        "title"         => "s", // TEXT NULL
        "eventCount"    => "i", // INT UNSIGNED NOT NULL (primary key)
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
