<?php

return [

    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [

        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'strict' => env('DB_STRICT_MODE', true),
            'engine' => env('DB_ENGINE'),
            'timezone' => env('DB_TIMEZONE', '+00:00'),
            // TiDB Cloud exige une connexion chiffrée (TLS) ; en local (WAMP),
            // le MySQL de dev n'a pas de certificat TLS valide donc DB_SSL
            // doit rester a false (voir .envv).
            'options' => (extension_loaded('pdo_mysql') && env('DB_SSL', false)) ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA', __DIR__.'/isrgrootx1.pem'),
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
            ]) : [],
        ],

    ],

    'migrations' => 'migrations',

];
