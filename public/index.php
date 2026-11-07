<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// The standalone installer runs before Laravel bootstraps because a fresh
// shared-hosting install has no .env, APP_KEY or database tables yet.
if (! is_file(__DIR__.'/../storage/app/.moramarket-installed')) {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (rtrim($path, '/') === '/installer') {
        require __DIR__.'/installer/index.php';
        exit;
    }

    header('Location: /installer/', true, 302);
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
