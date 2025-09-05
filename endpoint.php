<?php
require_once __DIR__ . '/src/Controller/AnalyticsController.php';

// Ensures we can use this API in reporting.annekelley.site
header("Access-Control-Allow-Origin: https://reporting.annekelley.site");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$scriptName = basename($_SERVER['SCRIPT_NAME']); // currently 'endpoint.php'
$path = preg_replace("#^$scriptName#", '', ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
$path = ltrim($path, '/');  // remove any leading slash remaining
$parts = explode('/', $path);

$resource = $parts[0] ?? null;
$id = $parts[1] ?? null;


$method = $_SERVER['REQUEST_METHOD'];

// Instantiates and calls
$controller = new \Controller\AnalyticsController();
$controller->route($resource, $id, $method);
?>