<?php

$routesRaw = shell_exec('php artisan route:list --json');
$routesRaw = preg_replace('/^\xEF\xBB\xBF/', '', $routesRaw);
if (strpos($routesRaw, '[') !== false) {
    $routesRaw = substr($routesRaw, strpos($routesRaw, '['));
}
$routes = json_decode($routesRaw, true);
file_put_contents(__DIR__ . '/routes_cleanup_before.json', json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "Snapshot saved: " . count($routes) . " routes.\n";
