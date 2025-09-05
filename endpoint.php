<?php
require_once __DIR__ . '/src/Controller/AnalyticsController.php';

// Gets and normalizes the request path (ex. "/reports/spanish-pages")
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Removes leading slash if present
$path = ltrim($path, '/');

// Splits by "/" into parts
$parts = explode('/', $path);

// Assigns resource and id (if present)
$resource = $parts[0] ?? null; // "reports", "static", etc.
$id = $parts[1] ?? null;       // specific report or record id

$method = $_SERVER['REQUEST_METHOD'];

// Instantiates and calls
$controller = new \Controller\AnalyticsController();
$controller->route($resource, $id, $method);
?>