<?php

/**
 * Dumps non-API routes as "METHOD  URI  Controller@method" for gap auditing.
 *
 * Usage: php scripts/dump_web_routes.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (Illuminate\Support\Facades\Route::getRoutes() as $route) {
    if (str_starts_with($route->uri(), 'api/')) {
        continue;
    }
    $action = $route->getActionName();
    if (! str_contains($action, '@')) {
        continue;
    }
    $methods = implode('|', array_diff($route->methods(), ['HEAD']));
    $short = str_replace('App\Http\Controllers\\', '', $action);

    echo str_pad($methods, 12) . str_pad('/' . $route->uri(), 60) . $short . PHP_EOL;
}
