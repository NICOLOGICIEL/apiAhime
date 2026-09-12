<?php

/*
|--------------------------------------------------------------------------
| Front controller
|--------------------------------------------------------------------------
|
| Point d'entrée HTTP unique : à pointer depuis la configuration du
| serveur web (Apache/Nginx) ou via `php -S localhost:8000 -t public`.
|
*/

require __DIR__.'/../vendor/autoload.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$app = require __DIR__.'/../bootstrap/app.php';

$app->run();
