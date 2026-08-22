<?php

/** @var \Laravel\Lumen\Routing\Router $router */

//use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\ApiController;


$router->get('/', function () {
    return response()->json(['name' => 'Ahime API', 'status' => 'ok']);
});

//Route API
//Route::post('/api/action', [ApiController::class, 'action']);
$router->post('action', 'ApiController@action');

// Hôtels
$router->get('hotels', 'HotelController@index');
$router->get('hotels/{id:\d+}', 'HotelController@show');
$router->get('hotels/{id:\d+}/images', 'HotelController@images');

// Images hôtel (CRUD)
$router->get('images', 'ImageHotelController@index');
$router->get('images/{id:\d+}', 'ImageHotelController@show');
$router->post('images', 'ImageHotelController@store');
$router->put('images/{id:\d+}', 'ImageHotelController@update');
$router->delete('images/{id:\d+}', 'ImageHotelController@destroy');

// Artisans
$router->get('artisans', 'ArtisanController@index');
$router->get('artisans/{id:\d+}', 'ArtisanController@show');
$router->get('artisans/{id:\d+}/notations', 'ArtisanController@notations');
$router->post('artisans/{id:\d+}/notations', 'ArtisanController@storeNotation');

// Transport
$router->get('transports', 'TransportController@index');
$router->get('transports/{ligneId:\d+}/horaires', 'TransportController@horaires');
$router->get('transports/departs/{departId:\d+}/escales', 'TransportController@escales');
$router->get('compagnies/{compagnieId:\d+}/notations', 'TransportController@compagnieNotations');
$router->post('compagnies/{compagnieId:\d+}/notations', 'TransportController@storeCompagnieNotation');

// Listes de référence (menus déroulants de recherche)
$router->get('villes', 'ReferenceController@villes');
$router->get('metiers', 'ReferenceController@metiers');
$router->get('categories', 'ReferenceController@categories');
$router->get('compagnies', 'ReferenceController@compagnies');
