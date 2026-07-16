<?php

$api = file_get_contents(__DIR__ . '/../routes/api.php');
preg_match_all("/Route::(get|post|put|patch|delete)\(\s*['\"]([^'\"]+)['\"]/", $api, $m, PREG_SET_ORDER);
$routes = [];
foreach ($m as $r) {
    $routes[] = [strtoupper($r[1]), '/' . ltrim($r[2], '/')];
}

$json = json_decode(file_get_contents(__DIR__ . '/../storage/api-docs/api-docs.json'), true);
$paths = $json['paths'] ?? [];

$missing = [];
foreach ($routes as [$method, $path]) {
    if (!isset($paths[$path])) {
        $missing[] = "$method $path (no path)";
        continue;
    }
    $methods = array_map('strtoupper', array_keys($paths[$path]));
    if (!in_array($method, $methods, true)) {
        $missing[] = "$method $path (method missing)";
    }
}

echo "Missing from Swagger (" . count($missing) . "):\n";
foreach ($missing as $line) {
    echo "  $line\n";
}

$routePaths = array_column($routes, 1);
$extra = [];
foreach (array_keys($paths) as $sp) {
    if (!in_array($sp, $routePaths, true)) {
        $extra[] = $sp;
    }
}
echo "\nExtra in Swagger only (" . count($extra) . "):\n";
foreach ($extra as $line) {
    echo "  $line\n";
}
