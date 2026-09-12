<?php

require_once __DIR__.'/../vendor/autoload.php';

(Dotenv\Dotenv::createImmutable(__DIR__.'/../'))->safeLoad();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Ici, nous allons charger le conteneur Lumen. Il fait office de "colle"
| pour tous les composants de l'application et constitue le point
| d'entrée principal vers celle-ci.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();
$app->withEloquent();

$app->configure('cache');
$app->configure('database');

// `$this->validate()` (trait ValidatesRequests, utilisé par les endpoints
// d'écriture POST .../notations) fonctionne sans registration manuelle :
// Lumen résout `translator`/`validator` à la demande via son propre
// mécanisme de bindings différés (Application::$availableBindings), qui
// configure correctement `path.lang` avant d'enregistrer les providers.
// Un `$app->register(...)` manuel de ces providers court-circuite ce
// mécanisme et casse la résolution de `path.lang`.

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
*/

$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Illuminate\Cache\CacheServiceProvider::class);
$app->register(App\Providers\AppServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

return $app;
