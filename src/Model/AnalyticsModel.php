<?php
namespace Model;

use mysqli;
use mysqli_sql_exception;

class AnalyticsModel {
    private $conn;

    // Also used for handling column request in Controller
    public $logsColMap = [
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

    public $staticColMap = [
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

    public $performanceColMap = [
        "pageLoadTimingObject" => "s",  // JSON stored as string in PHP
        "pageLoadStart"        => "d",  // DOUBLE
        "pageLoadEnd"          => "d",  // DOUBLE
        "pageLoadTimeTotal"    => "d",  // DOUBLE
        "id"                   => "s",  // VARCHAR(255)
    ];

    public $activityColMap = [
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
        if ($table === "activity" && isset($data['activityLog'])) { 
            if (!is_array($data['activityLog'])) {
                http_response_code(400);
                echo json_encode(["error" => "Missing or invalid activityLog data"]);
                exit();
            }
            $inner_data = $data['activityLog'];
            foreach($inner_data as $activity) {
                $this->insert("activity", $activity);
            }
            return;
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
        $q = "SELECT filename, AVG(timeToServeMS) AS avgTime FROM apacheLogs GROUP BY filename";
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

    /*
    This function retrieves all user language codes found in the static table 
    along with the counts how many times each language appears, 
    and then it attaches the English name for each language to the result.
    Example: Fetched ["userLang" => "en", "count" => 7] 
    becomes ["userLang" => "en", "count" => 7, "language" => "English"].
    */
    public function getUserLangCounts() {
        // Common lanugage codes and associated names in English
        $userLangNames = [
            "en"    => "English",
            "en-US" => "English (U.S.)",
            "es"    => "Spanish",
            "fr"    => "French",
            "de"    => "German",
            "zh"    => "Chinese",
            "ja"    => "Japanese",
            "ko"    => "Korean",
            "it"    => "Italian",
            "ru"    => "Russian",
            "ar"    => "Arabic",
            "pt"    => "Portuguese",
            "nl"    => "Dutch",
            "tr"    => "Turkish",
            "pl"    => "Polish",
            "sv"    => "Swedish",
            "no"    => "Norwegian",
            "da"    => "Danish",
            "fi"    => "Finnish",
            "cs"    => "Czech",
            "el"    => "Greek",
            "he"    => "Hebrew",
            "hi"    => "Hindi",
            "th"    => "Thai",
            "vi"    => "Vietnamese",
            "id"    => "Indonesian",
            "ms"    => "Malay",
            "ro"    => "Romanian",
            "hu"    => "Hungarian",
            "uk"    => "Ukrainian"
        ];

        $stmt = "SELECT userLang, COUNT(*) AS count " .
                "FROM static " .
                "GROUP BY userLang " .
                "ORDER BY count DESC";

        $result = $this->conn->query($stmt);
        $langResults = $result->fetch_all(MYSQLI_ASSOC);

        // Maps results to language name
        $finalResults = [];
        foreach ($langResults as $row) {
            $langCode = $row['userLang'];
            // Finds human name or keeps language code (i.e. 'fr') as fallback
            $row['language'] = isset($userLangNames[$langCode]) ? $userLangNames[$langCode] : $langCode;
            $finalResults[] = $row;
        }

        return $finalResults;
    }

    public function pageViewsBySpanishSpeakers() {
        // Groups Perl files together, others as usual
        $stmt = "SELECT 
                    CASE 
                        WHEN a.filename LIKE '%.pl' OR a.filename LIKE '%perl%' THEN 'Perl Demo Files' 
                        ELSE a.filename 
                    END AS FileName, 
                    COUNT(*) AS Visits 
                FROM activity a
                INNER JOIN static s ON a.id = s.id
                WHERE s.userLang = 'es'
                GROUP BY 
                    CASE 
                        WHEN a.filename LIKE '%.pl' OR a.filename LIKE '%perl%' THEN 'Perl Demo Files' 
                        ELSE a.filename 
                    END
                ORDER BY Visits DESC";

        $result = $this->conn->query($stmt);
        if (!$result) {
            return ["error" => "Query failed: " . $this->conn->error];
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function userAgentDistributionByType() {
        $stmt = "
            SELECT
                CASE
                    WHEN userAgent LIKE '%Chrome/%' THEN 'Chrome'
                    WHEN userAgent LIKE '%Safari/%' AND userAgent NOT LIKE '%Chrome/%' THEN 'Safari'
                    WHEN userAgent LIKE '%Firefox/%' THEN 'Firefox'
                    ELSE 'Other'
                END AS browser,
                CASE
                    WHEN userAgent LIKE '%Windows%' THEN 'Windows'
                    WHEN userAgent LIKE '%Macintosh%' THEN 'Mac'
                    WHEN userAgent LIKE '%iPhone%' OR userAgent LIKE '%iPad%' THEN 'iOS'
                    WHEN userAgent LIKE '%Linux%' THEN 'Linux'
                    ELSE 'Other'
                END AS platform,
                COUNT(*) AS count
            FROM static
            GROUP BY browser, platform
            ORDER BY browser, platform";

        $result = $this->conn->query($stmt);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function countErrorMessages() {
        $stmt = "
            SELECT message, COUNT(*) AS count
            FROM activity
            WHERE message IS NOT NULL AND message <> ''
            GROUP BY message
            ORDER BY count DESC
        ";
        $result = $this->conn->query($stmt);
        if (!$result) {
            return ["error" => "Query failed: " . $this->conn->error];
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    } 


    // Retrieves requested columns from table
    public function getCols($table, $cols) {
        $allowedCols = [];
        switch ($table) {
            case 'static': 
                $colMap = $this->staticColMap; 
                break;
            case 'performance': 
                $colMap = $this->performanceColMap; 
                break;
            case 'activity': 
                $colMap = $this->activityColMap; 
                break;
            case 'apacheLogs': 
                $colMap = $this->logsColMap; 
                break;
            default:
                http_response_code(400);
                echo json_encode(["error" => "Unsupported table: $table"]);
                exit();
        }
        // Only includes columns present in the map
        foreach ($cols as $col) {
            if (array_key_exists($col, $colMap)) {
                $allowedCols[] = $col;
            }
        }
        if (empty($allowedCols)) {
            http_response_code(400);
            echo json_encode(["error" => "No valid columns provided"]);
            exit();
        }
        $colString = implode(", ", array_map(fn($c) => "`$c`", $allowedCols));
        $q = "SELECT $colString FROM `$table`";
        $result = $this->conn->query($q);
        if (!$result) {
            http_response_code(500);
            echo json_encode(["error" => "Query failed: " . $this->conn->error]);
            exit();
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }


}
