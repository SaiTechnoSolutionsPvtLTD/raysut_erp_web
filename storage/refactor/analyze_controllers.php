<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controllers = [
    'BranchController' => \App\Http\Controllers\BranchOperations\BranchController::class,
    'CounterController' => \App\Http\Controllers\BranchOperations\CounterController::class,
    'ShiftController' => \App\Http\Controllers\BranchOperations\ShiftController::class,
    'PermitController' => \App\Http\Controllers\Sales\Retail\PermitController::class,
    'QuotationController' => \App\Http\Controllers\Sales\Wholesale\QuotationController::class,
    'SalesOrderController' => \App\Http\Controllers\Sales\Wholesale\SalesOrderController::class,
    'DeliveryNoteController' => \App\Http\Controllers\Sales\Wholesale\DeliveryNoteController::class,
    'InvoiceController' => \App\Http\Controllers\Sales\Wholesale\InvoiceController::class,
    'CustomerCreditController' => \App\Http\Controllers\Sales\Wholesale\CustomerCreditController::class,
];

$routesRaw = file_get_contents(__DIR__ . '/routes_cleanup_before.json');
$routes = json_decode($routesRaw, true);

// Map action => route info
$actionToRoute = [];
foreach ($routes as $r) {
    if (!empty($r['action'])) {
        $actionToRoute[$r['action']] = $r;
    }
}

// Function to search codebase for references
$baseDir = realpath(__DIR__ . '/../../');
$searchDirs = ['resources/views', 'app/Livewire', 'tests', 'routes', 'app/Services'];

function searchPattern($pattern, $dirs, $baseDir) {
    $matches = [];
    foreach ($dirs as $d) {
        $dir = $baseDir . '/' . $d;
        if (!is_dir($dir)) continue;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            $content = file_get_contents($file->getPathname());
            if (strpos($content, $pattern) !== false) {
                $rel = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $matches[] = $rel;
            }
        }
    }
    return array_unique($matches);
}

$report = [];

foreach ($controllers as $name => $class) {
    if (!class_exists($class)) {
        $report[$name] = "Class does not exist!";
        continue;
    }
    
    $ref = new ReflectionClass($class);
    $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
    $methodsReport = [];
    
    foreach ($methods as $m) {
        // Exclude parent methods
        if ($m->class !== $class) continue;
        
        $mName = $m->getName();
        $actionStr = $class . '@' . $mName;
        $invokableStr = $class;
        
        $route = $actionToRoute[$actionStr] ?? ($mName === '__invoke' ? ($actionToRoute[$invokableStr] ?? null) : null);
        
        $routeName = $route['name'] ?? null;
        $routeUri = $route['uri'] ?? null;
        $routeMethod = $route['method'] ?? null;
        
        $routeNameRefs = $routeName ? searchPattern("route('$routeName'", $searchDirs, $baseDir) : [];
        if ($routeName && empty($routeNameRefs)) {
            $routeNameRefs = searchPattern("route(\"$routeName\"", $searchDirs, $baseDir);
        }
        // Also check raw string of route name
        $rawRouteNameRefs = $routeName ? searchPattern("'$routeName'", $searchDirs, $baseDir) : [];

        $methodsReport[$mName] = [
            'has_route' => $route !== null,
            'route' => $route ? "$routeMethod $routeUri [name: " . ($routeName ?? 'none') . "]" : null,
            'route_name' => $routeName,
            'route_name_refs' => array_values(array_filter($rawRouteNameRefs, fn($p) => $p !== 'routes/web.php')),
        ];
    }
    $report[$name] = $methodsReport;
}

file_put_contents(__DIR__ . '/controller_methods_analysis.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
