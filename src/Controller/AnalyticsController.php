<?php
namespace Controller;
use Model\AnalyticsModel;
require __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

// Creates a Dotenv instance, pointing to project root directory
$dotenv = Dotenv::createImmutable(__DIR__);

// Loads vars from the .env file into environment (needed when connecting to api.php via server)
$dotenv->load();


class AnalyticsController {
    private $model;

    public function __construct() {
        $servername = $_ENV['DB_HOST'];
        $username = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASS'];
        $dbname = $_ENV['DB_NAME'];
        $port = 25060;
        $cert = "ca-certificate.crt";

        header("Content-Type: application/json");

        // Connects to mySQL database
        $conn = new \mysqli($servername, $username, $password, $dbname, $port, $cert); // Uses built-in mysqli
        // Note that ca-certificate.crt is not on the repo and is kept only on the server itself

        // Checks connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $this->model = new AnalyticsModel($conn);
    }

    public function route($resource, $id, $method) {
        // Reports
        
        if ($resource === "reports") {
            switch ($id) {
                case 'user-langs':
                    $data = $this->model->getUserLangCounts();
                    break;
                case 'spanish-pages':
                    $data = $this->model->pageViewsBySpanishSpeakers();
                    break;
                case 'avg-file-serve':
                    $data = $this->model->avgTimeToServeByFile();
                    break;
                case 'session-by-width':
                    $data = $this->model->sessionCountByWidth();
                    break;
                case 'device-memory':
                    $data = $this->model->deviceMemoryDistribution();
                    break;
                default:
                    http_response_code(404);
                    $this->sendJson(["error" => "Report not found"]);
                    return;
            }
            $this->sendJson($data);
            return;
        }

        // Only accept known tables
        $table = in_array($resource, ["static","activity","performance"]) ? $resource : null;
        if (!$table) {
            http_response_code(400);
            $this->sendJson(["error" => "Invalid resource"]);
            exit();
        }

        switch ($method) {
            case 'GET':
                if (empty($id)) {
                    $result = $this->model->fetchAll($table);
                    $this->sendJson($result);
                } else {
                    $result = $this->model->fetchById($table, $id);
                    if ($result !== null) {
                        $this->sendJson($result);
                    } else {
                        http_response_code(404);
                        $this->sendJson(["error" => "Not found"]);
                    }
                }
                break;
            case 'POST':
                $input = json_decode(file_get_contents("php://input"), true);
                $ok = $this->model->insert($table, $input);
                $this->sendJson(["success" => $ok]);
                break;
            case 'PUT':
                $input = json_decode(file_get_contents("php://input"), true);
                $ok = $this->model->update($table, $id, $input);
                $this->sendJson(["success" => $ok]);
                break;
            case 'DELETE':
                $ok = $this->model->delete($table, $id);
                $this->sendJson(["success" => $ok]);
                break;
            default:
                http_response_code(405);
                $this->sendJson(["error" => "Method not allowed"]);
        }
    }

    private function sendJson($data) {
        header("Content-Type: application/json");
        echo json_encode($data);
    }
}
