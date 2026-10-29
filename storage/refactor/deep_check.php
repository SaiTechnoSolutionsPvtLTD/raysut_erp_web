<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$routes = json_decode(file_get_contents(__DIR__ . '/routes_cleanup_before.json'), true);

$targets = [
    'BranchController' => [
        'controller' => \App\Http\Controllers\BranchOperations\BranchController::class,
        'livewire' => \App\Livewire\BranchOperations\BranchManagement::class,
    ],
    'CounterController' => [
        'controller' => \App\Http\Controllers\BranchOperations\CounterController::class,
        'livewire' => \App\Livewire\BranchOperations\CounterManagement::class,
    ],
    'ShiftController' => [
        'controller' => \App\Http\Controllers\BranchOperations\ShiftController::class,
        'livewire' => \App\Livewire\BranchOperations\ShiftManagement::class,
    ],
    'PermitController' => [
        'controller' => \App\Http\Controllers\Sales\Retail\PermitController::class,
        'livewire' => \App\Livewire\Sales\Retail\PermitManagement::class,
    ],
    'QuotationController' => [
        'controller' => \App\Http\Controllers\Sales\Wholesale\QuotationController::class,
        'livewire' => \App\Livewire\Sales\Wholesale\QuotationManagement::class,
    ],
    'SalesOrderController' => [
        'controller' => \App\Http\Controllers\Sales\Wholesale\SalesOrderController::class,
        'livewire' => \App\Livewire\Sales\Wholesale\SalesOrderManagement::class,
    ],
    'DeliveryNoteController' => [
        'controller' => \App\Http\Controllers\Sales\Wholesale\DeliveryNoteController::class,
        'livewire' => \App\Livewire\Sales\Wholesale\DeliveryNoteManagement::class,
    ],
    'InvoiceController' => [
        'controller' => \App\Http\Controllers\Sales\Wholesale\InvoiceController::class,
        'livewire' => \App\Livewire\Sales\Wholesale\SalesInvoiceManagement::class,
    ],
    'CustomerCreditController' => [
        'controller' => \App\Http\Controllers\Sales\Wholesale\CustomerCreditController::class,
        'livewire' => \App\Livewire\Sales\Wholesale\CustomerCreditManagement::class,
    ],
];

foreach ($targets as $name => $classes) {
    echo "=================================================\n";
    echo "$name\n";
    echo "=================================================\n";
    $cClass = $classes['controller'];
    $lClass = $classes['livewire'];
    
    $cRef = new ReflectionClass($cClass);
    $lRef = new ReflectionClass($lClass);
    
    $lMethods = array_map(fn($m) => $m->name, $lRef->getMethods(ReflectionMethod::IS_PUBLIC));
    
    foreach ($cRef->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        if ($m->class !== $cClass) continue;
        
        $mName = $m->name;
        // find route
        $matchedRoutes = array_filter($routes, fn($r) => ($r['action'] ?? '') === "$cClass@$mName");
        $routeNames = array_map(fn($r) => $r['name'] ?? 'unnamed', $matchedRoutes);
        $routeUris = array_map(fn($r) => ($r['method'] ?? '') . ' ' . ($r['uri'] ?? ''), $matchedRoutes);
        
        echo "Method: $mName\n";
        echo "  Routes: " . (empty($matchedRoutes) ? "NONE" : implode(', ', $routeUris)) . "\n";
        echo "  Route names: " . (empty($routeNames) ? "NONE" : implode(', ', $routeNames)) . "\n";
    }
}
