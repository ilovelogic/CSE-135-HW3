<?php
namespace Controller;
use Model\AnalyticsModel;

class AnalyticsController {
    private $model;

    public function __construct() {
        $this->model = new AnalyticsModel();
    }

    public function route($resource, $id, $method) {
        // Reports
        if ($resource === "reports") {
            switch ($id) {
                case 'avg-file-serve':
                    $data = $this->model->avgTimeToServeByFile();
                    $this->sendJson($data);
                    break;
                case 'session-by-width':
                    $data = $this->model->sessionCountByWidth();
                    $this->sendJson($data);
                    break;
                case 'device-memory':
                    $data = $this->model->deviceMemoryDistribution();
                    $this->sendJson($data);
                    break;
                default:
                    http_response_code(404);
                    $this->sendJson(["error" => "Report not found"]);
            }
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
