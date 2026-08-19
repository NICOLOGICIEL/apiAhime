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

$app = require __DIR__.'/../bootstrap/app.php';

$app->run();
