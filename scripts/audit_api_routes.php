<?php

/**
 * Verifies every registered API route maps to an existing controller method,
 * and lists public controller methods that have no route (dead code).
 *
 * Usage: php scripts/audit_api_routes.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = collect(Illuminate\Support\Facades\Route::getRoutes())
    ->filter(fn ($r) => str_starts_with($r->uri(), 'api/v1'));

$broken = [];
$wired = [];

foreach ($routes as $route) {
    $action = $route->getActionName();
    if (! str_contains($action, '@')) {
        continue;
    }
    [$class, $method] = explode('@', $action);

    if (! class_exists($class)) {
        $broken[] = "MISSING CLASS: {$class}";
        continue;
    }
    if (! method_exists($class, $method)) {
        $broken[] = implode('|', $route->methods()) . ' /' . $route->uri() . " -> {$action}";
        continue;
    }
    $wired[$class][] = $method;
}

echo 'Total API routes: ' . $routes->count() . PHP_EOL;
echo PHP_EOL . 'Broken routes (' . count($broken) . '):' . PHP_EOL;
foreach ($broken as $line) {
    echo "  {$line}" . PHP_EOL;
}

$ignored = ['__construct', 'middleware', 'callAction', 'getMiddleware'];
$orphans = [];

foreach (glob(__DIR__ . '/../app/Http/Controllers/Api/*.php') as $file) {
    $class = 'App\\Http\\Controllers\\Api\\' . basename($file, '.php');
    if (! class_exists($class)) {
        continue;
    }
    $reflection = new ReflectionClass($class);
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->class !== $class || in_array($m->name, $ignored, true)) {
            continue;
        }
        if (! in_array($m->name, $wired[$class] ?? [], true)) {
            $orphans[] = $reflection->getShortName() . '@' . $m->name;
        }
    }
}

echo PHP_EOL . 'Controller methods without a route (' . count($orphans) . '):' . PHP_EOL;
foreach ($orphans as $line) {
    echo "  {$line}" . PHP_EOL;
}
