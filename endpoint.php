<?php
/*
HTTP Method | Example Route (Path)                    | Route Description
--------------------------------------------------------------------------------------------
GET         | /api.php/reports/user-langs             | Retrieve user language counts report
GET         | /api.php/reports/spanish-pages          | Retrieve page views by Spanish speakers
GET         | /api.php/reports/avg-file-serve         | Retrieve average server response time by file
GET         | /api.php/reports/session-by-width       | Retrieve session counts by screen width
GET         | /api.php/reports/device-memory          | Retrieve device memory distribution

GET         | /api.php/{resource}                     | Retrieve all entries in resource table (static, activity, performance, apacheLogs)
GET         | /api.php/{resource}/{id}                | Retrieve specific entry by ID from resource table
GET         | /api.php/{resource}/{col_1&...&col_n}   | Retrieve specified columns (any delim works) for all entries in resource table

POST        | /api.php/{resource}                     | Add new entry to specified resource table
PUT         | /api.php/{resource}/{id}                | Update entry by ID in specified resource table
DELETE      | /api.php/{resource}/{id}                | Delete entry by ID from specified resource table
*/

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